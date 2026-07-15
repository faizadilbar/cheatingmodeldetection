# services/database_service.py

import sqlite3

def init_local_database():
    """Create local database for backup"""
    conn = sqlite3.connect('proctoring_data.db')
    cursor = conn.cursor()
    
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS exam_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT UNIQUE,
            student_id TEXT,
            student_name TEXT,
            course_name TEXT,
            quiz_code TEXT,
            exam_date TEXT,
            start_time TEXT,
            end_time TEXT,
            avg_risk_score REAL,
            max_risk_score REAL,
            total_blinks INTEGER,
            gaze_away_count INTEGER,
            head_turn_count INTEGER,
            no_face_count INTEGER,
            multiple_face_count INTEGER,
            cheating_status TEXT,
            alarm_triggered INTEGER DEFAULT 0,
            alarm_count INTEGER DEFAULT 0,
            total_count INTEGER DEFAULT 0,
            alarm_history TEXT,
            report_path TEXT,
            server_session_id TEXT,
            baseline_yaw REAL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS risk_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT,
            risk_score REAL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS violations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT,
            violation_type TEXT,
            severity TEXT,
            risk_score REAL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    # Run migrations for existing databases that don't have the new columns
    try:
        cursor.execute("SELECT server_session_id FROM exam_sessions LIMIT 1")
    except sqlite3.OperationalError:
        try:
            cursor.execute("ALTER TABLE exam_sessions ADD COLUMN server_session_id TEXT")
            conn.commit()
            print("[INFO] Migration: Added server_session_id column to exam_sessions")
        except Exception as e:
            print(f"[ERROR] Migration failed for server_session_id: {e}")
            
    try:
        cursor.execute("SELECT baseline_yaw FROM exam_sessions LIMIT 1")
    except sqlite3.OperationalError:
        try:
            cursor.execute("ALTER TABLE exam_sessions ADD COLUMN baseline_yaw REAL")
            conn.commit()
            print("[INFO] Migration: Added baseline_yaw column to exam_sessions")
        except Exception as e:
            print(f"[ERROR] Migration failed for baseline_yaw: {e}")
            
    try:
        cursor.execute("SELECT total_count FROM exam_sessions LIMIT 1")
    except sqlite3.OperationalError:
        try:
            cursor.execute("ALTER TABLE exam_sessions ADD COLUMN total_count INTEGER DEFAULT 0")
            conn.commit()
            print("[INFO] Migration: Added total_count column to exam_sessions")
        except Exception as e:
            print(f"[ERROR] Migration failed for total_count: {e}")
            
    conn.commit()
    conn.close()
    print("[INFO] Local database initialized")

def save_session_local(session_data):
    """Save session to local database"""
    try:
        conn = sqlite3.connect('proctoring_data.db')
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT OR REPLACE INTO exam_sessions 
            (session_id, student_id, student_name, course_name, quiz_code, 
             exam_date, start_time, end_time, avg_risk_score, max_risk_score,
             total_blinks, gaze_away_count, head_turn_count, no_face_count,
             multiple_face_count, cheating_status, alarm_triggered, alarm_count, total_count, alarm_history, report_path, server_session_id, baseline_yaw)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', (
            session_data.get('session_id'),
            session_data.get('student_id'),
            session_data.get('student_name'),
            session_data.get('course_name'),
            session_data.get('quiz_code'),
            session_data.get('exam_date'),
            session_data.get('start_time'),
            session_data.get('end_time'),
            session_data.get('avg_risk_score'),
            session_data.get('max_risk_score'),
            session_data.get('total_blinks'),
            session_data.get('gaze_away_count'),
            session_data.get('head_turn_count'),
            session_data.get('no_face_count'),
            session_data.get('multiple_face_count'),
            session_data.get('cheating_status'),
            session_data.get('alarm_triggered', 0),
            session_data.get('alarm_count', 0),
            session_data.get('total_count', 0),
            session_data.get('alarm_history'),
            session_data.get('report_path'),
            session_data.get('server_session_id'),
            session_data.get('baseline_yaw')
        ))
        
        conn.commit()
        conn.close()
    except Exception as e:
        print(f"[ERROR] Local save failed: {e}")

def save_risk_local(session_id, risk_score):
    """Save risk score to local database"""
    try:
        conn = sqlite3.connect('proctoring_data.db')
        cursor = conn.cursor()
        cursor.execute('INSERT INTO risk_history (session_id, risk_score) VALUES (?, ?)', 
                      (session_id, risk_score))
        conn.commit()
        conn.close()
    except:
        pass

def save_violation_local(session_id, violation_type, severity, risk_score):
    """Save violation to local database"""
    try:
        conn = sqlite3.connect('proctoring_data.db')
        cursor = conn.cursor()
        cursor.execute('''INSERT INTO violations (session_id, violation_type, severity, risk_score) 
                         VALUES (?, ?, ?, ?)''', (session_id, violation_type, severity, risk_score))
        conn.commit()
        conn.close()
    except:
        pass

def get_session_local(session_id):
    """Retrieve session from local database for recovery"""
    try:
        conn = sqlite3.connect('proctoring_data.db')
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute('SELECT * FROM exam_sessions WHERE session_id = ?', (session_id,))
        row = cursor.fetchone()
        conn.close()
        if row:
            return dict(row)
        return None
    except Exception as e:
        print(f"[ERROR] Local fetch failed: {e}")
        return None

