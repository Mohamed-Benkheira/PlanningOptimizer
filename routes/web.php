<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\GroupExamScheduleExplorer;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/schedule', GroupExamScheduleExplorer::class)
    ->name('public.schedule')->withoutMiddleware(['auth'])
    ->name('public.schedule');
;

Route::get('/setup-db', function () {
    Artisan::call('session:table');
    Artisan::call('cache:table');
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--class' => 'RealisticUniversitySeeder']);
    return "✅ Database setup complete!";
});