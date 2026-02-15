<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        
        // ⚠️ FIX: env() ব্যবহার করবেন না, config() ব্যবহার করুন
        // যদি config ফাইলে না থাকে, তবে সরাসরি env() ফলব্যাক হিসেবে কাজ করবে
        $token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');

        if (!$token) {
            Log::error("❌ Telegram Token Missing in Controller!");
            return response('Token Missing', 500);
        }

        // বাটনে ক্লিক করলে (Callback Query)
        if (isset($data['callback_query'])) {
            $callbackData = $data['callback_query']['data'];
            $chatId = $data['callback_query']['message']['chat']['id'];
            $callbackId = $data['callback_query']['id']; // এটি জরুরি লোডিং বন্ধ করার জন্য
            $messageId = $data['callback_query']['message']['message_id']; // মেসেজ আপডেটের জন্য

            Log::info("🔘 Telegram Button Clicked: $callbackData");

            // 1. STOP AI LOGIC
            if (Str::startsWith($callbackData, 'pause_ai_')) {
                $senderId = trim(str_replace('pause_ai_', '', $callbackData));
                
                OrderSession::where('sender_id', (string)$senderId)->update(['is_human_agent_active' => true]);
                
                // বাটন লোডিং বন্ধ করুন (Answer Callback)
                $this->answerCallback($token, $callbackId, "🛑 AI Stopped!");

                // বাটন আপডেট করে দিন (Stop বাটন সরিয়ে Resume বাটন দেখান)
                $this->updateMessageButtons($token, $chatId, $messageId, "🛑 **AI Stopped for User:** $senderId", [
                    [['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]]
                ]);
            }

            // 2. RESUME AI LOGIC
            if (Str::startsWith($callbackData, 'resume_ai_')) {
                $senderId = trim(str_replace('resume_ai_', '', $callbackData));
                
                OrderSession::where('sender_id', (string)$senderId)->update(['is_human_agent_active' => false]);

                // বাটন লোডিং বন্ধ করুন
                $this->answerCallback($token, $callbackId, "✅ AI Resumed!");

                // বাটন আপডেট করে দিন (Resume বাটন সরিয়ে Stop বাটন দেখান)
                $this->updateMessageButtons($token, $chatId, $messageId, "✅ **AI Active for User:** $senderId", [
                    [['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"]]
                ]);
            }
        }

        return response('OK', 200);
    }

    // ✅ লোডিং আইকন বন্ধ করার ফাংশন
    private function answerCallback($token, $callbackId, $text)
    {
        $response = Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackId,
            'text' => $text
        ]);
        
        if ($response->failed()) {
            Log::error("❌ Failed to answer callback: " . $response->body());
        }
    }

    // ✅ বাটন আপডেট করার ফাংশন (যাতে Stop চাপলে Resume বাটন আসে)
    private function updateMessageButtons($token, $chatId, $messageId, $newText, $newKeyboard)
    {
        Http::post("https://api.telegram.org/bot{$token}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $newText,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $newKeyboard])
        ]);
    }
}