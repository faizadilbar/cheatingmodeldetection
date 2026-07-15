# services/api_service.py
# Sends student proctoring data directly to the Laravel backend.
# No teacher authentication required — student info is passed directly.

import os
import requests
import threading

def _run_async(func, *args, **kwargs):
    thread = threading.Thread(target=func, args=args, kwargs=kwargs, daemon=True)
    thread.start()


API_BASE_URL = "https://bgnuf22eight.com/cheating/proctoring-backend/public/api"

# Standard JSON headers for all API calls
_HEADERS = {"Content-Type": "application/json", "Accept": "application/json"}


def get_teacher_token():
    """No-op — teacher authentication is not required.
    Kept for backward compatibility so callers don't break."""
    print("[API] ℹ️  Teacher auth skipped — student data is sent directly.")
    return None


def start_session_on_server(student_info):
    """Start exam session on the Laravel backend with student info."""
    try:
        start_time_val = student_info.get('start_time', '')
        if isinstance(start_time_val, str) and ":" in start_time_val:
            parts = start_time_val.split(":")
            if len(parts) >= 2:
                start_time_val = f"{parts[0].strip()}:{parts[1].strip()}"

        payload = {
            "student_id": student_info['student_id'],
            "student_name": student_info['student_name'],
            "quiz_code": student_info['quiz_code'],
            "course_name": student_info['course_name'],
            "exam_date": student_info['exam_date'],
            "start_time": start_time_val,
        }

        print(f"[API] Sending to: {API_BASE_URL}/exam-sessions/start")
        print(f"[API] Payload: {payload}")

        response = requests.post(
            f"{API_BASE_URL}/exam-sessions/start",
            json=payload,
            headers=_HEADERS,
            timeout=10
        )

        print(f"[API] Response Status: {response.status_code}")
        print(f"[API] Response Body: {response.text[:500]}")

        if response.status_code in (200, 201):
            data = response.json()
            server_session_id = (
                data.get('session_id')
                or data.get('id')
                or data.get('data', {}).get('id')
                or data.get('data', {}).get('session_id')
            )
            print(f"[API] ✅ Session started! ID: {server_session_id}")
            return server_session_id
        else:
            print(f"[API] ⚠️ Failed: {response.status_code}")
            print(f"[API] Response: {response.text}")
            return None
    except Exception as e:
        print(f"[API] ❌ Error: {e}")
        return None


def send_live_update_to_server(session_id, risk_score, gaze_away, head_turns,
                                no_face, multi_face, blinks, alarm_triggered, total_count,
                                max_risk_score=0.0):
    """Send live update to server with alarm tracking asynchronously.
    
    Sends current proctoring stats to the Laravel /live-update endpoint every
    LIVE_UPDATE_INTERVAL seconds. Logs the full server response body on errors
    so you can diagnose 500s from the backend.
    """
    def _send():
        if not session_id:
            return
        try:
            payload = {
                "avg_risk_score": round(risk_score, 1),
                "max_risk_score": round(max_risk_score, 1),
                "gaze_away_count": gaze_away,
                "head_turn_count": head_turns,
                "no_face_count": no_face,
                "multiple_face_count": multi_face,
                "total_blinks": blinks,
                "alarm_triggered": 1 if alarm_triggered else 0,
                "total_count": total_count,
                "alarm_count": total_count,
                "status": "active",
            }

            print(
                f"[API] → live-update session={session_id} | "
                f"avg_risk={payload['avg_risk_score']} max_risk={payload['max_risk_score']} | "
                f"gaze={gaze_away} head={head_turns} no_face={no_face} multi={multi_face} | "
                f"blinks={blinks} alarms={total_count}"
            )
            response = requests.post(
                f"{API_BASE_URL}/exam-sessions/{session_id}/live-update",
                json=payload,
                headers=_HEADERS,
                timeout=5
            )

            if response.status_code == 200:
                print(f"[API] ✅ Live update accepted by server")
            elif response.status_code == 500:
                # Print full body to diagnose the server error
                print(f"[API] ⚠️ Live update failed (500 Server Error). Response: {response.text[:500]}")
            else:
                print(f"[API] ⚠️ Live update failed: {response.status_code} — {response.text[:200]}")
        except Exception as e:
            print(f"[API] Live update request failed: {e}")

    _run_async(_send)



