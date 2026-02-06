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
        try {
            // ১. সেশন লোড
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
                return "দুঃখিত, আপনার শব্দচয়ন পলিসি বিরোধী। আমাদের প্রতিনিধি শীঘ্রই যোগাযোগ করবেন।";
            }

            $client = Client::find($clientId);
            if (!$client) return "দুঃখিত, শপের কনফিগারেশনে সমস্যা হচ্ছে।";

            // ২. ইনপুট প্রসেসিং
            $processedMessage = $this->convertToEnglishNumbers($userMessage);

            // ৩. হিস্ট্রি লোড
            $history = $session->customer_info['history'] ?? [];
            
            // ৪. স্মার্ট ইনভেন্টরি ও কনটেক্সট
            $productsJson = $this->getInventoryData($clientId, $processedMessage, $history);
            $orderContext = $this->buildOrderContext($clientId, $senderId);

            // মেমোরি কন্ট্রোল (২০ মেসেজ)
            if (count($history) > 20) $history = array_slice($history, -20);

            // ৫. সিস্টেম প্রম্পট (প্রোডাক্ট সিলেকশন লজিক সহ)
            $systemPrompt = $this->buildSystemPrompt($client, $orderContext, $productsJson);

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $chat) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
                $messages[] = ['role' => 'assistant', 'content' => $chat['bot']];
            }

            $userContent = $imageUrl ? [
                ['type' => 'text', 'text' => $processedMessage ?: "এই ছবিটির ব্যাপারে বলুন"],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
            ] : $processedMessage;

            $messages[] = ['role' => 'user', 'content' => $userContent];

            // ৬. AI কল
            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            // ৭. সেভ এবং রিটার্ন
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

        // যদি কিছু না পাওয়া যায়, তবে লেটেস্ট প্রোডাক্ট লোড করো (সাজেশনের জন্য)
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
                'Price' => (int)$p->sale_price . ' Tk', // Sale_Price হিসেবে পাঠানো হচ্ছে যাতে কনফিউশন না হয়
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

    // [UPGRADE] সিস্টেম প্রম্পট: প্রোডাক্ট সিলেক্ট না করলে অর্ডার নেওয়া যাবে না
    private function buildSystemPrompt($client, $orderContext, $productsJson)
    {
        $delivery = "ঢাকার ভিতরে " . ($client->delivery_charge_inside ?? 80) . " টাকা, বাইরে " . ($client->delivery_charge_outside ?? 150) . " টাকা।";
        $persona = $client->custom_prompt ?? "তুমি একজন স্মার্ট সেলস অ্যাসিস্ট্যান্ট।";

        return <<<EOT
{$persona}
তুমি একজন বন্ধুসুলভ ইকমার্স সেলস অ্যাসিস্ট্যান্ট।

[কঠোর নির্দেশাবলী - অর্ডার প্রসেস]:
⚠️ **STEP 0 (গুরুত্বপূর্ণ): প্রোডাক্ট ভেরিফিকেশন**
- কাস্টমার যদি বলে "অর্ডার করবো" বা "কিনবো", কিন্তু আগে কোনো প্রোডাক্ট নিয়ে কথা না বলে থাকে—তবে **কখনোই নাম/ঠিকানা চাইবে না**।
- আগে জিজ্ঞেস করো: *"অবশ্যই! আপনি আমাদের কোন প্রোডাক্টটি অর্ডার করতে চান?"* এবং [INVENTORY] থেকে সাজেশন দাও।
- যদি প্রোডাক্ট কনফার্ম না হয়, তবে ডেলিভারি চার্জ বা টোটাল প্রাইস বলা নিষিদ্ধ।

**STEP 1: তথ্য সংগ্রহ**
- প্রোডাক্ট সিলেক্ট হওয়ার পরেই নাম, ফোন (১১ ডিজিট) এবং ঠিকানা চাও।

**STEP 2: ভেরিফিকেশন**
- সব তথ্য পেলে একটি সামারি দেখাও: "ধন্যবাদ [নাম], আপনি [প্রোডাক্ট] অর্ডার করতে চেয়েছেন। আপনার ঠিকানা [ঠিকানা] এবং ফোন [ফোন]। ডেলিভারি সহ মোট [টাকা] টাকা। আমি কি কনফার্ম করবো?"

**STEP 3: অ্যাকশন**
- কাস্টমার "হ্যাঁ" বললে তবেই [ORDER_DATA] ট্যাগ জেনারেট করো।

[অন্যান্য নিয়ম]:
- Markdown বা লিস্ট ব্যবহার করবে না। প্যারাগ্রাফে কথা বলবে।
- ফোন নম্বরে ১৩ বা ১০ ডিজিট দেখলে বলো "দয়া করে সঠিক ১১ ডিজিটের নম্বর দিন"।

[DATA]:
- Delivery Info: {$delivery}
- Available Products: {$productsJson}
- Customer History: {$orderContext}

[SYSTEM TAGS - Use only after confirmation]:
- Create Order: [ORDER_DATA: {"product_id":ID, "name":"...", "phone":"...", "address":"...", "is_dhaka":true/false, "note":"..."}]
- Cancel: [CANCEL_ORDER: {"reason":"..."}]
EOT;
    }

    private function detectHateSpeech($message) {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'scam', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

    private function sendTelegramAlert($clientId, $senderId, $message) {
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

    private function convertToEnglishNumbers($str) {
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
        } catch (\Exception $e) { return null; }
    }
}