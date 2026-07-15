# backend/websocket/proctor_ws.py

import sys
import os
import json
import base64
import cv2
import numpy as np
import traceback
from datetime import datetime

# Ensure parent directory is in search path to import existing modules
PARENT_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
if PARENT_DIR not in sys.path:
    sys.path.append(PARENT_DIR)

from fastapi import APIRouter, WebSocket, WebSocketDisconnect, Query
from backend.core.detector_manager import detector_manager

router = APIRouter()

def decode_base64_frame(b64_string: str):
    """Decodes a base64 encoded image string into an OpenCV frame numpy array."""
    if not b64_string:
        return None
    try:
        if "," in b64_string:
            b64_string = b64_string.split(",", 1)[1]
        img_bytes = base64.b64decode(b64_string)
        nparr = np.frombuffer(img_bytes, np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        return frame
    except Exception as e:
        print(f"[WebSocket] Error decoding image: {e}")
        return None

@router.websocket("/ws/proctor/{session_id}")
async def websocket_endpoint(
    websocket: WebSocket,
    session_id: str,
    student_id: str = Query(None),
    student_name: str = Query(None),
    course_name: str = Query(None),
    quiz_code: str = Query(None)
):
    await websocket.accept()
    print(f"[WebSocket] Connected: session_id={session_id}")
    
    detector_manager.register_connection(session_id)
    session = None
    detector = None
    
    try:
        # Check if student info was passed via query parameters
        if student_id and student_name:
            student_info = {
                "student_id": student_id,
                "student_name": student_name,
                "course_name": course_name or "Unknown",
                "quiz_code": quiz_code or "Unknown",
                "exam_date": datetime.now().strftime("%Y-%m-%d"),
                "start_time": datetime.now().strftime("%H:%M")
            }
            session = detector_manager.get_or_create_session(session_id, student_info)
            detector = detector_manager.get_or_create_detector(session_id)
            print(f"[WebSocket] Session initialized via query parameters for student: {student_name}")
            
        while True:
            # Receive text payload (JSON or base64 frame string)
            data = await websocket.receive_text()
            detector_manager.update_activity(session_id)
            
            b64_image = None
            
            # Check if payload is JSON
            try:
                payload = json.loads(data)
                
                # Check for heartbeat ping
                if payload.get("type") == "ping":
                    await websocket.send_json({"status": "pong"})
                    continue
                
                # Check for explicit end session event
                if payload.get("type") == "end_session":
                    print(f"[WebSocket] Explicit end_session request received for session_id={session_id}")
                    detector_manager.force_cleanup(session_id)
                    await websocket.send_json({"status": "ended", "session_id": session_id})
                    break
                
                # Check for registration event
                if payload.get("type") == "register" or "student_id" in payload:
                    student_info = {
                        "student_id": payload.get("student_id", "Unknown"),
                        "student_name": payload.get("student_name", "Unknown"),
                        "course_name": payload.get("course_name", "Unknown"),
                        "quiz_code": payload.get("quiz_code", "Unknown"),
                        "exam_date": payload.get("exam_date", datetime.now().strftime("%Y-%m-%d")),
                        "start_time": payload.get("start_time", datetime.now().strftime("%H:%M"))
                    }
                    session = detector_manager.get_or_create_session(session_id, student_info)
                    detector = detector_manager.get_or_create_detector(session_id)
                    print(f"[WebSocket] Session registered via JSON payload for student: {student_info['student_name']}")
                    await websocket.send_json({"status": "registered", "session_id": session_id})
                    continue
                
                # If already registered, look for image frame
                b64_image = payload.get("image") or payload.get("frame")
            except json.JSONDecodeError:
                # If not JSON, treat raw message data as the base64 string
                b64_image = data
                
            if not session or not detector:
                await websocket.send_json({
                    "status": "error",
                    "message": "Session not registered yet. Please send registration info first."
                })
                continue
                
            if not b64_image:
                continue
                
            frame = decode_base64_frame(b64_image)
            if frame is None:
                await websocket.send_json({"status": "error", "message": "Failed to decode image frame"})
                continue
                
            # Process frame using the detector instance
            try:
                output = detector.process_single_frame(frame)
                
                # Update session statistics and reports
                if output.alarm_level != "calibrating":
                    session.record(output)
                    
                    # Record and alert on new violations (if cooldown elapsed)
                    if output.new_violation:
                        session.record_violation(output)
                        
                    # Throttled live update sync
                    session.send_live_update(output)
                    
                # Return the processed metrics to the client in real-time
                await websocket.send_json(output.to_dict())
            except Exception as e:
                print(f"[WebSocket] Frame processing error: {e}")
                traceback.print_exc()
                await websocket.send_json({"status": "error", "message": f"Processing error: {str(e)}"})
                
    except WebSocketDisconnect:
        print(f"[WebSocket] Disconnected: session_id={session_id}")
    except Exception as e:
        print(f"[WebSocket] Connection error: {e}")
        traceback.print_exc()
    finally:
        # Deregister connection and schedule cleanup only if it wasn't explicitly ended
        if session_id in detector_manager._sessions:
            detector_manager.deregister_connection(session_id)
            print(f"[WebSocket] Deregistered session_id={session_id} (cleanup scheduled if not reconnected)")
        else:
            print(f"[WebSocket] Connection closed cleanly after explicit end for session_id={session_id}")

