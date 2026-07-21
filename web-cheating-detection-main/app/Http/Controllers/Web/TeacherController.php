<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TeacherController extends Controller
{
    private string $api;
    private array  $curlOpts;

    public function __construct()
    {
        $this->api = 'https://bgnuf22eight.com/Exam-app/exam-evaluation-app/public/api';
        // Use direct IP + CURLOPT_RESOLVE to bypass DNS lookup (prevents cURL timeout)
        $this->curlOpts = [
            'force_ip_resolve' => 'v4',
            'verify'           => false,
            'curl'             => [CURLOPT_RESOLVE => ['bgnuf22eight.com:443:159.198.67.59']],
        ];
    }

    // ── DASHBOARD: GET /api/teacher-courses/{teacherId}
    // Flutter: TeacherService.fetchTeacherCourses -> returns raw array
    // Backend: CourseController.teacherCourses -> returns json($courses) as plain array
    public function dashboard()
    {
        $teacher = session('user');
        try {
            $res = Http::withOptions($this->curlOpts)->timeout(12)->withHeaders([
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->get("{$this->api}/teacher-courses/{$teacher['id']}");

            $raw = $res->json();
        } catch (\Exception $e) {
            \Log::warning('dashboard API error/timeout: ' . $e->getMessage());
            $raw = [];
        }

        // Backend returns plain array or wrapped in 'courses' key — handle both
        if (is_array($raw) && isset($raw[0])) {
            $courses = $raw;                       // plain array
        } else {
            $courses = $raw['courses'] ?? $raw['data'] ?? [];
        }

        // Calculate total students (same as Flutter: sum totalStudents per course)
        $totalStudents = array_sum(array_column($courses, 'total_students'));

        return view('teacher.dashboard', compact('teacher', 'courses', 'totalStudents'));
    }

    // ── COURSE QUIZZES: GET /api/course-quizzes/{teacherId}/{courseId}
    // Flutter: TeacherService.fetchCourseQuizzes -> data['quizzes']
    public function courseQuizzes(int $courseId)
    {
        $teacher = session('user');
        try {
            $res = Http::withOptions($this->curlOpts)->timeout(12)->withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->get("{$this->api}/course-quizzes/{$teacher['id']}/{$courseId}");

            $data    = $res->json();
            $quizzes = $data['quizzes'] ?? $data['data'] ?? [];
        } catch (\Exception $e) {
            \Log::warning('courseQuizzes API error/timeout: ' . $e->getMessage());
            $quizzes = [];
        }

        return view('teacher.course-quizzes', compact('quizzes', 'courseId', 'teacher'));
    }

    // ── CREATE QUIZ VIEW
    public function createQuiz(int $courseId)
    {
        $teacher = session('user');
        return view('teacher.create-quiz', compact('courseId', 'teacher'));
    }

    // ── PAST QUIZZES: GET /teacher/past-quizzes?course_id=X
    // Returns ALL past quizzes for this teacher+course (filtering done client-side)
    public function pastQuizzes(Request $request)
    {
        $teacher  = session('user');
        $courseId = $request->input('course_id');

        try {
            $res = Http::withOptions($this->curlOpts)
                ->timeout(8)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->get("{$this->api}/course-quizzes/{$teacher['id']}/{$courseId}");

            $data    = $res->json();
            $quizzes = $data['quizzes'] ?? $data['data'] ?? [];
        } catch (\Exception $e) {
            // Network/DNS issue — return empty list gracefully
            \Log::warning('pastQuizzes API timeout: ' . $e->getMessage());
            $quizzes = [];
        }

        return response()->json(['status' => true, 'quizzes' => array_values((array)$quizzes)]);
    }

    // ── PAST QUIZ QUESTIONS: GET /teacher/past-quiz/{code}/questions
    // Flutter uses: GET /api/quiz/teacher/{code}  → getQuizByCodeForTeacher
    public function pastQuizQuestions(string $code)
    {
        try {
            $res = Http::withOptions($this->curlOpts)
                ->timeout(8)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->get("{$this->api}/quiz/teacher/{$code}");

            $data = $res->json();
        } catch (\Exception $e) {
            \Log::warning('pastQuizQuestions API timeout: ' . $e->getMessage());
            $data = ['status' => false, 'questions' => [], 'message' => 'Could not load questions'];
        }

        // Normalize: JS expects { questions: [...] }
        return response()->json($data);
    }

    // ── GENERATE AI: POST /api/chatbot
    // Flutter payload: {topic, difficulty, is_poll, categories:{mcqs, short_questions, fill_blanks}}
    // Returns: {status:true, job_id:"..."}
    public function generateAI(Request $request)
    {
        $res = Http::withOptions($this->curlOpts)->timeout(120)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->post("{$this->api}/chatbot", [
            'topic'      => $request->topic,
            'difficulty' => $request->difficulty ?? 'medium',
            'is_poll'    => (bool) $request->is_poll,
            'categories' => $request->categories,
        ]);
        return response()->json($res->json());
    }

    // ── GENERATION STATUS: GET /api/generation-status/{jobId}
    // Flutter polls: data['status'] == true && data['data']['status'] == 'completed'
    public function generationStatus(string $jobId)
    {
        $res = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->get("{$this->api}/generation-status/{$jobId}");
        return response()->json($res->json());
    }

    // ── SAVE QUIZ: POST /api/save-quiz
    // Flutter payload: {quiz_name, quiz_code, teacher_id, course_id, quiz_date,
    //                   start_time, end_time, duration, total_questions, total_marks,
    //                   difficulty, is_poll, description, questions:[...]}
    public function saveQuiz(Request $request)
    {
        $teacher = session('user');
        $payload = $request->all();
        $payload['teacher_id'] = $teacher['id'];

        $res = Http::withOptions($this->curlOpts)->timeout(120)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->post("{$this->api}/save-quiz", $payload);

        return response()->json($res->json());
    }

    // ── MONITOR QUIZ: GET /api/quiz-attempts/{quizCode}
    // Flutter: data['status']==true, data['attempts'], data['quiz_name'], data['course_id']
    public function monitorQuiz(string $code)
    {
        $teacher = session('user');
        $res     = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->get("{$this->api}/quiz-attempts/{$code}");

        $data     = $res->json();
        $quizName = $data['quiz_name'] ?? $code;
        $attempts = $data['attempts']  ?? [];
        $courseId = $data['course_id'] ?? null;

        return view('teacher.monitor-quiz', compact('code', 'quizName', 'attempts', 'courseId', 'teacher'));
    }

    // ── MONITOR JSON POLL: GET /api/quiz-attempts/{quizCode} → JSON only
    // Called by JS polling every N seconds without page reload
    public function monitorAttemptsJson(string $code)
    {
        $res      = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->get("{$this->api}/quiz-attempts/{$code}");

        $data     = $res->json();
        $attempts = $data['attempts'] ?? [];

        // Fetch proctoring sessions from backend & consolidate duplicates
        $reportApi = 'https://bgnuf22eight.com/cheating/proctoring-backend/public/api';
        $pSessions = [];
        try {
            $pRes = Http::withOptions($this->curlOpts)->timeout(12)->withHeaders([
                'Accept' => 'application/json'
            ])->get("{$reportApi}/exam-sessions");
            $pData = $pRes->json();
            $rawPSessions = $pData['data'] ?? $pData['sessions'] ?? $pData ?? [];
            if (is_array($rawPSessions) && !empty($rawPSessions)) {
                $pSessions = (new \App\Http\Controllers\Web\ProctorController)->consolidateSessions($rawPSessions);
            }
        } catch (\Exception $e) {
            \Log::warning('monitorAttemptsJson proctor sessions fetch failed: ' . $e->getMessage());
        }

        // Fetch live metrics from model API as fallback for active sessions
        $modelApi = env('MODEL_API_URL', 'https://web-production-3a1d7.up.railway.app');
        $liveMetrics = null;
        try {
            $mRes = Http::timeout(5)->get("{$modelApi}/metrics");
            if ($mRes->ok()) {
                $liveMetrics = $mRes->json();
            }
        } catch (\Exception $e) {
            // silent fallback
        }

        // Map proctoring sessions to student attempts
        if (is_array($attempts)) {
            foreach ($attempts as &$attempt) {
                $attemptStudentId = (string)($attempt['student_id'] ?? '');
                $attemptRollNo    = (string)($attempt['student_identifier'] ?? '');
                $attemptName      = (string)($attempt['student_name'] ?? '');
                $matched          = null;

                if (is_array($pSessions)) {
                    foreach ($pSessions as $ps) {
                        $psQuizCode = (string)($ps['quiz_code'] ?? '');
                        $psStudentId = (string)($ps['student_id'] ?? '');
                        $psStudentName = (string)($ps['student_name'] ?? '');

                        $quizMatch = ($psQuizCode === '' || strcasecmp($psQuizCode, $code) === 0);
                        if ($quizMatch) {
                            if (
                                ($attemptStudentId !== '' && strcasecmp($psStudentId, $attemptStudentId) === 0) || 
                                ($attemptRollNo !== '' && strcasecmp($psStudentId, $attemptRollNo) === 0) ||
                                ($attemptName !== '' && $psStudentName !== '' && strcasecmp($psStudentName, $attemptName) === 0)
                            ) {
                                $matched = $ps;
                                break;
                            }
                        }
                    }
                }

                if ($matched) {
                    $gazeAway = (int)($matched['gaze_away_count'] ?? 0);
                    $headTurn = (int)($matched['head_turn_count'] ?? 0);
                    $noFace   = (int)($matched['no_face_count'] ?? 0);
                    $multiFace= (int)($matched['multiple_face_count'] ?? 0);
                    $alarms   = (int)($matched['alarm_count'] ?? $matched['total_alarms'] ?? ($gazeAway + $headTurn + $noFace + $multiFace));

                    $attempt['proctor_session'] = [
                        'id'                  => $matched['id'] ?? null,
                        'session_id'          => $matched['session_id'] ?? null,
                        'risk_score'          => (float)($matched['risk_score'] ?? $matched['avg_risk_score'] ?? $matched['max_risk_score'] ?? 0),
                        'max_risk_score'      => (float)($matched['max_risk_score'] ?? 0),
                        'alarm_level'         => $matched['alarm_level'] ?? 'none',
                        'alarm_count'         => $alarms,
                        'gaze_away_count'     => $gazeAway,
                        'head_turn_count'     => $headTurn,
                        'no_face_count'       => $noFace,
                        'multiple_face_count' => $multiFace,
                        'blink_count'         => (int)($matched['blink_count'] ?? $matched['total_blinks'] ?? 0),
                        'status'              => $matched['status'] ?? 'active',
                    ];
                } elseif (!empty($attempt['is_active']) && is_array($liveMetrics)) {
                    // Fallback to live metrics for active student session
                    $gazeAway = (int)($liveMetrics['gaze_away_count'] ?? 0);
                    $headTurn = (int)($liveMetrics['head_turn_count'] ?? 0);
                    $noFace   = (int)($liveMetrics['no_face_count'] ?? 0);
                    $multiFace= (int)($liveMetrics['multiple_face_count'] ?? 0);
                    $alarms   = (int)($liveMetrics['alarm_count'] ?? $liveMetrics['total_alarms'] ?? ($gazeAway + $headTurn + $noFace + $multiFace));

                    $attempt['proctor_session'] = [
                        'id'                  => null,
                        'session_id'          => 'live_session',
                        'risk_score'          => (float)($liveMetrics['risk_score'] ?? $liveMetrics['avg_risk_score'] ?? $liveMetrics['max_risk'] ?? 0),
                        'max_risk_score'      => (float)($liveMetrics['max_risk_score'] ?? $liveMetrics['max_risk'] ?? 0),
                        'alarm_level'         => $liveMetrics['alarm_level'] ?? 'none',
                        'alarm_count'         => $alarms,
                        'gaze_away_count'     => $gazeAway,
                        'head_turn_count'     => $headTurn,
                        'no_face_count'       => $noFace,
                        'multiple_face_count' => $multiFace,
                        'blink_count'         => (int)($liveMetrics['blink_count'] ?? $liveMetrics['total_blinks'] ?? 0),
                        'status'              => 'active',
                    ];
                } else {
                    $attempt['proctor_session'] = null;
                }
            }
        }

        return response()->json([
            'status'   => true,
            'attempts' => $attempts,
            'quiz_name'=> $data['quiz_name'] ?? $code,
        ]);
    }

    // ── UNLOCK ATTEMPT: POST /api/quiz-attempt/unlock
    // Flutter payload: {attempt_id: int}
    public function unlockAttempt(Request $request)
    {
        $request->validate(['attempt_id' => 'required|integer']);
        $res = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->post("{$this->api}/quiz-attempt/unlock", [
            'attempt_id' => (int) $request->attempt_id,
        ]);
        return response()->json($res->json());
    }

    // ── VIEW QUIZ: GET /api/quiz/teacher/{code}
    // Flutter: data['status']==true, FullQuiz.fromJson(data)
    public function viewQuiz(string $code)
    {
        $teacher = session('user');
        $res  = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->get("{$this->api}/quiz/teacher/{$code}");

        $quiz = $res->json();
        return view('teacher.view-quiz', compact('quiz', 'code', 'teacher'));
    }
}
