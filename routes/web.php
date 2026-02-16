<?php

use Illuminate\Support\Facades\Route;
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
Route::middleware([\App\Http\Middleware\DomainMappingMiddleware::class])->group(function () {

    // ১. মেইন ল্যান্ডিং অথবা কাস্টম ডোমেইন হোম
    Route::get('/', function (\Illuminate\Http\Request $request) {
        // যদি কাস্টম ডোমেইন দিয়ে ভিজিট করে
        if ($request->has('current_client')) {
            return app(ShopController::class)->show($request, null);
        }
        // মেইন ডোমেইন হলে ল্যান্ডিং পেজ
        return view('welcome');
    })->name('home');

    // ২. কাস্টম ডোমেইন রাউটস (example.com/...)
    Route::prefix('/')->group(function () {
        Route::get('/product/{productSlug}', [ShopController::class, 'productDetails'])->name('shop.product.custom');
        Route::get('/track', [ShopController::class, 'trackOrder'])->name('shop.track.custom');
        Route::post('/track', [ShopController::class, 'trackOrderSubmit'])->name('shop.track.submit.custom');
    });

    // ৩. সাব-প্যাথ বা স্লাগ রাউটস (maindomain.com/shop/slug/...)
    Route::prefix('shop/{slug}')->group(function () {
        Route::get('/', [ShopController::class, 'show'])->name('shop.show');
        Route::get('/product/{productSlug}', [ShopController::class, 'productDetails'])->name('shop.product.details');
        Route::get('/track', [ShopController::class, 'trackOrder'])->name('shop.track');
        Route::post('/track', [ShopController::class, 'trackOrderSubmit'])->name('shop.track.submit');
        Route::get('/page/{pageSlug}', [ShopController::class, 'showPage'])->name('shop.page.slug');
    });

    // ৪. 🔥 ডাইনামিক পেজ (Custom Domain এর জন্য সরাসরি URL)
    // এটি সবার শেষে রাখতে হয় যাতে অন্য রাউটগুলোর সাথে না মিলে যায়
    Route::get('/{pageSlug}', [ShopController::class, 'showPage'])
        ->where('pageSlug', '^(?!shop|webhook|auth|dashboard|login|register|api|admin).*$')
        ->name('shop.page.custom');
});

// ==========================================
// ⚡ AJAX & UTILITY FEATURES
// ==========================================
Route::post('/shop/load-more', [ShopController::class, 'loadMore'])->name('shop.load-more');
Route::get('/shop/category-counts', [ShopController::class, 'getCategoryCounts'])->name('shop.category-counts');


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
// 🛠️ SELLER DASHBOARD (Authenticated)
// ==========================================
Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    // ডোমেইন সেটিংস
    Route::get('/settings/domain', [ClientSettingsController::class, 'domainPage'])->name('dashboard.domain');
    Route::post('/settings/domain', [ClientSettingsController::class, 'updateDomain'])->name('dashboard.domain.update');
});