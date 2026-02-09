<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderSession;

// ✅ OrderFlow Classes Import
use App\Services\OrderFlow\StartStep;
use App\Services\OrderFlow\VariantStep;
use App\Services\OrderFlow\AddressStep;
use App\Services\OrderFlow\ConfirmStep;
use App\Services\OrderFlow\OrderTraits; // For shared logic like findProduct

class ChatbotService
{
    use OrderTraits; // Trait ব্যবহার করছি কারণ Context Switching-এ findProductSystematically দরকার

    /**
     * মেইন ফাংশন: কন্ট্রোলার থেকে রিকোয়েস্ট রিসিভ করে এবং প্রসেস করে
     * (Production Ready: Modular State Pattern + Optimized Transaction)
     */
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        // 🚀 PERFORMANCE FIX: ইমেজ প্রসেসিং ট্রানজেকশনের বাইরে (Database Locking এড়াতে)
        $base64Image = null;
        if ($imageUrl) {
            try {
                $imgResponse = Http::timeout(5)->get($imageUrl);
                if ($imgResponse->successful()) {
                    $base64Image = "data:" . $imgResponse->header('Content-Type') . ";base64," . base64_encode($imgResponse->body());
                }
            } catch (\Exception $e) {
                Log::error("Image Pre-fetch Error: " . $e->getMessage());
            }
        }

