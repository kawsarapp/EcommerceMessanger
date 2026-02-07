<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;

// ফেসবুক ভেরিফিকেশন (GET)
Route::get('/webhook', [WebhookController::class, 'verify']);

// মেসেজ রিসিভ করা (POST)
Route::post('/webhook', [WebhookController::class, 'handle']);
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);