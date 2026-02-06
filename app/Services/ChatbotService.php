<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderSession;

class ChatbotService
{
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        // ১. ক্লায়েন্ট এবং প্ল্যান ভেরিফিকেশন
        $client = Client::find($clientId);
        if (!$client || !$client->hasActivePlan()) {
            return "দুঃখিত, এই শপটি বর্তমানে অফলাইনে আছে।";
        }

        // ২. এআই মেসেজ লিমিট চেক
        if ($client->hasReachedAiLimit()) {
            return "দুঃখিত, এই মাসের ফ্রি মেসেজ লিমিট শেষ হয়ে গেছে। দয়া করে সরাসরি ইনবক্সে যোগাযোগ করুন।";
        }

        try {
            // ৩. সেশন লোড বা তৈরি
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['history' => []]]
            );

            // [SECURITY] হিউম্যান মোড চেক
            if ($session->is_human_agent_active) return null;

            // [SECURITY] হেট স্পিচ চেক
            if ($this->detectHateSpeech($userMessage)) {
                $session->update(['is_human_agent_active' => true]);
                $this->sendTelegramAlert($clientId, $senderId, $userMessage);
                return "দুঃখিত, আপনার শব্দচয়ন পলিসি বিরোধী। আমাদের প্রতিনিধি শীঘ্রই যোগাযোগ করবেন।";
            }

            // ৪. ইনপুট প্রসেসিং (বাংলা নাম্বার টু ইংলিশ)
            $processedMessage = $this->convertToEnglishNumbers($userMessage);

            // ৫. হিস্ট্রি লোড
            $history = $session->customer_info['history'] ?? [];

            // ৬. স্মার্ট ইনভেন্টরি ও কনটেক্সট লোড
            $productsJson = $this->getInventoryData($clientId, $processedMessage, $history);
            $orderContext = $this->buildOrderContext($clientId, $senderId);

            // মেমোরি কন্ট্রোল (সর্বশেষ ২০ মেসেজ রাখা হবে)
            if (count($history) > 20) $history = array_slice($history, -20);

            // ৭. সিস্টেম প্রম্পট তৈরি (প্রোডাক্ট সিলেকশন লজিক সহ)
            $systemPrompt = $this->buildSystemPrompt($client, $orderContext, $productsJson);

            // মেসেজ অ্যারে তৈরি
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $chat) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
                $messages[] = ['role' => 'assistant', 'content' => $chat['bot']];
            }

            // ইমেজ হ্যান্ডলিং
            $userContent = $imageUrl ? [
                ['type' => 'text', 'text' => $processedMessage ?: "এই ছবিটির ব্যাপারে বলুন"],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
            ] : $processedMessage;

            $messages[] = ['role' => 'user', 'content' => $userContent];

            // ৮. AI কল করা
            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            // ৯. সেভ এবং রিটার্ন
            if ($aiResponse) {
                $logMsg = $imageUrl ? "[Photo] " . $processedMessage : $processedMessage;
                $history[] = ['user' => $logMsg, 'bot' => $aiResponse];

                $session->update(['customer_info' => array_merge($session->customer_info, ['history' => $history])]);
                return $aiResponse;
            }

            return "দুঃখিত, বর্তমানে সংযোগে সমস্যা হচ্ছে।";

        } catch (\Exception $e) {
            Log::error('ChatbotService Error: ' . $e->getMessage());
            return "সাময়িক কারিগরি ত্রুটি হয়েছে।";
        }
    }

    // [SMART INVENTORY] কনটেক্সট বুঝে প্রোডাক্ট খোঁজা
    private function getInventoryData($clientId, $userMessage, $history)
    {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');
        $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2);

        $genericWords = ['price', 'details', 'dam', 'koto', 'eta', 'atar', 'size', 'color', 'picture', 'img', 'kemon', 'product', 'kinbo', 'order', 'chai', 'lagbe'];
        $isFollowUp = Str::contains(strtolower($userMessage), $genericWords) || count($keywords) < 2;

        if ($isFollowUp && !empty($history)) {
            $lastUserMsg = end($history)['user'] ?? '';
            $lastKeywords = array_filter(explode(' ', $lastUserMsg), fn($w) => mb_strlen($w) > 3);
            $keywords = array_unique(array_merge($keywords, $lastKeywords));
        }

        if (!empty($keywords)) {
            $query->where(function($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('name', 'like', "%{$word}%")
                      ->orWhere('colors', 'like', "%{$word}%")
                      ->orWhere('sku', 'like', "%{$word}%");
                }
            });
        }

        $products = $query->latest()->limit(8)->get();

        // যদি কিছু না পাওয়া যায়, তবে লেটেস্ট প্রোডাক্ট লোড করো (সাজেশনের জন্য)
        if ($products->isEmpty()) {
            $products = Product::where('client_id', $clientId)
                ->where('stock_status', 'in_stock')
                ->latest()->limit(5)->get();
        }

        return $products->map(function ($p) {
            $colors = is_string($p->colors) ? (json_decode($p->colors, true) ?: $p->colors) : $p->colors;
            $colorsStr = is_array($colors) ? implode(', ', $colors) : ((string)$colors ?: 'N/A');
            $sizes = is_string($p->sizes) ? (json_decode($p->sizes, true) ?: $p->sizes) : $p->sizes;
            $sizesStr = is_array($sizes) ? implode(', ', $sizes) : ((string)$sizes ?: 'N/A');

            return [
                'ID' => $p->id,
                'Name' => $p->name,
                'Price' => (int)$p->sale_price . ' Tk',
                'Stock' => $p->stock_quantity > 0 ? 'In Stock' : 'Out',
                'Colors' => $colorsStr,
                'Sizes' => $sizesStr,
                'Details' => Str::limit(strip_tags($p->description), 200)
            ];
        })->toJson();
    }

    private function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::where('client_id', $clientId)->where('sender_id', $senderId)->latest()->take(3)->get();
        if ($orders->isEmpty()) return "NO_ORDER_HISTORY";

        $context = "VERIFIED_ORDER_HISTORY:\n";
        foreach ($orders as $order) {
            $status = strtoupper($order->order_status);
            $context .= "- Order ID #{$order->id}: Status {$status}, Phone: {$order->customer_phone}\n";
        }
        return $context;
    }

    // [UPGRADE] সিস্টেম প্রম্পট বিল্ডার
    private function buildSystemPrompt($client, $orderContext, $productsJson)
    {
        $delivery = "ঢাকার ভিতরে " . ($client->delivery_charge_inside ?? 80) . " টাকা, বাইরে " . ($client->delivery_charge_outside ?? 150) . " টাকা।";
        $persona = $client->custom_prompt ?? "তুমি একজন স্মার্ট সেলস অ্যাসিস্ট্যান্ট।";

        return <<<EOT
{$persona}
তুমি একজন বন্ধুসুলভ ইকমার্স সেলস অ্যাসিস্ট্যান্ট।

[কঠোর নির্দেশাবলী - ক্যারোসেল ব্যবহার]:
- যখনই কাস্টমার কোনো প্রোডাক্ট দেখতে চাইবে বা তুমি কোনো প্রোডাক্ট সাজেস্ট করবে, তখন অবশ্যই [CAROUSEL: ID1, ID2] ট্যাগটি ব্যবহার করবে।
- সর্বোচ্চ ৩টি প্রোডাক্টের আইডি কমা দিয়ে লিখবে। উদাহরণ: [CAROUSEL: 5, 12, 8]
- ক্যারোসেল ট্যাগটি তোমার টেক্সট রিপ্লাইয়ের একদম শেষে দিবে।

[অর্ডার প্রসেস]:
- প্রোডাক্ট কনফার্ম না হওয়া পর্যন্ত নাম/ঠিকানা চাইবে না।
- ফোন নম্বর অবশ্যই ১১ ডিজিট হতে হবে।

[DATA]:
- Delivery Info: {$delivery}
- Available Products: {$productsJson}
- Customer History: {$orderContext}

[SYSTEM TAGS]:
- Show Carousel: [CAROUSEL: ID1, ID2, ID3]
- Create Order: [ORDER_DATA: {"product_id":ID, "name":"...", "phone":"...", "address":"...", "is_dhaka":true/false}]
EOT;
    }

    private function detectHateSpeech($message)
    {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'scam', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

    private function sendTelegramAlert($clientId, $senderId, $message)
    {
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            $chatId = env('TELEGRAM_CHAT_ID');
            if (!$token || !$chatId) return;
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "⚠️ HATE SPEECH\nUser: {$senderId}\nMsg: {$message}",
            ]);
        } catch (\Exception $e) {}
    }

    private function convertToEnglishNumbers($str)
    {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        return str_replace($bn, $en, $str);
    }

    private function callLlmChain($messages, $imageUrl)
    {
        try {
            $response = Http::withToken(config('services.openai.api_key') ?? env('OPENAI_API_KEY'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
                    'messages' => $messages,
                    'temperature' => 0.3, // কম টেম্পারেচার = স্ট্রিক্ট লজিক
                ]);
            return $response->json()['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}