<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\FacebookConnectController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// ==========================================
// 🛒 PUBLIC SHOP ROUTES (Customer Facing)
// ==========================================
Route::prefix('shop')->group(function () {
    
    // ১. দোকানের মেইন পেজ (প্রোডাক্ট লিস্ট)
    Route::get('/{slug}', [ShopController::class, 'show'])->name('shop.show');

    // ২. সিঙ্গেল প্রোডাক্ট ডিটেইলস পেজ (Extreme Design)
    Route::get('/{slug}/product/{productSlug}', [ShopController::class, 'productDetails'])->name('shop.product.details');

    // ৩. অর্ডার ট্র্যাকিং (Phone Number Search)
    Route::get('/{slug}/track', [ShopController::class, 'trackOrder'])->name('shop.track');
    Route::post('/{slug}/track', [ShopController::class, 'trackOrderSubmit'])->name('shop.track.submit');

    // ৪. অতিরিক্ত ফিচার (Ajax/Load More - যদি আগের ডিজাইন ব্যবহার করেন)
    Route::post('/load-more', [ShopController::class, 'loadMore'])->name('shop.load-more');
    Route::get('/category-counts', [ShopController::class, 'getCategoryCounts'])->name('shop.category-counts');
});


// ==========================================
// 🔗 FACEBOOK OAUTH (Seller Connection)
// ==========================================
Route::get('/auth/facebook/redirect', [FacebookConnectController::class, 'redirect'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [FacebookConnectController::class, 'callback']);


// ==========================================
// 🤖 CHATBOT WEBHOOKS (AI & Automation)
// ==========================================

// 🔵 Facebook Messenger Webhook
// (Facebook App Settings-এ URL হিসেবে দিবেন: https://yourdomain.com/webhook/messenger)
Route::prefix('webhook/messenger')->group(function () {
    Route::get('/', [WebhookController::class, 'verify'])->name('webhook.verify');
    Route::post('/', [WebhookController::class, 'handle'])->name('webhook.handle');
});

// 🔴 Telegram Webhook (Dynamic Token based SaaS)
// (Telegram BotFather-এ URL দিবেন: https://yourdomain.com/webhook/telegram/{token})
Route::post('/webhook/telegram/{token}', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');