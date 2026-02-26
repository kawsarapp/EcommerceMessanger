<?php

namespace App\Services\Telegram;

use App\Models\OrderSession;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TelegramAdminService
{
    protected $api;

    public function __construct(TelegramApiService $api)
    {
        $this->api = $api;
    }

    public function handleCallback($callback, $client)
    {
        $callbackData = $callback['data'];
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $callbackId = $callback['id'];
        $token = $client->telegram_bot_token;

        Log::info("🔘 Button Click: $callbackData");

        if (Str::startsWith($callbackData, 'pause_ai_')) {
            $senderId = trim(str_replace('pause_ai_', '', $callbackData));
            OrderSession::where('client_id', $client->id)->where('sender_id', $senderId)->update(['is_human_agent_active' => true]);
            $this->api->answerCallback($token, $callbackId, "🛑 AI Stopped!");
            $this->api->updateMessageButtons($token, $chatId, $messageId, "🛑 **AI Stopped for:** `$senderId`", [
                [['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"], ['text' => '📋 Stopped List', 'callback_data' => "list_stopped_users"]]
            ]);
        } elseif (Str::startsWith($callbackData, 'resume_ai_')) {
            $senderId = trim(str_replace('resume_ai_', '', $callbackData));
            OrderSession::where('client_id', $client->id)->where('sender_id', $senderId)->update(['is_human_agent_active' => false]);
            $this->api->answerCallback($token, $callbackId, "✅ AI Resumed!");
            $this->api->updateMessageButtons($token, $chatId, $messageId, "✅ **AI Active for:** `$senderId`", [
                [['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"], ['text' => '📋 Stopped List', 'callback_data' => "list_stopped_users"]]
            ]);
        } elseif (Str::startsWith($callbackData, 'status_')) {
            $parts = explode('_', $callbackData);
            if(count($parts) == 3) {
                $status = $parts[1]; 
                $orderId = $parts[2];
                $order = Order::where('client_id', $client->id)->find($orderId);
                if($order) {
                    $order->update(['order_status' => $status]);
                    $this->api->answerCallback($token, $callbackId, "Order Marked as " . ucfirst($status));
                    $this->api->sendMessage($token, $chatId, "✅ **Order #{$orderId} Updated!**\nNew Status: " . strtoupper($status));
                }
            }
        } elseif ($callbackData === 'list_stopped_users') {
            $this->api->answerCallback($token, $callbackId, "Loading list...");
            $this->showStoppedUsers($token, $chatId, $client->id);
        }
    }

    public function showMainMenu($token, $chatId, $shopName)
    {
        $keyboard = [
            ['📊 আজকের রিপোর্ট', '📦 পেন্ডিং অর্ডার'],
            ['🚚 শিপিং স্ট্যাটাস', '❌ বাতিল অর্ডার'],
            ['⚙️ সেটিংস / স্টপ লিস্ট']
        ];
        $msg = "👋 **স্বাগতম, {$shopName} অ্যাডমিন!**\n\n👇 **শর্টকাট কমান্ড:**\n`/order [ID]` - অর্ডার স্ট্যাটাস বদলান\n`/stock [Name]` - প্রডাক্ট স্টক চেক\n`/search [Phone]` - কাস্টমার হিস্ট্রি\n`/reply [ID] [Text]` - কাস্টমারকে মেসেজ";
        $this->api->sendMessageWithReplyKeyboard($token, $chatId, $msg, $keyboard);
    }

    public function showDailyReport($token, $chatId, $client)
    {
        $today = Carbon::today();
        $totalOrders = Order::where('client_id', $client->id)->whereDate('created_at', $today)->count();
        $totalSales = Order::where('client_id', $client->id)->whereDate('created_at', $today)->where('order_status', '!=', 'cancelled')->sum('total_amount');
        $processing = Order::where('client_id', $client->id)->whereDate('created_at', $today)->where('order_status', 'processing')->count();
        $completed = Order::where('client_id', $client->id)->whereDate('created_at', $today)->where('order_status', 'completed')->count();
        $lowStock = Product::where('client_id', $client->id)->where('stock_quantity', '<', 5)->count();

        $msg = "📊 **{$client->shop_name} - আজকের রিপোর্ট**\n📅 তারিখ: " . $today->format('d M, Y') . "\n\n";
        $msg .= "💰 **মোট সেল:** ৳" . number_format($totalSales) . "\n📦 **মোট অর্ডার:** $totalOrders টি\n⏳ **প্রসেসিং:** $processing টি\n✅ **কমপ্লিট:** $completed টি\n";
        if ($lowStock > 0) $msg .= "\n⚠️ **Low Stock Alert:** {$lowStock} টি পণ্যের স্টক কম!";
        
        $this->api->sendMessage($token, $chatId, $msg);
    }

    public function showPendingOrders($token, $chatId, $clientId)
    {
        $orders = Order::where('client_id', $clientId)->where('order_status', 'processing')->latest()->take(5)->get();
        if ($orders->isEmpty()) {
            $this->api->sendMessage($token, $chatId, "✅ কোনো পেন্ডিং অর্ডার নেই।");
            return;
        }
        $msg = "📦 **সর্বশেষ ৫টি পেন্ডিং অর্ডার:**\n(ডিটেইলস দেখতে `/order ID` লিখুন)\n\n";
        foreach ($orders as $order) {
            $msg .= "🔹 **#{$order->id}** - {$order->customer_name}\n📞 `{$order->customer_phone}`\n💰 ৳{$order->total_amount}\n------------------\n";
        }
        $this->api->sendMessage($token, $chatId, $msg);
    }

    public function searchOrderById($token, $chatId, $clientId, $orderId)
    {
        $order = Order::where('client_id', $clientId)->where('id', trim($orderId))->first();
        if (!$order) {
            $this->api->sendMessage($token, $chatId, "❌ অর্ডার #{$orderId} খুঁজে পাওয়া যায়নি।");
            return;
        }

        $msg = "📦 **অর্ডার বিস্তারিত (#{$order->id})**\n\n👤 নাম: {$order->customer_name}\n📞 ফোন: `{$order->customer_phone}`\n📍 ঠিকানা: {$order->shipping_address}\n💰 মোট বিল: ৳{$order->total_amount}\n📊 স্ট্যাটাস: " . strtoupper($order->order_status) . "\n";
        foreach($order->orderItems as $item) {
            $pName = $item->product->name ?? 'Unknown Product';
            $msg .= "🛒 {$pName} x {$item->quantity}\n";
        }
        $msg .= "\n👇 **স্ট্যাটাস পরিবর্তন করুন:**";
        
        $keyboard = [
            [['text' => '🚚 Ship', 'callback_data' => "status_shipped_{$order->id}"], ['text' => '✅ Deliver', 'callback_data' => "status_delivered_{$order->id}"]],
            [['text' => '❌ Cancel', 'callback_data' => "status_cancelled_{$order->id}"]]
        ];
        $this->api->sendMessageWithInlineKeyboard($token, $chatId, $msg, $keyboard);
    }

    public function searchCustomerByPhone($token, $chatId, $clientId, $phone)
    {
        $orders = Order::where('client_id', $clientId)->where('customer_phone', 'LIKE', "%{$phone}%")->latest()->take(5)->get();
        if ($orders->isEmpty()) {
            $this->api->sendMessage($token, $chatId, "❌ এই নম্বরে কোনো অর্ডার পাওয়া যায়নি।");
            return;
        }
        $msg = "🔍 **কাস্টমার হিস্ট্রি ({$phone})**\n\n";
        foreach ($orders as $order) $msg .= "🔹 #{$order->id} - ৳{$order->total_amount} ({$order->order_status})\n";
        $this->api->sendMessage($token, $chatId, $msg);
    }

    public function searchProductStock($token, $chatId, $clientId, $keyword)
    {
        $products = Product::where('client_id', $clientId)->where('name', 'LIKE', "%{$keyword}%")->take(5)->get();
        if ($products->isEmpty()) {
            $this->api->sendMessage($token, $chatId, "❌ '{$keyword}' নামে কোনো পণ্য পাওয়া যায়নি।");
            return;
        }
        $msg = "🔍 **স্টক রেজাল্ট ({$keyword})**\n\n";
        foreach ($products as $p) {
            $stockIcon = $p->stock_quantity > 0 ? "✅" : "⚠️";
            $msg .= "{$stockIcon} **{$p->name}**\n📦 স্টক: {$p->stock_quantity}\n💰 দাম: ৳{$p->regular_price}\n------------------\n";
        }
        $this->api->sendMessage($token, $chatId, $msg);
    }

    public function sendManualReply($client, $senderId, $message, $token, $chatId) {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$client->fb_page_token}";
        $response = Http::post($url, [
            'recipient' => ['id' => $senderId],
            'message' => ['text' => "👨‍💼 অ্যাডমিন: " . $message]
        ]);
        if ($response->successful()) {
            $this->api->sendMessage($token, $chatId, "✅ মেসেজ পাঠানো হয়েছে!");
        } else {
            $this->api->sendMessage($token, $chatId, "❌ মেসেজ পাঠানো যায়নি। কাস্টমার ২৪ ঘন্টার বেশি সময় আগে মেসেজ দিয়েছিল?");
        }
    }

    public function showCancelledOrders($token, $chatId, $clientId)
    {
        $today = Carbon::today();
        $orders = Order::where('client_id', $clientId)->whereDate('created_at', $today)->where('order_status', 'cancelled')->latest()->take(5)->get();
        $count = $orders->count();
        $msg = "❌ **আজকের বাতিল অর্ডার:** {$count} টি\n\n";
        if ($count > 0) {
            foreach ($orders as $order) $msg .= "🔸 #{$order->id} - {$order->customer_name}\n";
        } else {
            $msg .= "✅ আজ কোনো অর্ডার বাতিল হয়নি।";
        }
        $this->api->sendMessage($token, $chatId, $msg);
    }

    public function showShippingStatus($token, $chatId, $clientId)
    {
        $shipping = Order::where('client_id', $clientId)->where('order_status', 'shipped')->count();
        $this->api->sendMessage($token, $chatId, "🚚 **শিপিং আপডেট:**\nবর্তমানে {$shipping} টি পার্সেল ডেলিভারির পথে আছে।");
    }

    public function showStoppedUsers($token, $chatId, $clientId)
    {
        $users = OrderSession::where('client_id', $clientId)->where('is_human_agent_active', true)->limit(10)->get();
        if ($users->isEmpty()) {
            $this->api->sendMessage($token, $chatId, "✅ **সবাই একটিভ আছে।** কোনো ইউজার স্টপ নেই।");
            return;
        }
        $msg = "📋 **AI বন্ধ থাকা ইউজার লিস্ট:**\n(Resume করতে বাটনে ক্লিক করুন)\n\n";
        $keyboard = [];
        foreach ($users as $user) {
            $name = $user->customer_info['name'] ?? 'Guest User';
            $id = $user->sender_id;
            $msg .= "👤 $name (`$id`)\n";
            $keyboard[] = [['text' => "▶️ Resume ($name)", 'callback_data' => "resume_ai_{$id}"]];
        }
        $this->api->sendMessageWithInlineKeyboard($token, $chatId, $msg, $keyboard);
    }
}