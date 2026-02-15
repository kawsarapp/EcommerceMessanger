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
use App\Models\Client;
use App\Services\OrderService;

// ✅ OrderFlow Classes Import
use App\Services\OrderFlow\StartStep;
use App\Services\OrderFlow\VariantStep;
use App\Services\OrderFlow\AddressStep;
use App\Services\OrderFlow\ConfirmStep;
use App\Services\OrderFlow\OrderTraits; 

class ChatbotService
{
    use OrderTraits; 

    protected $orderService;

    public function __construct(OrderService $orderService) {
        $this->orderService = $orderService;
    }

    /**
     * মেইন ফাংশন: কন্ট্রোলার থেকে রিকোয়েস্ট রিসিভ করে এবং প্রসেস করে
     * (Production Ready: Modular State Pattern + Optimized Transaction)
     */
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        Log::info("🤖 AI Service Started for User: $senderId");

        // 🔥 NULL SAFETY GUARD
        $userMessage = $userMessage ?? '';

        // 🚀 1. IMAGE READING & VISION HANDLING (Extreme Upgrade)
        // কাস্টমার ছবি পাঠালে সেটা বেস৬৪ এনকোড করে AI-কে পাঠানো হবে
        $base64Image = null;
        if ($imageUrl) {
            try {
                $imgResponse = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->timeout(15)->get($imageUrl);

                if ($imgResponse->successful()) {
                    $mime = $imgResponse->header('Content-Type') ?: 'image/jpeg';
                    $base64Image = "data:" . $mime . ";base64," . base64_encode($imgResponse->body());
                    Log::info("📷 Image downloaded & encoded for Vision API: User $senderId");
                }
            } catch (\Exception $e) {
                Log::error("Image Pre-fetch Error: " . $e->getMessage());
            }
        }

        // যদি শুধু ইমেজ থাকে এবং কোনো টেক্সট না থাকে, তবে ডিফল্ট টেক্সট সেট করা
        if (empty(trim($userMessage)) && $base64Image) {
            $userMessage = "I have sent an image. Please analyze it and check if you have something similar in your inventory.";
            Log::info("ℹ️ Auto-filled message for image input.");
        } elseif (empty(trim($userMessage)) && !$base64Image) {
            Log::warning("⚠️ Empty message received in ChatbotService. Returning null.");
            return null;
        }

