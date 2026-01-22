<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessPlanning
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect('/admin/login')->with('error', 'Please login to access this page.');
        }

        $user = auth()->user();

        // Allow all authenticated users to access planning pages
        $allowedRoles = [
            \App\Models\User::ROLE_SUPER_ADMIN,
            \App\Models\User::ROLE_EXAM_ADMIN,
            \App\Models\User::ROLE_DEPARTMENT_HEAD,
            \App\Models\User::ROLE_DEAN,
            \App\Models\User::ROLE_PROFESSOR,  // ✅ Added
            \App\Models\User::ROLE_STUDENT,    // ✅ Added
        ];

        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
