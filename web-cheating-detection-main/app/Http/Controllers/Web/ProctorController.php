<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProctorController extends Controller
{
    /** Python model API base URL */
    private string $modelApi = '';

    public function __construct()
    {
        $this->modelApi = env('MODEL_API_URL', 'https://web-production-3a1d7.up.railway.app');
    }

    /** Backend report storage API base URL */
    private string $reportApi = 'https://bgnuf22eight.com/cheating/proctoring-backend/public/api';

    /** cURL options for the backend report API (same DNS trick as other controllers) */
    private array $curlOpts = [
        'force_ip_resolve' => 'v4',
        'verify'           => false,
    ];

    // ──────────────────────────────────────────────────────────────────────
    // STUDENT ENDPOINTS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * POST /proctor/start
     * Called when student's quiz page loads.
     * Forwards to Python model: POST /start-exam
     */
    public function startExam(Request $request)
    {
        $student = session('user');
        $quiz    = session('current_quiz', []);

        $quizCode   = $request->input('quiz_code', $quiz['quiz_code'] ?? '');
        $quizId     = $request->input('quiz_id',   $quiz['id'] ?? $quiz['quiz_id'] ?? 0);
        $quizDate   = $request->input('quiz_date',  $quiz['quiz_date'] ?? now()->toDateString());
        $startTime  = $request->input('start_time', $quiz['start_time'] ?? '');
        $endTime    = $request->input('end_time',   $quiz['end_time'] ?? '');
        $courseName = $request->input('course_name', $quiz['course_name'] ?? 'Unknown Course');

        $payload = [
            'student_id'   => (string)($student['id'] ?? 'S' . rand(100, 999)),
            'student_name' => $student['name'] ?? 'Student',
            'course_name'  => $courseName,
            'quiz_code'    => $quizCode,
            'quiz_id'      => (string)$quizId,
            'exam_date'    => $quizDate,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
        ];

        try {
            $res = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post("{$this->modelApi}/start-exam", $payload);

            // Store session_id for later use in stop
            $data = $res->json();
            session(['proctor_session_payload' => $payload]);
            session(['proctor_quiz_id'        => $quizId]);

            Log::info('ProctorController::startExam', ['payload' => $payload, 'response' => $data]);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::warning('ProctorController::startExam error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Proctoring service unavailable'], 200);
        }
    }

    /**
     * POST /proctor/frame
     * Receives a base64 JPEG frame from the browser and forwards it
     * to the Python model as multipart form-data.
     */
    public function uploadFrame(Request $request)
    {
        try {
            $frameData = $request->input('frame_b64');
            if (!$frameData) {
                return response()->json(['status' => false, 'message' => 'No frame data'], 400);
            }

            // Decode base64 → binary JPEG
            $b64 = preg_replace('/^data:image\/\w+;base64,/', '', $frameData);
            $binary = base64_decode($b64);

            if (!$binary) {
                return response()->json(['status' => false, 'message' => 'Invalid frame data'], 400);
            }

            $res = Http::timeout(10)
                ->attach('frame', $binary, 'frame.jpg')
                ->post("{$this->modelApi}/upload-frame");

            return response()->json($res->json());
        } catch (\Exception $e) {
            Log::warning('ProctorController::uploadFrame error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Frame upload failed'], 200);
        }
    }

    /**
     * GET /proctor/metrics
     * Proxies the live metrics from the Python model API.
     */
    public function metrics()
    {
        try {
            $res = Http::timeout(8)->get("{$this->modelApi}/metrics");
            $data = $res->json();
            if (is_array($data)) {
                // Normalize keys for frontend consistency
                $riskScore = $data['risk_score'] ?? $data['max_risk_score'] ?? $data['avg_risk_score'] ?? $data['max_risk'] ?? 0;
                $gazeAway  = $data['gaze_away_count'] ?? $data['gaze_away'] ?? $data['gaze'] ?? 0;
                $headTurn  = $data['head_turn_count'] ?? $data['head_turns'] ?? $data['head_turn'] ?? 0;
                $noFace    = $data['no_face_count'] ?? $data['no_face'] ?? $data['no_faces'] ?? 0;
                $multiFace = $data['multiple_face_count'] ?? $data['multi_face_count'] ?? $data['multiple_faces_count'] ?? $data['multi_face'] ?? $data['multiple_faces'] ?? 0;
                $blinks    = $data['blink_count'] ?? $data['total_blinks'] ?? $data['blinks'] ?? $data['blinks_count'] ?? 0;

                $alarms = $data['alarm_count'] ?? $data['total_alarms'] ?? $data['total_count'] ?? ($gazeAway + $headTurn + $noFace + $multiFace);

                $data['risk_score']          = (float)$riskScore;
                $data['alarm_count']         = (int)$alarms;
                $data['total_alarms']        = (int)$alarms;
                $data['gaze_away_count']     = (int)$gazeAway;
                $data['head_turn_count']     = (int)$headTurn;
                $data['no_face_count']       = (int)$noFace;
                $data['multiple_face_count'] = (int)$multiFace;
                $data['multi_face_count']    = (int)$multiFace;
                $data['blink_count']         = (int)$blinks;
                $data['total_blinks']        = (int)$blinks;
            }
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['status' => 'inactive', 'alarm_level' => 'none', 'risk_score' => 0]);
        }
    }

    /**
     * POST /proctor/stop
     * Stops the proctoring session, retrieves the session report from the
     * Python model, and stores it in the backend report API.
     */
    public function stopExam(Request $request)
    {
        $student = session('user');
        $quiz    = session('current_quiz', []);
        $payload = session('proctor_session_payload', []);
        $quizId  = session('proctor_quiz_id', $quiz['id'] ?? $quiz['quiz_id'] ?? 0);

        $result = ['status' => false, 'report_sent' => false, 'message' => ''];

        // 1. Stop the model API session
        try {
            $stopRes  = Http::timeout(15)->post("{$this->modelApi}/stop-exam", []);
            $stopData = $stopRes->json();
            $result['model_stop'] = $stopData;
            Log::info('ProctorController::stopExam model stop', $stopData);
        } catch (\Exception $e) {
            Log::warning('ProctorController::stopExam stop error: ' . $e->getMessage());
        }

        // 2. Fetch all sessions from model API to find our latest session
        $sessionData = [];
        try {
            $sessionsRes = Http::timeout(15)->get("{$this->modelApi}/api/sessions");
            $sessionsData = $sessionsRes->json();
            $sessions = $sessionsData['sessions'] ?? [];

            // The latest session for this student
            $studentId = (string)($student['id'] ?? '');
            foreach (array_reverse($sessions) as $s) {
                if (
                    (string)($s['student_id'] ?? '') === $studentId ||
                    ($s['student_name'] ?? '') === ($student['name'] ?? '')
                ) {
                    $sessionData = $s;
                    break;
                }
            }
            // Fallback: just use the last session
            if (empty($sessionData) && !empty($sessions)) {
                $sessionData = end($sessions);
            }
        } catch (\Exception $e) {
            Log::warning('ProctorController::stopExam fetch sessions error: ' . $e->getMessage());
        }

        $gaze     = (int)($sessionData['gaze_away_count'] ?? 0);
        $head     = (int)($sessionData['head_turn_count'] ?? 0);
        $noFace   = (int)($sessionData['no_face_count'] ?? 0);
        $multi    = (int)($sessionData['multiple_face_count'] ?? 0);
        $blinks   = (int)($sessionData['blink_count'] ?? $sessionData['total_blinks'] ?? 0);

        $rawRisk  = (float)($sessionData['max_risk_score'] ?? $sessionData['risk_score'] ?? $sessionData['avg_risk_score'] ?? 0);
        if ($rawRisk <= 0 && ($gaze > 0 || $head > 0 || $noFace > 0 || $multi > 0)) {
            $rawRisk = min(100, ($gaze * 10) + ($head * 8) + ($noFace * 20) + ($multi * 25));
        }

        $rawLevel = strtolower($sessionData['alarm_level'] ?? 'none');
        if (($rawLevel === 'none' || $rawLevel === '' || $rawLevel === 'calibrating') && $rawRisk > 0) {
            if ($rawRisk >= 75) $rawLevel = 'critical';
            elseif ($rawRisk >= 50) $rawLevel = 'high';
            elseif ($rawRisk >= 25) $rawLevel = 'medium';
            else $rawLevel = 'low';
        }

        $totViolations = (int)($sessionData['total_violations'] ?? $sessionData['total_alarms'] ?? ($gaze + $head + $noFace + $multi));

        // 3. Build report payload for backend API
        $reportPayload = [
            'student_id'          => $payload['student_id']   ?? (string)($student['id'] ?? ''),
            'student_name'        => $payload['student_name']  ?? ($student['name'] ?? 'Student'),
            'course_name'         => $payload['course_name']   ?? ($quiz['course_name'] ?? 'Unknown'),
            'quiz_code'           => $payload['quiz_code']     ?? ($quiz['quiz_code'] ?? ''),
            'quiz_id'             => (string)($payload['quiz_id'] ?? $quizId),
            'exam_date'           => $payload['exam_date']     ?? now()->toDateString(),
            'start_time'          => $payload['start_time']    ?? '',
            'end_time'            => $payload['end_time']      ?? '',
            'risk_score'          => round($rawRisk),
            'alarm_level'         => $rawLevel,
            'total_alarms'        => $totViolations,
            'total_violations'    => $totViolations,
            'gaze_away_count'     => $gaze,
            'head_turn_count'     => $head,
            'no_face_count'       => $noFace,
            'multiple_face_count' => $multi,
            'blink_count'         => $blinks,
            'session_id'          => $sessionData['session_id'] ?? '',
            'raw_session'         => json_encode($sessionData),
        ];

        // 4. POST report to backend API
        try {
            $reportRes = Http::withOptions($this->curlOpts)
                ->timeout(20)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post("{$this->reportApi}/exam-sessions", $reportPayload);

            $reportData = $reportRes->json();
            $result['report_sent'] = $reportRes->ok();
            $result['report_response'] = $reportData;
            $result['status'] = true;

            Log::info('ProctorController::stopExam report sent', $reportData);
        } catch (\Exception $e) {
            Log::error('ProctorController::stopExam report send error: ' . $e->getMessage());
            $result['message'] = 'Report could not be sent: ' . $e->getMessage();
        }

        // 5. Clear proctoring session data
        session()->forget(['proctor_session_payload', 'proctor_quiz_id']);

        return response()->json($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // TEACHER ENDPOINTS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Consolidate duplicate sessions for the same student + quiz_code
     */
    public function consolidateSessions(array $rawSessions): array
    {
        $grouped = [];

        foreach ($rawSessions as $s) {
            $studentKey = trim((string)($s['student_id'] ?? ''));
            if ($studentKey === '') {
                $studentKey = trim((string)($s['student_name'] ?? 'unknown'));
            }
            $quizKey = trim((string)($s['quiz_code'] ?? ''));
            $key = strtolower($studentKey . '_' . $quizKey);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $s;
        }

        $result = [];
        $levelPriority = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1, 'none' => 0, '' => 0];

        foreach ($grouped as $items) {
            // Sort by ID ascending so earliest is first
            usort($items, function ($a, $b) {
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            });

            $first = $items[0];
            $last  = end($items);

            $maxRisk  = 0;
            $maxLevel = 'none';
            $gaze     = 0;
            $head     = 0;
            $noFace   = 0;
            $multi    = 0;
            $blinks   = 0;
            $totV     = 0;

            foreach ($items as $item) {
                $r = (float)($item['risk_score'] ?? $item['max_risk_score'] ?? 0);
                if ($r > $maxRisk) {
                    $maxRisk = $r;
                }

                $lvl = strtolower($item['alarm_level'] ?? 'none');
                if (($levelPriority[$lvl] ?? 0) > ($levelPriority[$maxLevel] ?? 0)) {
                    $maxLevel = $lvl;
                }

                $gaze   += (int)($item['gaze_away_count'] ?? 0);
                $head   += (int)($item['head_turn_count'] ?? 0);
                $noFace += (int)($item['no_face_count'] ?? 0);
                $multi  += (int)($item['multiple_face_count'] ?? 0);
                $blinks += (int)($item['blink_count'] ?? $item['total_blinks'] ?? 0);

                $itemViolations = (int)($item['total_violations'] ?? $item['total_alarms'] ?? (($item['gaze_away_count']??0) + ($item['head_turn_count']??0) + ($item['no_face_count']??0) + ($item['multiple_face_count']??0)));
                $totV += $itemViolations;
            }

            // Consolidated record
            $merged = $last;
            $merged['id']                  = $last['id'] ?? $first['id'];
            $merged['student_id']          = $first['student_id'] ?? $last['student_id'];
            $merged['student_name']        = $first['student_name'] ?? $last['student_name'];
            $merged['course_name']         = $first['course_name'] ?? $last['course_name'];
            $merged['quiz_code']           = $first['quiz_code'] ?? $last['quiz_code'];
            $merged['quiz_id']             = $first['quiz_id'] ?? $last['quiz_id'];
            $merged['exam_date']           = $first['exam_date'] ?? $last['exam_date'];
            $merged['start_time']          = $first['start_time'] ?? $last['start_time'];
            $merged['end_time']            = $last['end_time'] ?? $first['end_time'];
            $merged['risk_score']          = round($maxRisk);
            $merged['alarm_level']         = strtoupper($maxLevel);
            $merged['total_alarms']        = $totV;
            $merged['total_violations']    = $totV;
            $merged['gaze_away_count']     = $gaze;
            $merged['head_turn_count']     = $head;
            $merged['no_face_count']       = $noFace;
            $merged['multiple_face_count'] = $multi;
            $merged['blink_count']         = $blinks;
            $merged['attempt_count']       = count($items);

            $result[] = $merged;
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────
    // TEACHER ENDPOINTS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * GET /teacher/proctor/reports
     * Shows teacher the list of all proctoring session reports (consolidated per student & quiz).
     */
    public function reports(Request $request)
    {
        $teacher  = session('user');
        $rawSessions = [];
        $sessions = [];
        $error    = null;
        $total    = 0;

        try {
            $res  = Http::withOptions($this->curlOpts)
                ->timeout(20)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->reportApi}/exam-sessions");

            $data        = $res->json();
            $rawSessions = $data['data'] ?? $data['sessions'] ?? $data ?? [];

            // Filter by quiz_code if provided before consolidating
            $filterCode = $request->query('quiz_code');
            if ($filterCode) {
                $rawSessions = array_values(array_filter($rawSessions, fn($s) => ($s['quiz_code'] ?? '') === $filterCode));
            }

            // Consolidate duplicate sessions per student + quiz_code
            $sessions = $this->consolidateSessions($rawSessions);
            $total    = count($sessions);
        } catch (\Exception $e) {
            Log::warning('ProctorController::reports error: ' . $e->getMessage());
            $error = 'Could not load reports. Backend API may be unreachable.';
        }

        return view('teacher.proctoring-reports', compact('teacher', 'sessions', 'total', 'error'));
    }

    /**
     * GET /teacher/proctor/reports/{id}
     * Shows detailed report for a single proctoring session (consolidated).
     */
    public function reportDetail(string $id)
    {
        $teacher = session('user');
        $session = [];
        $violations = [];
        $error = null;

        try {
            // Fetch single session from backend API
            $res  = Http::withOptions($this->curlOpts)
                ->timeout(20)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->reportApi}/exam-sessions/{$id}");

            $data    = $res->json();
            $session = $data['data'] ?? $data['session'] ?? $data ?? [];

            // Fetch all sessions to consolidate attempts for this student & quiz_code
            try {
                $allRes  = Http::withOptions($this->curlOpts)
                    ->timeout(15)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get("{$this->reportApi}/exam-sessions");
                $allData = $allRes->json();
                $allList = $allData['data'] ?? $allData['sessions'] ?? $allData ?? [];

                if (is_array($allList) && !empty($session)) {
                    $targetStudentId = (string)($session['student_id'] ?? '');
                    $targetQuizCode  = (string)($session['quiz_code'] ?? '');
                    $targetName      = (string)($session['student_name'] ?? '');

                    $matching = array_filter($allList, function ($s) use ($targetStudentId, $targetQuizCode, $targetName) {
                        $codeMatch = strcasecmp((string)($s['quiz_code'] ?? ''), $targetQuizCode) === 0;
                        $idMatch   = $targetStudentId !== '' && strcasecmp((string)($s['student_id'] ?? ''), $targetStudentId) === 0;
                        $nameMatch = $targetName !== '' && strcasecmp((string)($s['student_name'] ?? ''), $targetName) === 0;
                        return $codeMatch && ($idMatch || $nameMatch);
                    });

                    if (count($matching) > 1) {
                        $consolidated = $this->consolidateSessions(array_values($matching));
                        if (!empty($consolidated)) {
                            $session = $consolidated[0];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Non-critical fallback to single session data
            }

            // Try fetching violations from model API if session_id is available
            $modelSessionId = $session['session_id'] ?? '';
            if ($modelSessionId) {
                try {
                    $vRes       = Http::timeout(10)->get("{$this->modelApi}/api/violations?session_id={$modelSessionId}");
                    $vData      = $vRes->json();
                    $violations = $vData['violations'] ?? [];
                } catch (\Exception $e) {
                    // Violations not critical — continue without them
                }
            }
        } catch (\Exception $e) {
            Log::warning('ProctorController::reportDetail error: ' . $e->getMessage());
            $error = 'Could not load session detail.';
        }

        return view('teacher.proctoring-report-detail', compact('teacher', 'session', 'violations', 'id', 'error'));
    }
}
