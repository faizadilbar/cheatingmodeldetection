<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    private string $api = 'https://bgnuf22eight.com/Exam-app/exam-evaluation-app/public/api';

    private array $headers = [
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ];

    // ─── Helper: safe JSON response ───────────────────────────────
    private function apiGet(string $url): array
    {
        try {
            $curlOpts = [
                'force_ip_resolve' => 'v4',
                'verify'           => false,
                'curl'             => [CURLOPT_RESOLVE => ['bgnuf22eight.com:443:159.198.67.59']],
            ];
            $res = Http::withOptions($curlOpts)->timeout(30)->withHeaders($this->headers)->get($url);
            return $res->json() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function apiPost(string $url, array $data): array
    {
        try {
            $curlOpts = [
                'force_ip_resolve' => 'v4',
                'curl'             => [CURLOPT_RESOLVE => ['bgnuf22eight.com:443:159.198.67.59']],
            ];
            $res = Http::withOptions($curlOpts)->timeout(30)->withHeaders($this->headers)->post($url, $data);
            return $res->json() ?? [];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Network error: ' . $e->getMessage()];
        }
    }

    // ─── DASHBOARD ────────────────────────────────────────────────
    // Flutter: GET /admin/students  → data['students'] ?? data
    //          GET /admin/courses   → data['courses']  ?? data
    //          GET /admin/teachers  → data['teachers'] ?? data
    public function dashboard()
    {
        $sData    = $this->apiGet("{$this->api}/admin/students");
        $cData    = $this->apiGet("{$this->api}/admin/courses");
        $tData    = $this->apiGet("{$this->api}/admin/teachers");

        $students = is_array($sData) && isset($sData[0]) ? $sData : ($sData['students'] ?? $sData['data'] ?? []);
        $courses  = is_array($cData) && isset($cData[0]) ? $cData : ($cData['courses']  ?? $cData['data']  ?? []);
        $teachers = is_array($tData) && isset($tData[0]) ? $tData : ($tData['teachers'] ?? $tData['data']  ?? []);

        return view('admin.dashboard', compact('teachers','students','courses'));
    }

    // ─── TEACHERS ─────────────────────────────────────────────────
    // GET /admin/teachers → {status, teachers:[{id,name,email}]}
    public function teachers()
    {
        $data     = $this->apiGet("{$this->api}/admin/teachers");
        $teachers = is_array($data) && isset($data[0]) ? $data : ($data['teachers'] ?? $data['data'] ?? []);
        return view('admin.teachers', compact('teachers'));
    }

    // POST /admin/add-teacher  {name, email, password}
    public function addTeacher(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $data = $this->apiPost("{$this->api}/admin/add-teacher", [
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if ($data['status'] ?? false) {
            return back()->with('success', 'Teacher added successfully!');
        }

        $msg = $data['message'] ?? 'Failed to add teacher';
        if (!empty($data['errors'])) {
            $firstErr = array_values($data['errors'])[0];
            $msg = is_array($firstErr) ? $firstErr[0] : $firstErr;
        }
        return back()->withErrors(['msg' => $msg])->withInput();
    }

    // ─── STUDENTS ─────────────────────────────────────────────────
    // GET /admin/students → {status, students:[{id,name,email,rollno}]}
    public function students()
    {
        $data     = $this->apiGet("{$this->api}/admin/students");
        $students = is_array($data) && isset($data[0]) ? $data : ($data['students'] ?? $data['data'] ?? []);
        return view('admin.students', compact('students'));
    }

    // POST /admin/add-student  {name, email, password, rollno}
    public function addStudent(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required',
            'rollno'   => 'required',
        ]);

        $data = $this->apiPost("{$this->api}/admin/add-student", [
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'rollno'   => $request->rollno,
        ]);

        if ($data['status'] ?? false) {
            return back()->with('success', 'Student added successfully!');
        }

        $msg = $data['message'] ?? 'Failed to add student';
        if (!empty($data['errors'])) {
            $firstErr = array_values($data['errors'])[0];
            $msg = is_array($firstErr) ? $firstErr[0] : $firstErr;
        }
        return back()->withErrors(['msg' => $msg])->withInput();
    }

    // ─── COURSES ──────────────────────────────────────────────────
    // GET /admin/courses   → {status, courses:[{id,course_title,course_code,teacher_id,is_active,teacher:{id,name}}]}
    // GET /admin/teachers  → {status, teachers:[{id,name,email}]}
    public function addCourse(Request $request)
    {
        $request->validate([
            'course_title' => 'required',
            'course_code'  => 'required',
        ]);

        $data = $this->apiPost("{$this->api}/courses/add", [
            'course_title' => trim($request->course_title),
            'course_code'  => trim($request->course_code),
            'description'  => trim((string) $request->description),
            'is_active'    => (bool) $request->boolean('is_active'),
        ]);

        if ($data['status'] ?? false) {
            return back()->with('success', $data['message'] ?? 'Course added successfully!');
        }

        $msg = $data['message'] ?? 'Failed to add course';
        if (!empty($data['errors'])) {
            $firstErr = array_values($data['errors'])[0];
            $msg = is_array($firstErr) ? $firstErr[0] : $firstErr;
        }

        return back()->withErrors(['msg' => $msg])->withInput();
    }

    public function courses()
    {
        $cData    = $this->apiGet("{$this->api}/admin/courses");
        $tData    = $this->apiGet("{$this->api}/admin/teachers");

        $courses  = is_array($cData) && isset($cData[0]) ? $cData : ($cData['courses']  ?? $cData['data'] ?? []);
        $teachers = is_array($tData) && isset($tData[0]) ? $tData : ($tData['teachers'] ?? $tData['data'] ?? []);

        return view('admin.courses', compact('courses','teachers'));
    }

    // POST /admin/assign-courses  {teacher_id, course_ids:[...]}
    // Flutter uses: /admin/assign-courses (with 's')
    public function assignTeacher(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required',
            'course_ids' => 'required|array',
        ]);

        $data = $this->apiPost("{$this->api}/admin/assign-courses", [
            'teacher_id' => (int) $request->teacher_id,
            'course_ids' => array_map('intval', $request->course_ids),
        ]);

        if ($data['status'] ?? false) {
            return back()->with('success', $data['message'] ?? 'Teacher assigned successfully!');
        }
        return back()->withErrors(['msg' => $data['message'] ?? 'Failed to assign teacher']);
    }

    // POST /admin/remove-course  {course_id}
    public function removeTeacher(Request $request)
    {
        $request->validate(['course_id' => 'required']);

        try {
            $url = "{$this->api}/admin/remove-course";
            $res = Http::withOptions(['force_ip_resolve' => 'v4'])->timeout(30)->withHeaders($this->headers)->post($url, [
                'course_id' => (int) $request->course_id,
            ]);

            $data = $res->json() ?? [];
            if ($data['status'] ?? false) {
                return back()->with('success', $data['message'] ?? 'Teacher removed.');
            }
            return back()->withErrors(['msg' => $data['message'] ?? 'Failed to remove teacher']);

        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Network error: ' . $e->getMessage()]);
        }
    }

    // ─── STUDENT COURSES ──────────────────────────────────────────
    // GET /admin/student-courses/{user_id}
    // GET /admin/students → filter by id (no /admin/students/{id} route in backend)
    public function studentCourses(int $id)
    {
        // Backend has NO /admin/students/{id} route — fetch all and filter
        $allStudentsData = $this->apiGet("{$this->api}/admin/students");
        $allStudents     = $allStudentsData['students'] ?? $allStudentsData['data'] ?? [];
        // If plain array
        if (is_array($allStudentsData) && isset($allStudentsData[0])) {
            $allStudents = $allStudentsData;
        }

        $student = null;
        foreach ($allStudents as $s) {
            if ((int)($s['id'] ?? 0) === $id) {
                $student = $s;
                break;
            }
        }

        // Student enrolled courses
        // GET /admin/student-courses/{user_id} → {status, data:[{course_title, course_code,...}]}
        $cData   = $this->apiGet("{$this->api}/admin/student-courses/{$id}");
        $courses = [];
        if (is_array($cData)) {
            if (isset($cData[0])) {
                $courses = $cData; // plain array
            } else {
                $courses = $cData['courses'] ?? $cData['data'] ?? [];
            }
        }

        // All available courses for dropdown
        $aData      = $this->apiGet("{$this->api}/admin/courses");
        $allCourses = is_array($aData) && isset($aData[0]) ? $aData : ($aData['courses'] ?? $aData['data'] ?? []);

        return view('admin.student-courses', compact('courses','allCourses','student','id'));
    }

    // POST /admin/assign-course  {user_id, course_id}
    // Flutter sends: {user_id, course_id} (single assign from enroll tab)
    public function assignCourse(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
            'user_ids'  => 'required|array',
        ]);

        $data = $this->apiPost("{$this->api}/admin/assign-course", [
            'user_id'   => (int) $request->user_ids[0],
            'course_id' => (int) $request->course_id,
        ]);

        if ($data['status'] ?? false) {
            return back()->with('success', $data['message'] ?? 'Course assigned!');
        }
        return back()->withErrors(['msg' => $data['message'] ?? 'Failed to assign course']);
    }

    // POST /admin/remove-student-course  {user_id, course_id}
    public function removeCourse(Request $request)
    {
        $request->validate([
            'user_id'   => 'required',
            'course_id' => 'required',
        ]);

        $data = $this->apiPost("{$this->api}/admin/remove-student-course", [
            'user_id'   => (int) $request->user_id,
            'course_id' => (int) $request->course_id,
        ]);

        if ($data['status'] ?? false) {
            return back()->with('success', $data['message'] ?? 'Course removed!');
        }
        return back()->withErrors(['msg' => $data['message'] ?? 'Failed to remove course']);
    }
}
