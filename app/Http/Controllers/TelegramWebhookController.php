<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    private $token;

    public function __construct()
    {
        // Constructor-এ টোকেন সেট করা ভালো প্র্যাকটিস
        $this->token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
    }

    public function handle(Request $request)
    {
        $data = $request->all();

        if (!$this->token) {
            Log::error("❌ Telegram Token Missing!");
            return response('Token Missing', 500);
        }

        // 1. BUTTON CLICK HANDLING (Callback Query)
        if (isset($data['callback_query'])) {
            $this->handleCallback($data['callback_query']);
            return response('OK', 200);
        }

        // 2. TEXT COMMAND HANDLING (Optional: /list কমান্ড দিলে লিস্ট দেখাবে)
        if (isset($data['message']['text'])) {
            $text = $data['message']['text'];
            $chatId = $data['message']['chat']['id'];

            if ($text === '/list' || $text === '/stopped') {
                $this->showStoppedUsers($chatId);
            }
        }

        return response('OK', 200);
    }

    private function handleCallback($callback)
    {
        $callbackData = $callback['data'];
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $callbackId = $callback['id'];

        Log::info("🔘 Telegram Button Clicked: $callbackData");

        // --- ACTION: STOP AI ---
        if (Str::startsWith($callbackData, 'pause_ai_')) {
            $senderId = trim(str_replace('pause_ai_', '', $callbackData));
            
            OrderSession::where('sender_id', (string)$senderId)->update(['is_human_agent_active' => true]);
            
            $this->answerCallback($callbackId, "🛑 AI Stopped!");
            
            // Update Message: Show 'Resume' & 'List' buttons
            $this->updateMessageButtons($chatId, $messageId, "🛑 **AI Stopped for User:** `$senderId`\nYou can chat manually now.", [
                [
                    ['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"],
                    ['text' => '📋 Stopped List', 'callback_data' => "list_stopped_users"]
                ]
            ]);
        }

        // --- ACTION: RESUME AI ---
        elseif (Str::startsWith($callbackData, 'resume_ai_')) {
            $senderId = trim(str_replace('resume_ai_', '', $callbackData));
            
            OrderSession::where('sender_id', (string)$senderId)->update(['is_human_agent_active' => false]);
            
            $this->answerCallback($callbackId, "✅ AI Resumed!");

            // Update Message: Show 'Stop' & 'List' buttons
            $this->updateMessageButtons($chatId, $messageId, "✅ **AI Active for User:** `$senderId`", [
                [
                    ['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"],
                    ['text' => '📋 Stopped List', 'callback_data' => "list_stopped_users"]
                ]
            ]);
        }

        // --- ACTION: SHOW STOPPED LIST ---
        elseif ($callbackData === 'list_stopped_users') {
            $this->answerCallback($callbackId, "Loading list...");
            $this->showStoppedUsers($chatId);
        }
    }

    private function showStoppedUsers($chatId)
    {
        // ১. যারা পজ করা আছে তাদের বের করা (নাম ও ফোন সহ)
        $users = OrderSession::where('is_human_agent_active', true)
            ->limit(10) // ১০ জনের বেশি দেখালে লিস্ট বড় হয়ে যাবে
            ->get();

        if ($users->isEmpty()) {
            $this->sendMessage($chatId, "✅ **No users are currently stopped.**\nAI is active for everyone.");
            return;
        }

        $msg = "📋 **Stopped Users List:**\n\n";
        $keyboard = [];

        foreach ($users as $user) {
            // ডাটাবেস থেকে নাম/ফোন বের করা (যদি থাকে)
            $info = $user->customer_info ?? [];
            $name = $info['name'] ?? 'Unknown';
            $phone = $info['phone'] ?? 'No Phone';
            $id = $user->sender_id;

            $msg .= "👤 **Name:** $name\n📞 **Phone:** $phone\n🆔 `$id`\n------------------\n";
            
            // প্রতি ইউজারের জন্য আলাদা Resume বাটন
            $keyboard[] = [['text' => "▶️ Resume ($name)", 'callback_data' => "resume_ai_{$id}"]];
        }

        // বাটন সহ লিস্ট পাঠানো
        $this->sendMessageWithKeyboard($chatId, $msg, $keyboard);
    }

    // --- HELPER METHODS ---

    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);
    }

    private function sendMessageWithKeyboard($chatId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    private function updateMessageButtons($chatId, $messageId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$this->token}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    private function answerCallback($callbackId, $text)
    {
        Http::post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
            'callback_query_id' => $callbackId,
            'text' => $text
        ]);
    }
}