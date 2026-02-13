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

        // 🔥 NULL SAFETY GUARD: Ensure message is never null to prevent TypeErrors
        $userMessage = $userMessage ?? '';

        // 🚀 1. IMAGE HANDLING (Robust)
        $base64Image = null;
        if ($imageUrl) {
            try {
                // Facebook Image sometimes needs User-Agent
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
            // If both are empty, safely return null
            Log::warning("⚠️ Empty message received in ChatbotService. Returning null.");
            return null;
        }

        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image) {

            // Session Lock
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'history' => []]]
            );
            $session = OrderSession::where('sender_id', $senderId)->lockForUpdate()->first();

            if ($session->is_human_agent_active) {
                Log::info("⏸️ Human Agent Active. AI Paused.");
                return null;
            }

            $client = Client::find($clientId);
            
            // 🔄 SMART RESET: Check if user is asking for a SPECIFIC product
            $newProduct = $this->findProductSystematically($clientId, $userMessage);
            
            if ($newProduct) {
                $currentProductId = $session->customer_info['product_id'] ?? null;
                $currentStep = $session->customer_info['step'] ?? '';

                if ($newProduct->id != $currentProductId || $currentStep === 'collect_info') {
                    Log::info("🔄 Product Switch: Found ({$newProduct->name})");
                    $session->update([
                        'customer_info' => [
                            'step' => 'start', 
                            'product_id' => $newProduct->id, 
                            'history' => $session->customer_info['history'] ?? []
                        ]
                    ]);
                }
            } else {
                // 🔥 GENERIC QUERY RESET
                // If user asks general questions like "ki ace", "offer", reset session to start
                $genericPhrases = ['ki ace', 'ki ase', 'product ace', 'offer', 'collection', 'list', 'show', 'কি আছে', 'অফার', 'price koto', 'dam koto'];
                foreach ($genericPhrases as $phrase) {
                    if (stripos(strtolower($userMessage), $phrase) !== false) {
                        Log::info("🔄 Generic Query Reset Triggered.");
                        $session->update([
                            'customer_info' => [
                                'step' => 'start', 
                                'history' => $session->customer_info['history'] ?? []
                            ]
                        ]);
                        break;
                    }
                }
            }

            // Step Processing
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
            
            // 🔥 SAFE CALL: Force string type to prevent AddressStep error
            $result = $handler->process($session, (string)$userMessage);
            
            $instruction = $result['instruction'] ?? "আমি বুঝতে পারিনি।";
            $contextData = $result['context'] ?? "[]";

            // Order Creation Action
            if (isset($result['action']) && $result['action'] === 'create_order') {
                Log::info("🚀 Action Triggered: create_order");
                try {
                    $order = $this->orderService->finalizeOrderFromSession($clientId, $senderId, $client);
                    $instruction .= " (System: Order #{$order->id} created successfully! Congratulate the customer.)";
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

            // Prompt Generation (Dynamic)
            $systemPrompt = $this->generateSystemPrompt($instruction, $contextData, $orderHistory, $inventoryData, $currentTime);
            Log::info("📝 System Prompt Generated.");

            // Message Building
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            
            // Add History
            $history = $session->customer_info['history'] ?? [];
            foreach (array_slice($history, -4) as $chat) {
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
    // (Specific logic moved to Step Classes, keeping generic ones here)
    // =====================================

    /**
     * [OPTIMIZED] ইনভেন্টরি সার্চ (Price, Description, Image সহ)
     */
    private function getInventoryData($clientId, $userMessage)
    {
        // Cache key based on message keywords
        $cacheKey = "inv_{$clientId}_" . md5(Str::limit($userMessage, 20));

        return Cache::remember($cacheKey, 60, function () use ($clientId, $userMessage) {
            // Stop words prevent searching for generic terms in "name"
            $stopWords = ['product', 'products', 'item', 'items', 'offer', 'offers', 'collection', 'list', 'show', 'dekhann', 'janan', 'bolen', 'ki', 'ace', 'ase', 'store', 'shop', 'kicu', 'kichu', 'stock', 'available', 'details', 'pic', 'picture'];
            
            $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2 && !in_array(strtolower($w), $stopWords));
            
            $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');
            
            if (!empty($keywords)) {
                $query->where(function($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%")
                          ->orWhere('tags', 'like', "%{$word}%")
                          // ✅ SQL FIX: Relation Search
                          ->orWhereHas('category', function($cq) use ($word){
                              $cq->where('name', 'like', "%{$word}%");
                          });
                    }
                });
            } else {
                // If no keywords, show random featured products
                $query->inRandomOrder();
            }

            $products = $query->limit(5)->get();
            
            if ($products->isEmpty()) {
                // Fallback to random if search yields nothing
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
                    'regular_price' => $p->regular_price, // Added regular price
                    'stock' => $p->stock_quantity,
                    'description' => Str::limit(strip_tags($p->short_description ?? $p->description), 150), // Added Description
                    'image_url' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null // Added full image URL
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

            Log::info("Updated Note for Order #{$recentOrder->id}: $note");
            return true;
        }
        return false;
    }

    /**
     * 🔥 DYNAMIC PROMPT GENERATION
     */
    private function generateSystemPrompt($instruction, $prodCtx, $ordCtx, $invData, $time)
    {
        return <<<EOT
{$instruction}

**System Role:** Elite AI Sales Associate for an E-commerce Brand in Bangladesh.
**Objective:** Convert inquiries into orders politely and efficiently.

### 🛡️ STRICT GUIDELINES:
1. **INVENTORY FIRST:** The [Inventory Match] list contains the products we ACTUALLY have. 
2. **SHOWING PRODUCTS:** If user asks "ki ace", "offer", or general product questions, list items from [Inventory Match].
   - **IMPORTANT:** Use [CAROUSEL: id1, id2, id3] to show multiple products. Comma separate IDs.
3. **PRICING & OFFERS:** - If 'regular_price' > 'sale_price', say: "Regular Price: X, Offer Price: Y". Otherwise just show Sale Price.
   - Mention key details from the 'description'.
4. **IMAGES:** If user asks for a picture (e.g., "pic daw"), output the 'image_url' from [Inventory Match] directly in the text.
5. **Context:** [Product Context] is the specific item user selected. Use it for specific questions.
6. If [Inventory Match] is empty, assume we are out of stock.

### 📂 DATA PACKETS:
- [Product Context]: {$prodCtx}
- [Inventory Match]: {$invData}
- [Customer History]: {$ordCtx}
- [Time]: {$time}

Respond now in friendly Bangla (using English terms for Price, Size, etc).
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

    private function callLlmChain($messages) {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            $response = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'max_tokens' => 450, // Increased token limit for better details
            ]);
            return $response->json()['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error("LLM Error: " . $e->getMessage());
            return null;
        }
    }

    public function sendTelegramAlert($clientId, $senderId, $message) {
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
        } catch (\Exception $e) { Log::error("Telegram Error: " . $e->getMessage()); }
    }
}