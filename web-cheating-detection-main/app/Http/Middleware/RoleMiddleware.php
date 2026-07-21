<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        if ($user['role'] !== $role) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
        return $next($request);
    }
}
