<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use App\Services\Telegram\TelegramWebhookService;

class TelegramWebhookController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramWebhookService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * ডাইনামিক হ্যান্ডলার: {token} দিয়ে সেলার চিহ্নিত করা হবে
     */
    public function handle(Request $request, $token)
    {
        // ১. টোকেন দিয়ে সেলার/ক্লায়েন্ট খুঁজে বের করা
        $client = Client::where('telegram_bot_token', $token)->first();

        if (!$client) {
            Log::error("❌ Invalid Telegram Token received in webhook: $token");
            return response('Unauthorized', 401);
        }

        $data = $request->all();

        // ২. সব প্রসেসিং সার্ভিসের কাছে পাঠিয়ে দেওয়া হলো
        $this->telegramService->processPayload($data, $token, $client);

        return response('OK', 200);
    }
}