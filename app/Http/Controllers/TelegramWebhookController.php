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
        $token = env('TELEGRAM_BOT_TOKEN');

        // বাটনে ক্লিক করলে যা আসবে (Callback Query)
        if (isset($data['callback_query'])) {
            $callbackData = $data['callback_query']['data'];
            $chatId = $data['callback_query']['message']['chat']['id'];
            $callbackId = $data['callback_query']['id'];

            // AI পজ করার লজিক
            if (Str::startsWith($callbackData, 'pause_ai_')) {
                $senderId = str_replace('pause_ai_', '', $callbackData);
                OrderSession::where('sender_id', $senderId)->update(['is_human_agent_active' => true]);
                
                $this->sendMessage($chatId, "⏸️ ইউজার ($senderId) এর জন্য AI বন্ধ করা হয়েছে। এখন আপনি সরাসরি কথা বলুন।", $token);
            }

            // AI পুনরায় চালু করার লজিক
            if (Str::startsWith($callbackData, 'resume_ai_')) {
                $senderId = str_replace('resume_ai_', '', $callbackData);
                OrderSession::where('sender_id', $senderId)->update(['is_human_agent_active' => false]);
                
                $this->sendMessage($chatId, "▶️ ইউজার ($senderId) এর জন্য AI পুনরায় চালু করা হয়েছে।", $token);
            }

            // টেলিগ্রামের লোডিং আইকন বন্ধ করা
            Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", ['callback_query_id' => $callbackId]);
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
