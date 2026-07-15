# models/detector_state.py

class DetectorState:
    """Represents the proctoring detector's state / output for a single frame."""
    def __init__(self, face_count=0, looking_away=False, head_yaw=0.0, head_pitch=0.0,
                 gaze_yaw=0.0, gaze_pitch=0.0, blink_count=0, risk_score=0.0,
                 multiple_faces=False, flags=None):
        self.face_count = face_count
        self.looking_away = looking_away
        self.head_yaw = head_yaw
        self.head_pitch = head_pitch
        self.gaze_yaw = gaze_yaw
        self.gaze_pitch = gaze_pitch
        self.blink_count = blink_count
        self.risk_score = risk_score
        self.multiple_faces = multiple_faces
        self.flags = flags if flags is not None else {}

    def to_dict(self):
        """Converts the detector state into a dictionary matching the required API format."""
        return {
            "face_count": self.face_count,
            "looking_away": self.looking_away,
            "head_yaw": self.head_yaw,
            "head_pitch": self.head_pitch,
            "gaze_yaw": self.gaze_yaw,
            "gaze_pitch": self.gaze_pitch,
            "blink_count": self.blink_count,
            "risk_score": self.risk_score,
            "multiple_faces": self.multiple_faces,
            "flags": self.flags
        }
