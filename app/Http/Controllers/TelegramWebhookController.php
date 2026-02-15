<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\OrderSession;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, $token)
    {
        // ১. টোকেন দিয়ে সেলার/ক্লায়েন্ট খুঁজে বের করা
        $client = Client::where('telegram_bot_token', $token)->first();

        if (!$client) {
            Log::error("❌ Invalid Telegram Token received in webhook: $token");
            return response('Unauthorized', 401);
        }

        $adminChatId = $client->telegram_chat_id;
        $data = $request->all();

        // ২. বাটন ক্লিক হ্যান্ডলিং (Callback Query)
        if (isset($data['callback_query'])) {
            $this->handleCallback($data['callback_query'], $client);
            return response('OK', 200);
        }

        // ৩. টেক্সট মেসেজ হ্যান্ডলিং (Dashboard)
        if (isset($data['message']['text'])) {
            $chatId = $data['message']['chat']['id'];
            $text = $data['message']['text'];

            // সিকিউরিটি চেক: শুধু ওই সেলারের চ্যাট আইডি থেকেই এক্সেস পাবে
            if ((string)$chatId !== (string)$adminChatId) {
                $this->sendMessage($token, $chatId, "⛔ Unauthorized Access. This bot belongs to {$client->shop_name}.");
                return response('OK', 200);
            }

            // মেনু লজিক
            switch ($text) {
                case '/start':
                case '/menu':
                    $this->showMainMenu($token, $chatId);
                    break;
                case '📊 আজকের রিপোর্ট':
                    $this->showDailyReport($token, $chatId, $client->id);
                    break;
                case '📦 পেন্ডিং অর্ডার':
                    $this->showPendingOrders($token, $chatId, $client->id);
                    break;
                case '⚙️ সেটিংস / স্টপ লিস্ট':
                    $this->showStoppedUsers($token, $chatId, $client->id);
                    break;
            }
        }

        return response('OK', 200);
    }

    // --- Callback Handler ---
    private function handleCallback($callback, $client)
    {
        $callbackData = $callback['data'];
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $callbackId = $callback['id'];
        $token = $client->telegram_bot_token;

        // STOP AI
        if (Str::startsWith($callbackData, 'pause_ai_')) {
            $senderId = trim(str_replace('pause_ai_', '', $callbackData));
            // সেলার আইডি দিয়ে ফিল্টার করা জরুরি
            OrderSession::where('client_id', $client->id)->where('sender_id', $senderId)->update(['is_human_agent_active' => true]);
            
            $this->answerCallback($token, $callbackId, "🛑 AI Stopped!");
            $this->updateMessageButtons($token, $chatId, $messageId, "🛑 **AI Stopped for:** `$senderId`", [
                [['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]]
            ]);
        }
        // RESUME AI
        elseif (Str::startsWith($callbackData, 'resume_ai_')) {
            $senderId = trim(str_replace('resume_ai_', '', $callbackData));
            OrderSession::where('client_id', $client->id)->where('sender_id', $senderId)->update(['is_human_agent_active' => false]);
            
            $this->answerCallback($token, $callbackId, "✅ AI Resumed!");
            $this->updateMessageButtons($token, $chatId, $messageId, "✅ **AI Active for:** `$senderId`", [
                [['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"]]
            ]);
        }
        // LIST
        elseif ($callbackData === 'list_stopped_users') {
            $this->showStoppedUsers($token, $chatId, $client->id);
            $this->answerCallback($token, $callbackId, "Loading...");
        }
    }

    // --- Helper Methods (Now accepts Token) ---

    private function showDailyReport($token, $chatId, $clientId)
    {
        $today = Carbon::today();
        $totalSales = Order::where('client_id', $clientId)->whereDate('created_at', $today)->where('order_status', '!=', 'cancelled')->sum('total_amount');
        $totalOrders = Order::where('client_id', $clientId)->whereDate('created_at', $today)->count();
        
        $this->sendMessage($token, $chatId, "📅 **আজকের রিপোর্ট:**\n💰 সেল: {$totalSales} Tk\n📦 অর্ডার: {$totalOrders} টি");
    }

    private function showPendingOrders($token, $chatId, $clientId)
    {
        $orders = Order::where('client_id', $clientId)->where('order_status', 'processing')->latest()->take(5)->get();
        if($orders->isEmpty()) {
            $this->sendMessage($token, $chatId, "✅ কোনো পেন্ডিং অর্ডার নেই।");
            return;
        }
        $msg = "📦 **পেন্ডিং অর্ডার:**\n";
        foreach($orders as $o) $msg .= "#{$o->id} - {$o->customer_name} ({$o->total_amount} Tk)\n";
        $this->sendMessage($token, $chatId, $msg);
    }

    private function showStoppedUsers($token, $chatId, $clientId)
    {
        $users = OrderSession::where('client_id', $clientId)->where('is_human_agent_active', true)->get();
        if($users->isEmpty()) {
            $this->sendMessage($token, $chatId, "✅ সবাই একটিভ আছে।");
            return;
        }
        $keyboard = [];
        foreach($users as $u) {
            $name = $u->customer_info['name'] ?? 'User';
            $keyboard[] = [['text' => "▶️ Resume ($name)", 'callback_data' => "resume_ai_{$u->sender_id}"]];
        }
        $this->sendMessageWithInlineKeyboard($token, $chatId, "📋 **AI বন্ধ থাকা ইউজার:**", $keyboard);
    }

    private function showMainMenu($token, $chatId)
    {
        $keyboard = [['📊 আজকের রিপোর্ট', '📦 পেন্ডিং অর্ডার'], ['⚙️ সেটিংস / স্টপ লিস্ট']];
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => "মেনু সিলেক্ট করুন:",
            'reply_markup' => json_encode(['keyboard' => $keyboard, 'resize_keyboard' => true])
        ]);
    }

    // API Calls
    private function sendMessage($token, $chatId, $text) {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown']);
    }
    private function sendMessageWithInlineKeyboard($token, $chatId, $text, $keyboard) {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown', 'reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }
    private function updateMessageButtons($token, $chatId, $messageId, $text, $keyboard) {
        Http::post("https://api.telegram.org/bot{$token}/editMessageText", ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text, 'parse_mode' => 'Markdown', 'reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }
    private function answerCallback($token, $callbackId, $text) {
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", ['callback_query_id' => $callbackId, 'text' => $text]);
    }
}