        // 🔥 2. SAFETY CHECK (Hate Speech / Abuse)
        if ($this->detectHateSpeech($userMessage)) {
            Log::warning("🚫 Hate speech detected from User: $senderId");
            $this->sendTelegramAlert($clientId, $senderId, "⚠️ Abusive Language Detected: '$userMessage'");
            return "অনুগ্রহ করে ভদ্র ভাষা ব্যবহার করুন। আমাদের এজেন্ট শীঘ্রই আপনার সাথে যোগাযোগ করবে।";
        }

        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image) {

            // Session Lock & Creation
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'history' => []]]
            );
            $session = OrderSession::where('sender_id', $senderId)->lockForUpdate()->first();

            // Human Agent Handover Check
            if ($session->is_human_agent_active) {
                Log::info("⏸️ Human Agent Active. AI Paused.");
                return null;
            }

            // 🔥 3. LOOP DETECTION (Advanced)
            // ইউজার বা AI যদি একই কথা বারবার বলে, তবে লুপ ব্রেক করা হবে
            $history = $session->customer_info['history'] ?? [];
            if (count($history) >= 4) {
                $lastUserMsgs = array_slice(array_column($history, 'user'), -3);
                // যদি একই মেসেজ ৩ বার আসে
                if (count($lastUserMsgs) === 3 && count(array_unique($lastUserMsgs)) === 1 && end($lastUserMsgs) == $userMessage) {
                    $this->sendTelegramAlert($clientId, $senderId, "⚠️ **Loop Detected:** User repeating '{$userMessage}'. AI Paused.");
                    $session->update(['is_human_agent_active' => true]);
                    return "দুঃখিত, আমি আপনার কথা বুঝতে পারছি না। আমাদের একজন প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।";
                }
            }

            // ক্লায়েন্ট লোড করা
            $client = Client::find($clientId);
            $customerInfo = $session->customer_info;

            // 🔥 4. SMART ORDER TRACKING (Database Priority)
            // কাস্টমার যদি অর্ডারের অবস্থা জানতে চায়
            if ($this->isTrackingIntent($userMessage) || preg_match('/01[3-9]\d{8}/', $userMessage)) {
                $orderStatusMsg = $this->lookupOrderByPhone($clientId, $userMessage);
                if ($orderStatusMsg && str_contains($orderStatusMsg, 'FOUND_ORDER')) {
                    $cleanMsg = str_replace('FOUND_ORDER:', '', $orderStatusMsg);
                    return "স্যার/ম্যাম, আপনার অর্ডারের তথ্য পেয়েছি: \n" . $cleanMsg . "\nআমাদের সাথে থাকার জন্য ধন্যবাদ!";
                }
            }
            
            // 🔄 5. PRODUCT SEARCH & RESET LOGIC
            $newProduct = $this->findProductSystematically($clientId, $userMessage);
            
            if ($newProduct) {
                $currentProductId = $customerInfo['product_id'] ?? null;
                $currentStep = $customerInfo['step'] ?? '';

                // If new product found OR currently collecting info but user switched topic
                if ($newProduct->id != $currentProductId || $currentStep === 'collect_info') {
                    Log::info("🔄 Product Switch: Found ({$newProduct->name})");
                    $session->update([
                        'customer_info' => [
                            'step' => 'start', 
                            'product_id' => $newProduct->id, 
                            'history' => $customerInfo['history'] ?? []
                        ]
                    ]);
                }
            } else {
                // GENERIC RESET (Menu/Offer/Start Over)
                $genericPhrases = ['ki ace', 'ki ase', 'product ace', 'offer', 'collection', 'list', 'show', 'কি আছে', 'অফার', 'price koto', 'dam koto', 'menu', 'start', 'suru', 'first'];
                foreach ($genericPhrases as $phrase) {
                    if (stripos(strtolower($userMessage), $phrase) !== false) {
                        Log::info("🔄 Generic Query Reset Triggered.");
                        $session->update([
                            'customer_info' => [
                                'step' => 'start', 
                                'history' => $customerInfo['history'] ?? []
                            ]
                        ]);
                        break;
                    }
                }
            }

            // ✅ 6. ORDER FLOW PROCESSING
            $session->refresh(); 
            $stepName = $session->customer_info['step'] ?? 'start';
            Log::info("👣 Processing Step: $stepName");

            $steps = [
                'start' => new StartStep(),
                'select_variant' => new VariantStep(),
                'collect_info' => new AddressStep(),
                'confirm_order' => new ConfirmStep(),
                'completed' => new StartStep(),
            ];

            $handler = $steps[$stepName] ?? $steps['start'];
            
            // Execute Step Logic (With Image URL support)
            $result = $handler->process($session, (string)$userMessage, $imageUrl);
            
            $instruction = $result['instruction'] ?? "আমি বুঝতে পারিনি।";
            $contextData = $result['context'] ?? "[]";

            // 🔥 7. ORDER CREATION ACTION
            if (isset($result['action']) && $result['action'] === 'create_order') {
                Log::info("🚀 Action Triggered: create_order");
                try {
                    $order = $this->orderService->finalizeOrderFromSession($clientId, $senderId, $client);
                    
                    // AI-কে অর্ডার আইডি জানিয়ে দেওয়া হচ্ছে
                    $instruction .= " (SYSTEM: Order Created Successfully! Order ID is #{$order->id}. You MUST congratulate the user and explicitly tell them the Order ID.)";
                    
                    // Telegram Notification (SaaS Dynamic Token)
                    $this->sendTelegramAlert($clientId, $senderId, "✅ Order Placed: #{$order->id} - {$order->total_amount} Tk");
                } catch (\Exception $e) {
                    $instruction = "Technical error creating order. Please apologize.";
                    Log::error("❌ Order Error: " . $e->getMessage());
                }
            }

            // ✅ 8. CONTEXT LOADING (Extreme Upgrade: Link + Description)
            // এখানে ক্লায়েন্ট মডেল পাস করা হচ্ছে যাতে ইনভেন্টরিতে লিংক জেনারেট করা যায়
            $inventoryData = $this->getInventoryData($client, $userMessage); 
            $orderHistory = $this->buildOrderContext($clientId, $senderId);
            $currentTime = now()->format('l, h:i A');
            $userName = $session->customer_info['name'] ?? 'Sir/Ma\'am';

            // 🔥 Knowledge Base & Delivery Info (From Dashboard)
            $knowledgeBase = $client->knowledge_base ?? "সাধারণ ই-কমার্স পলিসি ফলো করো।";
            $deliveryInfo = "Inside Dhaka: {$client->delivery_charge_inside} Tk, Outside: {$client->delivery_charge_outside} Tk";

            // 🔥 DYNAMIC PROMPT GENERATION (Salesman Brain)
            $systemPrompt = $this->generateDynamicSystemPrompt($client, $instruction, $contextData, $orderHistory, $inventoryData, $currentTime, $userName, $knowledgeBase, $deliveryInfo);
            
            Log::info("📝 System Prompt Generated.");

            // Message Building
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            
            // History Injection (Last 6 Interactions)
            $history = $session->customer_info['history'] ?? [];
            foreach (array_slice($history, -6) as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            
            // Current Message (With Vision Support)
            if ($base64Image) {
                $messages[] = [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $userMessage],
                        ['type' => 'image_url', 'image_url' => ['url' => $base64Image]]
                    ]
                ];
            } else {
                $messages[] = ['role' => 'user', 'content' => $userMessage];
            }

            // Call LLM
            Log::info("📡 Calling LLM...");
            $aiResponse = $this->callLlmChain($messages);
            
            if (!$aiResponse) {
                Log::error("❌ LLM returned null.");
                return "দুঃখিত, আমি এই মুহূর্তে উত্তর দিতে পারছি না। কিছুক্ষণ পর আবার চেষ্টা করুন।";
            }

            // Save History
            $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
            $info = $session->customer_info;
            $info['history'] = array_slice($history, -20);
            $session->update(['customer_info' => $info]);

            return $aiResponse;
        });
    }


    // =====================================
    // GLOBAL HELPER METHODS
    // =====================================

    /**
     * 🔥 DYNAMIC PROMPT GENERATOR WITH TAGS (The Heart of Salesman Logic)
     */
    private function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo)
    {
        // 1. সেলারের কাস্টম প্রম্পট আছে কিনা চেক করা
        $customPrompt = $client->custom_prompt;

        // 2. যদি কাস্টম প্রম্পট না থাকে, তবে ডিফল্ট সেলসম্যান প্রম্পট ব্যবহার করা (EXTREME VERSION)
        if (empty($customPrompt)) {
            $customPrompt = <<<EOT
তুমি হলে **{{shop_name}}**-এর একজন দক্ষ এবং স্মার্ট অনলাইন সেলস এক্সিকিউটিভ।

**твоমার নলেজ বেস (দোকানের নিয়মকানুন):**
{{knowledge_base}}
**ডেলিভারি চার্জ:** {{delivery_info}}

**তোমার নিয়মাবলী (Strict Rules):**
১. সবসময় ভদ্র এবং প্রফেশনাল ভাষায় (বাংলায়) কথা বলবে। "তুমি" না বলে "আপনি" বলবে।
২. **LINK SHARING:** কাস্টমার যদি বিস্তারিত দেখতে চায় বা কিনতে চায়, তবে **{{inventory}}** থেকে পাওয়া `link` তাকে দিবে। লিংকটি সরাসরি মেসেজে দিবে।
৩. **IMAGE SHOWING:** কাস্টমার যদি কোনো পণ্যের ছবি দেখতে চায়, তবে **{{inventory}}** লিস্টে থাকা `image_url` এর লিংকটি সরাসরি মেসেজে দিবে।
৪. **DETAILS SHARING:** কাস্টমার যদি পণ্যের বিবরণ (Description) জানতে চায়, তবে **{{inventory}}** থেকে `desc` বা `description` পড়ে বিস্তারিত জানাবে।
৫. কাস্টমার "অর্ডার" বা "কিনব" বললে অর্ডার কনফার্ম করার প্রসেস শুরু করবে (নাম, ফোন, ঠিকানা নিবে)।
৬. অর্ডার কনফার্ম হলে অবশ্যই **{{last_order}}** চেক করে অর্ডার আইডি কাস্টমারকে দিবে।
৭. যদি কাস্টমার ছবি পাঠায়, সেটা বিশ্লেষণ করে ইনভেন্টরি থেকে সিমিলার প্রোডাক্ট সাজেস্ট করবে।

**বর্তমান পরিস্থিতি (Instruction):**
{{instruction}}

**প্রয়োজনীয় তথ্য (Database Context):**
- বর্তমান সময়: {{time}}
- কাস্টমার: {{customer_name}}
- অর্ডার ইতিহাস: {{order_history}}
- প্রোডাক্ট প্রসঙ্গ: {{product_context}}
- ইনভেন্টরি (লিংক ও ডিটেইলস সহ): {{inventory}}
EOT;
        }

        // 3. লেটেস্ট অর্ডারের তথ্য (Last Order Tag)
        $recentOrder = Order::where('client_id', $client->id)
            ->where('sender_id', request('sender_id') ?? 0)
            ->latest()
            ->first();
            
        $recentOrderInfo = $recentOrder 
            ? "সর্বশেষ অর্ডার আইডি: #{$recentOrder->id} (অবস্থা: {$recentOrder->order_status}, মোট: {$recentOrder->total_amount} টাকা)" 
            : "কোনো সাম্প্রতিক অর্ডার নেই।";

        // 4. Tag Replacement Map (Data Injection)
        $tags = [
            '{{shop_name}}'       => $client->shop_name,
            '{{knowledge_base}}'  => $knowledgeBase,
            '{{delivery_info}}'   => $deliveryInfo,
            '{{instruction}}'     => $instruction,
            '{{product_context}}' => $prodCtx,
            '{{order_history}}'   => $ordCtx,
            '{{inventory}}'       => $invData,
            '{{time}}'            => $time,
            '{{customer_name}}'   => $userName,
            '{{last_order}}'      => $recentOrderInfo,
            
            // Fallback Support
            '{shop_name}'       => $client->shop_name,
            '{knowledge_base}'  => $knowledgeBase,
            '{delivery_info}'   => $deliveryInfo,
            '{instruction}'     => $instruction,
            '{product_context}' => $prodCtx,
            '{order_history}'   => $ordCtx,
            '{inventory}'       => $invData,
            '{time}'            => $time,
            '{customer_name}'   => $userName,
            '{last_order}'      => $recentOrderInfo,
        ];

        return strtr($customPrompt, $tags);
    }

    /**
     * [EXTREME UPGRADE] ইনভেন্টরি সার্চ - লিংক এবং ডিটেইলস সহ
     * Accept Client Model to generate routes
     */
    private function getInventoryData($client, $userMessage)
    {
        $clientId = $client->id;
        $cacheKey = "inv_{$clientId}_" . md5(Str::limit($userMessage, 20));

        return Cache::remember($cacheKey, 60, function () use ($client, $userMessage) {
            $stopWords = ['product', 'offer', 'collection', 'list', 'show', 'ki', 'ace', 'store', 'shop', 'stock', 'pic', 'photo', 'chobi', 'link', 'details'];
            $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2 && !in_array(strtolower($w), $stopWords));
            
            $query = Product::where('client_id', $client->id)->where('stock_status', 'in_stock');
            
            if (!empty($keywords)) {
                $query->where(function($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%")
                          ->orWhereHas('category', function($cq) use ($word){
                              $cq->where('name', 'like', "%{$word}%");
                          });
                    }
                });
            } else {
                $query->inRandomOrder();
            }

            $products = $query->limit(5)->get();
            
            if ($products->isEmpty()) {
                $products = Product::where('client_id', $client->id)
                    ->where('stock_status', 'in_stock')
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
            }

            // 🔥 Extreme Data Mapping
            return $products->map(function($p) use ($client) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => "Tk " . ($p->sale_price ?? $p->regular_price),
                    'stock' => $p->stock_quantity > 0 ? 'In Stock' : 'Out of Stock',
                    // Description Truncated to avoid token limit but enough for AI context
                    'desc' => Str::limit(strip_tags($p->description ?? $p->short_description), 300),
                    // Generated Product Page Link
                    'link' => route('shop.product.details', [$client->slug, $p->slug]),
                    'image_url' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null
                ];
            })->toJson();
        });
    }

    /**
     * 🔥 VOICE TO TEXT CONVERSION (Whisper API)
     */
    public function convertVoiceToText($audioUrl)
    {
        $tempPath = null;
        try {
            // 1. অডিও ডাউনলোড
            $audioResponse = Http::get($audioUrl);
            if (!$audioResponse->successful()) return null;

            // 2. টেম্প ফাইল তৈরি
            $extension = 'mp3'; 
            if (str_contains($audioResponse->header('Content-Type'), 'ogg')) $extension = 'ogg';
            
            $tempFileName = 'voice_' . uniqid() . '.' . $extension;
            $tempPath = storage_path('app/' . $tempFileName);
            file_put_contents($tempPath, $audioResponse->body());

            // 3. Whisper API কল
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            
            $response = Http::withToken($apiKey)
                ->attach('file', fopen($tempPath, 'r'), $tempFileName)
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => 'bn', // বাংলা ডিটেকশন ফোর্স করা
                    'response_format' => 'json'
                ]);

            if ($response->successful()) {
                return $response->json()['text'] ?? null;
            } else {
                Log::error("Whisper API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Voice Conversion Failed: " . $e->getMessage());
        } finally {
            // ক্লিনআপ
            if ($tempPath && file_exists($tempPath)) @unlink($tempPath);
        }
        return null;
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
                $statusMap = [
                    'pending' => 'অপেমান (Pending)',
                    'processing' => 'প্রক্রিয়াধীন (Processing)',
                    'shipped' => 'শিপ করা হয়েছে (Shipped)',
                    'delivered' => 'ডেলিভারি সম্পন্ন (Delivered)',
                    'cancelled' => 'বাতিল (Cancelled)',
                    'hold' => 'হোল্ডে আছে (On Hold)'
                ];
                $status = $statusMap[$order->order_status] ?? ucfirst($order->order_status);
                
                return "FOUND_ORDER: অর্ডার #{$order->id}। বর্তমান অবস্থা: {$status}। মোট বিল: {$order->total_amount} টাকা।";
            }
        }
        return null;
    }

    private function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->latest()
            ->take(1)
            ->get();

        if ($orders->isEmpty()) return "নতুন কাস্টমার (কোনো পূর্ববর্তী অর্ডার নেই)।";
        
        $o = $orders->first();
        return "সর্বশেষ অর্ডার: #{$o->id} ({$o->order_status}) - {$o->total_amount} টাকা।";
    }

    private function callLlmChain($messages) {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            $response = Http::withToken($apiKey)->timeout(40)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'max_tokens' => 600, 
                'temperature' => 0.4, 
            ]);
            return $response->json()['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error("LLM Error: " . $e->getMessage());
            return null;
        }
    }

    private function isTrackingIntent($msg) {
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অর্ডার কি', 'অর্ডার চেক', 'অবস্থা', 'জানতে চাই', 'পৌঁছাবে', 'কবে পাব', 'tracking', 'order status'];
        $msgLower = mb_strtolower($msg, 'UTF-8');
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function detectHateSpeech($message) {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fuck', 'shit', 'bitch', 'asshole'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

    /**
     * 🔥 SAAS ENABLED: Sends Telegram alert using CLIENT'S token
     */
    public function sendTelegramAlert($clientId, $senderId, $message) {
        try {
            $client = Client::find($clientId);
            if (!$client || empty($client->telegram_bot_token) || empty($client->telegram_chat_id)) return;

            Http::post("https://api.telegram.org/bot{$client->telegram_bot_token}/sendMessage", [
                'chat_id' => $client->telegram_chat_id,
                'text' => "🔔 **Shop Alert: {$client->shop_name}**\nUser: `{$senderId}`\n{$message}",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"],
                        ['text' => '📋 Check List', 'callback_data' => "list_stopped_users"]
                    ]]
                ])
            ]);
        } catch (\Exception $e) { Log::error("Telegram Error: " . $e->getMessage()); }
    }
}