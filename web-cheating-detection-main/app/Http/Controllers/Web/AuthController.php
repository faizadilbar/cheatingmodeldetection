<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private string $apiBase = 'https://bgnuf22eight.com/Exam-app/exam-evaluation-app/public/api';

    public function showLogin()
    {
        if (session('user')) {
            return $this->redirectByRole(session('user')['role']);
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required',
            'password' => 'required',
        ]);

        $input = $request->email;
        $body = ['password' => $request->password];
        if (str_contains($input, '@')) {
            $body['email'] = $input;
        } else {
            $body['rollno'] = $input;
        }

        try {
            $curlOpts = [
                'force_ip_resolve' => 'v4',
                'verify'           => false,
                'curl'             => [CURLOPT_RESOLVE => ['bgnuf22eight.com:443:159.198.67.59']],
            ];
            $response = Http::withOptions($curlOpts)->timeout(30)->post("{$this->apiBase}/login", $body);

            $data = $response->json();

            if ($response->successful() && (isset($data['user']) || isset($data['role']))) {
                $user = $data['user'] ?? null;
                if (!$user) {
                    return back()->withErrors(['email' => 'Invalid response from server'])->withInput();
                }
                session([
                    'user' => [
                        'id'     => $user['id'],
                        'name'   => $user['name'],
                        'email'  => $user['email'] ?? '',
                        'role'   => $user['role'] ?? $data['role'],
                        'rollno' => $user['rollno'] ?? null,
                    ]
                ]);
                return $this->redirectByRole($user['role'] ?? $data['role']);
            }

            $message = $data['message'] ?? 'Invalid credentials';
            return back()->withErrors(['email' => $message])->withInput();

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Connection error: ' . $e->getMessage()])->withInput();
        }
    }

    private function redirectByRole(string $role)
    {
        return match($role) {
            'admin'   => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            default   => redirect('/'),
        };
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
