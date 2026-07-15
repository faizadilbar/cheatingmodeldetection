# flask_server.py - Complete Flask Server for Flutter Integration

from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
import threading
import subprocess
import sys
import os
import json
import signal
import time
import sqlite3
import base64
import io
from datetime import datetime

app = Flask(__name__)
CORS(app)

# Store current exam info
current_exam = {}
exam_process = None
exam_thread = None
is_exam_running = False

_session_finalized = False
_auto_finalize_timer = None

active_detector = None
active_session = None

LIVE_METRICS_FILE = os.path.join(os.path.dirname(__file__), 'live_metrics.json')
_in_memory_metrics = None

# ── Frame storage for teacher view ─────────────────────────────────────────
_latest_frame = None
_latest_frame_timestamp = None
_latest_frame_student_id = None

def write_live_metrics(data: dict):
    global _in_memory_metrics
    _in_memory_metrics = data
    if data is None:
        try:
            if os.path.exists(LIVE_METRICS_FILE):
                os.remove(LIVE_METRICS_FILE)
        except Exception:
            pass
        return

    try:
        temp_file = LIVE_METRICS_FILE + ".tmp"
        with open(temp_file, 'w') as f:
            json.dump(data, f)
        os.replace(temp_file, LIVE_METRICS_FILE)
    except Exception:
        pass

MAIN_PY_PATH = os.path.join(os.path.dirname(__file__), 'main.py')

