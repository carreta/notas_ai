<?php

use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/meetings', [MeetingController::class, 'store']);
Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
