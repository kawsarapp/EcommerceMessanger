<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

// ফেসবুক ভেরিফিকেশন (GET)
Route::get('/webhook', [WebhookController::class, 'verify']);

// মেসেজ রিসিভ করা (POST)
Route::post('/webhook', [WebhookController::class, 'handle']);
