# backend/detectors/proctoring_detector.py

import sys
import os
import time
import numpy as np

# Ensure parent directory is in search path to import existing modules
PARENT_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
if PARENT_DIR not in sys.path:
    sys.path.append(PARENT_DIR)

import config
from utils.face_mesh import (
    create_face_mesh,
    process_frame,
    LEFT_EYE_INDICES, RIGHT_EYE_INDICES,
    LEFT_IRIS_CENTER, RIGHT_IRIS_CENTER,
    HEAD_POSE_INDICES,
)
from detectors.eye_aspect_ratio import compute_ear, average_ear
from detectors.gaze_estimator import estimate_gaze
from detectors.blink_counter import BlinkCounter
from detectors.head_pose import estimate_head_pose, build_camera_matrix
from risk.scorer import RiskScorer
from models.detector_output import DetectorOutput

class ProctoringDetector:
    """Computes face detection, pose estimation, gaze direction and risk scoring asynchronously over streamed WebSocket frames."""
    def __init__(self):
        self.face_mesh = create_face_mesh()
        self.blink_counter = BlinkCounter()
        self.scorer = RiskScorer(ema_alpha=0.25)
        self.cam_matrix = build_camera_matrix(config.FRAME_WIDTH, config.FRAME_HEIGHT)
        self.baseline_yaw = config.HEAD_BASELINE_YAW
        
        self.max_risk = 0.0
        
        # Consecutive infraction counters (detector running state)
        self.consecutive_gaze_away = 0
        self.consecutive_head_turn = 0
        self.consecutive_multiple_faces = 0
        self.consecutive_no_face = 0
        
        # Dynamic/Asynchronous calibration state
        self.is_calibrated = False
        self.calibration_samples = []
        
        # Instance-level alarm counters for concurrent session safety
        self.total_alarms = 0
        self.last_alarm_time = 0.0
        self.last_alarm_type = "NONE"

    def process_single_frame(self, frame):
        """Processes frame to produce proctoring calculations. Performs calibration on the first 15 valid frames."""
        h, w = frame.shape[:2]
        results = process_frame(self.face_mesh, frame)
        
        face_count = 0
        ear = 0.0
        gaze_yaw = 0.0
        gaze_pitch = 0.0
        looking_away = False
        head_yaw = 0.0
        head_pitch = 0.0
        
        # 1. Asynchronous Calibration logic
        if not self.is_calibrated:
            if results.multi_face_landmarks:
                landmarks = results.multi_face_landmarks[0].landmark
                head_yaw, _, _ = estimate_head_pose(
                    landmarks, HEAD_POSE_INDICES, w, h, self.cam_matrix
                )
                self.calibration_samples.append(head_yaw)
                
                if len(self.calibration_samples) >= 15:
                    self.baseline_yaw = float(np.mean(self.calibration_samples))
                    self.scorer.set_baseline(self.baseline_yaw)
                    self.is_calibrated = True
                    print(f"[CAL] WebSession successfully calibrated baseline yaw: {self.baseline_yaw:.1f} degrees")
            
            # Return calibration progress output
            flags = {
                "gaze_away": False,
                "head_turn": False,
                "multiple_faces": False,
                "no_face": not results.multi_face_landmarks
            }
            consecutive_frames = {
                "gaze_away": 0,
                "head_turn": 0,
                "multiple_faces": 0,
                "no_face": 0
            }
            output = DetectorOutput(
                face_count=len(results.multi_face_landmarks) if results.multi_face_landmarks else 0,
                multiple_faces=False,
                no_face=not results.multi_face_landmarks,
                looking_away=False,
                head_turn=False,
                head_yaw=0.0,
                head_pitch=0.0,
                gaze_yaw=0.0,
                gaze_pitch=0.0,
                blink_count=0,
                risk_score=0.0,
                max_risk=0.0,
                alarm_level="calibrating",  # Special status indicating calibration
                flags=flags,
                consecutive_frames=consecutive_frames,
                total_alarms=0,
                last_alarm_type="NONE",
                last_alarm_time=0.0,
                timestamp=time.time()
            )
            output.ear = 0.0
            output.blink_rate = 0.0
            output.raw_risk = 0.0
            output.baseline_yaw = self.baseline_yaw
            output.active_flags = []
            output.new_violation = None
            return output

        # 2. Fully calibrated processing
        if results.multi_face_landmarks:
            face_count = len(results.multi_face_landmarks)
            landmarks = results.multi_face_landmarks[0].landmark
            self.consecutive_no_face = 0
            
            # Eye aspect ratio
            left_ear = compute_ear(landmarks, LEFT_EYE_INDICES, w, h)
            right_ear = compute_ear(landmarks, RIGHT_EYE_INDICES, w, h)
            ear = average_ear(left_ear, right_ear)
            # Use single-frame blink detection since frames arrive sparsely
            self.blink_counter.update_single_frame(ear)
            
            # Gaze estimation
            gaze_yaw, gaze_pitch, looking_away = estimate_gaze(
                landmarks,
                LEFT_EYE_INDICES, RIGHT_EYE_INDICES,
                LEFT_IRIS_CENTER, RIGHT_IRIS_CENTER,
                w, h,
            )
            
            # Head pose estimation
            head_yaw, head_pitch, _ = estimate_head_pose(
                landmarks, HEAD_POSE_INDICES, w, h, self.cam_matrix
            )
        else:
            self.consecutive_no_face += 1
            
        head_turned = abs(head_yaw - self.baseline_yaw) > config.HEAD_YAW_THRESHOLD
        
        # Update consecutive frame infraction counters
        if face_count > 1:
            self.consecutive_multiple_faces += 1
        else:
            self.consecutive_multiple_faces = 0
            
        if looking_away:
            self.consecutive_gaze_away += 1
        else:
            self.consecutive_gaze_away = 0
            
        if head_turned:
            self.consecutive_head_turn += 1
        else:
            self.consecutive_head_turn = 0
            
        # Calculate risk scores
        raw_risk, smooth_risk, flags_tuple = self.scorer.compute(
            face_count=face_count,
            looking_away=looking_away,
            gaze_yaw=gaze_yaw,
            gaze_pitch=gaze_pitch,
            head_yaw=head_yaw,
            head_pitch=head_pitch,
            blink_anomalous=self.blink_counter.is_anomalous(),
            baseline_yaw=self.baseline_yaw,
        )
        
        # Track maximum risk
        if smooth_risk > self.max_risk:
            self.max_risk = smooth_risk
            
        # Determine potential alarm severity and violation type
        alarm_level = "none"
        violation_type = "NONE"
        
        if face_count > 1:
            alarm_level = "high"
            violation_type = "MULTIPLE_FACES"
        elif self.consecutive_gaze_away > 15:
            alarm_level = "high" if self.consecutive_gaze_away > 25 else "medium"
            violation_type = "GAZE_AWAY"
        elif self.consecutive_head_turn > 15:
            alarm_level = "high" if self.consecutive_head_turn > 25 else "medium"
            violation_type = "HEAD_TURN"
        elif self.consecutive_no_face > 20:
            alarm_level = "low"
            violation_type = "NO_FACE"
            
        # Cooldown check and instance-level alarm triggering
        new_violation = None
        if alarm_level != "none":
            current_time = time.time()
            cooldown = getattr(config, 'ALARM_COOLDOWN', 3)
            if (current_time - self.last_alarm_time) > cooldown:
                self.last_alarm_time = current_time
                self.total_alarms += 1
                self.last_alarm_type = violation_type
                
                timestamp_str = time.strftime("%H:%M:%S")
                new_violation = {
                    "violation_type": violation_type,
                    "severity": alarm_level,
                    "risk_score": smooth_risk,
                    "timestamp": timestamp_str,
                    "alarm_number": self.total_alarms
                }
            
        # Map boolean flags dict
        flags = {
            "gaze_away": looking_away,
            "head_turn": head_turned,
            "multiple_faces": face_count > 1,
            "no_face": face_count == 0
        }
        
        # Map consecutive frames counter dict
        consecutive_frames = {
            "gaze_away": self.consecutive_gaze_away,
            "head_turn": self.consecutive_head_turn,
            "multiple_faces": self.consecutive_multiple_faces,
            "no_face": self.consecutive_no_face
        }
        
        output = DetectorOutput(
            face_count=face_count,
            multiple_faces=face_count > 1,
            no_face=face_count == 0,
            looking_away=looking_away,
            head_turn=head_turned,
            head_yaw=head_yaw,
            head_pitch=head_pitch,
            gaze_yaw=gaze_yaw,
            gaze_pitch=gaze_pitch,
            blink_count=self.blink_counter.count,
            risk_score=smooth_risk,
            max_risk=self.max_risk,
            alarm_level=alarm_level,
            flags=flags,
            consecutive_frames=consecutive_frames,
            total_alarms=self.total_alarms,
            last_alarm_type=self.last_alarm_type,
            last_alarm_time=self.last_alarm_time,
            timestamp=time.time()
        )
        
        # Set dynamic properties for overlays and compatibility
        output.ear = ear
        output.blink_rate = self.blink_counter.blink_rate()
        output.raw_risk = raw_risk
        output.baseline_yaw = self.baseline_yaw
        output.active_flags = flags_tuple
        output.new_violation = new_violation
        
        return output

    def restore_from_row(self, row):
        """Restores detector state from a database row to recover gracefully on backend restart."""
        import json
        self.total_alarms = row['alarm_count'] or 0
        self.blink_counter.count = row['total_blinks'] or 0
        self.max_risk = row['max_risk_score'] or 0.0
        
        alarm_history = json.loads(row['alarm_history']) if row['alarm_history'] else []
        if alarm_history:
            last_alarm = alarm_history[-1]
            self.last_alarm_type = last_alarm.get('type', last_alarm.get('violation_type', 'NONE'))
            self.last_alarm_time = time.time()
            
        # Restore calibration baseline if exists
        baseline = row['baseline_yaw']
        if baseline is not None:
            self.baseline_yaw = float(baseline)
            self.scorer.set_baseline(self.baseline_yaw)
            self.is_calibrated = True
            print(f"[ProctoringDetector] Recovered baseline yaw: {self.baseline_yaw:.1f} degrees (skipping calibration)")

    def reset(self):
        """Resets all metrics and counters."""
        self.consecutive_gaze_away = 0
        self.consecutive_head_turn = 0
        self.consecutive_multiple_faces = 0
        self.consecutive_no_face = 0
        self.max_risk = 0.0
        self.total_alarms = 0
        self.last_alarm_time = 0.0
        self.last_alarm_type = "NONE"
        self.blink_counter = BlinkCounter()
        self.scorer = RiskScorer(ema_alpha=0.25)
        self.scorer.set_baseline(self.baseline_yaw)

    def close(self):
        """Closes detector resources."""
        if self.face_mesh is not None:
            self.face_mesh.close()
            self.face_mesh = None
