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

// ==========================================
// 🌐 MAIN LANDING (Main Domain Only)
// ==========================================
Route::get('/', function () {
    return view('welcome');
})->name('landing');


// ==========================================
// 🛒 PUBLIC SHOP ROUTES (Slug Based - Main Domain)
// ==========================================
Route::prefix('shop')->group(function () {

    // ১. দোকানের মেইন পেজ
    Route::get('/{slug}', [ShopController::class, 'show'])->name('shop.show');

    // ২. সিঙ্গেল প্রোডাক্ট ডিটেইলস
    Route::get('/{slug}/product/{productSlug}', [ShopController::class, 'productDetails'])
        ->name('shop.product.details');

    // ৩. অর্ডার ট্র্যাকিং
    Route::get('/{slug}/track', [ShopController::class, 'trackOrder'])
        ->name('shop.track');

    Route::post('/{slug}/track', [ShopController::class, 'trackOrderSubmit'])
        ->name('shop.track.submit');

    // ৪. Ajax Features
    Route::post('/load-more', [ShopController::class, 'loadMore'])
        ->name('shop.load-more');

    Route::get('/category-counts', [ShopController::class, 'getCategoryCounts'])
        ->name('shop.category-counts');
});


// ==========================================
// 🔗 FACEBOOK OAUTH (Seller Connection)
// ==========================================
Route::get('/auth/facebook/redirect', [FacebookConnectController::class, 'redirect'])
    ->name('auth.facebook');

Route::get('/auth/facebook/callback', [FacebookConnectController::class, 'callback'])
    ->name('auth.facebook.callback');


// ==========================================
// 🤖 CHATBOT WEBHOOKS
// ==========================================

// 🔵 Facebook Messenger Webhook
Route::prefix('webhook/messenger')->group(function () {
    Route::get('/', [WebhookController::class, 'verify'])
        ->name('webhook.verify');

    Route::post('/', [WebhookController::class, 'handle'])
        ->name('webhook.handle');
});

// 🔴 Telegram Webhook (Dynamic Token)
Route::post('/webhook/telegram/{token}', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');


// ==========================================
// 🌍 DYNAMIC SHOP ROUTES (Custom Domain Support)
// ==========================================
Route::middleware([\App\Http\Middleware\DomainMappingMiddleware::class])->group(function () {

    // ১. Custom Domain Home
    Route::get('/', function (\Illuminate\Http\Request $request) {

        if ($request->has('current_client')) {
            return app(\App\Http\Controllers\ShopController::class)
                ->show($request, null);
        }

        return view('welcome');
    })->name('home');

    // ২. Custom Domain Product
    Route::get('/product/{productSlug}', [ShopController::class, 'productDetails'])
        ->name('shop.product.custom');

    // ৩. Custom Domain Order Tracking
    Route::match(['get', 'post'], '/track', [ShopController::class, 'trackOrder'])
        ->name('shop.track.custom');
});



Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    // ডোমেইন সেটিংস পেজ
    Route::get('/settings/domain', [ClientSettingsController::class, 'domainPage'])->name('dashboard.domain');
    // ডোমেইন আপডেট রিকোয়েস্ট
    Route::post('/settings/domain', [ClientSettingsController::class, 'updateDomain'])->name('dashboard.domain.update');
});