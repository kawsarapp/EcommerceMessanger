<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\FacebookConnectController;
use App\Http\Controllers\TelegramWebhookController; // ✅ এই লাইনটি জরুরি


Route::get('/', function () {
    return view('welcome');
});


Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/shop/{slug}/product/{productSlug}', [ShopController::class, 'productDetail'])->name('shop.product');
Route::post('/shop/load-more', [ShopController::class, 'loadMore'])->name('shop.load-more');
Route::get('/shop/category-counts', [ShopController::class, 'getCategoryCounts'])->name('shop.category-counts');


Route::get('/auth/facebook/redirect', [FacebookConnectController::class, 'redirect'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [FacebookConnectController::class, 'callback']);


// ✅ Facebook Webhook (Verify & Handle)
Route::get('/webhook', [WebhookController::class, 'verify'])->name('webhook.verify');
Route::post('/webhook', [WebhookController::class, 'handle'])->name('webhook.handle');

// ✅ Telegram Webhook (এই রাউটটি এখানে থাকতে হবে)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');