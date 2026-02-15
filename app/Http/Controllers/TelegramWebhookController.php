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
        $this->token = env('TELEGRAM_BOT_TOKEN');
    }

    public function handle(Request $request)
    {
        $data = $request->all();

        // 1. Button Click Handling (Callback Query)
        if (isset($data['callback_query'])) {
            $this->handleCallback($data['callback_query']);
            return response('OK', 200);
        }

        // 2. Text Message Handling (For Commands like /list)
        if (isset($data['message']['text'])) {
            $chatId = $data['message']['chat']['id'];
            $text = $data['message']['text'];

            if ($text === '/list' || $text === '/stopped') {
                $this->listStoppedUsers($chatId);
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
            
            // Update Message with Resume Button Only
            $this->editMessageButtons($chatId, $messageId, "🛑 **AI Stopped for User:** $senderId", [
                [['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]]
            ]);
        }

        // --- ACTION: RESUME AI ---
        elseif (Str::startsWith($callbackData, 'resume_ai_')) {
            $senderId = trim(str_replace('resume_ai_', '', $callbackData));
            
            OrderSession::where('sender_id', (string)$senderId)->update(['is_human_agent_active' => false]);
            
            $this->answerCallback($callbackId, "✅ AI Resumed!");

            // Update Message with Stop Button Only
            $this->editMessageButtons($chatId, $messageId, "✅ **AI Active for User:** $senderId", [
                [['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"]]
            ]);
        }

        // --- ACTION: LIST STOPPED USERS ---
        elseif ($callbackData === 'list_stopped_users') {
            $this->listStoppedUsers($chatId);
            $this->answerCallback($callbackId, "Loading list...");
        }
    }

    private function listStoppedUsers($chatId)
    {
        $users = OrderSession::where('is_human_agent_active', true)
            ->limit(10)
            ->get(['sender_id', 'updated_at']);

        if ($users->isEmpty()) {
            $this->sendMessage($chatId, "✅ No users are currently stopped. AI is active for everyone.");
            return;
        }

        $msg = "📋 **Stopped Users List:**\n";
        $keyboard = [];

        foreach ($users as $user) {
            $msg .= "👤 ID: `{$user->sender_id}`\n";
            // Add individual Resume button for each user in the list
            $keyboard[] = [['text' => "▶️ Resume {$user->sender_id}", 'callback_data' => "resume_ai_{$user->sender_id}"]];
        }

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

    private function editMessageButtons($chatId, $messageId, $text, $keyboard)
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