@app.route('/start-exam', methods=['POST'])
def start_exam():
    global current_exam, is_exam_running, exam_thread, _in_memory_metrics
    global active_detector, active_session, _session_finalized, _auto_finalize_timer

    try:
        data = request.json
        print(f"[FLASK] Received exam start request: {data}")

        if _auto_finalize_timer is not None:
            _auto_finalize_timer.cancel()
            _auto_finalize_timer = None
            print("[FLASK] Cancelled leftover auto-finalize timer from previous session.")

        if is_exam_running and active_session is not None:
            print("[FLASK] Previous session still active — force-ending it before starting new one.")
            try:
                active_session.end()
            except Exception:
                pass
        
        active_detector = None
        active_session = None
        is_exam_running = False
        _session_finalized = False
        _in_memory_metrics = None

        current_exam = {
            "student_id": data.get('roll_no', data.get('student_id', '')),
            "student_name": data.get('student_name', ''),
            "course_name": data.get('course_name', data.get('book_name', '')),
            "book_name": data.get('course_name', data.get('book_name', '')),
            "course_code": data.get('quiz_code', data.get('course_code', '')),
            "quiz_code": data.get('quiz_code', ''),
            "quiz_id": data.get('quiz_id', ''),
            "exam_date": data.get('exam_date', '') or time.strftime('%Y-%m-%d'),
            "start_time": data.get('start_time', '') or time.strftime('%H:%M'),
            "end_time": data.get('end_time', ''),
        }

        start_time_val = current_exam["start_time"]
        if isinstance(start_time_val, str) and ":" in start_time_val:
            parts = start_time_val.split(":")
            if len(parts) >= 2:
                current_exam["start_time"] = f"{parts[0].strip()}:{parts[1].strip()}"

        with open('exam_session.json', 'w') as f:
            json.dump(current_exam, f)

        if os.path.exists('exam_stop.signal'):
            os.remove('exam_stop.signal')

        print(f"[FLASK] Exam started for {current_exam['student_name']}")

        print("[FLASK] Starting in REMOTE FRAME RECEIVER mode.")
        from detector_service import ProctoringDetector
        from session_service import ExamSession
        from alarm_service import initialize_alarm

        initialize_alarm()
        active_detector = ProctoringDetector(calibrate_locally=False)
        active_session = ExamSession()
        active_session.start()
        is_exam_running = True

        # Calculate dynamic safety timeout based on end_time
        _SAFETY_TIMEOUT = 7200  # default 2 hours fallback
        end_time_str = current_exam.get('end_time', '')
        if end_time_str:
            try:
                exam_date_str = current_exam["exam_date"]
                # Parse exam_date (YYYY-MM-DD)
                date_parts = [int(x) for x in exam_date_str.split('-')]
                # Parse end_time (HH:MM:SS or HH:MM)
                time_parts = [int(x) for x in end_time_str.split(':')]
                
                end_hour = time_parts[0]
                end_min = time_parts[1]
                end_sec = time_parts[2] if len(time_parts) > 2 else 0
                
                end_dt = datetime(date_parts[0], date_parts[1], date_parts[2], end_hour, end_min, end_sec)
                now_dt = datetime.now()
                
                # Handle midnight crossing (if end_time hour is less than start_time hour, it's next day)
                if end_dt <= now_dt and now_dt.hour > end_hour:
                    from datetime import timedelta
                    end_dt += timedelta(days=1)
                
                seconds_left = int((end_dt - now_dt).total_seconds())
                if seconds_left > 0:
                    _SAFETY_TIMEOUT = seconds_left
                    print(f"[FLASK] Dynamic safety timeout set to {_SAFETY_TIMEOUT} seconds (until {end_dt})")
                else:
                    print(f"[FLASK] Calculated timeout {seconds_left} <= 0. Using default {_SAFETY_TIMEOUT} seconds.")
            except Exception as te:
                print(f"[FLASK] Error parsing end_time/exam_date for timeout calculation: {te}")

        def _auto_finalize():
            global is_exam_running, _in_memory_metrics, active_detector, active_session, _session_finalized
            if _session_finalized:
                print("[FLASK] Auto-finalize skipped — session already ended by /stop-exam.")
                return
            _session_finalized = True
            print(f"[FLASK] ⏱️ Safety timer ({_SAFETY_TIMEOUT}s) elapsed — auto-finalizing session...")
            is_exam_running = False
            _in_memory_metrics = None
            try:
                with open('exam_stop.signal', 'w') as _f:
                    _f.write('stop')
            except Exception:
                pass
            if active_session is not None:
                try:
                    active_session.end()
                    print("[FLASK] ✅ Auto-finalize complete — report generated and sent to API.")
                except Exception as _e:
                    print(f"[FLASK] ⚠️ Auto-finalize error: {_e}")
            active_detector = None
            active_session = None

        _auto_finalize_timer = threading.Timer(_SAFETY_TIMEOUT, _auto_finalize)
        _auto_finalize_timer.daemon = True
        _auto_finalize_timer.start()
        print("[FLASK] ✅ Exam session active — call /stop-exam to end.")

        return jsonify({
            'status': True,
            'message': 'Exam monitoring started',
            'data': current_exam
        })

    except Exception as e:
        print(f"[FLASK] Error starting exam: {e}")
        return jsonify({
            'status': False,
            'message': str(e)
        }), 500

@app.route('/stop-exam', methods=['POST'])
def stop_exam():
    global is_exam_running, _in_memory_metrics, active_detector, active_session, _session_finalized, _auto_finalize_timer
    
    try:
        if _auto_finalize_timer is not None:
            _auto_finalize_timer.cancel()
            _auto_finalize_timer = None

        with open('exam_stop.signal', 'w') as f:
            f.write('stop')
        
        if os.path.exists('exam_session.json'):
            try:
                os.remove('exam_session.json')
            except Exception as e:
                print(f"[FLASK] Error removing exam_session.json: {e}")
        
        print("[FLASK] Exam stop signal received — finalizing session...")
        is_exam_running = False
        _in_memory_metrics = None
        
        if _session_finalized:
            print("[FLASK] Session was already auto-finalized — skipping duplicate end.")
        elif active_session is not None:
            _session_finalized = True
            try:
                active_session.end()
                print("[FLASK] ✅ Session finalized and report generated.")
            except Exception as e:
                print(f"[FLASK] Error ending session: {e}")
        
        active_detector = None
        active_session = None
        
        return jsonify({
            'status': True,
            'message': 'Exam monitoring stopped and report generated'
        })
        
    except Exception as e:
        print(f"[FLASK] Error stopping exam: {e}")
        return jsonify({
            'status': False,
            'message': str(e)
        }), 500

