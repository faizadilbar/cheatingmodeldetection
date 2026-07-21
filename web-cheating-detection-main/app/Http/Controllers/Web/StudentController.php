<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StudentController extends Controller
{
    private string $api;
    private array  $curlOpts;

    private array $jsonHeaders = [
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ];

    public function __construct()
    {
        $this->api = 'https://bgnuf22eight.com/Exam-app/exam-evaluation-app/public/api';
        $this->curlOpts = [
            'force_ip_resolve' => 'v4',
            'verify'           => false,
            'curl'             => [CURLOPT_RESOLVE => ['bgnuf22eight.com:443:159.198.67.59']],
        ];
    }

    // ── DASHBOARD: GET /api/my-courses/{rollno}
    // Flutter: StudentService.fetchMyCourses -> data['courses'] ?? data['data']
    public function dashboard()
    {
        $student = session('user');
        $rollno  = $student['rollno'] ?? null;

        $courses = [];
        if ($rollno) {
            try {
                $res     = Http::withOptions($this->curlOpts)->timeout(20)->withHeaders($this->jsonHeaders)
                               ->get("{$this->api}/my-courses/{$rollno}");
                $data    = $res->json();
                $courses = $data['courses'] ?? $data['data'] ?? [];
            } catch (\Exception $e) {
                \Log::warning('StudentController::dashboard error: ' . $e->getMessage());
            }
        }

        return view('student.dashboard', compact('student', 'courses'));
    }

    // ── COURSE DETAIL: GET /api/my-courses/{rollno}
    public function courseDetail(int $courseId)
    {
        $student = session('user');
        $rollno  = $student['rollno'] ?? null;
        $course  = ['id' => $courseId, 'course_title' => 'Course', 'course_code' => ''];

        if ($rollno) {
            try {
                $res  = Http::withOptions($this->curlOpts)->timeout(20)->withHeaders($this->jsonHeaders)
                            ->get("{$this->api}/my-courses/{$rollno}");
                $data = $res->json();
                $all  = $data['courses'] ?? $data['data'] ?? [];
                foreach ($all as $c) {
                    if ((int)($c['id'] ?? 0) === $courseId) {
                        $course = $c;
                        break;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('StudentController::courseDetail error: ' . $e->getMessage());
            }
        }

        return view('student.course-detail', compact('student', 'course', 'courseId'));
    }

    // ── ENTER QUIZ PAGE
    public function enterQuiz(Request $request)
    {
        $student = session('user');

        // Always clear stale lock when student visits this page.
        // The banner has served its purpose — student is now choosing to enter a new code.
        // The actual backend lock still exists and will block them if they try the same quiz.
        session()->forget(['locked', 'locked_quiz_id', 'locked_course_id']);

        return view('student.enter-quiz', compact('student'));
    }

    // ── CONFIRM QUIZ: POST /student/quiz/confirm
    // Step 1: Validate code, fetch quiz info, show Exam Ticket — NO backend attempt created yet.
    public function confirmQuiz(Request $request)
    {
        $request->validate(['quiz_code' => 'required|string|min:4']);
        $student  = session('user');
        $quizCode = strtoupper(trim($request->quiz_code));

        // Clear any old stale lock sessions immediately upon checking a new code
        session()->forget(['locked', 'locked_quiz_id', 'locked_course_id']);

        // 1. Fetch quiz details (no attempt created)
        try {
            $quizDetailRes = Http::withOptions($this->curlOpts)->timeout(20)->withHeaders($this->jsonHeaders)
                                 ->get("{$this->api}/quiz/{$quizCode}");
        } catch (\Exception $e) {
            \Log::warning('StudentController::confirmQuiz cURL error: ' . $e->getMessage());
            // Retry with verify false fallback
            try {
                $quizDetailRes = Http::withOptions(['verify' => false])->timeout(20)->withHeaders($this->jsonHeaders)
                                     ->get("{$this->api}/quiz/{$quizCode}");
            } catch (\Exception $e2) {
                return redirect()->back()
                    ->withErrors(['quiz_code' => 'Could not reach the server. Please try again.'])
                    ->withInput();
            }
        }

        if (!$quizDetailRes->ok()) {
            return redirect()->back()
                ->withErrors(['quiz_code' => 'Could not reach the server. Please try again.'])
                ->withInput();
        }

        $quizDetail = $quizDetailRes->json();

        if (!($quizDetail['status'] ?? false)) {
            $msg = $quizDetail['message'] ?? 'Quiz not found. Please check your code.';
            return redirect()->back()->withErrors(['quiz_code' => $msg])->withInput();
        }

        $quizId = $quizDetail['quiz_id'] ?? $quizDetail['id'] ?? null;

        // 2. Course pre-validation using course_id from quiz API (no attempt created here)
        // NOTE: GET /quiz/{code} does NOT return course_id in its response.
        // So we call POST /quiz-attempt to get full quiz info INCLUDING course_id,
        // but we do NOT want to create an attempt. 
        // Solution: call GET /api/my-courses/{rollno} to get the student's enrolled courses,
        // then find which course this quiz belongs to by looking up the quiz's course_id
        // via a safe GET /api/exam-quiz/{quizId}/{studentId} endpoint.
        //
        // ACTUALLY: We validate using course_id sent in the request vs course_id from exam-quiz.
        // exam-quiz is a pure GET - confirmed it does NOT create any attempt.

        $requestedCourseId   = (int) $request->input('course_id', 0);
        $requestedCourseName = trim($request->input('course_name', ''));

        // Fetch student's enrolled courses to resolve course names
        $rollno     = $student['rollno'] ?? '';
        $allCourses = [];
        if ($rollno) {
            try {
                $coursesRes  = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders($this->jsonHeaders)
                                   ->get("{$this->api}/my-courses/{$rollno}");
                $coursesData = $coursesRes->ok() ? ($coursesRes->json() ?? []) : [];
                $allCourses  = $coursesData['courses'] ?? $coursesData['data'] ?? [];
            } catch (\Exception $e) {
                \Log::warning('StudentController::confirmQuiz my-courses error: ' . $e->getMessage());
            }
        }

        // Build a map: course_id (int) => course_title
        $courseMap = [];
        foreach ($allCourses as $c) {
            $cid = (int)($c['id'] ?? 0);
            if ($cid > 0) {
                $courseMap[$cid] = $c['course_title'] ?? $c['course_name'] ?? $c['name'] ?? '';
            }
        }

        // Get the quiz's actual course_id from exam-quiz (pure GET, confirmed no side effects)
        $quizActualCourseId = 0;
        $quizCourseName     = '';
        $examData           = [];
        if ($quizId) {
            try {
                $examRes = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders($this->jsonHeaders)
                               ->get("{$this->api}/exam-quiz/{$quizId}/{$student['id']}");
                if ($examRes->ok()) {
                    $examData = $examRes->json() ?? [];
                    // exam-quiz returns the course the quiz belongs to
                    $quizCourseName = trim($examData['course_name'] ?? '');
                    // Match this course name against our courseMap to find the course_id
                    foreach ($courseMap as $cid => $cname) {
                        if ($cname && strcasecmp($cname, $quizCourseName) === 0) {
                            $quizActualCourseId = $cid;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('StudentController::confirmQuiz exam-quiz error: ' . $e->getMessage());
            }
        }

        // Block if the student is browsing a specific course that doesn't match the quiz's course
        if ($requestedCourseId > 0 && $quizActualCourseId > 0 && $requestedCourseId !== $quizActualCourseId) {
            $belongsTo = $quizCourseName ?: 'a different course';
            return redirect()->back()
                ->withErrors(['quiz_code' =>
                    "This quiz does not belong to \"{$requestedCourseName}\". "
                    . "It belongs to \"{$belongsTo}\". "
                    . "Please go to that course and enter the code there."
                ])
                ->withInput();
        }

        // Resolve display course name
        // If we know the quiz's course name from exam-quiz, use it; else fall back to requested
        $displayCourseName = $quizCourseName ?: ($courseMap[$requestedCourseId] ?? $requestedCourseName);

        // 3. Build ticket data — no attempt created yet, only on Start Exam
        $ticket = [
            'quiz_id'         => $quizId,
            'quiz_code'       => $quizDetail['quiz_code']      ?? $quizCode,
            'quiz_name'       => $quizDetail['quiz_name']       ?? '',
            'quiz_date'       => $quizDetail['quiz_date']       ?? '',
            'start_time'      => $quizDetail['start_time']      ?? '',
            'end_time'        => $quizDetail['end_time']        ?? '',
            'total_questions' => $quizDetail['total_questions'] ?? count($quizDetail['questions'] ?? []),
            'total_marks'     => $quizDetail['total_marks']     ?? 0,
            'is_poll'         => $quizDetail['is_poll']         ?? false,
            'course_id'       => $quizActualCourseId ?: $requestedCourseId,
            'course_name'     => $displayCourseName,
            'student_name'    => $examData['user_name'] ?? $student['name']   ?? 'Student',
            'roll_no'         => $examData['roll_no']   ?? $student['rollno'] ?? '',
        ];

        return view('student.confirm-quiz', compact('student', 'ticket'));
    }

    // ── START QUIZ: POST /api/quiz-attempt
    // Step 2: Called when student clicks "Start Exam Now" on the ticket.
    // Only NOW does the backend attempt get created.
    public function startQuiz(Request $request)
    {
        $request->validate(['quiz_code' => 'required|string']);
        $student  = session('user');
        $quizCode = strtoupper(trim($request->quiz_code));

        // Clear lock session variables to prevent carrying over stale warnings
        session()->forget(['locked', 'locked_quiz_id', 'locked_course_id']);

        \Log::info("startQuiz triggered", [
            'student_id' => $student['id'] ?? null,
            'quiz_code' => $quizCode,
            'course_id' => $request->input('course_id')
        ]);

        try {
            $res = Http::withOptions($this->curlOpts)->timeout(120)->withHeaders($this->jsonHeaders)
                       ->post("{$this->api}/quiz-attempt", [
                           'quiz_code'  => $quizCode,
                           'student_id' => (int) $student['id'],
                       ]);
            $data = $res->json() ?? [];
        } catch (\Exception $e) {
            \Log::error("startQuiz API exception: " . $e->getMessage());
            return redirect()->route('student.quiz.enter')
                             ->withErrors(['quiz_code' => 'Server response timed out. Please try again.']);
        }

        \Log::info("startQuiz API Response", [
            'status' => $res->status(),
            'body' => $data
        ]);

        $hasQuestions = !empty($data['questions'])
            || isset($data['quiz'])
            || !empty($data['mcqs'])
            || isset($data['quiz_name']);

        if ($hasQuestions) {
            session(['current_quiz' => $data]);
            $quizId = $data['id'] ?? $data['quiz_id'] ?? ($data['quiz']['id'] ?? null);

            if ($quizId) {
                session()->forget('quiz_attempt_status_' . $quizId);
            }

            // Enrich with exam-quiz info (student name, roll no, course)
            if ($quizId) {
                try {
                    $examRes  = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders($this->jsonHeaders)
                                    ->get("{$this->api}/exam-quiz/{$quizId}/{$student['id']}");
                    $examData = $examRes->json();
                    if (!empty($examData)) {
                        $data = array_merge($data, $examData);
                        session(['current_quiz' => $data]);
                    }
                } catch (\Exception $e) {
                    \Log::warning("startQuiz exam-quiz error: " . $e->getMessage());
                }
            }

            return redirect()->route('student.quiz.take', ['quizId' => $quizId ?? 0]);
        }


        $msg = $data['message'] ?? 'Quiz is not available right now. Please try again later.';

        // Check if locked
        if (str_contains(strtolower($msg), 'locked') || str_contains(strtolower($msg), 'abandoned')) {
            // Store which quiz/course caused the lock so we can detect stale locks later
            $lockedQuizCode  = $request->quiz_code ?? null;
            $lockedCourseId  = (int) $request->input('course_id', 0);
            session(['locked_quiz_id'   => $lockedQuizCode,
                     'locked_course_id' => $lockedCourseId]);
            return redirect()->route('student.quiz.enter')->with('locked', $msg);
        }

        return redirect()->route('student.quiz.enter')
                         ->withErrors(['quiz_code' => $msg]);
    }

    // ── TAKE QUIZ PAGE: GET /api/exam-quiz/{quizId}/{studentId}
    public function takeQuiz(int $quizId)
    {
        $student   = session('user');
        $quizData  = session('current_quiz', []);

        // If no session data, force student to go through code entry page
        if (empty($quizData) || (($quizData['id'] ?? $quizData['quiz_id'] ?? null) != $quizId)) {
            return redirect()->route('student.quiz.enter')
                             ->withErrors(['quiz_code' => 'Please enter the quiz code to start.']);
        }

        // Verify if attempt is currently locked in session
        if (session('quiz_attempt_status_' . $quizId) === 'locked') {
            return redirect()->route('student.quiz.enter')
                             ->withErrors(['quiz_code' => 'Re-entry Blocked! You left the quiz or switched tabs. Please ask your teacher to unlock your attempt.']);
        }

        // ── Calculate Target End Time (Only once per attempt to prevent resets on reload) ──
        $targetEndTimeKey = 'target_end_time_' . $quizId;
        $targetEndTimeIso = session($targetEndTimeKey);

        if (!$targetEndTimeIso) {
            $loadedAtStr = $quizData['attempt']['loaded_at'] ?? now('Asia/Karachi')->toDateTimeString();
            $loadedAt = \Carbon\Carbon::parse($loadedAtStr, 'Asia/Karachi');

            // Calculate quiz duration dynamically using start_time and end_time (Karachi timezone)
            $quizDate  = $quizData['quiz_date'] ?? now('Asia/Karachi')->toDateString();
            $startTime = \Carbon\Carbon::parse($quizDate . ' ' . $quizData['start_time'], 'Asia/Karachi');
            $endTime   = \Carbon\Carbon::parse($quizDate . ' ' . $quizData['end_time'], 'Asia/Karachi');
            
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }
            $duration = $startTime->diffInMinutes($endTime);

            // 1. Duration based limit
            $durationEndTime = $loadedAt->copy()->addMinutes($duration);

            // 2. Hard scheduled end time limit
            $quizScheduledEndTime = $endTime;

            // Target time is the absolute minimum of these two limits
            $targetEndTime = $durationEndTime->lt($quizScheduledEndTime) ? $durationEndTime : $quizScheduledEndTime;
            $targetEndTimeIso = $targetEndTime->toIso8601String();

            session([$targetEndTimeKey => $targetEndTimeIso]);
            session(['quiz_attempt_status_' . $quizId => 'started']);

            // ── Persist end_time for result-detail unlock time display ──
            // This key is intentionally NOT cleared on submit so resultDetail() can read it.
            session(['quiz_end_time_' . $quizId => [
                'end_time'  => $quizData['end_time']  ?? $q['end_time']  ?? '',
                'quiz_date' => $quizData['quiz_date'] ?? $q['quiz_date'] ?? now('Asia/Karachi')->toDateString(),
            ]]);
        }

        // Verify if the calculated time is already expired
        $now = now('Asia/Karachi');
        $targetEndTime = \Carbon\Carbon::parse($targetEndTimeIso, 'Asia/Karachi');
        if ($now->gte($targetEndTime)) {
            session()->forget($targetEndTimeKey);
            session()->forget('quiz_attempt_status_' . $quizId);
            session()->forget('current_quiz');
            return redirect()->route('student.quiz.enter')
                             ->withErrors(['quiz_code' => 'Quiz time has expired.']);
        }

        // Build FullQuiz from response (same as Flutter FullQuiz.fromJson)
        $q = $quizData['quiz'] ?? $quizData;

        $rawQuestions = [];
        if (!empty($q['questions'])) {
            $rawQuestions = $q['questions'];
        } elseif (!empty($quizData['questions'])) {
            $rawQuestions = $quizData['questions'];
        } elseif (!empty($q['mcqs']) || !empty($q['short_questions']) || !empty($q['fill_in_the_blank'])) {
            $rawQuestions = array_merge(
                $q['mcqs']             ?? [],
                $q['short_questions']  ?? [],
                $q['fill_in_the_blank'] ?? []
            );
        } elseif (!empty($quizData['mcqs']) || !empty($quizData['short_questions'])) {
            $rawQuestions = array_merge(
                $quizData['mcqs']             ?? [],
                $quizData['short_questions']  ?? [],
                $quizData['fill_in_the_blank'] ?? []
            );
        }

        // Normalize question types (same as QuizQuestion._normalizeType)
        $rawQuestions = array_map(function($q) {
            $type = $q['type'] ?? 'mcq';
            if ($type === 'mcqs')             $type = 'mcq';
            if ($type === 'short_questions')  $type = 'short';
            if ($type === 'fill_blanks')      $type = 'fill';
            $q['type'] = $type;
            return $q;
        }, $rawQuestions);

        $quiz = [
            'quiz_id'         => $quizData['id'] ?? $q['id'] ?? $q['quiz_id'] ?? $quizId,
            'quiz_code'       => $q['quiz_code'] ?? $quizData['quiz_code'] ?? '',
            'quiz_name'       => $q['quiz_name'] ?? $quizData['quiz_name'] ?? '',
            'quiz_date'       => $q['quiz_date'] ?? $quizData['quiz_date'] ?? '',
            'start_time'      => $q['start_time'] ?? $quizData['start_time'] ?? '',
            'end_time'        => $q['end_time'] ?? $quizData['end_time'] ?? '',
            'duration'        => $q['duration'] ?? $quizData['duration'] ?? 30,
            'total_marks'     => $q['total_marks'] ?? $quizData['total_marks'] ?? count($rawQuestions),
            'is_poll'         => ($q['is_poll'] ?? $quizData['is_poll'] ?? false) == true,
            'questions'       => $rawQuestions,
            'course_name'     => $quizData['course_name'] ?? $q['course_name'] ?? '',
        ];

        return view('student.take-quiz', [
            'student'          => $student,
            'quiz'             => $quiz,
            'targetEndTimeIso' => $targetEndTimeIso
        ]);
    }

    // ── SUBMIT QUIZ: POST /api/quiz/submit
    // Flutter payload: {quiz_id: int, student_id: int, answers: {"qId": "A", ...}}
    public function submitQuiz(Request $request)
    {
        $student = session('user');

        $res = Http::withOptions($this->curlOpts)->timeout(120)->withHeaders($this->jsonHeaders)
                   ->post("{$this->api}/quiz/submit", [
                       'quiz_id'    => (int) $request->quiz_id,
                       'student_id' => (int) $student['id'],
                       'answers'    => $request->answers ?? [],
                   ]);

        // Clean up timing and attempt status sessions upon successful submission
        // Note: quiz_end_time_{quizId} is intentionally kept for result-detail page
        session()->forget('target_end_time_' . $request->quiz_id);
        session()->forget('quiz_attempt_status_' . $request->quiz_id);
        session()->forget('current_quiz');

        return response()->json($res->json());
    }

    // ── HEARTBEAT: POST /api/quiz-attempt/heartbeat
    // Flutter payload: {quiz_id: int, student_id: int}
    public function heartbeat(Request $request)
    {
        $student = session('user');
        $res = Http::withOptions($this->curlOpts)->timeout(10)->withHeaders($this->jsonHeaders)
                   ->post("{$this->api}/quiz-attempt/heartbeat", [
                       'quiz_id'    => (int) $request->quiz_id,
                       'student_id' => (int) $student['id'],
                   ]);
        return response()->json($res->json());
    }

    // ── TAB SWITCH: POST /api/quiz-attempt/tab-switch
    // Flutter payload: {quiz_id: int, student_id: int}
    public function tabSwitch(Request $request)
    {
        $student = session('user');
        $res = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders($this->jsonHeaders)
                   ->post("{$this->api}/quiz-attempt/tab-switch", [
                       'quiz_id'    => (int) $request->quiz_id,
                       'student_id' => (int) $student['id'],
                   ]);
        
        // Lock the attempt in session
        session(['quiz_attempt_status_' . $request->quiz_id => 'locked']);

        return response()->json($res->json());
    }

    // ── SCREEN CLOSE: POST /api/quiz-attempt/screen-close
    // Flutter payload: {quiz_id: int, student_id: int}
    public function screenClose(Request $request)
    {
        $student = session('user');
        $res = Http::withOptions($this->curlOpts)->timeout(10)->withHeaders($this->jsonHeaders)
                   ->post("{$this->api}/quiz-attempt/screen-close", [
                       'quiz_id'    => (int) $request->quiz_id,
                       'student_id' => (int) $student['id'],
                   ]);

        // Lock the attempt in session
        session(['quiz_attempt_status_' . $request->quiz_id => 'locked']);

        return response()->json($res->json());
    }

    // ── MARK SUBMITTED: POST /api/quiz-attempt/submitted
    // Flutter payload: {quiz_id: int, student_id: int}
    public function markSubmitted(Request $request)
    {
        $student = session('user');
        $res = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders($this->jsonHeaders)
                   ->post("{$this->api}/quiz-attempt/submitted", [
                       'quiz_id'    => (int) $request->quiz_id,
                       'student_id' => (int) $student['id'],
                   ]);
        return response()->json($res->json());
    }

    // ── CLEAR LOCAL LOCK: clears only the session-based lock (not the API lock)
    // Used when student wants to try a different quiz code after a stale lock state
    public function clearLock(Request $request)
    {
        // Clear all quiz-related session data
        $keys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'quiz_attempt_status_') || str_starts_with($k, 'target_end_time_'));
        foreach ($keys as $key) {
            session()->forget($key);
        }
        session()->forget(['current_quiz', 'locked', 'locked_quiz_id', 'locked_course_id']);

        return redirect()->route('student.quiz.enter')
                         ->with('success', 'Session cleared. You can now try entering a quiz code.');
    }


    // ── RESULTS: GET /api/student/results/{studentId}
    // Flutter: fetchStudentResults -> data['results'] ?? data['data']
    public function results(Request $request)
    {
        $student = session('user');
        $res  = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders($this->jsonHeaders)
                    ->get("{$this->api}/student/results/{$student['id']}");
        $data    = $res->json();
        $results = $data['results'] ?? $data['data'] ?? [];

        // Filter by course if ?course_id= is passed (e.g. from course-detail page)
        $filterCourseId   = $request->query('course_id');
        $filterCourseName = null;
        if ($filterCourseId) {
            $results = array_values(array_filter($results, fn($r) => ($r['course_id'] ?? null) == $filterCourseId));
            $filterCourseName = $results[0]['course_name'] ?? null;
        }

        return view('student.results', compact('student', 'results', 'filterCourseId', 'filterCourseName'));
    }

    // ── RESULT DETAIL: GET /api/quiz/result/{quizId}/{studentId}
    public function resultDetail(int $quizId)
    {
        $student = session('user');
        $res     = Http::withOptions($this->curlOpts)->timeout(30)->withHeaders($this->jsonHeaders)
                       ->get("{$this->api}/quiz/result/{$quizId}/{$student['id']}");
        $result  = $res->json();

     // Normalize response keys to support flat properties on the view
        if (($result['status'] ?? false) && isset($result['data'])) {
            $data = $result['data'];
            if (!isset($data['quiz_name'])) {
                $data['quiz_name'] = $result['quiz_name'] ?? '';
            }
            $result = array_merge($result, $data);
        }

        // ── Ensure short_answers is always at top level ──
        // API returns short answers under 'short_answers_detail' key
        if (empty($result['short_answers'])) {
            // ✅ PRIMARY: API uses 'short_answers_detail'
            if (!empty($result['short_answers_detail'])) {
                $result['short_answers'] = $result['short_answers_detail'];
            }
            // Try inside 'result' key
            elseif (!empty($result['result']['short_answers'])) {
                $result['short_answers'] = $result['result']['short_answers'];
            }
            // Try inside 'result.short_answers_detail'
            elseif (!empty($result['result']['short_answers_detail'])) {
                $result['short_answers'] = $result['result']['short_answers_detail'];
            }
            // Try inside 'evaluation' key
            elseif (!empty($result['evaluation'])) {
                $result['short_answers'] = $result['evaluation'];
            }
            // Try 'questions_feedback'
            elseif (!empty($result['questions_feedback'])) {
                $result['short_answers'] = $result['questions_feedback'];
            }
            // Try 'answers' key
            elseif (!empty($result['answers'])) {
                $result['short_answers'] = $result['answers'];
            }
        }

        // Normalize each short_answer entry to have consistent keys
        if (!empty($result['short_answers'])) {
            $result['short_answers'] = array_map(function($sa) {
                return [
                    'question'          => $sa['question']           ?? $sa['question_text'] ?? '',
                    'student_answer'    => $sa['student_answer']     ?? $sa['answer']        ?? $sa['student_response'] ?? '',
                    'keyword_score'     => $sa['keyword_score']      ?? $sa['keyword_marks']  ?? 0,
                    'ai_score'          => $sa['ai_score']           ?? $sa['ai_marks']       ?? $sa['conceptual_score'] ?? 0,
                    'keyword_weight'    => $sa['keyword_weight']     ?? 20,
                    'ai_weight'         => $sa['ai_weight']          ?? 80,
                    'final_score'       => $sa['final_score']        ?? $sa['score']          ?? $sa['percentage']       ?? 0,
                    'feedback'          => $sa['feedback']           ?? $sa['ai_feedback']    ?? $sa['comment']          ?? '',
                    'expected_keywords' => $sa['expected_keywords']  ?? $sa['keywords']       ?? [],
                ];
            }, $result['short_answers']);
        }

        // ── Fix unlock_time: derive from quiz end_time since API may not return it ──
        // Priority: quiz_end_time_ session → result fields → target_end_time session
        if (empty($result['unlock_time'])) {
            $endTime  = null;
            $quizDate = null;

            // 1. Best source: dedicated persistent session key (set when quiz loads, kept after submit)
            $savedEndTime = session('quiz_end_time_' . $quizId);
            if ($savedEndTime) {
                $endTime  = $savedEndTime['end_time']  ?? null;
                $quizDate = $savedEndTime['quiz_date'] ?? null;
            }

            // 2. From API result fields
            if (!$endTime) {
                $endTime  = $result['end_time']  ?? $result['quiz']['end_time']  ?? null;
                $quizDate = $result['quiz_date'] ?? $result['quiz']['quiz_date'] ?? null;
            }

            // 3. From current_quiz session
            if (!$endTime) {
                $sess     = session('current_quiz', []);
                $endTime  = $sess['end_time']  ?? null;
                $quizDate = $quizDate ?? ($sess['quiz_date'] ?? null);
            }

            // 4. From target_end_time session (ISO string)
            if (!$endTime) {
                $iso = session('target_end_time_' . $quizId);
                if ($iso) {
                    $dt       = \Carbon\Carbon::parse($iso, 'Asia/Karachi');
                    $endTime  = $dt->format('H:i:s');
                    $quizDate = $dt->toDateString();
                }
            }

            if ($endTime) {
                $result['unlock_time'] = $endTime;

                // Compute human-readable date label
                if ($quizDate && $quizDate !== 'Today') {
                    try {
                        $dt = \Carbon\Carbon::parse($quizDate, 'Asia/Karachi');
                        $result['unlock_date'] = $dt->isToday() ? 'Today' : $dt->format('d M Y');
                    } catch (\Exception $e) {
                        $result['unlock_date'] = 'Today';
                    }
                } else {
                    $result['unlock_date'] = 'Today';
                }

                // Pre-format the time so blade doesn't need to parse strings
                try {
                    $result['unlock_time_formatted'] = \Carbon\Carbon::parse($endTime)->format('h:i A');
                } catch (\Exception $e) {
                    // If end_time is already like "23:35" or "11:35 PM", clean it
                    $result['unlock_time_formatted'] = $endTime;
                }
            }
        }

        // Pre-format if unlock_time already existed in API response
        if (!isset($result['unlock_time_formatted']) && !empty($result['unlock_time'])) {
            try {
                $result['unlock_time_formatted'] = \Carbon\Carbon::parse($result['unlock_time'])->format('h:i A');
            } catch (\Exception $e) {
                $result['unlock_time_formatted'] = $result['unlock_time'];
            }
        }

        return view('student.result-detail', compact('student', 'result', 'quizId'));
    }
}
