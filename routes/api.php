<?php

use App\Http\Controllers\Api\MailController;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(ApiTokenMiddleware::class)->group(function () {
    Route::post('/v1/mails/send', [MailController::class, 'send']);
});
