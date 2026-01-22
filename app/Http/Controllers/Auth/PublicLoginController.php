<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PublicLoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Redirect based on role
            if ($user->role === 'professor') {
                return redirect()->route('planning.professors');
            }

            if ($user->role === 'student') {
                return redirect()->route('planning.index');
            }

            // Admin users go to admin panel
            if (in_array($user->role, ['exam_admin', 'dean', 'department_head', 'super_admin'])) {
                return redirect('/admin');
            }
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect admin roles to admin panel
            if (in_array($user->role, ['exam_admin', 'dean', 'department_head', 'super_admin'])) {
                return redirect('/admin')->with('info', 'Please use the admin login page for administrative access.');
            }

            // Redirect professors to professor supervision page
            if ($user->role === 'professor') {
                session()->flash('success', 'Welcome back, ' . $user->name . '!');
                return redirect()->intended(route('planning.professors'));
            }

            // Redirect students to exam schedule page
            if ($user->role === 'student') {
                session()->flash('success', 'Welcome back, ' . $user->name . '!');
                return redirect()->intended(route('planning.index'));
            }

            // Fallback for unknown roles
            Auth::logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => 'Your account type cannot access this system.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}
