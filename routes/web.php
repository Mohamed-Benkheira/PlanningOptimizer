<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentPlanningController;
use App\Http\Controllers\ProfessorPlanningController;
use App\Http\Controllers\Auth\PublicLoginController;

// Welcome Page (Public)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public Login Routes (for students & professors)
Route::middleware('guest')->group(function () {
    Route::get('/login', [PublicLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [PublicLoginController::class, 'login']);
});

// Logout Route (for all users)
Route::post('/logout', [PublicLoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Protected Planning Routes (require authentication)
Route::middleware(['auth'])->group(function () {
    // Student/General Exam Schedule
    Route::get('/schedule', [StudentPlanningController::class, 'index'])->name('planning.index');

    // Professor Supervision Schedule
    Route::get('/professors', [ProfessorPlanningController::class, 'index'])->name('planning.professors');
});
