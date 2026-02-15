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
        $token = env('TELEGRAM_BOT_TOKEN'); // .env থেকে টোকেন নিন

        // বাটনে ক্লিক করলে (Callback Query)
        if (isset($data['callback_query'])) {
            $callbackData = $data['callback_query']['data'];
            $chatId = $data['callback_query']['message']['chat']['id'];
            $callbackId = $data['callback_query']['id'];

            Log::info("🔘 Telegram Button Clicked: $callbackData");

            // 1. STOP AI LOGIC
            if (Str::startsWith($callbackData, 'pause_ai_')) {
                // ID ক্লিন করা (খুবই গুরুত্বপূর্ণ)
                $senderId = trim(str_replace('pause_ai_', '', $callbackData));
                
                // ডাটাবেস আপডেট চেক
                $updatedCount = OrderSession::where('sender_id', (string)$senderId)
                    ->update(['is_human_agent_active' => true]);
                
                if ($updatedCount > 0) {
                    Log::info("✅ SUCCESS: AI Paused for User: $senderId");
                    $this->sendMessage($chatId, "🛑 AI Stopped for User ($senderId). You can chat manually now.", $token);
                } else {
                    Log::error("❌ FAIL: Could not find session for User: $senderId to Pause.");
                    $this->sendMessage($chatId, "⚠️ Error: Session not found for ID $senderId", $token);
                }
            }

            // 2. RESUME AI LOGIC
            if (Str::startsWith($callbackData, 'resume_ai_')) {
                $senderId = trim(str_replace('resume_ai_', '', $callbackData));
                
                $updatedCount = OrderSession::where('sender_id', (string)$senderId)
                    ->update(['is_human_agent_active' => false]);

                if ($updatedCount > 0) {
                    Log::info("✅ SUCCESS: AI Resumed for User: $senderId");
                    $this->sendMessage($chatId, "▶️ AI Restarted for User ($senderId).", $token);
                } else {
                    Log::error("❌ FAIL: Could not find session for User: $senderId to Resume.");
                    $this->sendMessage($chatId, "⚠️ Error: Session not found for ID $senderId", $token);
                }
            }

            // টেলিগ্রামের লোডিং আইকন বন্ধ করা
            Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                'callback_query_id' => $callbackId,
                'text' => 'Processing...'
            ]);
        }

        return response('OK', 200);
    }

    private function sendMessage($chatId, $text, $token)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}