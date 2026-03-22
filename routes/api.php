<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Api\StoreSyncController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\WebsiteConnectorController;
use App\Http\Controllers\Api\WidgetChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ১. অথেনটিকেটেড ইউজার ডাটা (Default Sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ===========================================
// 🤖 CHATBOT & WEBHOOK API (Extreme SaaS)
// ===========================================

/**
 * 🔵 Facebook Messenger Webhook
 * URL: https://yourdomain.com/api/webhook
 * (আপনার ফেসবুক অ্যাপ ড্যাশবোর্ডে এই URL-টি সেট করুন)
 */
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);


/**
 * 🔴 Telegram Dynamic Webhook (SaaS Ready)
 * URL: https://yourdomain.com/api/telegram/webhook/{token}
 * (প্রতিটি সেলারের জন্য আলাদা টোকেন দিয়ে এটি কাজ করবে)
 */
Route::post('/telegram/webhook/{token}', [TelegramWebhookController::class, 'handle'])
     ->name('api.telegram.webhook');


// ===========================================
// 🛠️ API v1 — Rate Limited Public Endpoints
// ===========================================
Route::prefix('v1')->group(function () {

    // WhatsApp Integration
    Route::middleware(['throttle:120,1'])->group(function () {
        Route::post('/whatsapp/status',  [WhatsAppWebhookController::class, 'updateStatus']);
        Route::post('/whatsapp/receive', [WhatsAppWebhookController::class, 'receiveMessage']);

        // Product Import / Sync (WooCommerce, Shopify, etc.)
        Route::post('/import-products', [StoreSyncController::class, 'pushProducts']);
    });

    /**
     * 🤖 Embeddable Widget Chat
     * POST /api/v1/chat/widget
     * Used by chatbot-widget.js to send/receive messages.
     * Auth: X-Api-Key header  |  Throttle: 60 msg/min
     */
    // Browser sends OPTIONS preflight before cross-origin POST — must return 200 immediately
    Route::options('/chat/widget', function () {
        return response()->json('OK', 200, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Api-Key, Authorization, Accept',
        ]);
    });
    Route::middleware(['throttle:60,1'])->post('/chat/widget', [WidgetChatController::class, 'handle'])
         ->name('api.chat.widget');
});

// ===========================================
// 🔌 UNIVERSAL WEBSITE CONNECTOR
// Rate limited: 60 requests/minute per IP
// Any website (WordPress, Shopify, custom HTML, React) can use these.
// ===========================================
Route::prefix('connector')->group(function () {
    Route::middleware(['throttle:60,1'])->group(function () {
        // Test if API Key is valid
        Route::get('/verify',         [WebsiteConnectorController::class, 'verify']);
        // Push products from any website
        Route::post('/sync-products', [WebsiteConnectorController::class, 'syncProducts']);
        // Get the embeddable JS snippet
        Route::get('/js-snippet',     [WebsiteConnectorController::class, 'getJsSnippet']);
    });
});

// ===========================================
// 🚚 Courier Webhook (Multi-tenant)
// ===========================================
Route::post('/webhook/courier/{client_id}/{courier_name}', [\App\Http\Controllers\CourierWebhookController::class, 'handle'])
     ->name('webhook.courier');

// ===========================================
// 📦 WooCommerce Order Notification (WordPress Plugin → SaaS)
// ===========================================
Route::middleware(['throttle:120,1'])->post('/v1/wc-order-notify', [\App\Http\Controllers\Api\WooCommerceWebhookController::class, 'orderNotify'])
     ->name('api.wc.order-notify');

// ===========================================
// 💻 WordPress Plugin Download
// ===========================================
Route::get('/v1/wordpress-plugin/download', function () {
    $folder = public_path('wordpress-plugin/ai-commerce-bot');
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ai-commerce-bot.zip';

    if (!is_dir($folder)) abort(404, 'Plugin not found.');

    // Build zip on-the-fly
    $zip = new \ZipArchive();
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $relative = 'ai-commerce-bot' . DIRECTORY_SEPARATOR . substr($file->getPathname(), strlen($folder) + 1);
            $zip->addFile($file->getPathname(), $relative);
        }
    }
    $zip->close();

    return response()->download($zipPath, 'ai-commerce-bot.zip', [
        'Content-Type' => 'application/zip',
    ]);
})->name('api.wp.plugin.download');