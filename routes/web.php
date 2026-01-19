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