<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ===========================================
// 🔥 BACKUP ROUTES (যাতে পুরনো সেটআপ কাজ করে)
// ===========================================

// Facebook Webhook (Verification & Handle)
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Telegram Webhook
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);