@app.route('/status', methods=['GET'])
def status():
    try:
        exam_running = os.path.exists('exam_session.json')
        stop_signal = os.path.exists('exam_stop.signal')
        
        exam_data = {}
        if exam_running:
            try:
                with open('exam_session.json', 'r') as f:
                    exam_data = json.load(f)
            except:
                pass
        
        return jsonify({
            'status': True,
            'running': exam_running and not stop_signal,
            'exam': exam_data
        })
        
    except Exception as e:
        return jsonify({
            'status': False,
            'message': str(e)
        }), 500

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': True,
        'message': 'Proctoring server is running'
    })

@app.route('/metrics', methods=['GET'])
def metrics():
    global _in_memory_metrics
    if _in_memory_metrics is not None:
        return jsonify(_in_memory_metrics)

    try:
        if os.path.exists(LIVE_METRICS_FILE):
            with open(LIVE_METRICS_FILE, 'r') as f:
                data = json.load(f)
            return jsonify(data)
        else:
            return jsonify({
                'status': 'calibrating',
                'alarm_level': 'calibrating',
                'risk_score': 0.0,
                'max_risk': 0.0,
                'total_alarms': 0,
                'blink_count': 0,
                'last_alarm_type': 'NONE',
                'flags': {
                    'gaze_away': False,
                    'head_turn': False,
                    'multiple_faces': False,
                    'no_face': False
                }
            })
    except Exception as e:
        return jsonify({'status': False, 'message': str(e)}), 500

