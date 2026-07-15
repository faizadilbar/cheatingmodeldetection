# backend/core/detector_manager.py

import sys
import os

# Ensure parent directory is in search path to import existing modules
PARENT_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
if PARENT_DIR not in sys.path:
    sys.path.append(PARENT_DIR)

from backend.detectors.proctoring_detector import ProctoringDetector
from session_service import ExamSession
from models.exam_session import ExamSessionState
import services.database_service as db_service
import services.api_service as api_service
from report import SessionReport
from datetime import datetime
import json
import time

import config
import asyncio

class WebExamSession(ExamSession):
    """Subclass of ExamSession adapted for non-blocking execution in a WebSocket environment with state recovery."""
    def __init__(self, student_info, session_id=None):
        super().__init__()
        self.state = ExamSessionState(student_info)
        self.state.local_session_id = session_id or f"{student_info['student_id']}_{int(time.time())}"
        self._last_api_update = 0.0
        self.frame_count = 0
        self._is_ended = False

    def start(self):
        """Starts the exam session, initializes databases, and registers with Laravel server."""
        db_service.init_local_database()
        
        # Start session on Laravel server
        print(f"\n[API WebSocket] Starting session on server for {self.state.student_info['student_name']}...")
        self.state.server_session_id = api_service.start_session_on_server(self.state.student_info)
        
        if self.state.server_session_id:
            print(f"[API WebSocket] ✅ Session active on server! ID: {self.state.server_session_id}")
        else:
            print("[API WebSocket] ⚠️ Offline mode")
            
        # Set up SessionReport tracking
        self.state.session_report = SessionReport()
        self.state.session_start_time = time.time()
        self.frame_count = 0
        
        # Save initial session metadata to SQLite database
        session_data = {
            'session_id': self.state.local_session_id,
            'student_id': self.state.student_info['student_id'],
            'student_name': self.state.student_info['student_name'],
            'course_name': self.state.student_info['course_name'],
            'quiz_code': self.state.student_info['quiz_code'],
            'exam_date': self.state.student_info['exam_date'],
            'start_time': self.state.student_info['start_time'],
            'end_time': None,
            'avg_risk_score': 0,
            'max_risk_score': 0,
            'total_blinks': 0,
            'gaze_away_count': 0,
            'head_turn_count': 0,
            'no_face_count': 0,
            'multiple_face_count': 0,
            'cheating_status': 'clean',
            'alarm_triggered': 0,
            'alarm_count': 0,
            'total_count': 0,
            'alarm_history': None,
            'report_path': None,
            'server_session_id': self.state.server_session_id,
            'baseline_yaw': None
        }
        db_service.save_session_local(session_data)
        self._last_api_update = time.time()

    def record(self, result):
        """Processes frame result, increments infraction counters, logs risk periodically, and records to report."""
        super().record(result)
        # Periodically persist full state to SQLite to prevent loss on server restart (every 15 frames)
        if self.frame_count % 15 == 0:
            self.persist_state(result.get("baseline_yaw"))

    def record_violation(self, result):
        """Saves violation locally and reports immediately to proctoring Laravel server."""
        super().record_violation(result)
        # Immediately persist state when a violation occurs
        self.persist_state(result.get("baseline_yaw"))

    def persist_state(self, baseline_yaw=None):
        """Periodically saves the current session metrics and alarm history to SQLite database to prevent loss on server restart."""
        if self._is_ended:
            return
        blink_count = self.state.session_report.total_blinks()
        avg_risk = round(self.state.session_report.avg_risk(), 1)
        max_risk = round(self.state.session_report.max_risk(), 1)
        
        session_data = {
            'session_id': self.state.local_session_id,
            'student_id': self.state.student_info['student_id'],
            'student_name': self.state.student_info['student_name'],
            'course_name': self.state.student_info['course_name'],
            'quiz_code': self.state.student_info['quiz_code'],
            'exam_date': self.state.student_info['exam_date'],
            'start_time': self.state.student_info['start_time'],
            'end_time': None,
            'avg_risk_score': avg_risk,
            'max_risk_score': max_risk,
            'total_blinks': blink_count,
            'gaze_away_count': self.state.gaze_away_count,
            'head_turn_count': self.state.head_turn_count,
            'no_face_count': self.state.no_face_count,
            'multiple_face_count': self.state.multi_face_count,
            'cheating_status': 'suspicious' if avg_risk >= 40 else 'clean',
            'alarm_triggered': 1 if len(self.state.alarm_history_list) > 0 else 0,
            'alarm_count': len(self.state.alarm_history_list),
            'total_count': len(self.state.alarm_history_list),
            'alarm_history': json.dumps(self.state.alarm_history_list),
            'report_path': None,
            'server_session_id': self.state.server_session_id,
            'baseline_yaw': baseline_yaw
        }
        db_service.save_session_local(session_data)

    def restore_from_row(self, row):
        """Restores session statistics and state from a database row."""
        self.state.local_session_id = row['session_id']
        self.state.server_session_id = row['server_session_id']
        self.state.gaze_away_count = row['gaze_away_count'] or 0
        self.state.head_turn_count = row['head_turn_count'] or 0
        self.state.no_face_count = row['no_face_count'] or 0
        self.state.multi_face_count = row['multiple_face_count'] or 0
        self.state.alarm_history_list = json.loads(row['alarm_history']) if row['alarm_history'] else []
        
        try:
            start_str = row['start_time']
            exam_date = row['exam_date']
            start_dt = datetime.strptime(f"{exam_date} {start_str}", "%Y-%m-%d %H:%M")
            self.state.session_start_time = start_dt.timestamp()
        except Exception:
            self.state.session_start_time = time.time()
            
        # Rebuild SessionReport
        self.state.session_report = SessionReport()
        elapsed = time.time() - self.state.session_start_time
        stats = {
            "face_count": 1 if self.state.no_face_count == 0 else 0,
            "ear": 0.25,
            "blink_count": row['total_blinks'] or 0,
            "blink_rate": 15.0,
            "gaze_yaw": 0.0,
            "gaze_pitch": 0.0,
            "head_yaw": row['baseline_yaw'] or 0.0,
            "head_pitch": 0.0,
            "raw_risk": row['avg_risk_score'] or 0.0,
            "smooth_risk": row['avg_risk_score'] or 0.0
        }
        self.state.session_report.record(elapsed, stats, [])
        self.frame_count = 1
        print(f"[WebExamSession] Restored state for session {self.state.local_session_id} (Server ID: {self.state.server_session_id})")

    def end(self, baseline_yaw=None):
        """Ends session, saves report, posts final results, and uploads logs to server without blocking. Idempotent."""
        if self._is_ended:
            print(f"[WebExamSession] Session {self.state.local_session_id} is already ended. Skipping duplicate end call.")
            return
        self._is_ended = True
        
        exam_duration = time.time() - self.state.session_start_time
        minutes = int(exam_duration // 60)
        seconds = int(exam_duration % 60)
        
        blink_count = self.state.session_report.total_blinks()
        
        # Save local report
        os.makedirs("reports", exist_ok=True)
        report_filename = f"reports/report_{self.state.student_info['student_id']}_{self.state.student_info['quiz_code']}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
        self.state.session_report.save(report_filename, alarm_history=self.state.alarm_history_list)
        
        # Calculate summary metrics
        avg_risk = round(self.state.session_report.avg_risk(), 1)
        max_risk = round(self.state.session_report.max_risk(), 1)
        
        if avg_risk >= 70:
            cheating_status = "cheating"
        elif avg_risk >= 40:
            cheating_status = "suspicious"
        else:
            cheating_status = "clean"
            
        end_time_str = datetime.now().strftime("%H:%M:%S")
        
        # Save final session stats to local SQLite
        session_data = {
            'session_id': self.state.local_session_id,
            'student_id': self.state.student_info['student_id'],
            'student_name': self.state.student_info['student_name'],
            'course_name': self.state.student_info['course_name'],
            'quiz_code': self.state.student_info['quiz_code'],
            'exam_date': self.state.student_info['exam_date'],
            'start_time': self.state.student_info['start_time'],
            'end_time': end_time_str,
            'avg_risk_score': avg_risk,
            'max_risk_score': max_risk,
            'total_blinks': blink_count,
            'gaze_away_count': self.state.gaze_away_count,
            'head_turn_count': self.state.head_turn_count,
            'no_face_count': self.state.no_face_count,
            'multiple_face_count': self.state.multi_face_count,
            'cheating_status': cheating_status,
            'alarm_triggered': 1 if len(self.state.alarm_history_list) > 0 else 0,
            'alarm_count': len(self.state.alarm_history_list),
            'total_count': len(self.state.alarm_history_list),
            'alarm_history': json.dumps(self.state.alarm_history_list),
            'report_path': report_filename,
            'server_session_id': self.state.server_session_id,
            'baseline_yaw': baseline_yaw
        }
        db_service.save_session_local(session_data)
        
        # Synchronize session completion with Laravel server
        if self.state.server_session_id:
            print("\n[API WebSocket] Sending final data to server...")
            api_service.end_session_on_server(
                self.state.server_session_id, end_time_str, avg_risk, max_risk,
                blink_count, self.state.gaze_away_count, self.state.head_turn_count,
                self.state.no_face_count, self.state.multi_face_count, cheating_status,
                len(self.state.alarm_history_list)
            )
            api_service.upload_report_to_server(self.state.server_session_id, report_filename, self.state.student_info['quiz_code'])
            print("[API WebSocket] ✅ Data synchronized with server!")
        else:
            print("\n[API WebSocket] No server connection. Data saved locally.")
            
        # Print CLI summary details
        print("\n" + "=" * 60)
        print("   EXAM SUMMARY (WEB SOCKET SESSION)")
        print("=" * 60)
        print(f"   Student        : {self.state.student_info['student_name']}")
        print(f"   Roll No        : {self.state.student_info['student_id']}")
        print(f"   Course Name    : {self.state.student_info['course_name']}")
        print(f"   Quiz Code      : {self.state.student_info['quiz_code']}")
        print(f"   Duration       : {minutes}m {seconds}s")
        print(f"   Average Risk   : {avg_risk}%")
        print(f"   Maximum Risk   : {max_risk}%")
        print(f"   Total Blinks   : {blink_count}")
        print(f"   Gaze Away      : {self.state.gaze_away_count} times")
        print(f"   Head Turns     : {self.state.head_turn_count} times")
        print(f"   No Face        : {self.state.no_face_count} frames")
        print(f"   Multiple Faces : {self.state.multi_face_count} frames")
        print(f"   Cheating Status: {cheating_status.upper()}")
        print(f"   Total Alarms   : {len(self.state.alarm_history_list)}")
        print("=" * 60)
        print(f"\n[INFO WebSocket] Report saved: {report_filename}")

class DetectorManager:
    """Manages active proctoring detector and session instances to support multi-student concurrent sessions."""
    def __init__(self):
        self._detectors = {}
        self._sessions = {}
        self._active_connections = set()
        self._cleanup_tasks = {}
        self._last_activity = {}

    def register_connection(self, session_id: str):
        """Registers a connection as active, cancelling any pending cleanups."""
        self._active_connections.add(session_id)
        self.update_activity(session_id)
        if session_id in self._cleanup_tasks:
            task = self._cleanup_tasks[session_id]
            task.cancel()
            del self._cleanup_tasks[session_id]
            print(f"[DetectorManager] Cancelled pending cleanup for session {session_id} due to reconnection")

    def deregister_connection(self, session_id: str):
        """Deregisters a connection and schedules a cleanup if it doesn't reconnect in time."""
        if session_id in self._active_connections:
            self._active_connections.remove(session_id)
        self.update_activity(session_id)
        
        # Schedule cleanup task
        cleanup_timeout = getattr(config, 'SESSION_CLEANUP_TIMEOUT', 30)
        
        async def delayed_cleanup():
            try:
                await asyncio.sleep(cleanup_timeout)
                if session_id not in self._active_connections:
                    print(f"[DetectorManager] Session {session_id} reconnection timeout expired. Cleaning up...")
                    self.force_cleanup(session_id)
            except asyncio.CancelledError:
                pass
            finally:
                if session_id in self._cleanup_tasks:
                    del self._cleanup_tasks[session_id]

        loop = asyncio.get_event_loop()
        task = loop.create_task(delayed_cleanup())
        self._cleanup_tasks[session_id] = task

    def update_activity(self, session_id: str):
        """Updates the last activity timestamp for a session."""
        self._last_activity[session_id] = time.time()

    def force_cleanup(self, session_id: str):
        """Immediately cleans up a session, cancelling any pending tasks. Idempotent."""
        if session_id in self._active_connections:
            self._active_connections.remove(session_id)
        if session_id in self._cleanup_tasks:
            self._cleanup_tasks[session_id].cancel()
            del self._cleanup_tasks[session_id]
        
        self.remove_session(session_id)
        self.remove_detector(session_id)

    async def start_cleanup_loop(self):
        """Runs a periodic loop to clean up silent/inactive/disconnected sessions."""
        print("[DetectorManager] Cleanup background task started.")
        while True:
            try:
                await asyncio.sleep(5)
                await self.check_inactive_sessions()
            except asyncio.CancelledError:
                break
            except Exception as e:
                print(f"[DetectorManager] Error in cleanup loop: {e}")

    async def check_inactive_sessions(self):
        """Inspects all active sessions, checking for silent heartbeats or disconnection timeouts."""
        cleanup_timeout = getattr(config, 'SESSION_CLEANUP_TIMEOUT', 30)
        heartbeat_timeout = getattr(config, 'HEARTBEAT_TIMEOUT', 10)
        
        current_time = time.time()
        sessions_to_cleanup = []
        connections_to_disconnect = []
        
        # 1. Check for active connections that have gone silent (half-open)
        for session_id in list(self._active_connections):
            last_act = self._last_activity.get(session_id, 0.0)
            if last_act > 0.0 and (current_time - last_act) > heartbeat_timeout:
                print(f"[DetectorManager] Session {session_id} has gone silent (no heartbeat for {current_time - last_act:.1f}s). Deregistering connection.")
                connections_to_disconnect.append(session_id)
                
        for session_id in connections_to_disconnect:
            self.deregister_connection(session_id)
            
        # 2. Check for disconnected sessions that have exceeded cleanup timeout
        for session_id in list(self._sessions.keys()):
            if session_id not in self._active_connections:
                last_act = self._last_activity.get(session_id, 0.0)
                if last_act == 0.0:
                    session = self._sessions.get(session_id)
                    if session:
                        last_act = session.state.session_start_time
                
                if (current_time - last_act) > cleanup_timeout:
                    print(f"[DetectorManager] Session {session_id} cleanup timeout exceeded ({current_time - last_act:.1f}s). Cleaning up session.")
                    sessions_to_cleanup.append(session_id)
                    
        for session_id in sessions_to_cleanup:
            self.force_cleanup(session_id)

    def get_or_create_detector(self, session_id: str) -> ProctoringDetector:
        """Retrieves an existing ProctoringDetector instance or restores/starts a new one."""
        if session_id not in self._detectors:
            print(f"[DetectorManager] Initializing new ProctoringDetector for session: {session_id}")
            detector = ProctoringDetector()
            
            # Check SQLite database to recover calibration baseline and stats
            row = db_service.get_session_local(session_id)
            if row:
                detector.restore_from_row(row)
                print(f"[DetectorManager] Restored detector state for session: {session_id}")
            self._detectors[session_id] = detector
        return self._detectors[session_id]

    def remove_detector(self, session_id: str):
        """Releases and removes a ProctoringDetector instance to prevent memory leaks on session disconnect."""
        if session_id in self._detectors:
            print(f"[DetectorManager] Cleaning up detector for session: {session_id}")
            try:
                self._detectors[session_id].close()
            except Exception as e:
                print(f"[DetectorManager] Error closing detector for session {session_id}: {e}")
            del self._detectors[session_id]

    def reset_detector(self, session_id: str):
        """Resets the statistics/calibration for a session's detector."""
        if session_id in self._detectors:
            self._detectors[session_id].reset()

    def get_or_create_session(self, session_id: str, student_info: dict) -> WebExamSession:
        """Retrieves an existing WebExamSession instance or restores/starts a new one."""
        if session_id not in self._sessions:
            row = db_service.get_session_local(session_id)
            if row:
                print(f"[DetectorManager] Found existing session in database for recovery: {session_id}")
                session = WebExamSession(student_info, session_id=session_id)
                session.restore_from_row(row)
                self._sessions[session_id] = session
            else:
                print(f"[DetectorManager] Initializing new WebExamSession for session: {session_id}")
                session = WebExamSession(student_info, session_id=session_id)
                session.start()
                self._sessions[session_id] = session
        return self._sessions[session_id]

    def remove_session(self, session_id: str):
        """Concludes and cleans up the active exam session."""
        if session_id in self._sessions:
            print(f"[DetectorManager] Closing session: {session_id}")
            try:
                baseline_yaw = None
                if session_id in self._detectors:
                    baseline_yaw = self._detectors[session_id].baseline_yaw
                self._sessions[session_id].end(baseline_yaw=baseline_yaw)
            except Exception as e:
                print(f"[DetectorManager] Error ending session {session_id}: {e}")
            del self._sessions[session_id]

# Global singleton manager instance
detector_manager = DetectorManager()

