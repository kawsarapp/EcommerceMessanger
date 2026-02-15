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
    /**
     * ডাইনামিক হ্যান্ডলার: {token} দিয়ে সেলার চিহ্নিত করা হবে
     */
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

        // ২. বাটন ক্লিক হ্যান্ডলিং (Callback Query - Inline Buttons)
        if (isset($data['callback_query'])) {
            $this->handleCallback($data['callback_query'], $client);
            return response('OK', 200);
        }

        // ৩. টেক্সট মেসেজ ও মেনু হ্যান্ডলিং
        if (isset($data['message']['text'])) {
            $chatId = $data['message']['chat']['id'];
            $text = $data['message']['text'];

            // 🔒 সিকিউরিটি চেক: শুধু ওই সেলারের চ্যাট আইডি থেকেই এক্সেস পাবে
            // গ্রুপ চ্যাটের জন্য আমরা স্ট্রিক্ট টাইপ চেক (string conversion) করছি
            if ((string)$chatId !== (string)$adminChatId) {
                $this->sendMessage($token, $chatId, "⛔ Unauthorized Access. This bot belongs to {$client->shop_name}.");
                return response('OK', 200);
            }

            // 📋 মেনু কমান্ড হ্যান্ডলিং
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
                
                case '❌ বাতিল অর্ডার': // নতুন ফিচার
                    $this->showCancelledOrders($token, $chatId, $client->id);
                    break;

                case '🚚 শিপিং স্ট্যাটাস': // নতুন ফিচার
                    $this->showShippingStatus($token, $chatId, $client->id);
                    break;

                case '⚙️ সেটিংস / স্টপ লিস্ট':
                    $this->showStoppedUsers($token, $chatId, $client->id);
                    break;

                default:
                    // অন্য কিছু লিখলে মেনু শো করবে না (যাতে সাধারণ চ্যাটিং এ সমস্যা না হয়)
                    // তবে চাইলে এখানেও showMainMenu কল করতে পারেন
                    break;
            }
        }

        return response('OK', 200);
    }

    // ==========================================
    // ⚙️ SYSTEM HANDLERS (Callback & Logic)
    // ==========================================

    private function handleCallback($callback, $client)
    {
        $callbackData = $callback['data'];
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $callbackId = $callback['id'];
        $token = $client->telegram_bot_token;

        Log::info("🔘 Button Click: $callbackData");

        // --- STOP AI ---
        if (Str::startsWith($callbackData, 'pause_ai_')) {
            $senderId = trim(str_replace('pause_ai_', '', $callbackData));
            
            // SAAS Fix: শুধু এই ক্লায়েন্টের সেশন আপডেট হবে
            OrderSession::where('client_id', $client->id)
                ->where('sender_id', $senderId)
                ->update(['is_human_agent_active' => true]);
            
            $this->answerCallback($token, $callbackId, "🛑 AI Stopped!");
            
            // বাটন আপডেট
            $this->updateMessageButtons($token, $chatId, $messageId, "🛑 **AI Stopped for:** `$senderId`", [
                [
                    ['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"],
                    ['text' => '📋 Stopped List', 'callback_data' => "list_stopped_users"]
                ]
            ]);
        }

        // --- RESUME AI ---
        elseif (Str::startsWith($callbackData, 'resume_ai_')) {
            $senderId = trim(str_replace('resume_ai_', '', $callbackData));
            
            OrderSession::where('client_id', $client->id)
                ->where('sender_id', $senderId)
                ->update(['is_human_agent_active' => false]);
            
            $this->answerCallback($token, $callbackId, "✅ AI Resumed!");
            
            // বাটন আপডেট
            $this->updateMessageButtons($token, $chatId, $messageId, "✅ **AI Active for:** `$senderId`", [
                [
                    ['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"],
                    ['text' => '📋 Stopped List', 'callback_data' => "list_stopped_users"]
                ]
            ]);
        }

        // --- LIST STOPPED USERS ---
        elseif ($callbackData === 'list_stopped_users') {
            $this->answerCallback($token, $callbackId, "Loading list...");
            $this->showStoppedUsers($token, $chatId, $client->id);
        }
    }

    // ==========================================
    // 📊 DASHBOARD FEATURES (SAAS Enabled)
    // ==========================================

    private function showMainMenu($token, $chatId)
    {
        $keyboard = [
            ['📊 আজকের রিপোর্ট', '📦 পেন্ডিং অর্ডার'],
            ['🚚 শিপিং স্ট্যাটাস', '❌ বাতিল অর্ডার'],
            ['⚙️ সেটিংস / স্টপ লিস্ট']
        ];

        $this->sendMessageWithReplyKeyboard($token, $chatId, "👋 স্বাগতম অ্যাডমিন প্যানেলে! নিচের অপশনগুলো চেক করুন:", $keyboard);
    }

    private function showDailyReport($token, $chatId, $clientId)
    {
        $today = Carbon::today();
        
        $totalOrders = Order::where('client_id', $clientId)->whereDate('created_at', $today)->count();
        $totalSales = Order::where('client_id', $clientId)
            ->whereDate('created_at', $today)
            ->where('order_status', '!=', 'cancelled')
            ->sum('total_amount');
        
        $processing = Order::where('client_id', $clientId)->whereDate('created_at', $today)->where('order_status', 'processing')->count();
        $completed = Order::where('client_id', $clientId)->whereDate('created_at', $today)->where('order_status', 'completed')->count();

        $msg = "📅 **আজকের রিপোর্ট (" . $today->format('d M') . ")**\n\n";
        $msg .= "💰 **মোট সেল:** " . number_format($totalSales) . " Tk\n";
        $msg .= "📦 **মোট অর্ডার:** $totalOrders টি\n";
        $msg .= "⏳ **প্রসেসিং:** $processing টি\n";
        $msg .= "✅ **কমপ্লিট:** $completed টি\n";

        $this->sendMessage($token, $chatId, $msg);
    }

    private function showPendingOrders($token, $chatId, $clientId)
    {
        $orders = Order::where('client_id', $clientId)
            ->where('order_status', 'processing')
            ->latest()
            ->take(5)
            ->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($token, $chatId, "✅ কোনো পেন্ডিং অর্ডার নেই।");
            return;
        }

        $msg = "📦 **সর্বশেষ ৫টি পেন্ডিং অর্ডার:**\n\n";
        foreach ($orders as $order) {
            $msg .= "#{$order->id} - {$order->customer_name} ({$order->total_amount} Tk)\n📞 {$order->customer_phone}\n------------------\n";
        }
        $this->sendMessage($token, $chatId, $msg);
    }

    private function showCancelledOrders($token, $chatId, $clientId)
    {
        $today = Carbon::today();
        $count = Order::where('client_id', $clientId)
            ->whereDate('created_at', $today)
            ->where('order_status', 'cancelled')
            ->count();
            
        $msg = "❌ **আজকের বাতিল অর্ডার:** {$count} টি\n\n";
        
        if ($count > 0) {
            $orders = Order::where('client_id', $clientId)
                ->whereDate('created_at', $today)
                ->where('order_status', 'cancelled')
                ->latest()
                ->take(5)
                ->get();
                
            foreach ($orders as $order) {
                $msg .= "#{$order->id} - {$order->customer_name} ({$order->customer_phone})\n";
            }
        }
        
        $this->sendMessage($token, $chatId, $msg);
    }

    private function showShippingStatus($token, $chatId, $clientId)
    {
        // Shipped status চেক করা
        $shipping = Order::where('client_id', $clientId)
            ->where('order_status', 'shipped')
            ->count();
            
        $msg = "🚚 **বর্তমানে শিপিং-এ আছে:** {$shipping} টি অর্ডার।";
        $this->sendMessage($token, $chatId, $msg);
    }

    private function showStoppedUsers($token, $chatId, $clientId)
    {
        // SAAS Logic: শুধু এই ক্লায়েন্টের ইউজারদের দেখাবে
        $users = OrderSession::where('client_id', $clientId)
            ->where('is_human_agent_active', true)
            ->limit(10)
            ->get();

        if ($users->isEmpty()) {
            $this->sendMessage($token, $chatId, "✅ **সবাই একটিভ আছে।** কোনো ইউজার স্টপ নেই।");
            return;
        }

        $msg = "📋 **AI বন্ধ থাকা ইউজার লিস্ট:**\n\n";
        $keyboard = [];

        foreach ($users as $user) {
            $info = $user->customer_info ?? [];
            $name = $info['name'] ?? 'Unknown';
            $phone = $info['phone'] ?? 'No Phone';
            $id = $user->sender_id;

            $msg .= "👤 $name ($phone)\n";
            $keyboard[] = [['text' => "▶️ Resume ($name)", 'callback_data' => "resume_ai_{$id}"]];
        }

        // Inline বাটন সহ লিস্ট পাঠানো
        $this->sendMessageWithInlineKeyboard($token, $chatId, $msg, $keyboard);
    }

    // ==========================================
    // 📨 API HELPERS (Dynamic Token Support)
    // ==========================================

    private function sendMessage($token, $chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);
    }

    private function sendMessageWithReplyKeyboard($token, $chatId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false
            ])
        ]);
    }

    private function sendMessageWithInlineKeyboard($token, $chatId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    private function updateMessageButtons($token, $chatId, $messageId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$token}/editMessageText", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }

    private function answerCallback($token, $callbackId, $text)
    {
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackId,
            'text' => $text
        ]);
    }
}