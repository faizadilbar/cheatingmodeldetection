# models/detector_output.py

import json
from dataclasses import dataclass, field

@dataclass
class DetectorOutput:
    """Standardized JSON-serializable proctoring data structure representing a processed frame."""
    face_count: int = 0
    multiple_faces: bool = False
    no_face: bool = False

    looking_away: bool = False
    head_turn: bool = False

    head_yaw: float = 0.0
    head_pitch: float = 0.0

    gaze_yaw: float = 0.0
    gaze_pitch: float = 0.0

    blink_count: int = 0

    risk_score: float = 0.0
    max_risk: float = 0.0

    alarm_level: str = "none"

    flags: dict = field(default_factory=dict)
    consecutive_frames: dict = field(default_factory=dict)

    total_alarms: int = 0
    last_alarm_type: str = "NONE"
    last_alarm_time: float = 0.0
    timestamp: float = 0.0
    face_center_x: float = 0.5
    face_center_y: float = 0.5

    def to_dict(self):
        """Converts to a dictionary mapping all standardized fields."""
        return {
            "face_count": self.face_count,
            "multiple_faces": self.multiple_faces,
            "no_face": self.no_face,
            "looking_away": self.looking_away,
            "head_turn": self.head_turn,
            "head_yaw": self.head_yaw,
            "head_pitch": self.head_pitch,
            "gaze_yaw": self.gaze_yaw,
            "gaze_pitch": self.gaze_pitch,
            "blink_count": self.blink_count,
            "risk_score": self.risk_score,
            "max_risk": self.max_risk,
            "alarm_level": self.alarm_level,
            "flags": self.flags,
            "consecutive_frames": self.consecutive_frames,
            "total_alarms": self.total_alarms,
            "last_alarm_type": self.last_alarm_type,
            "last_alarm_time": self.last_alarm_time,
            "timestamp": self.timestamp,
            "face_center_x": self.face_center_x,
            "face_center_y": self.face_center_y
        }

    def to_json(self):
        """Returns JSON-serialized representation of standardized fields."""
        return json.dumps(self.to_dict())

    def __getitem__(self, item):
        """Supports dictionary-like subscript lookup for backward compatibility."""
        return getattr(self, item)

    def __setitem__(self, key, value):
        """Supports dictionary-like subscript updates for backward compatibility."""
        setattr(self, key, value)
        
    def __contains__(self, item):
        """Supports checking if key is a class attribute or is dynamic on this instance."""
        return hasattr(self, item)
        
    def get(self, item, default=None):
        """Supports dict.get method for compatibility."""
        return getattr(self, item, default)
