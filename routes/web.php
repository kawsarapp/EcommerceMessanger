<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\FacebookConnectController;
use App\Http\Controllers\ClientSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =============================================================
// 🌍 DYNAMIC SHOP ENGINE (Custom Domain & Slug Management)
// =============================================================
// আমরা পুরো শপ লজিককে একটি মিডলওয়্যার গ্রুপের মধ্যে রাখছি যাতে কোড ক্লিন থাকে
Route::middleware([\App\Http\Middleware\DomainMappingMiddleware::class])->group(function () {

    // ১. মেইন ল্যান্ডিং অথবা কাস্টম ডোমেইন হোম
    Route::get('/', function (Request $request) {
        // যদি কাস্টম ডোমেইন থাকে, তাহলে শপ হোমপেজ দেখাবে
        if ($request->has('current_client')) {
            return app(ShopController::class)->show($request, null);
        }
        // না হলে মেইন সাইটের ওয়েলকাম পেজ
        return view('welcome');
    })->name('home');


    // ==========================================
    // 🛒 SUB-PATH ROUTING (maindomain.com/shop/...)
    // ==========================================
    // নোট: রাউটের অর্ডার এখানে খুব গুরুত্বপূর্ণ!
    Route::prefix('shop/{slug}')->group(function () {
        
        // 🔥 ডাইনামিক পেজ রাউট (সবার উপরে রাখতে হবে)
        // URL: asianhost.net/shop/fashion-bd/page/terms
        Route::get('/page/{pageSlug}', [ShopController::class, 'showPage'])
            ->name('shop.page.slug');

        // দোকানের মেইন হোমপেজ
        Route::get('/', [ShopController::class, 'show'])
            ->name('shop.show');

        // সিঙ্গেল প্রোডাক্ট ডিটেইলস
        Route::get('/product/{productSlug}', [ShopController::class, 'productDetails'])
            ->name('shop.product.details');

        // অর্ডার ট্র্যাকিং
        Route::get('/track', [ShopController::class, 'trackOrder'])
            ->name('shop.track');
        Route::post('/track', [ShopController::class, 'trackOrderSubmit'])
            ->name('shop.track.submit');
    });


    // ==========================================
    // 🌍 CUSTOM DOMAIN ROUTING (example.com/...)
    // ==========================================
    
    // সিঙ্গেল প্রোডাক্ট (Custom Domain)
    Route::get('/product/{productSlug}', [ShopController::class, 'productDetails'])
        ->name('shop.product.custom');

    // অর্ডার ট্র্যাকিং (Custom Domain)
    Route::get('/track', [ShopController::class, 'trackOrder'])
        ->name('shop.track.custom');
    Route::post('/track', [ShopController::class, 'trackOrderSubmit'])
        ->name('shop.track.submit.custom');

    // 🔥 ডাইনামিক পেজ (Custom Domain - সবার শেষে)
    // URL: example.com/terms-condition
    // এটি সবার শেষে রাখা হয়েছে যাতে /product বা /track এর সাথে কনফ্লিক্ট না করে
    Route::get('/{pageSlug}', [ShopController::class, 'showPage'])
        ->where('pageSlug', '^(?!shop|webhook|auth|dashboard|login|register|api|admin|storage|css|js|images).*$')
        ->name('shop.page.custom');
});


// ==========================================
// ⚡ AJAX & UTILITY FEATURES
// ==========================================
Route::prefix('shop-api')->group(function () {
    Route::post('/load-more', [ShopController::class, 'loadMore'])->name('shop.load-more');
    Route::get('/category-counts', [ShopController::class, 'getCategoryCounts'])->name('shop.category-counts');
});


// ==========================================
// 🔗 OAUTH & INTEGRATIONS
// ==========================================

// Facebook Connect
Route::get('/auth/facebook/redirect', [FacebookConnectController::class, 'redirect'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [FacebookConnectController::class, 'callback'])->name('auth.facebook.callback');

// Webhooks
Route::prefix('webhook')->group(function () {
    // Messenger
    Route::get('/messenger', [WebhookController::class, 'verify'])->name('webhook.verify');
    Route::post('/messenger', [WebhookController::class, 'handle'])->name('webhook.handle');
    
    // Telegram (Dynamic Token)
    Route::post('/telegram/{token}', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
});


// ==========================================
// 🧑‍💼 SELLER DASHBOARD (Authenticated)
// ==========================================
Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    // ডোমেইন সেটিংস
    Route::get('/settings/domain', [ClientSettingsController::class, 'domainPage'])->name('dashboard.domain');
    Route::post('/settings/domain', [ClientSettingsController::class, 'updateDomain'])->name('dashboard.domain.update');
});