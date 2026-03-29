<?php

use App\Http\Controllers\MailLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/logs', [MailLogController::class, 'index']);
