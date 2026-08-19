<?php

use App\Http\Controllers\MeetingController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/history', [PageController::class, 'history'])->name('history');
Route::get('/debug', [PageController::class, 'debug'])->name('debug');
Route::post('/analyze', [MeetingController::class, 'store'])->name('meetings.store');

Route::post('/meetings', [MeetingController::class, 'store']);
Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
