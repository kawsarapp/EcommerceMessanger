<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderSession;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TelegramWebhookController extends Controller
{
    private $token;
    private $adminChatId;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $this->adminChatId = config('services.telegram.chat_id') ?? env('TELEGRAM_CHAT_ID');
    }

    public function handle(Request $request)
    {
        $data = $request->all();

        if (!$this->token) return response('Token Missing', 500);

        // 1. BUTTON CLICK HANDLING (Callback Query - Inline Buttons)
        if (isset($data['callback_query'])) {
            $this->handleCallback($data['callback_query']);
            return response('OK', 200);
        }

        // 2. TEXT & MENU HANDLING
        if (isset($data['message']['text'])) {
            $chatId = $data['message']['chat']['id'];
            $text = $data['message']['text'];

            // সিকিউরিটি চেক: শুধু অ্যাডমিন এক্সেস পাবে
            if ((string)$chatId !== (string)$this->adminChatId) {
                $this->sendMessage($chatId, "⛔ Unauthorized Access.");
                return response('OK', 200);
            }

            // মেনু কমান্ড হ্যান্ডলিং
            switch ($text) {
                case '/start':
                case '/menu':
                    $this->showMainMenu($chatId);
                    break;

                case '📊 আজকের রিপোর্ট':
                    $this->showDailyReport($chatId);
                    break;

                case '📦 পেন্ডিং অর্ডার':
                    $this->showPendingOrders($chatId);
                    break;
                
                case '❌ বাতিল অর্ডার':
                    $this->showCancelledOrders($chatId);
                    break;

                case '🚚 শিপিং স্ট্যাটাস':
                    $this->showShippingStatus($chatId);
                    break;

                case '⚙️ সেটিংস / স্টপ লিস্ট':
                    $this->showStoppedUsers($chatId);
                    break;

                default:
                    // অন্য কিছু লিখলে মেনু শো করবে
                    //$this->showMainMenu($chatId);
                    break;
            }
        }

        return response('OK', 200);
    }

    // ==========================================
    // 📊 DASHBOARD LOGIC METHODS
    // ==========================================

    private function showMainMenu($chatId)
    {
        $keyboard = [
            ['📊 আজকের রিপোর্ট', '📦 পেন্ডিং অর্ডার'],
            ['🚚 শিপিং স্ট্যাটাস', '❌ বাতিল অর্ডার'],
            ['⚙️ সেটিংস / স্টপ লিস্ট']
        ];

        $this->sendMessageWithReplyKeyboard($chatId, "👋 স্বাগতম অ্যাডমিন প্যানেলে! নিচের অপশনগুলো থেকে বেছে নিন:", $keyboard);
    }

    private function showDailyReport($chatId)
    {
        $today = Carbon::today();
        
        $totalOrders = Order::whereDate('created_at', $today)->count();
        $totalSales = Order::whereDate('created_at', $today)
            ->where('order_status', '!=', 'cancelled')
            ->sum('total_amount');
        
        $processing = Order::whereDate('created_at', $today)->where('order_status', 'processing')->count();
        $completed = Order::whereDate('created_at', $today)->where('order_status', 'completed')->count();

        $msg = "📅 **আজকের রিপোর্ট (" . $today->format('d M') . ")**\n\n";
        $msg .= "💰 **মোট সেল:** " . number_format($totalSales) . " Tk\n";
        $msg .= "📦 **মোট অর্ডার:** $totalOrders টি\n";
        $msg .= "⏳ **প্রসেসিং:** $processing টি\n";
        $msg .= "✅ **কমপ্লিট:** $completed টি\n";

        $this->sendMessage($chatId, $msg);
    }

    private function showPendingOrders($chatId)
    {
        $orders = Order::where('order_status', 'processing')->latest()->take(5)->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($chatId, "✅ কোনো পেন্ডিং অর্ডার নেই।");
            return;
        }

        $msg = "📦 **সর্বশেষ ৫টি পেন্ডিং অর্ডার:**\n\n";
        foreach ($orders as $order) {
            $msg .= "#{$order->id} - {$order->customer_name} ({$order->total_amount} Tk)\n📞 {$order->customer_phone}\n------------------\n";
        }
        $this->sendMessage($chatId, $msg);
    }

    private function showCancelledOrders($chatId)
    {
        $count = Order::whereDate('created_at', Carbon::today())
            ->where('order_status', 'cancelled')->count();
            
        $msg = "❌ **আজকের বাতিল অর্ডার:** {$count} টি\n\n";
        
        if ($count > 0) {
            $orders = Order::whereDate('created_at', Carbon::today())
                ->where('order_status', 'cancelled')->latest()->take(5)->get();
            foreach ($orders as $order) {
                $msg .= "#{$order->id} - {$order->customer_phone}\n";
            }
        }
        
        $this->sendMessage($chatId, $msg);
    }

    private function showShippingStatus($chatId)
    {
        $shipping = Order::where('order_status', 'shipped')->count();
        $msg = "🚚 **বর্তমানে শিপিং-এ আছে:** {$shipping} টি অর্ডার।";
        $this->sendMessage($chatId, $msg);
    }

    // ==========================================
    // ⚙️ SYSTEM HANDLERS (Callback & Logic)
    // ==========================================

    private function handleCallback($callback)
    {
        $callbackData = $callback['data'];
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $callbackId = $callback['id'];

        Log::info("🔘 Button Click: $callbackData");

        // --- STOP AI ---
        if (Str::startsWith($callbackData, 'pause_ai_')) {
            $senderId = trim(str_replace('pause_ai_', '', $callbackData));
            OrderSession::where('sender_id', (string)$senderId)->update(['is_human_agent_active' => true]);
            $this->answerCallback($callbackId, "🛑 AI Stopped!");
            $this->updateMessageButtons($chatId, $messageId, "🛑 **AI Stopped for:** `$senderId`", [
                [['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]]
            ]);
        }

        // --- RESUME AI ---
        elseif (Str::startsWith($callbackData, 'resume_ai_')) {
            $senderId = trim(str_replace('resume_ai_', '', $callbackData));
            OrderSession::where('sender_id', (string)$senderId)->update(['is_human_agent_active' => false]);
            $this->answerCallback($callbackId, "✅ AI Resumed!");
            $this->updateMessageButtons($chatId, $messageId, "✅ **AI Active for:** `$senderId`", [
                [['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"]]
            ]);
        }

        // --- LIST STOPPED USERS ---
        elseif ($callbackData === 'list_stopped_users') {
            $this->answerCallback($callbackId, "Loading list...");
            $this->showStoppedUsers($chatId);
        }
    }

    private function showStoppedUsers($chatId)
    {
        $users = OrderSession::where('is_human_agent_active', true)->limit(10)->get();

        if ($users->isEmpty()) {
            $this->sendMessage($chatId, "✅ **সবাই একটিভ আছে।** কোনো ইউজার স্টপ নেই।");
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
        $this->sendMessageWithInlineKeyboard($chatId, $msg, $keyboard);
    }

    // ==========================================
    // 📨 API HELPERS
    // ==========================================

    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);
    }

    // ফিক্সড মেনু বাটন (Fixed Keyboard)
    private function sendMessageWithReplyKeyboard($chatId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false // এটি false রাখলে মেনু সবসময় থাকবে
            ])
        ]);
    }

    // ইনলাইন বাটন (Inline Keyboard - মেসেজের সাথে)
    private function sendMessageWithInlineKeyboard($chatId, $text, $keyboard)
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