# models/exam_session.py

class ExamSessionState:
    """Represents the data structure and stats counters of an exam proctoring session."""
    def __init__(self, student_info=None):
        self.student_info = student_info if student_info is not None else {}
        self.local_session_id = None
        self.server_session_id = None
        self.session_report = None
        self.session_start_time = 0.0
        
        # Cumulative stats
        self.gaze_away_count = 0
        self.head_turn_count = 0
        self.no_face_count = 0
        self.multi_face_count = 0
        self.alarm_history_list = []
