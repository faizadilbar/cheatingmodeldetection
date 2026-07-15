# ui_service.py
# Move all visualization code here: draw_stats, draw_risk_bar, draw_flags, draw_warning_messages, draw_overlay.

import cv2
import config
from utils.drawing import draw_risk_bar, draw_stats, draw_flags

def draw_warning_messages(frame, result):
    """Draw continuous alert warnings on the frame based on infraction counters."""
    face_count = result["face_count"]
    consecutive_frames = result.get("consecutive_frames", {})
    consecutive_gaze_away = consecutive_frames.get("gaze_away", 0)
    consecutive_head_turn = consecutive_frames.get("head_turn", 0)
    consecutive_no_face = consecutive_frames.get("no_face", 0)
    
    if face_count > 1:
        cv2.putText(frame, "CHEATING ALERT! Multiple Faces!", (10, 150), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)
    elif consecutive_gaze_away > 15:
        cv2.putText(frame, "WARNING: Gaze away!", (10, 180), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
    elif consecutive_head_turn > 15:
        cv2.putText(frame, "WARNING: Head turn!", (10, 210), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
    elif consecutive_no_face > 20:
        cv2.putText(frame, "WARNING: Face not detected!", (10, 240), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 165, 255), 2)

def draw_overlay(frame, result):
    """Complete HUD drawing coordinating sub-draw routines and session state."""
    # 1. Base drawing utilities
    smooth_risk = result["risk_score"]
    draw_risk_bar(frame, smooth_risk)
    
    stats = {
        "face_count": result["face_count"],
        "ear": result.get("ear", 0.0),
        "blink_count": result["blink_count"],
        "blink_rate": result.get("blink_rate", 0.0),
        "gaze_yaw": result["gaze_yaw"],
        "gaze_pitch": result["gaze_pitch"],
        "head_yaw": result["head_yaw"],
        "head_pitch": result["head_pitch"],
        "raw_risk": result.get("raw_risk", 0.0),
        "smooth_risk": smooth_risk,
    }
    draw_stats(frame, stats)
    
    # Flags representation in draw_flags
    active_flags = getattr(result, "active_flags", None)
    if active_flags is None:
        active_flags = []
        flags_dict = result.get("flags", {})
        if flags_dict.get("no_face"):
            active_flags.append("face_absent")
        if flags_dict.get("multiple_faces"):
            active_flags.append("multi_face")
        if flags_dict.get("gaze_away"):
            active_flags.append("gaze_away")
        if flags_dict.get("head_turn"):
            active_flags.append("head_turned")
        
    draw_flags(frame, active_flags)
    
    # 2. Risk warning messages
    draw_warning_messages(frame, result)
    
    # 3. Resolve active session values dynamically to populate overlay
    import session_service
    session = session_service.active_session
    
    student_name = ""
    course_name = ""
    quiz_code = ""
    gaze_away_count = 0
    head_turn_count = 0
    total_alarms = result.get("total_alarms", 0)
    server_session_id = None
    
    if session is not None and session.state is not None:
        student_info = session.state.student_info
        student_name = student_info.get("student_name", "")
        course_name = student_info.get("course_name", "")
        quiz_code = student_info.get("quiz_code", "")
        gaze_away_count = session.state.gaze_away_count
        head_turn_count = session.state.head_turn_count
        server_session_id = session.state.server_session_id
        
    # Risk color text overlay
    if smooth_risk >= 70:
        risk_color = (0, 0, 255)
        risk_text = "HIGH RISK!"
    elif smooth_risk >= 40:
        risk_color = (0, 165, 255)
        risk_text = "MEDIUM RISK"
    else:
        risk_color = (0, 255, 0)
        risk_text = "LOW RISK"
        
    cv2.putText(
        frame,
        f"Student: {student_name} | Risk: {smooth_risk:.1f}% [{risk_text}]",
        (10, 30),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.5,
        risk_color,
        1,
    )
    
    cv2.putText(
        frame,
        f"Course: {course_name} | Quiz: {quiz_code}",
        (10, 55),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.45,
        (200, 200, 200),
        1,
    )
    
    cv2.putText(
        frame,
        f"Gaze: {gaze_away_count} | Head: {head_turn_count} | Blinks: {result['blink_count']}",
        (10, 80),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.45,
        (200, 200, 200),
        1,
    )
    
    # Alarms status overlay
    import alarm_service
    if alarm_service.ALARM_AVAILABLE:
        cv2.putText(
            frame,
            f"Alarms: {total_alarms}",
            (10, 105),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.4,
            (0, 0, 255) if total_alarms > 0 else (0, 255, 0),
            1,
        )
        
    # Connection / Exit options
    cv2.putText(
        frame,
        f"Press 'Q' to stop exam | {'Online' if server_session_id else 'Offline'}",
        (10, frame.shape[0] - 10),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.4,
        (0, 255, 0) if server_session_id else (0, 165, 255),
        1,
    )

def draw_calibration_overlay(frame, time_remaining):
    """Draw baseline calibration text details on the calibration frames."""
    cv2.putText(
        frame,
        "CALIBRATING - Look Straight",
        (10, 40),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.8,
        (0, 255, 0),
        2,
    )
    cv2.putText(
        frame,
        f"Time remaining: {time_remaining}s",
        (10, 80),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.6,
        (255, 255, 255),
        1,
    )

def show_calibration_frame(frame, time_remaining):
    """Draw overlay and display calibration frame window directly."""
    draw_calibration_overlay(frame, time_remaining)
    cv2.imshow(config.DISPLAY_WINDOW_NAME, frame)
    cv2.waitKey(1)
