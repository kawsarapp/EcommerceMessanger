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
    /**
     * মেইন ফাংশন: কন্ট্রোলার থেকে রিকোয়েস্ট রিসিভ করে এবং প্রসেস করে
     * এটি এখন স্টেপ-বাই-স্টেপ অর্ডার প্রসেসিং সিস্টেম ব্যবহার করবে
     */
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        try {
            // ১. সেশন ম্যানেজমেন্ট
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]
            );

            if ($session->is_human_agent_active) return null;

            // ২. হেট স্পিচ বা নেগেটিভ কথা চেক
            if ($this->detectHateSpeech($userMessage) || $this->isNegativeIntent($userMessage)) {
                return "ঠিক আছে, কোনো সমস্যা নেই। পরবর্তীতে কিছু প্রয়োজন হলে জানাবেন।";
            }

            // ৩. ফোন নম্বর লুকআপ চেক (পুরানো লজিক রাখা হলো)
            $phoneLookupResult = $this->lookupOrderByPhone($clientId, $userMessage);
            if ($phoneLookupResult) {
                return $phoneLookupResult;
            }

            // ৪. স্টেপ অনুযায়ী লজিক (Systematic Flow)
            $step = $session->customer_info['step'] ?? 'start';
            $currentProductId = $session->customer_info['product_id'] ?? null;
            $history = $session->customer_info['history'] ?? [];
            
            $systemInstruction = "";
            $productContext = "";

            // --- STEP 1: প্রোডাক্ট খোঁজা ---
            if ($step === 'start' || !$currentProductId) {
                // ইনভেন্টরি সার্চ (নতুন সিস্টেমেটিক লজিক)
                $product = $this->findProductSystematically($clientId, $userMessage);
                
                if ($product) {
                    // প্রোডাক্ট পাওয়া গেছে! এখন চেক করব ভেরিয়েশন আছে কি না
                    $hasColor = $product->colors && strtolower($product->colors) !== 'n/a';
                    $hasSize = $product->sizes && strtolower($product->sizes) !== 'n/a';

                    // লজিক: যদি ভেরিয়েশন থাকে, তবে স্টেপ হবে 'variant', না থাকলে সরাসরি 'info'
                    if ($hasColor || $hasSize) {
                        $nextStep = 'select_variant';
                        $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। কিন্তু এটার কালার/সাইজ আছে ({$product->colors} / {$product->sizes})। তুমি এখন শুধু কালার বা সাইজ জিজ্ঞেস করো। অন্য কিছু না।";
                    } else {
                        $nextStep = 'collect_info'; // সরাসরি নাম ঠিকানায় জাম্প
                        $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। এই প্রোডাক্টের কোনো কালার বা সাইজ নেই (Single Variation)। তাই ভুলেও কালার/সাইজ চাইবে না। সরাসরি কাস্টমারের নাম, ফোন নম্বর এবং ঠিকানা চাও।";
                    }

                    // সেশন আপডেট
                    $session->update(['customer_info' => array_merge($session->customer_info, ['step' => $nextStep, 'product_id' => $product->id])]);
                    $productContext = json_encode(['name' => $product->name, 'price' => $product->sale_price, 'stock' => 'Available']);
                
                } else {
                    // প্রোডাক্ট পাওয়া না গেলে ইনভেন্টরি ডেটা দেখানোর জন্য পুরানো লজিক ব্যবহার করব
                    $inventoryData = $this->getInventoryData($clientId, $userMessage, $history);
                    $systemInstruction = "কাস্টমার কিছু কিনতে চাচ্ছে কিন্তু আমরা প্রোডাক্টটি চিনতে পারছি না। বিনীতভাবে প্রোডাক্টের সঠিক নাম বা কোড জানতে চাও। ইনভেন্টরি ডেটা: {$inventoryData}";
                }
            } 
            
            // --- STEP 2: ভেরিয়েশন কনফার্মেশন ---
            elseif ($step === 'select_variant') {
                $product = Product::find($currentProductId);
                $systemInstruction = "কাস্টমার ভেরিয়েশন সিলেক্ট করছে। যদি সে কালার/সাইজ বলে থাকে, তবে এখন তার নাম, ফোন এবং ঠিকানা চাও। আর যদি না বলে থাকে, তবে আবার জিজ্ঞেস করো।";
                
                // যদি ইউজার কালার/সাইজ বলে দেয়, তবে পরের স্টেপে পাঠাও
                if ($this->hasVariantInMessage($userMessage)) {
                     $session->update(['customer_info' => array_merge($session->customer_info, ['step' => 'collect_info'])]);
                     $systemInstruction = "কাস্টমার ভেরিয়েশন কনফার্ম করেছে। এখন দ্রুত অর্ডার কনফার্ম করতে তার নাম, ফোন এবং ঠিকানা চাও।";
                }
            }

            // --- STEP 3: তথ্য সংগ্রহ ও অর্ডার কনফার্ম ---
            elseif ($step === 'collect_info') {
                $product = Product::find($currentProductId);
                
                // হার্ড-কোড চেক: মেসেজে ফোন নম্বর আছে কি না
                $phone = $this->extractPhoneNumber($userMessage);
                
                if ($phone) {
                    // ফোন নম্বর পেলে আমরা ধরে নিব অর্ডার কনফার্ম
                    $systemInstruction = "কাস্টমার ফোন নম্বর ({$phone}) দিয়েছে। এখন তুমি অর্ডারটি কনফার্ম করো এবং [ORDER_DATA] ট্যাগ জেনারেট করো। নাম না থাকলে 'Guest' ব্যবহার করো।";
                } else {
                    $systemInstruction = "আমরা এখনো ফোন নম্বর পাইনি। অর্ডার কনফার্ম করতে বিনীতভাবে ফোন নম্বর এবং ঠিকানা চাও।";
                }
            }
            
            // --- STEP 4: অর্ডার কমপ্লিট ---
            elseif ($step === 'completed') {
                return "আপনার অর্ডারটি ইতিমধ্যে আমাদের সিস্টেমে জমা হয়েছে। ধন্যবাদ!";
            }

            // ----------------------------------------
            // AI কল (এখন AI কন্ট্রোলড এনভায়রনমেন্টে আছে)
            // ----------------------------------------
            // কাস্টমার হিস্ট্রি বিল্ড করা (পুরানো লজিক ব্যবহার করব)
            $orderContext = $this->buildOrderContext($clientId, $senderId);
            
            $finalPrompt = <<<EOT
{$systemInstruction}

