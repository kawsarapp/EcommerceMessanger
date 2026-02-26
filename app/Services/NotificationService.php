<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * 🔥 টেলিগ্রাম অ্যাডমিনকে অটো অ্যালার্ট পাঠানো
     */
    public function sendTelegramAlert($client, $senderId, $message, $type = 'info')
    {
        if (!$client || empty($client->telegram_bot_token) || empty($client->telegram_chat_id)) {
            return;
        }

        // অ্যালার্ট আইকন সেট করা
        $icon = match ($type) {
            'danger' => '🛑',
            'warning' => '⚠️',
            'success' => '✅',
            default => '🔔'
        };

        $text = "{$icon} **Shop Alert: {$client->shop_name}**\n";
        $text .= "👤 User: `{$senderId}`\n";
        $text .= "📝 Msg: {$message}";

        // ইনলাইন বাটন (অ্যাকশনের জন্য)
        $keyboard = [
            'inline_keyboard' => [[
                ['text' => '⏸️ Pause AI', 'callback_data' => "pause_ai_{$senderId}"],
                ['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]
            ]]
        ];

        try {
            Http::post("https://api.telegram.org/bot{$client->telegram_bot_token}/sendMessage", [
                'chat_id' => $client->telegram_chat_id,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode($keyboard)
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram Alert Failed: " . $e->getMessage());
        }
    }

    /**
     * মেসেঞ্জারে ম্যানুয়াল রিপ্লাই পাঠানো (Bot হয়ে)
     */
    public function sendMessengerReply($client, $recipientId, $message)
    {
        try {
            Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$client->fb_page_token}", [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message]
            ]);
        } catch (\Exception $e) {
            Log::error("Messenger Reply Failed: " . $e->getMessage());
        }
    }


    //--------

    /**
     * ইনস্টাগ্রামে রিপ্লাই পাঠানো
     */
    public function sendInstagramReply($client, $recipientId, $message)
    {
        if (!$client || empty($client->fb_page_token)) return;

        try {
            // Instagram এর মেসেজও Page Token দিয়েই যায়, তবে Endpoint একই থাকে
            $response = Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$client->fb_page_token}", [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message]
            ]);

            if ($response->failed()) {
                Log::error("❌ Instagram Reply Failed: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("❌ Instagram Reply Error: " . $e->getMessage());
        }
    }

    //----------

    /**
     * Telegram Customer k AI Reply pathano
     */
    public function sendTelegramCustomerReply($token, $chatId, $message)
    {
        if (empty($token) || empty($chatId)) return;

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("❌ Telegram Customer Reply Error: " . $e->getMessage());
        }
    }

    //-----

















}