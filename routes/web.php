<?php

use App\Http\Controllers\MailLogAccessController;
use App\Http\Controllers\MailLogController;
use App\Http\Middleware\EnsureMailLogsAuthenticated;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/logs/login', [MailLogAccessController::class, 'showLogin'])->name('logs.login');
Route::post('/logs/login', [MailLogAccessController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('logs.authenticate');
Route::post('/logs/logout', [MailLogAccessController::class, 'logout'])
    ->middleware(EnsureMailLogsAuthenticated::class)
    ->name('logs.logout');
Route::get('/logs', [MailLogController::class, 'index'])
    ->middleware(EnsureMailLogsAuthenticated::class)
    ->name('logs.index');
