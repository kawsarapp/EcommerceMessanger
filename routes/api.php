<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Api\StoreSyncController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\WebsiteConnectorController;

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
 * (প্রতিটি সেলারের জন্য আলাদা টোকেন দিয়ে এটি কাজ করবে)
 */
Route::post('/telegram/webhook/{token}', [TelegramWebhookController::class, 'handle'])
     ->name('api.telegram.webhook');


// ===========================================
// 🛠️ ADDITIONAL API HELPERS (Optional)
// ===========================================

// যদি ভবিষ্যতে মোবাইল অ্যাপ বা অন্য কোনো সিস্টেমের জন্য ডাটা লাগে
Route::prefix('v1')->group(function () {
    // এখানে আপনার অন্যান্য API এন্ডপয়েন্ট রাখতে পারেন
});

Route::post('/v1/whatsapp/status', [WhatsAppWebhookController::class, 'updateStatus']);
Route::post('/v1/whatsapp/receive', [WhatsAppWebhookController::class, 'receiveMessage']);
Route::post('/v1/import-products', [StoreSyncController::class, 'pushProducts']);

// ===========================================
// 🔌 UNIVERSAL WEBSITE CONNECTOR
// ===========================================
// Any website (WordPress, Shopify, custom HTML, React) can use these.
Route::prefix('connector')->group(function () {
    Route::get('/verify',        [WebsiteConnectorController::class, 'verify']);
    Route::post('/sync-products',[WebsiteConnectorController::class, 'syncProducts']);
    Route::get('/js-snippet',    [WebsiteConnectorController::class, 'getJsSnippet']);
});

// Courier Webhook Route (Multi-tenant)
Route::post('/webhook/courier/{client_id}/{courier_name}', [\App\Http\Controllers\CourierWebhookController::class, 'handle'])->name('webhook.courier');