def report_alarm_to_server(payload):
    """Report alarm to server with the live alarm dashboard payload asynchronously.
    
    Tries the dedicated /report-violation endpoint first. If it returns 404 (route
    not available on the server), falls back to embedding the violation data inside
    a /live-update call so the teacher dashboard still receives real-time alerts.
    """
    def _send():
        session_id = payload.get("session_id")
        if not session_id:
            return
        try:
            response = requests.post(
                f"{API_BASE_URL}/exam-sessions/{session_id}/report-violation",
                json=payload,
                headers=_HEADERS,
                timeout=5
            )
            if response.status_code == 200:
                print(f"[API] ✅ Alarm reported live: {payload.get('violation_type')} (Alarm #{payload.get('alarm_number')})")
                return
            elif response.status_code == 404:
                # Route doesn't exist on this server version — fall back to live-update
                print(f"[API] ⚠️  report-violation route not found (404). Sending via live-update fallback.")
                _send_violation_via_live_update(session_id, payload)
            else:
                print(f"[API] ⚠️ Alarm report failed: {response.status_code} — {response.text[:200]}")
        except Exception as e:
            print(f"[API] Live Alarm report failed: {e}")
            # Try fallback silently
            try:
                _send_violation_via_live_update(session_id, payload)
            except Exception:
                pass

    _run_async(_send)


def _send_violation_via_live_update(session_id, violation_payload):
    """Fallback: embed violation details into a live-update call when the
    dedicated report-violation endpoint is unavailable."""
    try:
        merged = {
            "alarm_triggered": 1,
            "latest_violation_type": violation_payload.get("violation_type"),
            "latest_severity": violation_payload.get("severity"),
            "latest_risk_score": violation_payload.get("risk_score"),
            "alarm_number": violation_payload.get("alarm_number"),
        }
        response = requests.post(
            f"{API_BASE_URL}/exam-sessions/{session_id}/live-update",
            json=merged,
            headers=_HEADERS,
            timeout=5
        )
        if response.status_code == 200:
            print(f"[API] ✅ Violation sent via live-update fallback: {violation_payload.get('violation_type')}")
        else:
            print(f"[API] ⚠️ Fallback live-update also failed: {response.status_code}")
    except Exception as e:
        print(f"[API] Fallback live-update error: {e}")



def end_session_on_server(session_id, end_time, avg_risk, max_risk, total_blinks,
                          gaze_away, head_turns, no_face, multi_face, cheating_status, alarm_count):
    """End session on server"""
    if not session_id:
        return
    try:
        if len(end_time) == 5:  # HH:MM format
            end_time = end_time + ":00"  # Make HH:MM:SS

        payload = {
            "end_time": end_time,
            "avg_risk_score": round(avg_risk, 1),
            "max_risk_score": round(max_risk, 1),
            "total_blinks": total_blinks,
            "gaze_away_count": gaze_away,
            "head_turn_count": head_turns,
            "no_face_count": no_face,
            "multiple_face_count": multi_face,
            "cheating_status": cheating_status,
            "alarm_count": alarm_count,
            "total_count": alarm_count,
            "alarm_triggered": 1 if alarm_count > 0 else 0,
        }
        response = requests.post(
            f"{API_BASE_URL}/exam-sessions/{session_id}/end",
            json=payload,
            headers=_HEADERS,
            timeout=10
        )
        if response.status_code == 200:
            print(f"[API] ✅ Session ended on server")
            return True
        else:
            print(f"[API] ⚠️ End session failed: {response.status_code} — {response.text[:300]}")
            return False
    except Exception as e:
        print(f"[API] ❌ End session error: {e}")
        return False


def upload_report_to_server(session_id, report_path, quiz_code):
    """Upload report to server with enhanced error handling"""
    if not session_id:
        print("[UPLOAD] No session ID, skipping upload")
        return False

    if not os.path.exists(report_path):
        print(f"[UPLOAD] ERROR: Report file not found at {report_path}")
        return False

    try:
        file_size = os.path.getsize(report_path)
        print(f"[UPLOAD] File found: {report_path} ({file_size} bytes)")

        with open(report_path, 'rb') as f:
            files = {'report': (os.path.basename(report_path), f, 'text/plain')}
            data = {'quiz_code': quiz_code}

            url = f"{API_BASE_URL}/exam-sessions/{session_id}/report"
            print(f"[UPLOAD] POST to: {url}")

            response = requests.post(
                url,
                files=files,
                data=data,
                timeout=30
            )

            print(f"[UPLOAD] Response status: {response.status_code}")
            print(f"[UPLOAD] Response body: {response.text}")

            if response.status_code == 200:
                response_data = response.json()
                print(f"[UPLOAD] ✅ Report uploaded successfully!")
                if response_data.get('report_path'):
                    print(f"[UPLOAD] Report path saved to DB: {response_data.get('report_path')}")
                return True
            else:
                print(f"[UPLOAD] ❌ Upload failed with status {response.status_code}")
                return False

    except requests.exceptions.Timeout:
        print(f"[UPLOAD] ❌ Request timeout - server took too long to respond")
        return False
    except Exception as e:
        print(f"[UPLOAD] ❌ Exception: {e}")
        return False
