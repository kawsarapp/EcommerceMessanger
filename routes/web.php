<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\FacebookConnectController;
use App\Http\Controllers\ClientSettingsController;
use App\Http\Controllers\PaymentController;
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
})->middleware([\App\Http\Middleware\DomainMappingMiddleware::class, 'tenant.customer'])->name('home');

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

// Plugin Download (Auth protected)
Route::middleware(['auth'])->get('/download/neuralcart-plugin', [
    \App\Http\Controllers\PluginDownloadController::class, 'download'
])->name('plugin.download');

// =============================================================
// 🛍️ DYNAMIC SHOP ENGINE (Powered by DomainMappingMiddleware)
// =============================================================
Route::middleware([\App\Http\Middleware\DomainMappingMiddleware::class, 'tenant.customer'])->group(function () {

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

        // 🛒 Cart Routes (Sub-path)
        Route::post('/cart/add',              [ShopController::class, 'addToCart'])->name('shop.cart.add');
        Route::get('/cart',                   [ShopController::class, 'viewCart'])->name('shop.cart');
        Route::post('/cart/remove',           [ShopController::class, 'removeCartItem'])->name('shop.cart.remove');
        Route::post('/cart/update',           [ShopController::class, 'updateCartItem'])->name('shop.cart.update');
        Route::post('/cart/clear',            [ShopController::class, 'clearCart'])->name('shop.cart.clear');
        Route::get('/cart/checkout',          [ShopController::class, 'cartCheckout'])->name('shop.cart.checkout');
        Route::post('/cart/checkout/process', [ShopController::class, 'processCartCheckout'])->name('shop.cart.checkout.process');

        // ⚖️ Product Compare
        Route::get('/compare',  [ShopController::class, 'comparePage'])->name('shop.compare');

        // 🔔 Stock Notify Me
        Route::post('/stock/notify', [ShopController::class, 'stockNotify'])->name('shop.stock.notify');

        // 🔐 Customer Portal Auth
        Route::get('/login', [App\Http\Controllers\CustomerAuthController::class, 'showLoginForm'])->name('shop.customer.login');
        Route::post('/login', [App\Http\Controllers\CustomerAuthController::class, 'login'])->name('shop.customer.login.submit');
        Route::get('/register', [App\Http\Controllers\CustomerAuthController::class, 'showRegisterForm'])->name('shop.customer.register');
        Route::post('/register', [App\Http\Controllers\CustomerAuthController::class, 'register'])->name('shop.customer.register.submit');
        Route::get('/forgot-password', [App\Http\Controllers\CustomerAuthController::class, 'showForgotForm'])->name('shop.customer.forgot');
        Route::post('/forgot-password', [App\Http\Controllers\CustomerAuthController::class, 'processForgot'])->name('shop.customer.forgot.submit');
        Route::post('/logout', [App\Http\Controllers\CustomerAuthController::class, 'logout'])->name('shop.customer.logout');
        
        Route::middleware(['auth:customer'])->group(function () {
            Route::get('/customer/dashboard', [App\Http\Controllers\CustomerDashboardController::class, 'index'])->name('shop.customer.dashboard');
        });
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

    // 🛒 Cart Routes (Custom Domain)
    Route::post('/cart/add',              [ShopController::class, 'addToCart'])->name('shop.cart.add.custom');
    Route::get('/cart',                   [ShopController::class, 'viewCart'])->name('shop.cart.custom');
    Route::post('/cart/remove',           [ShopController::class, 'removeCartItem'])->name('shop.cart.remove.custom');
    Route::post('/cart/update',           [ShopController::class, 'updateCartItem'])->name('shop.cart.update.custom');
    Route::post('/cart/clear',            [ShopController::class, 'clearCart'])->name('shop.cart.clear.custom');
    Route::get('/cart/checkout',          [ShopController::class, 'cartCheckout'])->name('shop.cart.checkout.custom');
    Route::post('/cart/checkout/process', [ShopController::class, 'processCartCheckout'])->name('shop.cart.checkout.process.custom');

    // ⚖️ Product Compare (Custom Domain)
    Route::get('/compare',  [ShopController::class, 'comparePage'])->name('shop.compare.custom');

    // 🔔 Stock Notify Me (Custom Domain)
    Route::post('/stock/notify', [ShopController::class, 'stockNotify'])->name('shop.stock.notify.custom');

    // 🔐 Customer Portal Auth (Custom Domain)
    Route::get('/login', [App\Http\Controllers\CustomerAuthController::class, 'showLoginForm'])->name('shop.customer.login.custom');
    Route::post('/login', [App\Http\Controllers\CustomerAuthController::class, 'login'])->name('shop.customer.login.submit.custom');
    Route::get('/register', [App\Http\Controllers\CustomerAuthController::class, 'showRegisterForm'])->name('shop.customer.register.custom');
    Route::post('/register', [App\Http\Controllers\CustomerAuthController::class, 'register'])->name('shop.customer.register.submit.custom');
    Route::get('/forgot-password', [App\Http\Controllers\CustomerAuthController::class, 'showForgotForm'])->name('shop.customer.forgot.custom');
    Route::post('/forgot-password', [App\Http\Controllers\CustomerAuthController::class, 'processForgot'])->name('shop.customer.forgot.submit.custom');
    Route::post('/logout', [App\Http\Controllers\CustomerAuthController::class, 'logout'])->name('shop.customer.logout.custom');
    
    Route::middleware(['auth:customer'])->group(function () {
        Route::get('/customer/dashboard', [App\Http\Controllers\CustomerDashboardController::class, 'index'])->name('shop.customer.dashboard.custom');
    });
    
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

// ==========================================
// 💳 PAYMENT GATEWAY ROUTES
// ==========================================
Route::middleware([\App\Http\Middleware\DomainMappingMiddleware::class, 'tenant.customer'])->prefix('payment')->group(function () {

    // 📲 bKash Reference Confirmation (AJAX — Manual)
    Route::post('/bkash/personal/confirm',  [PaymentController::class, 'confirmBkashPersonal'])->name('payment.bkash.personal.confirm');
    Route::post('/bkash/merchant/confirm',  [PaymentController::class, 'confirmBkashMerchant'])->name('payment.bkash.merchant.confirm');

    // 🔴 bKash PGW — Official Tokenized Checkout API
    Route::get('/bkash-pgw/{orderId}/init',     [PaymentController::class, 'initiateBkashPgw'])->name('payment.bkash.pgw.init');
    Route::get('/bkash-pgw/{orderId}/callback', [PaymentController::class, 'bkashPgwCallback'])->name('payment.bkash.pgw.callback');

    // 💳 SSL Commerz
    Route::get('/sslcommerz/{orderId}/init',    [PaymentController::class, 'initiateSslCommerz'])->name('payment.sslcommerz.init');
    Route::post('/sslcommerz/{orderId}/success', [PaymentController::class, 'sslcommerzSuccess'])->name('payment.sslcommerz.success');
    Route::post('/sslcommerz/{orderId}/fail',    [PaymentController::class, 'sslcommerzFail'])->name('payment.sslcommerz.fail');
    Route::post('/sslcommerz/{orderId}/cancel',  [PaymentController::class, 'sslcommerzCancel'])->name('payment.sslcommerz.cancel');

    // 🌙 Surjopay
    Route::get('/surjopay/{orderId}/init',    [PaymentController::class, 'initiateSurjopay'])->name('payment.surjopay.init');
    Route::get('/surjopay/{orderId}/success', [PaymentController::class, 'surjopaySuccess'])->name('payment.surjopay.success');
    Route::get('/surjopay/{orderId}/fail',    [PaymentController::class, 'surjopayFail'])->name('payment.surjopay.fail');
    Route::get('/surjopay/{orderId}/cancel',  [PaymentController::class, 'surjopayCancel'])->name('payment.surjopay.cancel');
});