[কঠোর রুলস]:
1. তোমাকে যে টাস্ক দেওয়া হয়েছে, ঠিক সেটাই করবে। এর বাইরে কোনো প্রশ্ন করবে না।
2. যদি বলা হয় "কালার চাইবে না", তবে ভুলেও কালার চাইবে না।
3. অর্ডার কনফার্ম হলে ট্যাগ দিবে: [ORDER_DATA: {"product_id":ID, "name":"Name", "phone":"...", "address":"...", "is_dhaka":true/false, "note":"..."}]

[Product Info]: {$productContext}
[Customer History]: {$orderContext}
EOT;

            $messages = [
                ['role' => 'system', 'content' => $finalPrompt],
                ['role' => 'user', 'content' => $userMessage]
            ];

            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            return $aiResponse;

        } catch (\Exception $e) {
            Log::error('ChatbotService Error: ' . $e->getMessage());
            return "দুঃখিত, একটু সমস্যা হচ্ছে।";
        }
    }

    /**
     * [LOGIC] মেসেজে ফোন নম্বর থাকলে অর্ডার স্ট্যাটাস বের করা (পুরানো লজিক রাখা হলো)
     */
    private function lookupOrderByPhone($clientId, $message)
    {
        // ১১ ডিজিটের বিডি নম্বর প্যাটার্ন (01xxxxxxxxx)
        if (preg_match('/01[3-9]\d{8}/', $message, $matches)) {
            $phone = $matches[0];
            $order = Order::where('client_id', $clientId)
                          ->where('customer_phone', $phone)
                          ->latest()
                          ->first();

            if ($order) {
                $status = strtoupper($order->order_status);
                $note = $order->admin_note ?? $order->notes ?? ''; 
                $noteInfo = $note ? " (Admin Note: {$note})" : "";
                
                return "FOUND_ORDER: Phone {$phone} matched Order ID #{$order->id}. Status: {$status} {$noteInfo}. Total: {$order->total_amount} Tk.";
            } else {
                return "NO_ORDER_FOUND: Phone {$phone} provided but no order exists.";
            }
        }
        return null;
    }

    /**
     * [LOGIC] স্মার্ট ইনভেন্টরি সার্চ (কনটেক্সট মেমোরি সহ) (পুরানো লজিক রাখা হলো)
     */
    private function getInventoryData($clientId, $userMessage, $history)
    {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        // সাধারণ সার্চ লজিক
        $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2);
        $genericWords = ['price', 'details', 'dam', 'koto', 'eta', 'atar', 'size', 'color', 'picture', 'img', 'kemon', 'product', 'available', 'stock', 'kinbo', 'order', 'chai', 'lagbe', 'nibo'];
        $isFollowUp = Str::contains(strtolower($userMessage), $genericWords) || count($keywords) < 2;

        // কনটেক্সট অনুসারে আগের মেসেজের কীওয়ার্ড যোগ
        if ($isFollowUp && !empty($history)) {
            $lastUserMsg = end($history)['user'] ?? '';
            $lastKeywords = array_filter(explode(' ', $lastUserMsg), fn($w) => mb_strlen($w) > 3);
            $keywords = array_unique(array_merge($keywords, $lastKeywords));
        }

        // কীওয়ার্ড অনুসারে সার্চ
        if (!empty($keywords)) {
            $query->where(function($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('name', 'like', "%{$word}%")
                      ->orWhere('colors', 'like', "%{$word}%")
                      ->orWhere('sku', 'like', "%{$word}%");
                }
            });
        }

        $products = $query->latest()->limit(5)->get();

        // যদি সার্চে কিছু না পাওয়া যায়, সর্বশেষ 5 প্রোডাক্ট দেখাও
        if ($products->isEmpty()) {
            $products = Product::where('client_id', $clientId)
                ->where('stock_status', 'in_stock')
                ->latest()->limit(5)->get();
        }

        // প্রোডাক্ট ডাটা ম্যাপিং
        return $products->map(function ($p) {
            // কালার/সাইজ ডিকোডিং
            $colors = is_string($p->colors) ? (json_decode($p->colors, true) ?: $p->colors) : $p->colors;
            $colorsStr = is_array($colors) ? implode(', ', $colors) : ((string)$colors ?: null);

            $sizes = is_string($p->sizes) ? (json_decode($p->sizes, true) ?: $p->sizes) : $p->sizes;
            $sizesStr = is_array($sizes) ? implode(', ', $sizes) : ((string)$sizes ?: null);

            $desc = strip_tags(str_replace(["<br>", "</p>", "&nbsp;", "\n"], " ", $p->description));

            $data = [
                'ID' => $p->id,
                'Name' => $p->name,
                'Sale_Price' => (int)$p->sale_price . ' Tk',
                'Regular_Price' => $p->regular_price ? (int)$p->regular_price . ' Tk' : null,
                'Stock' => $p->stock_quantity > 0 ? 'Available' : 'Out of Stock',
                'Details' => Str::limit($desc, 200),
                'Image_URL' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null,
            ];

            // [FIX] কেবল বৈধ কালার ও সাইজ দেখানো হবে
            if ($colorsStr && strtolower($colorsStr) !== 'n/a') {
                $data['Colors'] = $colorsStr;
            }
            if ($sizesStr && strtolower($sizesStr) !== 'n/a') {
                $data['Sizes'] = $sizesStr;
            }

            return $data;
        })->toJson();
    }

    /**
     * [UPGRADED] স্মার্ট অর্ডার কনটেক্সট বিল্ডার (পুরানো লজিক রাখা হলো)
     */
    private function buildOrderContext($clientId, $senderId)
    {
        // ১. রিলেশনসহ অর্ডার লোড করা (যাতে প্রোডাক্টের নাম পাওয়া যায়)
        $orders = Order::with('items.product') // Eager loading for performance
                        ->where('client_id', $clientId)
                        ->where('sender_id', $senderId)
                        ->latest()
                        ->take(3)
                        ->get();

        if ($orders->isEmpty()) {
            return "CUSTOMER HISTORY: No previous orders found (New Customer).";
        }
        
        $context = "CUSTOMER ORDER HISTORY (Last 3 Orders):\n";
        
        foreach ($orders as $order) {
            // ২. প্রোডাক্টের নাম বের করা
            $productNames = $order->items->map(function($item) {
                return $item->product->name ?? 'Unknown Product';
            })->implode(', ');

            if (empty($productNames)) {
                $productNames = "Product ID: " . ($order->product_id ?? 'N/A');
            }

            // ৩. সময় বের করা (Human Readable)
            $timeAgo = $order->created_at->diffForHumans();
            $status = strtoupper($order->order_status);
            
            // ৪. নোট হ্যান্ডলিং
            $note = $order->admin_note ?? $order->notes ?? $order->customer_note ?? '';
            $noteInfo = $note ? " | Note: [{$note}]" : "";

            // ৫. কাস্টমার ইনফো (যাতে এআই নাম/ঠিকানা মনে রাখতে পারে)
            $customerInfo = "Name: {$order->customer_name}, Phone: {$order->customer_phone}, Address: {$order->shipping_address}";

            // ৬. ফরম্যাটেড স্ট্রিং তৈরি
            $context .= "- Order #{$order->id} ({$timeAgo}):\n";
            $context .= "  Product: {$productNames}\n";
            $context .= "  Status: [{$status}] | Amount: {$order->total_amount} Tk\n";
            $context .= "  Info: {$customerInfo}{$noteInfo}\n";
            $context .= "  -----------------------------\n";
        }
        
        return $context;
    }

    /**
     * [LOGIC] হেট স্পিচ ডিটেকশন (পুরানো লজিক রাখা হলো)
     */
    private function detectHateSpeech($message)
    {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'mamla', 'cheat', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

    /**
     * [LOGIC] নেগেটিভ ইন্টেন্ট ডিটেকশন (নতুন লজিক)
     */
    private function isNegativeIntent($msg) {
        $bad = ['nebo na', 'cancel', 'bad', 'fals', 'nibo na', 'lagbe na'];
        foreach($bad as $b) {
            if (str_contains(strtolower($msg), $b)) return true;
        }
        return false;
    }



    // ChatbotService.php এর ভেতরে এই নতুন মেথডটি যোগ করুন

public function convertVoiceToText($audioUrl)
{
    try {
        Log::info("Starting Voice Transcription for: " . $audioUrl);

        // ১. অডিও ফাইলটি ডাউনলোড করা
        $audioResponse = Http::get($audioUrl);
        if (!$audioResponse->successful()) return null;

        $tempFileName = 'voice_' . time() . '.mp4'; // ফেসবুক সাধারণত mp4/aac ফরম্যাট দেয়
        $tempPath = storage_path('app/' . $tempFileName);
        file_put_contents($tempPath, $audioResponse->body());

        // ২. OpenAI Whisper API কল করা
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
        
        $response = Http::withToken($apiKey)
            ->attach('file', fopen($tempPath, 'r'), $tempFileName)
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'bn', // সরাসরি বাংলা সেট করে দেওয়া হলো নিখুঁত রেজাল্টের জন্য
            ]);

        // ৩. ফাইলটি ডিলিট করে দেওয়া (সার্ভার পরিষ্কার রাখতে)
        unlink($tempPath);

        if ($response->successful()) {
            $transcribedText = $response->json()['text'] ?? null;
            Log::info("Voice Result: " . $transcribedText);
            return $transcribedText;
        }

        Log::error("Whisper API Error: " . $response->body());
        return null;

    } catch (\Exception $e) {
        Log::error("Voice Conversion Failed: " . $e->getMessage());
        return null;
    }
}

    /**
     * [LOGIC] ফোন নম্বর এক্সট্রাক্ট (নতুন লজিক)
     */
    private function extractPhoneNumber($msg) {
        if (preg_match('/01[3-9]\d{8}/', $msg, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * [LOGIC] প্রোডাক্ট খোঁজার হার্ড লজিক (নতুন লজিক)
     */
    private function findProductSystematically($clientId, $message) {
        // প্রথমে কোড দিয়ে খোঁজা (যেমন: V18)
        $words = explode(' ', strtoupper($message));
        foreach($words as $word) {
            $p = Product::where('client_id', $clientId)->where('sku', 'LIKE', "%$word%")->first();
            if($p) return $p;
        }
        // নাম দিয়ে খোঁজা
        return Product::where('client_id', $clientId)->where('name', 'LIKE', "%$message%")->first();
    }

    /**
     * [LOGIC] ভেরিয়েশন চেক (সিম্পল লজিক) (নতুন লজিক)
     */
    private function hasVariantInMessage($msg) {
        // মেসেজটি ছোট হলে (যেমন: "Red", "XL") ধরে নিব ভেরিয়েশন বলেছে
        return strlen($msg) < 15; 
    }

    /**
     * [LOGIC] বাংলা নাম্বার ইংরেজিতে কনভার্ট (পুরানো লজিক রাখা হলো)
     */
    private function convertToEnglishNumbers($str) {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        return str_replace($bn, $en, $str);
    }

    /**
     * [CORE] LLM কল (আপডেটেড ভার্সন)
     */
    private function callLlmChain($messages, $imageUrl)
    {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

            if (empty($apiKey)) {
                Log::error("OpenAI API Key missing! .env ফাইলে API কী আছে কিনা এবং VPS-এ ক্যাশ ক্লিয়ার করা হয়েছে কিনা চেক করুন।");
                return null;
            }

            $response = Http::withToken($apiKey)
                ->timeout(30) // VPS-এর জন্য সময় বাড়িয়ে ৩০ সেকেন্ড করা হলো
                ->retry(2, 500) // সাময়িক নেটওয়ার্ক সমস্যার জন্য ২ বার ট্রাই করবে
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
                    'messages' => $messages,
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error("OpenAI API Error: " . $response->status() . " - " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("LLM Call Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * [LOGIC] টেলিগ্রাম অ্যালার্ট সেন্ড (পুরানো লজিক রাখা হলো)
     */
    public function sendTelegramAlert($clientId, $senderId, $message)
    {
        try {
            $token = config('services.telegram.bot_token');
            $chatId = config('services.telegram.chat_id');

            if (!$token || !$chatId) {
                Log::warning("Telegram Credentials missing in .env");
                return;
            }

            $payload = [
                'chat_id' => $chatId,
                'text' => "🔔 **নতুন আপডেট**\nUser: {$senderId}\n{$message}",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"],
                        ['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]
                    ]]
                ])
            ];

            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            if (!$response->successful()) {
                Log::error("Telegram API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Telegram Notification Error: " . $e->getMessage());
        }
    }
}