@app.route('/upload-frame', methods=['POST'])
def upload_frame():
    global active_detector, active_session, _latest_frame, _latest_frame_timestamp, _latest_frame_student_id
    
    if not is_exam_running or active_session is None:
        return jsonify({
            'status': False,
            'message': 'No active exam session running'
        }), 400
        
    try:
        if 'frame' not in request.files:
            return jsonify({
                'status': False,
                'message': "Missing 'frame' file parameter"
            }), 400
            
        file = request.files['frame']
        file_bytes = file.read()
        
        if len(file_bytes) == 0:
            return jsonify({
                'status': False,
                'message': 'Empty frame uploaded'
            }), 400
            
        if len(file_bytes) < 4 or file_bytes[0] != 0xFF or file_bytes[1] != 0xD8:
            return jsonify({
                'status': False,
                'message': 'Invalid image format (not a valid JPEG)'
            }), 400

        # ── Store the latest frame for teacher viewing ──
        _latest_frame = file_bytes
        _latest_frame_timestamp = datetime.now()
        _latest_frame_student_id = current_exam.get('student_id', 'unknown')
        print(f"[FRAME STORED] Student: {_latest_frame_student_id}, Size: {len(file_bytes)} bytes")

        import numpy as np
        import cv2
        import io
        from PIL import Image, ImageOps
        
        try:
            pil_img = Image.open(io.BytesIO(file_bytes))
            pil_img = ImageOps.exif_transpose(pil_img)
            frame = cv2.cvtColor(np.array(pil_img), cv2.COLOR_RGB2BGR)
        except Exception:
            nparr = np.frombuffer(file_bytes, np.uint8)
            frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        if frame is None or frame.size == 0:
            return jsonify({
                'status': False,
                'message': 'Failed to decode image frame'
            }), 400
            
        if active_detector is None:
            return jsonify({
                'status': False,
                'message': 'Proctoring detector is still initializing or calibrating'
            }), 503
            
        result = active_detector.process_single_frame(frame)
        
        import alarm_service
        alarm_service.trigger_alarm(result)
        
        active_session.record(result)
        active_session.record_violation(result)

        import services.api_service as api_service
        sess = active_session.state
        alarm_triggered = len(sess.alarm_history_list) > 0
        total_count = len(sess.alarm_history_list)
        max_risk_val = round(float(active_detector.max_risk), 1)
        avg_risk_val = round(float(result.get('risk_score', 0.0)), 1)

        if sess.server_session_id:
            api_service.send_live_update_to_server(
                sess.server_session_id,
                avg_risk_val,
                sess.gaze_away_count,
                sess.head_turn_count,
                sess.no_face_count,
                sess.multi_face_count,
                int(result.get('blink_count', 0)),
                alarm_triggered,
                total_count,
                max_risk_score=max_risk_val
            )
        
        new_viol = result.get('new_violation')
        metrics_payload = {
            'status': 'active',
            'alarm_level': result.get('alarm_level', 'none'),
            'avg_risk_score': avg_risk_val,
            'max_risk_score': max_risk_val,
            'risk_score': avg_risk_val,
            'max_risk': max_risk_val,
            'gaze_away_count': sess.gaze_away_count,
            'head_turn_count': sess.head_turn_count,
            'no_face_count': sess.no_face_count,
            'multiple_face_count': sess.multi_face_count,
            'total_blinks': int(active_detector.blink_counter.count),
            'total_count': total_count,
            'alarm_count': total_count,
            'total_alarms': alarm_service.total_alarms,
            'blink_count': int(active_detector.blink_counter.count),
            'last_alarm_type': result.get('last_alarm_type', 'NONE'),
            'new_violation': new_viol,
            'play_alarm': bool(new_viol is not None or result.get('alarm_level') in ['high', 'medium']),
            'flags': {
                'gaze_away': bool(result.get('flags', {}).get('gaze_away', False)),
                'head_turn': bool(result.get('flags', {}).get('head_turn', False)),
                'multiple_faces': bool(result.get('flags', {}).get('multiple_faces', False)),
                'no_face': bool(result.get('flags', {}).get('no_face', False))
            },
            'face_center_x': float(result.get('face_center_x', 0.5)),
            'face_center_y': float(result.get('face_center_y', 0.5))
        }
        write_live_metrics(metrics_payload)
        
        print(
            f"[FRAME] Risk={avg_risk_val:.1f}% MaxRisk={max_risk_val:.1f}% "
            f"Alarm={metrics_payload['alarm_level']} "
            f"Gaze={sess.gaze_away_count} Head={sess.head_turn_count} "
            f"NoFace={sess.no_face_count} MultiFace={sess.multi_face_count} "
            f"Blinks={metrics_payload['blink_count']} Alarms={total_count}"
        )
        
        return jsonify({
            'status': True,
            'message': 'Frame processed successfully',
            'result': {
                'avg_risk_score': avg_risk_val,
                'max_risk_score': max_risk_val,
                'risk_score': avg_risk_val,
                'max_risk': max_risk_val,
                'alarm_level': metrics_payload['alarm_level'],
                'violation_type': metrics_payload['last_alarm_type'],
                'new_violation': new_viol,
                'play_alarm': metrics_payload['play_alarm'],
                'gaze_away_count': sess.gaze_away_count,
                'head_turn_count': sess.head_turn_count,
                'no_face_count': sess.no_face_count,
                'multiple_face_count': sess.multi_face_count,
                'total_blinks': metrics_payload['blink_count'],
                'total_count': total_count,
                'alarm_count': total_count,
                'status': 'active',
                'flags': metrics_payload['flags'],
                'face_center_x': float(result.get('face_center_x', 0.5)),
                'face_center_y': float(result.get('face_center_y', 0.5))
            }
        })
        
    except Exception as e:
        import traceback
        print(f"[FLASK] Error processing uploaded frame: {e}")
        traceback.print_exc()
        return jsonify({
            'status': False,
            'message': str(e)
        }), 500

# ── Endpoint to serve frames to teacher ─────────────────────────────────────

