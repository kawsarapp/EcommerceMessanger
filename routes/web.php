<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\FacebookConnectController;
use App\Http\Controllers\ClientSettingsController;
use App\Models\Plan;
use App\Models\Order;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 🌍 PUBLIC GLOBAL ROUTES (Landing, Pricing)
// ==========================================

Route::get('/', function (Request $request) {
    // যদি কাস্টম ডোমেইন থাকে, তাহলে শপ হোমপেজ দেখাবে
    if ($request->has('current_client')) {
        return app(ShopController::class)->show($request, null);
    }
    // না হলে মেইন সাইটের ওয়েলকাম পেজ
    return view('welcome');
})->middleware([\App\Http\Middleware\DomainMappingMiddleware::class])->name('home');

// 🔥 Pricing Page
Route::get('/pricing', function () {
    $plans = Plan::where('is_active', true)->orderBy('price', 'asc')->get();
    return view('pricing', compact('plans'));
})->name('pricing');

// Print Order Invoice (Secured)
Route::middleware(['auth'])->get('/orders/{order}/print', function (Order $order) {
    // Security Check: Only super admin or the shop owner can print
    abort_if(
        !auth()->user()?->isSuperAdmin() && $order->client->user_id !== auth()->id(),
        403,
        'Unauthorized access.'
    );
    
    return view('filament.pages.invoice-print', compact('order'));
})->name('orders.print');

// =============================================================
// 🛍️ DYNAMIC SHOP ENGINE (Powered by DomainMappingMiddleware)
// =============================================================
Route::middleware([\App\Http\Middleware\DomainMappingMiddleware::class])->group(function () {

    // ==========================================
    // 🛒 SUB-PATH ROUTING (maindomain.com/shop/...)
    // ==========================================
    Route::prefix('shop/{slug}')->group(function () {
        
        // দোকানের মেইন হোমপেজ
        Route::get('/', [ShopController::class, 'show'])->name('shop.show');
        
        // ডাইনামিক পেজ রাউট
        Route::get('/page/{pageSlug}', [ShopController::class, 'showPage'])->name('shop.page.slug');

        // সিঙ্গেল প্রোডাক্ট ডিটেইলস
        Route::get('/product/{productSlug}', [ShopController::class, 'productDetails'])->name('shop.product.details');

        // অর্ডার ট্র্যাকিং
        Route::get('/track', [ShopController::class, 'trackOrder'])->name('shop.track');
        Route::post('/track', [ShopController::class, 'trackOrderSubmit'])->name('shop.track.submit');

        // Direct Checkout Routes
        Route::get('/checkout/{productSlug}', [ShopController::class, 'checkout'])->name('shop.checkout');
        Route::post('/checkout/process', [ShopController::class, 'processCheckout'])->name('shop.checkout.process');

        // Coupon Apply Route (Ajax, Sub-path)
        Route::post('/apply-coupon', [ShopController::class, 'applyCoupon'])->name('shop.apply-coupon.sub');
    });

    // ==========================================
    // 🌍 CUSTOM DOMAIN ROUTING (example.com/...)
    // ==========================================
    
    // সিঙ্গেল প্রোডাক্ট (Custom Domain)
    Route::get('/product/{productSlug}', [ShopController::class, 'productDetails'])->name('shop.product.custom');

    // অর্ডার ট্র্যাকিং (Custom Domain)
    Route::get('/track', [ShopController::class, 'trackOrder'])->name('shop.track.custom');
    Route::post('/track', [ShopController::class, 'trackOrderSubmit'])->name('shop.track.submit.custom');

    // Direct Checkout Routes (Custom Domain)
    Route::get('/checkout/{productSlug}', [ShopController::class, 'checkout'])->name('shop.checkout.custom');
    Route::post('/checkout/process', [ShopController::class, 'processCheckout'])->name('shop.checkout.process.custom');

    // Coupon Apply Route (Ajax)
    Route::post('/apply-coupon', [ShopController::class, 'applyCoupon'])->name('shop.apply-coupon');
    
    // 🔥 ডাইনামিক পেজ (Custom Domain - সবার শেষে)
    // URL: example.com/terms-condition
    Route::get('/{pageSlug}', [ShopController::class, 'showPage'])
        ->where('pageSlug', '^(?!shop|webhook|auth|dashboard|login|register|api|admin|storage|css|js|images|pricing|orders|shop-api).*$') 
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
Route::prefix('auth/facebook')->group(function () {
    Route::get('/redirect', [FacebookConnectController::class, 'redirect'])->name('auth.facebook');
    Route::get('/callback', [FacebookConnectController::class, 'callback'])->name('auth.facebook.callback');
});

// Webhooks (Ensure these routes are excluded from VerifyCsrfToken middleware)
Route::prefix('webhook')->group(function () {
    Route::get('/messenger', [WebhookController::class, 'verify'])->name('webhook.verify');
    Route::post('/messenger', [WebhookController::class, 'handle'])->name('webhook.handle');
    Route::post('/telegram/{token}', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
});

// ==========================================
// 🧑‍💼 SELLER DASHBOARD (Authenticated)
// ==========================================
Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::get('/settings/domain', [ClientSettingsController::class, 'domainPage'])->name('dashboard.domain');
    Route::post('/settings/domain', [ClientSettingsController::class, 'updateDomain'])->name('dashboard.domain.update');
});

// ==========================================
// 🛡️ BACKUP ROUTES (Super Admin Only)
// ==========================================
Route::middleware(['auth'])->prefix('admin-backup')->group(function () {
    Route::get('/db', function () {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        return app(\App\Filament\Pages\BackupManager::class)->downloadFullDatabaseBackup();
    })->name('filament.admin.pages.backup-manager.db');

    Route::get('/zip', function () {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        return app(\App\Filament\Pages\BackupManager::class)->downloadFullWebsiteBackup();
    })->name('filament.admin.pages.backup-manager.zip');

    Route::get('/client/{clientId}', function ($clientId) {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        return app(\App\Filament\Pages\BackupManager::class)->downloadClientBackup($clientId);
    })->name('filament.admin.pages.backup-manager.client');
});