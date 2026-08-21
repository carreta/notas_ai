<?php

use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

// API endpoint for meeting retrieval (JSON only)
// Used by tests and API consumers
Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