        // ⚠️ Transaction শুরু
        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image) {
            
            // 1. Lock & Load Session
            $session = OrderSession::where('sender_id', $senderId)->lockForUpdate()->first();

            if (!$session) {
                $session = OrderSession::create([
                    'sender_id' => $senderId,
                    'client_id' => $clientId,
                    'customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]
                ]);
            }

            // Human agent check
            if ($session->is_human_agent_active) return null;

            // Basic Setup
            $currentTime = now()->format('l, h:i A');
            $customerInfo = $session->customer_info ?? ['step' => 'start', 'product_id' => null, 'history' => []];
            $step = $customerInfo['step'] ?? 'start';
            $history = $customerInfo['history'] ?? [];

            // ========================================
            // 1. SMART CONTEXT SWITCHING
            // ========================================
            // Trait থেকে findProductSystematically ব্যবহার করা হচ্ছে
            $newProduct = $this->findProductSystematically($clientId, $userMessage);
            $currentProductId = $customerInfo['product_id'] ?? null;
            
            // যদি ইউজার নতুন কোনো প্রোডাক্টের কথা বলে যা বর্তমান কনটেক্সট থেকে আলাদা
            if ($newProduct && $newProduct->id != $currentProductId) {
                $currentProductId = $newProduct->id;
                $step = 'start'; // Reset step
                
                $customerInfo['product_id'] = $currentProductId;
                $customerInfo['step'] = 'start';
                unset($customerInfo['variant'], $customerInfo['note']);
                
                $session->update(['customer_info' => $customerInfo]);
            }

            // ========================================
            // 2. SESSION RESET LOGIC
            // ========================================
            if ($step === 'completed' && !$this->isOrderRelatedMessage($userMessage)) {
                // ডেলিভারি নোট চেক
                if ($this->detectDeliveryNote($userMessage)) {
                    $note = $this->extractDeliveryNote($userMessage);
                    if ($this->updateRecentOrderNote($clientId, $senderId, $note)) {
                        return "ধন্যবাদ! আপনার নোটটি ('$note') অর্ডারে যুক্ত করা হয়েছে।";
                    }
                }

                // Reset Logic
                $customerInfo['step'] = 'start';
                $customerInfo['product_id'] = null;
                unset($customerInfo['variant'], $customerInfo['note']); 
                
                $session->update(['customer_info' => $customerInfo]);
                $step = 'start';
            }

            // ✅ Critical early-exit checks
            if ($this->detectOrderCancellation($userMessage, $senderId)) {
                return "[CANCEL_ORDER: {\"reason\": \"Customer requested cancellation\"}]";
            }
            if ($this->detectHateSpeech($userMessage)) {
                return "দুঃখিত, আমরা শালীন আলোচনা করি। অন্য কোনো সাহায্য প্রয়োজন?";
            }

            // ডেলিভারি নোট থাকলে সেশনে সেভ রাখার জন্য এক্সট্রাক্ট করা (Step Class-এ পাস করার জন্য)
            // নোট: স্টেপ ক্লাসগুলো সেশন রিড করে, তাই আমরা এখানে নোট সেশনে পুশ করতে পারি যদি দরকার হয়
            // তবে আপনার বর্তমান লজিক অনুযায়ী Address/Confirm স্টেপ এটি হ্যান্ডেল করে।

            // ========================================
            // 3. MODULAR ORDER FLOW (Using Step Classes)
            // ========================================
            
            // ইনভেন্টরি ডাটা গ্লোবাল প্রম্পটের জন্য জেনারেট করে রাখা
            $inventoryData = $this->getInventoryData($clientId, $userMessage, $history);

            // 🔥 STEP MAPPING 🔥
            $steps = [
                'start'         => new StartStep(),
                'select_variant'=> new VariantStep(),
                'collect_info'  => new AddressStep(),
                'confirm_order' => new ConfirmStep(),
            ];

            // সঠিক হ্যান্ডলার সিলেক্ট করা
            $currentStepName = $customerInfo['step'] ?? 'start';
            $handler = $steps[$currentStepName] ?? $steps['start'];

            // 🔥 PROCESS LOGIC 🔥
            // সব লজিক এখন আলাদা ক্লাসে। ChatbotService এখন শুধু অরকেস্ট্রেটর।
            $result = $handler->process($session, $userMessage);

            $systemInstruction = $result['instruction'] ?? "আমি বুঝতে পারিনি, আবার বলুন।";
            $productContext = $result['context'] ?? "[]";

            // ========================================
            // 4. GENERATE PROMPT & CALL AI
            // ========================================
            $orderContext = $this->buildOrderContext($clientId, $senderId);
            
            $finalPrompt = $this->generateSystemPrompt(
                $systemInstruction,
                $productContext,
                $orderContext,
                $inventoryData, // এটি গ্লোবাল সার্চ বা ফলব্যাকের জন্য পাঠানো হচ্ছে
                $currentTime
            );

            // Message History Building
            $messages = [['role' => 'system', 'content' => $finalPrompt]];
            foreach (array_slice($history, -4) as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            // Attach Image (if available)
            if ($base64Image) {
                 $lastMsg = array_pop($messages);
                 $messages[] = [
                     'role' => 'user',
                     'content' => [
                         ['type' => 'text', 'text' => $lastMsg['content']], 
                         ['type' => 'image_url', 'image_url' => ['url' => $base64Image]]
                     ]
                 ];
            }

            // Call LLM
            $aiResponse = $this->callLlmChain($messages);

            // Save History
            if ($aiResponse) {
                $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
                
                // রি-ফেচ সেশন (process মেথড সেশন আপডেট করে থাকতে পারে)
                $session->refresh();
                $customerInfo = $session->customer_info;
                $customerInfo['history'] = array_slice($history, -20);
                
                $session->update(['customer_info' => $customerInfo]);
            }

            return $aiResponse;

        }); // End Transaction
    }

    // =====================================
    // GLOBAL HELPER METHODS
    // (Specific logic moved to Step Classes, keeping generic ones here)
    // =====================================

    /**
     * [OPTIMIZED] স্মার্ট ইনভেন্টরি সার্চ (With Caching)
     * এটি গ্লোবাল প্রম্পটের জন্য দরকার, তাই এখানে রাখা হলো।
     */
    private function getInventoryData($clientId, $userMessage, $history)
    {
        $cacheKey = "inventory_{$clientId}_" . md5($userMessage);

        return Cache::remember($cacheKey, 600, function () use ($clientId, $userMessage, $history) {
            
            $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');
            $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2);
            $genericWords = ['price', 'details', 'dam', 'koto', 'eta', 'atar', 'size', 'color', 'picture', 'img', 'kemon', 'product', 'available', 'stock', 'kinbo', 'order', 'chai', 'lagbe', 'nibo', 'টাকা', 'দাম', 'কেমন', 'ছবি'];
            
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

            $products = $query->latest()->limit(5)->get();

            if ($products->isEmpty()) {
                $products = Product::where('client_id', $clientId)
                    ->where('stock_status', 'in_stock')
                    ->where('stock_quantity', '>', 0)
                    ->latest()->limit(5)->get();
            }

            return $products->map(function ($p) {
                $decode = fn($v) => is_string($v) ? (json_decode($v, true) ?: $v) : $v;
                $colors = $decode($p->colors);
                $colorsStr = is_array($colors) ? implode(', ', $colors) : ((string)$colors ?: null);
                $sizes = $decode($p->sizes);
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

                if ($colorsStr && strtolower($colorsStr) !== 'n/a') $data['Colors'] = $colorsStr;
                if ($sizesStr && strtolower($sizesStr) !== 'n/a') $data['Sizes'] = $sizesStr;

                return $data;
            })->toJson();
        });
    }

    private function updateRecentOrderNote($clientId, $senderId, $note)
    {
        $recentOrder = Order::where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->latest()
            ->first();

        if ($recentOrder) {
            $existingNote = $recentOrder->admin_note ?? $recentOrder->notes ?? '';
            $newNote = $existingNote ? "$existingNote | $note" : $note;

            if (\Schema::hasColumn('orders', 'admin_note')) {
                $recentOrder->update(['admin_note' => $newNote]);
            } elseif (\Schema::hasColumn('orders', 'notes')) {
                $recentOrder->update(['notes' => $newNote]);
            }

            Log::info("Updated Note for Order #{$recentOrder->id}: $note");
            return true;
        }
        return false;
    }

    private function generateSystemPrompt($instruction, $prodCtx, $ordCtx, $invData, $time)
    {
        return <<<EOT
{$instruction}

**System Role:** Elite AI Sales Associate for a top-tier E-commerce Brand in Bangladesh.
**Objective:** Convert inquiries into confirmed orders efficiently using a polite, mixed Bangla-English tone.

### 🛡️ PRIME DIRECTIVES (ABSOLUTE RULES):
1.  **CONTEXT FIREWALL (High Priority):**
    - The USER is currently looking at [Current Product Info].
    - DO NOT mix this with [Customer Order History] unless the user specifically asks "What did I buy before?".
    - If [Current Product Info] is empty, check [Product Inventory].

2.  **THE "NO DATA, NO ORDER" RULE:**
    - You are FORBIDDEN from generating the `[ORDER_DATA]` tag unless you have captured BOTH:
      (A) A valid 11-digit Phone Number.
      (B) A Clear Shipping Address (reject vague answers like "Dhaka" or "Same").

3.  **INVENTORY TRUTH:**
    - ONLY offer Variants (Color/Size) listed in [Current Product Info].
    - If a user asks for a color NOT in the list, politely say it's out of stock. Do not guess.

4.  **VISUAL CONFIRMATION:**
    - You MUST display the [CAROUSEL: Product_ID] tag *immediately* before asking for final confirmation.

### 🗣️ LANGUAGE & TONE GUIDELINES:
- **Script:** Use Bangla script for sentences, but keep key terms in English.
- **Keywords in English:** Price, Size, Color, Delivery Charge, Stock, Order, Delivery Time.
- **Tone:** Professional, Concise, and Helpful. (Use 'Apni' for respect).
- **Example:** "জি স্যার, এই Product টি Stock এ আছে। Price মাত্র 1250 Tk। আপনি কি Order টি Confirm করতে চান?"

### 🧠 INTELLIGENT FLOW:
1.  **Selection:** If the product has options, ask for Color/Size first. Don't proceed without it.
2.  **Collection:** Ask for Phone & Address only after product selection is done.
3.  **Closing:** Show Summary -> Show [CAROUSEL] -> Ask for "Yes/Confirm".

### 📂 DATA PACKETS:
[Current Product Info]: {$prodCtx}
[Customer History]: {$ordCtx}
[Inventory Lookup]: {$invData}
[Metadata]: Time: {$time} | Delivery: 2-4 Days Standard.

### 🔧 SYSTEM COMMANDS (JSON MUST BE VALID):
- Show Image: [CAROUSEL: {Product_ID}]
- Finalize Order: [ORDER_DATA: {"product_id": 123, "phone": "01xxx", "address": "Full Address", "variant": {"color":"Red"}, "note": "...", "status": "PROCESSING"}]
- Tracking: [TRACK_ORDER: "01xxx"]

Now, respond to the user professionally.
EOT;
    }

    private function isTrackingIntent($msg)
    {
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অর্ডার কি', 'অর্ডার চেক', 'অবস্থা', 'জানতে চাই', 'পৌঁছাবে', 'কবে পাব', 'tracking'];
        $msgLower = mb_strtolower($msg, 'UTF-8');
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function isOrderRelatedMessage($msg)
    {
        $orderKeywords = ['order', 'অর্ডার', 'buy', 'কিনবো', 'purchase', 'কেনা', 'product', 'প্রোডাক্ট', 'item', 'জিনিস'];
        $msgLower = strtolower($msg);
        foreach ($orderKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function detectDeliveryNote($msg)
    {
        $noteKeywords = [
            'friday', 'শুক্রবার', 'saturday', 'শনিবার', 'sunday', 'রবিবার',
            'monday', 'সোমবার', 'tuesday', 'মঙ্গলবার', 'wednesday', 'বুধবার', 'thursday', 'বৃহস্পতিবার',
            'delivery', 'ডেলিভারি', 'দিবেন', 'দিবে', 'দিয়েন', 'দিয়ে', 'পৌছে', 'পৌছাবেন',
            'tomorrow', 'আগামীকাল', 'next day', 'asap', 'জরুরি', 'urgent', 'দ্রুত', 'সকালে', 'রাতে',
            'evening', 'সন্ধ্যায়', 'morning', 'afternoon', 'time', 'সময়', 'before', 'পরে', 'আগে'
        ];
        $msgLower = strtolower($msg);
        foreach ($noteKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function extractDeliveryNote($msg)
    {
        $commonWords = ['ami', 'amra', 'tumi', 'apni', 'she', 'i', 'you', 'we', 'they', 'want', 'need', 'please', 'kindly', 'দয়া', 'করে', 'চাই', 'লাগবে'];
        $words = explode(' ', strtolower($msg));
        $filtered = array_filter($words, function($w) use ($commonWords) {
            return !in_array(strtolower(trim($w)), $commonWords) && strlen(trim($w)) > 2;
        });
        return implode(' ', $filtered);
    }

    private function detectOrderCancellation($msg, $senderId)
    {
        if (empty($msg)) return false;
        $cancelPhrases = [
            'cancel', 'বাতিল', 'cancel koro', 'cancel kore', 'বাতিল কর', 'বাতিল করে', 'বাতিল দেন',
            'order ta cancel', 'order cancel', 'অর্ডার বাতিল', 'অর্ডারটা বাতিল',
            'দরকার নাই', 'নিবো না', 'লাগবে না', 'চাই না', 'দরকার নেই', 'না লাগবে',
            'নিব না', 'নিতে চাই না', 'রাখব না', 'চাইনা', 'লাগবেনা', 'নিবোনা',
            'change mind', 'changed my mind', 'ভুল হয়েছে', 'ভুল', 'ভুল করেছি'
        ];
        $msgLower = mb_strtolower($msg, 'UTF-8');
        foreach ($cancelPhrases as $phrase) {
            if (mb_strpos($msgLower, mb_strtolower($phrase, 'UTF-8')) !== false) {
                return Order::where('sender_id', $senderId)
                    ->whereIn('order_status', ['processing', 'pending'])
                    ->exists();
            }
        }
        return false;
    }

    private function detectHateSpeech($message)
    {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'mamla', 'cheat', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud', 'fuck', 'shit', 'bitch', 'asshole'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

    private function lookupOrderByPhone($clientId, $message)
    {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $message = str_replace($bn, $en, $message);

        if (preg_match('/01[3-9]\d{8,9}/', $message, $matches)) {
            $phone = substr($matches[0], 0, 11);
            $order = Order::where('client_id', $clientId)
                ->where('customer_phone', $phone)
                ->latest()
                ->first();

            if ($order) {
                $status = strtoupper($order->order_status);
                $note = $order->admin_note ?? $order->notes ?? '';
                $noteInfo = $note ? " (Note: {$note})" : "";
                return "FOUND_ORDER: Phone {$phone} matched Order #{$order->id}. Status: {$status} {$noteInfo}. Total: {$order->total_amount} Tk.";
            } else {
                return "NO_ORDER_FOUND: Phone {$phone} provided but no order exists.";
            }
        }
        return null;
    }

    private function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::with('items.product')
            ->where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->latest()
            ->take(3)
            ->get();

        if ($orders->isEmpty()) return "CUSTOMER HISTORY: No previous orders found (New Customer).";

        $context = "CUSTOMER ORDER HISTORY (Last 3 Orders):\n";
        foreach ($orders as $order) {
            $productNames = $order->items->map(fn($item) => $item->product->name ?? 'Unknown')->implode(', ');
            if (empty($productNames)) $productNames = "Product ID: " . ($order->product_id ?? 'N/A');
            $timeAgo = $order->created_at->diffForHumans();
            $status = strtoupper($order->order_status);
            $note = $order->admin_note ?? $order->notes ?? $order->customer_note ?? '';
            $noteInfo = $note ? " | Note: [{$note}]" : "";
            $customerInfo = "Name: {$order->customer_name}, Phone: {$order->customer_phone}, Address: {$order->shipping_address}";

            $context .= "- Order #{$order->id} ({$timeAgo}):\nProduct: {$productNames}\nStatus: [{$status}] | Amount: {$order->total_amount} Tk\nInfo: {$customerInfo}{$noteInfo}\n-----------------------------\n";
        }

        return $context;
    }

    public function convertVoiceToText($audioUrl)
    {
        $tempPath = null;
        try {
            Log::info("Starting Voice Transcription for: " . $audioUrl);
            $audioResponse = Http::get($audioUrl);
            if (!$audioResponse->successful()) return null;

            $contentType = $audioResponse->header('Content-Type');
            $extension = 'mp3';
            if (strpos($contentType, 'ogg') !== false) $extension = 'ogg';
            elseif (strpos($contentType, 'mp4') !== false) $extension = 'mp4';
            elseif (strpos($contentType, 'm4a') !== false) $extension = 'm4a';

            $tempFileName = 'voice_' . time() . '_' . uniqid() . '.' . $extension;
            $tempPath = storage_path('app/' . $tempFileName);
            file_put_contents($tempPath, $audioResponse->body());

            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            $response = Http::withToken($apiKey)
                ->attach('file', fopen($tempPath, 'r'), $tempFileName)
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'prompt' => 'This is a Bengali voice message about ordering products.',
                ]);

            if ($response->successful()) {
                $text = $response->json()['text'] ?? null;
                Log::info("Voice Result: " . $text);
                return $text;
            }

            Log::error("Whisper API Error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Voice Conversion Failed: " . $e->getMessage());
            return null;
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function callLlmChain($messages, $imageUrl = null)
    {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            if (empty($apiKey)) {
                Log::error("OpenAI API Key missing!");
                return null;
            }

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'response_format' => ['type' => 'json_object'],
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error("OpenAI API Error: {$response->status()} - " . substr($response->body(), 0, 200));
            return null;
        } catch (\Throwable $e) {
            Log::error("LLM Call Exception: " . $e->getMessage());
            return null;
        }
    }

    public function sendTelegramAlert($clientId, $senderId, $message)
    {
        try {
            $token = config('services.telegram.bot_token');
            $chatId = config('services.telegram.chat_id');
            if (!$token || !$chatId) return;

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "🔔 **নতুন আপডেট**\nUser: {$senderId}\n{$message}",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"],
                        ['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]
                    ]]
                ])
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram Notification Error: " . $e->getMessage());
        }
    }
}