@app.route('/get-frame/<student_id>', methods=['GET'])
def get_frame(student_id):
    """Serve the latest frame to the teacher's LiveStudentScreen."""
    global _latest_frame, _latest_frame_timestamp, _latest_frame_student_id
    
    if _latest_frame is None:
        return jsonify({
            'status': False,
            'message': 'No frame available from student'
        }), 404
    
    if _latest_frame_timestamp is not None:
        age = (datetime.now() - _latest_frame_timestamp).total_seconds()
        if age > 10:
            return jsonify({
                'status': False,
                'message': f'Frame is too old ({age:.1f}s ago)',
                'timestamp': _latest_frame_timestamp.isoformat()
            }), 404
    
    frame_b64 = base64.b64encode(_latest_frame).decode('utf-8')
    return jsonify({
        'status': True,
        'frame': frame_b64,
        'student_id': _latest_frame_student_id,
        'timestamp': _latest_frame_timestamp.isoformat() if _latest_frame_timestamp else None,
        'age_seconds': (datetime.now() - _latest_frame_timestamp).total_seconds() if _latest_frame_timestamp else None
    })

# ── Database API endpoints ──

DB_PATH = os.path.join(os.path.dirname(__file__), 'proctoring_data.db')

def _query_db(query, args=(), one=False):
    try:
        conn = sqlite3.connect(DB_PATH)
        conn.row_factory = sqlite3.Row
        cur = conn.execute(query, args)
        rows = [dict(r) for r in cur.fetchall()]
        conn.close()
        return rows[0] if (one and rows) else rows
    except Exception as e:
        return None if one else []

@app.route('/api/sessions', methods=['GET'])
def api_sessions():
    rows = _query_db('SELECT * FROM exam_sessions ORDER BY created_at DESC')
    return jsonify({'status': True, 'count': len(rows), 'sessions': rows})

@app.route('/api/sessions/<session_id>', methods=['GET'])
def api_session_detail(session_id):
    session = _query_db('SELECT * FROM exam_sessions WHERE session_id = ?', (session_id,), one=True)
    if not session:
        return jsonify({'status': False, 'message': 'Session not found'}), 404
    violations = _query_db('SELECT * FROM violations WHERE session_id = ? ORDER BY timestamp', (session_id,))
    risk_history = _query_db('SELECT risk_score, timestamp FROM risk_history WHERE session_id = ? ORDER BY timestamp', (session_id,))
    return jsonify({'status': True, 'session': session, 'violations': violations, 'risk_history': risk_history})

@app.route('/api/violations', methods=['GET'])
def api_violations():
    session_id = request.args.get('session_id')
    if session_id:
        rows = _query_db('SELECT * FROM violations WHERE session_id = ? ORDER BY timestamp DESC', (session_id,))
    else:
        rows = _query_db('SELECT v.*, e.student_name, e.student_id FROM violations v LEFT JOIN exam_sessions e ON v.session_id = e.session_id ORDER BY v.timestamp DESC')
    return jsonify({'status': True, 'count': len(rows), 'violations': rows})

@app.route('/api/risk-history', methods=['GET'])
def api_risk_history():
    session_id = request.args.get('session_id')
    if not session_id:
        return jsonify({'status': False, 'message': 'session_id required'}), 400
    rows = _query_db('SELECT risk_score, timestamp FROM risk_history WHERE session_id = ? ORDER BY timestamp', (session_id,))
    return jsonify({'status': True, 'count': len(rows), 'risk_history': rows})

def run_flask_server():
    # Read PORT from environment variable (Railway sets this dynamically)
    port = int(os.environ.get('PORT', 5000))
    print("=" * 50)
    print("  EYE PROCTORING — FLASK SERVER")
    print("=" * 50)
    print(f"  Listening on http://0.0.0.0:{port}")
    print("  Endpoints:")
    print("    POST /start-exam            - Start exam monitoring")
    print("    POST /stop-exam             - Stop exam monitoring")
    print("    GET  /status                - Get current status")
    print("    GET  /health                - Health check")
    print("    GET  /metrics               - Live proctoring metrics")
    print("    GET  /get-frame/<student_id> - Get latest frame (base64 JSON)")
    print("    GET  /api/sessions          - All sessions with cheating data")
    print("    GET  /api/sessions/<id>     - Single session + violations")
    print("    GET  /api/violations        - All violations")
    print("    GET  /api/risk-history      - Risk score timeline")
    print("=" * 50)
    app.run(host='0.0.0.0', port=port, debug=False, threaded=True)

if __name__ == '__main__':
    run_flask_server()