# main.py - Modularized Proctoring System (Phase 2)

import cv2
import time
import os
import json
import config
from datetime import datetime

# Import modular services
from camera_service import initialize_camera, get_frame, release_camera
from detector_service import ProctoringDetector
from alarm_service import initialize_alarm, trigger_alarm
from session_service import ExamSession
import ui_service


def _ensure_exam_session_json():
    """When running main.py directly (without Flutter/flask_server),
    prompt user for student info and write exam_session.json so
    session_service can read proper credentials instead of stale data."""
    session_file = os.path.join(os.path.dirname(__file__), 'exam_session.json')

    # If file already exists with valid data, ask whether to keep it
    if os.path.exists(session_file):
        try:
            with open(session_file, 'r') as f:
                existing = json.load(f)
            name = existing.get('student_name', '')
            sid  = existing.get('student_id', '')
            if name and name != 'Unknown Student' and sid and sid != 'unknown':
                print(f"\n[INFO] Found existing session: {name} ({sid})")
                keep = input("[?] Use this student data? (Y/n): ").strip().lower()
                if keep != 'n':
                    return  # reuse existing file
        except Exception:
            pass  # corrupted file — re-prompt

    print("\n" + "=" * 50)
    print("   DIRECT RUN — Enter Student Info")
    print("=" * 50)
    student_name = input("Student Name    : ").strip() or "Test Student"
    student_id   = input("Roll No / ID    : ").strip() or "test001"
    course_name  = input("Course Name     : ").strip() or "Test Course"
    quiz_code    = input("Quiz Code       : ").strip() or "TEST"

    data = {
        "student_id":   student_id,
        "student_name": student_name,
        "book_name":    course_name,
        "course_name":  course_name,
        "course_code":  quiz_code,
        "quiz_code":    quiz_code,
        "quiz_id":      "",
        "exam_date":    datetime.now().strftime("%Y-%m-%d"),
        "start_time":   datetime.now().strftime("%H:%M"),
    }
    with open(session_file, 'w') as f:
        json.dump(data, f, indent=2)
    print(f"[INFO] exam_session.json written for {student_name} ({student_id})\n")

def run_proctored_exam():
    """Main proctoring engine coordinating the camera, detector, session, and alarm services."""
    # 1. Initialize Camera
    if not initialize_camera():
        print("[ERROR] Cannot access camera!")
        import threading
        if threading.current_thread() == threading.main_thread():
            input("\nPress Enter to exit...")
        return
        
    # 2. Instantiate detector (loads models and runs calibration)
    detector = ProctoringDetector(calibrate_locally=True)
    try:
        import flask_server
        flask_server.active_detector = detector
    except Exception:
        pass
    
    # 3. Initialize alarm system
    alarm = initialize_alarm()
    
    # 4. Instantiate and start exam session
    session = ExamSession()
    session.start()
    try:
        import flask_server
        flask_server.active_session = session
    except Exception:
        pass
    
    print("\n" + "=" * 60)
    print("   EXAM STARTED")
    print("=" * 60)
    print("[INFO] Proctoring is now active!")
    print("[INFO] Press 'Q' to stop the exam")
    print("[INFO] Keep looking at the camera")
    print("=" * 60 + "\n")
    
    running = True
    
    while running:
        # Check for external stop signal from Flutter app
        if os.path.exists('exam_stop.signal'):
            print("\n[INFO] Stop signal received from app. Stopping proctoring...")
            running = False
            break

        frame = get_frame()
        if frame is None:
            print("[WARNING] Camera frame lost!")
            break
            
        # Process single frame
        result = detector.process_single_frame(frame)
        
        # Draw overlays through ui_service
        ui_service.draw_overlay(frame, result)
        
        # Evaluate and trigger alarms
        trigger_alarm(result)
        
        # Record frame statistics, database logs, and SessionReport
        session.record(result)
        
        # Send throttled live updates to server
        session.send_live_update(result)
        
        # Save violations locally and notify Laravel server immediately
        session.record_violation(result)
        
        # Show video frame
        cv2.imshow(config.DISPLAY_WINDOW_NAME, frame)
        
        # Check for exit key
        key = cv2.waitKey(1) & 0xFF
        if key == ord('q') or key == ord('Q') or key == 27:
            print("\n[INFO] Exam stopped by user.")
            running = False
            
        # Check if window was closed
        if cv2.getWindowProperty(config.DISPLAY_WINDOW_NAME, cv2.WND_PROP_VISIBLE) < 1:
            running = False
            
    # Cleanup camera, windows, and detector mesh
    release_camera()
    cv2.destroyAllWindows()
    detector.close()
    
    # End session and output diagnostics
    session.end()

if __name__ == "__main__":
    # NOTE: We do NOT delete proctoring_data.db here anymore.
    # Deleting it wiped all historical session data.
    # The database_service.init_local_database() already handles
    # schema migrations safely via CREATE TABLE IF NOT EXISTS.

    # When running directly (not via flask_server), prompt for student info
    # so exam_session.json always has fresh/correct credentials.
    _ensure_exam_session_json()
    run_proctored_exam()