# alarm_service.py
# Exposes and maintains alarm triggering logic, total_alarms counter, and history.

import time
import config

_alarm_instance = None
ALARM_AVAILABLE = False

# State variables managed by the service
total_alarms = 0
alarm_history = []
_last_alarm_time = 0.0
_last_alarm_type = "NONE"

def initialize_alarm():
    """Initializes the alarm system and resets the session alarm state variables."""
    global _alarm_instance, ALARM_AVAILABLE, total_alarms, alarm_history, _last_alarm_time, _last_alarm_type
    
    total_alarms = 0
    alarm_history = []
    _last_alarm_time = 0.0
    _last_alarm_type = "NONE"
    
    try:
        from alarm import ProctoringAlarm
        _alarm_instance = ProctoringAlarm()
        ALARM_AVAILABLE = True
        print("[INFO] Alarm system initialized")
    except ImportError:
        _alarm_instance = None
        ALARM_AVAILABLE = False
        print("[WARNING] Alarm module not found. Alarm disabled.")
        
    return _alarm_instance

def trigger_alarm(result):
    """Evaluates the detector result, checks the alarm cooldown, and triggers alarm alert beeps."""
    global _alarm_instance, total_alarms, alarm_history, _last_alarm_time, _last_alarm_type
    
    if not ALARM_AVAILABLE:
        return
        
    if not hasattr(config, 'ALARM_ENABLED') or not config.ALARM_ENABLED:
        return
        
    # Read potential alarm status from the frame's detector result
    alarm_level = result.get("alarm_level", "none")
    if alarm_level == "none" or alarm_level == "None":
        return
        
    current_time = time.time()
    cooldown = getattr(config, 'ALARM_COOLDOWN', 3)
    
    # Verify the cooldown window has elapsed
    if (current_time - _last_alarm_time) > cooldown:
        _last_alarm_time = current_time
        total_alarms += 1
        
        violation_type = result.get("last_alarm_type", "UNKNOWN")
        _last_alarm_type = violation_type
        
        # Trigger the corresponding beep pattern
        if alarm_level == "high":
            if violation_type == "MULTIPLE_FACES":
                play_cheating_sound()
            else:
                if _alarm_instance is not None:
                    _alarm_instance.trigger_alarm("high", violation_type.lower())
        elif alarm_level == "medium":
            if _alarm_instance is not None:
                _alarm_instance.trigger_alarm("medium", violation_type.lower())
        elif alarm_level == "low":
            play_warning_sound()
            
        # Append the violation event to the alarm history list
        timestamp_str = time.strftime("%H:%M:%S")
        alarm_record = {
            "alarm_number": total_alarms,
            "type": violation_type,
            "severity": alarm_level,
            "risk_score": result["risk_score"],
            "timestamp": timestamp_str
        }
        alarm_history.append(alarm_record)
        
        # Update the result object in-place so downstream services have latest alarm details
        result["total_alarms"] = total_alarms
        result["last_alarm_type"] = violation_type
        result["last_alarm_time"] = current_time
        result["new_violation"] = {
            "violation_type": violation_type,
            "severity": alarm_level,
            "risk_score": result["risk_score"],
            "timestamp": timestamp_str,
            "alarm_number": total_alarms
        }

def play_warning_sound():
    """Plays standard warn beep."""
    global _alarm_instance
    if _alarm_instance is not None:
        _alarm_instance.play_warning_sound()

def play_cheating_sound():
    """Plays cheat warning siren."""
    global _alarm_instance
    if _alarm_instance is not None:
        _alarm_instance.play_cheating_sound()
