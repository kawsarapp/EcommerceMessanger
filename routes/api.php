<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ফেসবুক ভেরিফিকেশন (GET)

//Route::get('/webhook', [WebhookController::class, 'verify']);
//Route::post('/webhook', [WebhookController::class, 'handle']);
//Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);