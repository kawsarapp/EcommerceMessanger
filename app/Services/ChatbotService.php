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

        // 🔥 NULL SAFETY GUARD: Ensure message is never null
        $userMessage = $userMessage ?? '';

        // 🚀 1. IMAGE HANDLING (Robust)
        $base64Image = null;
        if ($imageUrl) {
            try {
                $imgResponse = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->timeout(10)->get($imageUrl);

                if ($imgResponse->successful()) {
                    $mime = $imgResponse->header('Content-Type') ?: 'image/jpeg';
                    $base64Image = "data:" . $mime . ";base64," . base64_encode($imgResponse->body());
                    Log::info("Image downloaded successfully for User: $senderId");
                } else {
                    Log::error("Image download failed: " . $imgResponse->status());
                }
            } catch (\Exception $e) {
                Log::error("Image Pre-fetch Error: " . $e->getMessage());
            }
        }

        // যদি শুধু ইমেজ থাকে এবং কোনো টেক্সট না থাকে
        if (empty(trim($userMessage)) && $base64Image) {
            $userMessage = "User sent an image. Please describe it and match with inventory.";
            Log::info("ℹ️ Auto-filled message for image input.");
        } elseif (empty(trim($userMessage))) {
            Log::warning("⚠️ Empty message received in ChatbotService. Returning null.");
            return null;
        }

        // 🔥 2. SAFETY CHECK (Hate Speech / Abuse)
        // এআই কল করার আগেই চেক করা হবে, যাতে টোকেন সেভ হয় এবং সিস্টেম নিরাপদ থাকে
        if ($this->detectHateSpeech($userMessage)) {
            Log::warning("🚫 Hate speech detected from User: $senderId");
            $this->sendTelegramAlert($clientId, $senderId, "⚠️ Abusive Language Detected: '$userMessage'");
            return "অনুগ্রহ করে ভদ্র ভাষা ব্যবহার করুন। আমাদের এজেন্ট শীঘ্রই আপনার সাথে যোগাযোগ করবে।";
        }

        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image) {

            // Session Lock
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

            $client = Client::find($clientId);
            $customerInfo = $session->customer_info;

            // 🔥 3. SMART ORDER TRACKING SHORTCUT (Token Saver)
            // যদি ইউজার ফোন নম্বর দেয় বা অর্ডারের স্ট্যাটাস জানতে চায়, এআই ছাড়াই উত্তর দিন
            if ($this->isTrackingIntent($userMessage) || preg_match('/01[3-9]\d{8}/', $userMessage)) {
                $orderStatusMsg = $this->lookupOrderByPhone($clientId, $userMessage);
                if ($orderStatusMsg && str_contains($orderStatusMsg, 'FOUND_ORDER')) {
                    // সুন্দর ফরম্যাটে উত্তর রিটার্ন করুন
                    $cleanMsg = str_replace('FOUND_ORDER:', '', $orderStatusMsg);
                    return "আপনার অর্ডারের তথ্য পেয়েছি: \n" . $cleanMsg . "\nধন্যবাদ আমাদের সাথে থাকার জন্য!";
                }
            }
            
            // 🔄 SMART RESET: Check if user is asking for a SPECIFIC product
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
                // 🔥 GENERIC QUERY RESET
                $genericPhrases = ['ki ace', 'ki ase', 'product ace', 'offer', 'collection', 'list', 'show', 'কি আছে', 'অফার', 'price koto', 'dam koto', 'menu'];
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

            // Step Processing
            $session->refresh(); 
            $stepName = $session->customer_info['step'] ?? 'start';
            Log::info("👣 Processing Step: $stepName");

            $steps = [
                'start' => new StartStep(),
                'select_variant' => new VariantStep(),
                'collect_info' => new AddressStep(),
                'confirm_order' => new ConfirmStep(), // Updated Logic included here
                'completed' => new StartStep(),
            ];

            $handler = $steps[$stepName] ?? $steps['start'];
            
            // Safe Call
            $result = $handler->process($session, (string)$userMessage);
            
            $instruction = $result['instruction'] ?? "আমি বুঝতে পারিনি।";
            $contextData = $result['context'] ?? "[]";

            // Order Creation Action
            if (isset($result['action']) && $result['action'] === 'create_order') {
                Log::info("🚀 Action Triggered: create_order");
                try {
                    $order = $this->orderService->finalizeOrderFromSession($clientId, $senderId, $client);
                    
                    // 🔥 AI কে অর্ডার আইডি জানিয়ে দেওয়া হচ্ছে
                    $instruction .= " (SYSTEM: Order Created Successfully! Order ID is #{$order->id}. You MUST tell the user this Order ID immediately.)";
                    
                    // Telegram Notification (Dynamic Token)
                    $this->sendTelegramAlert($clientId, $senderId, "✅ Order Placed: #{$order->id} - {$order->total_amount} Tk");
                } catch (\Exception $e) {
                    $instruction = "Technical error creating order. Please apologize.";
                    Log::error("❌ Order Error: " . $e->getMessage());
                }
            }

            // Context Loading
            $inventoryData = $this->getInventoryData($clientId, $userMessage); 
            $orderHistory = $this->buildOrderContext($clientId, $senderId);
            $currentTime = now()->format('l, h:i A');
            $userName = $session->customer_info['name'] ?? 'Unknown';

            // Prompt Generation (Dynamic)
            $systemPrompt = $this->generateSystemPrompt($instruction, $contextData, $orderHistory, $inventoryData, $currentTime, $userName, $clientId, $senderId);
            Log::info("📝 System Prompt Generated.");

            // Message Building
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            
            // Add History (Limit context window)
            $history = $session->customer_info['history'] ?? [];
            foreach (array_slice($history, -6) as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            
            // Current Message
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
            
            // Fallback if LLM fails
            if (!$aiResponse) {
                Log::error("❌ LLM returned null.");
                return "দুঃখিত, আমি এই মুহূর্তে উত্তর দিতে পারছি না। কিছুক্ষণ পর আবার চেষ্টা করুন।";
            }

            Log::info("🗣️ AI Response: " . Str::limit($aiResponse, 50));

            // Save History
            if ($aiResponse) {
                $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
                $info = $session->customer_info;
                $info['history'] = array_slice($history, -20);
                $session->update(['customer_info' => $info]);
            }

            return $aiResponse;
        });
    }


    // =====================================
    // GLOBAL HELPER METHODS
    // =====================================

    /**
     * [OPTIMIZED] ইনভেন্টরি সার্চ (Price, Description, Image সহ)
     */
    private function getInventoryData($clientId, $userMessage)
    {
        $cacheKey = "inv_{$clientId}_" . md5(Str::limit($userMessage, 20));

        return Cache::remember($cacheKey, 60, function () use ($clientId, $userMessage) {
            $stopWords = ['product', 'products', 'item', 'items', 'offer', 'offers', 'collection', 'list', 'show', 'dekhann', 'janan', 'bolen', 'ki', 'ace', 'ase', 'store', 'shop', 'kicu', 'kichu', 'stock', 'available', 'details', 'pic', 'picture'];
            
            $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2 && !in_array(strtolower($w), $stopWords));
            
            $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');
            
            if (!empty($keywords)) {
                $query->where(function($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%")
                          ->orWhere('tags', 'like', "%{$word}%")
                          ->orWhereHas('category', function($cq) use ($word){
                              $cq->where('name', 'like', "%{$word}%");
                          });
                    }
                });
            } else {
                $query->inRandomOrder();
            }

            $products = $query->limit(5)->get();
            
            // Fallback Logic
            if ($products->isEmpty()) {
                $products = Product::where('client_id', $clientId)
                    ->where('stock_status', 'in_stock')
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
            }

            return $products->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sale_price' => $p->sale_price,
                    'regular_price' => $p->regular_price,
                    'stock_available' => $p->stock_quantity,
                    'description' => Str::limit(strip_tags($p->short_description ?? $p->description), 150),
                    'image_url' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null
                ];
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
            return true;
        }
        return false;
    }

    /**
     * 🔥 DYNAMIC PROMPT GENERATION (FUTURE PROOF)
     */
    private function generateSystemPrompt($instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $clientId, $senderId)
    {
        // লেটেস্ট অর্ডারের তথ্য নিয়ে আসা (যাতে ইউজার জিজ্ঞেস করলেই আইডি বলা যায়)
        $recentOrder = Order::where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->latest()
            ->first();
            
        $recentOrderInfo = $recentOrder 
            ? "LAST ORDER: #{$recentOrder->id} (Status: {$recentOrder->order_status}, Total: {$recentOrder->total_amount})" 
            : "No recent order";

        return <<<EOT
{$instruction}

**System Role:** Elite AI Sales Associate for an E-commerce Brand in Bangladesh.
**Objective:** Convert inquiries into orders politely and efficiently.
**Customer Name:** {$userName}
**Current Time:** {$time}

### 🛑 CRITICAL LOGIC RULES (DO NOT IGNORE):
1. **CONFIRMATION:** Before creating an order, ALWAYS show a summary (Product, Price, Address) and ask: "Is this correct?".
2. **CREATE:** Only create order if user says "Yes", "Ji", "Confirm" AFTER seeing the summary.
3. **ORDER ID:** If user asks for Order ID, check [LATEST ORDER DB] below and tell them immediately.
4. **PICS vs TAKA:** "200 pics" = QUANTITY. "200 tk" = PRICE.
5. **INVENTORY:** Use [Inventory Match] to answer "ki ace" or "offer". Use [CAROUSEL: id1, id2] for multiple products.
6. **IMAGES:** If user asks for picture, output 'image_url' from inventory.

### 📂 DATA PACKETS:
- [Product Context]: {$prodCtx}
- [Inventory Match]: {$invData}
- [Customer History]: {$ordCtx}
- [LATEST ORDER DB]: {$recentOrderInfo} 👈 (Use this for status/ID check)

Reply in friendly Bangla (using English terms for Price, Size, etc).
EOT;
    }

    private function isTrackingIntent($msg)
    {
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অর্ডার কি', 'অর্ডার চেক', 'অবস্থা', 'জানতে চাই', 'পৌঁছাবে', 'কবে পাব', 'tracking', 'order status'];
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
            'delivery', 'ডেলিভারি', 'দিবেন', 'দিবে', 'দিয়েন', 'দিয়ে', 'পৌছে', 'urgent', 'দ্রুত', 'সকালে', 'রাতে'
        ];
        $msgLower = strtolower($msg);
        foreach ($noteKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function extractDeliveryNote($msg)
    {
        $commonWords = ['ami', 'amra', 'tumi', 'apni', 'she', 'i', 'you', 'we', 'want', 'need', 'please', 'kindly', 'দয়া', 'করে', 'চাই', 'লাগবে'];
        $words = explode(' ', strtolower($msg));
        $filtered = array_filter($words, function($w) use ($commonWords) {
            return !in_array(strtolower(trim($w)), $commonWords) && strlen(trim($w)) > 2;
        });
        return implode(' ', $filtered);
    }

    private function detectOrderCancellation($msg, $senderId)
    {
        if (empty($msg)) return false;
        $cancelPhrases = ['cancel', 'বাতিল', 'cancel koro', 'নিবো না', 'লাগবে না', 'চাই না', 'change mind', 'ভুল হয়েছে'];
        $msgLower = mb_strtolower($msg, 'UTF-8');
        foreach ($cancelPhrases as $phrase) {
            if (mb_strpos($msgLower, mb_strtolower($phrase, 'UTF-8')) !== false) {
                return Order::where('sender_id', $senderId)->whereIn('order_status', ['processing', 'pending'])->exists();
            }
        }
        return false;
    }

    private function detectHateSpeech($message)
    {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fuck', 'shit', 'bitch', 'asshole'];
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

        if ($orders->isEmpty()) return "No previous orders.";
        
        $o = $orders->first();
        return "Last Order: #{$o->id} ({$o->order_status}) - {$o->total_amount} Tk";
    }

    public function convertVoiceToText($audioUrl)
    {
        // (Existing Logic Kept Same for Stability)
        $tempPath = null;
        try {
            Log::info("Starting Voice Transcription for: " . $audioUrl);
            $audioResponse = Http::get($audioUrl);
            if (!$audioResponse->successful()) return null;

            $contentType = $audioResponse->header('Content-Type');
            $extension = 'mp3';
            // Map content type to extension logic...
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
                return $response->json()['text'] ?? null;
            }
            Log::error("Whisper API Error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("Voice Conversion Failed: " . $e->getMessage());
            return null;
        } finally {
            if ($tempPath && file_exists($tempPath)) @unlink($tempPath);
        }
    }

    private function callLlmChain($messages) {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            $response = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'max_tokens' => 450, 
                'temperature' => 0.2, 
            ]);
            return $response->json()['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error("LLM Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 🔥 SAAS ENABLED: Sends Telegram alert using CLIENT'S token
     */
    public function sendTelegramAlert($clientId, $senderId, $message) {
        try {
            // 1. সেলার খুঁজে বের করা (Config থেকে নয়, DB থেকে)
            $client = Client::find($clientId);

            // 2. টোকেন চেক করা
            if (!$client || empty($client->telegram_bot_token) || empty($client->telegram_chat_id)) {
                return; // টেলিগ্রাম সেটআপ করা নেই
            }

            $token = $client->telegram_bot_token;
            $chatId = $client->telegram_chat_id;

            // 3. ডাইনামিক টোকেন দিয়ে মেসেজ পাঠানো
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "🔔 **New Alert**\nShop: {$client->shop_name}\nUser: `{$senderId}`\n{$message}",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"],
                            ['text' => '📋 Stopped List', 'callback_data' => "list_stopped_users"]
                        ]
                    ]
                ])
            ]);
        } catch (\Exception $e) { 
            Log::error("Telegram Notification Error: " . $e->getMessage()); 
        }
    }
}