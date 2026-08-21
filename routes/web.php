<?php

use App\Http\Controllers\AnalyzeController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AnalyzeController::class, 'index'])->name('home');
Route::get('/history', [HistoryController::class, 'index'])->name('history');
Route::get('/debug', [DebugController::class, 'index'])->name('debug');
