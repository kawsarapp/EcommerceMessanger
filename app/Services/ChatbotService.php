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

        $userMessage = $userMessage ?? '';

        // 1. IMAGE HANDLING
        $base64Image = null;
        if ($imageUrl) {
            try {
                $imgResponse = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->timeout(10)->get($imageUrl);

                if ($imgResponse->successful()) {
                    $mime = $imgResponse->header('Content-Type') ?: 'image/jpeg';
                    $base64Image = "data:" . $mime . ";base64," . base64_encode($imgResponse->body());
                    Log::info("Image downloaded successfully.");
                } else {
                    Log::error("Image download failed: " . $imgResponse->status());
                }
            } catch (\Exception $e) {
                Log::error("Image Pre-fetch Error: " . $e->getMessage());
            }
        }

        // Image only logic
        if (empty(trim($userMessage)) && $base64Image) {
            $userMessage = "User sent an image. Please describe it and match with inventory.";
            Log::info("ℹ️ Auto-filled message for image input.");
        } elseif (empty(trim($userMessage))) {
            Log::warning("⚠️ Empty message received. Returning null.");
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
            
            // 🔄 SMART RESET CHECK
            // Check if the user is asking for a SPECIFIC product
            $newProduct = $this->findProductSystematically($clientId, $userMessage);
            
            if ($newProduct) {
                $currentProductId = $session->customer_info['product_id'] ?? null;
                $currentStep = $session->customer_info['step'] ?? '';

                if ($newProduct->id != $currentProductId || $currentStep === 'collect_info') {
                    Log::info("🔄 Product Switch / Reset: Found ({$newProduct->name})");
                    $session->update([
                        'customer_info' => [
                            'step' => 'start', 
                            'product_id' => $newProduct->id, 
                            'history' => $session->customer_info['history'] ?? []
                        ]
                    ]);
                }
            } else {
                // 🔥 GENERIC QUERY HANDLING
                // যদি প্রোডাক্ট না পাওয়া যায়, কিন্তু ইউজার সাধারণ প্রশ্ন করে (ki ace, offer ki),
                // তবে সেশন রিসেট করো যাতে সে 'collect_info' লুপে আটকে না থাকে।
                $genericPhrases = ['ki ace', 'ki ase', 'product ace', 'offer', 'collection', 'list', 'show', 'কি আছে', 'অফার'];
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
            
            $result = $handler->process($session, (string)$userMessage);
            
            $instruction = $result['instruction'] ?? "আমি বুঝতে পারিনি।";
            $contextData = $result['context'] ?? "[]";

            // Order Creation
            if (isset($result['action']) && $result['action'] === 'create_order') {
                Log::info("🚀 Creating Order...");
                try {
                    $order = $this->orderService->finalizeOrderFromSession($clientId, $senderId, $client);
                    $instruction .= " (System: Order #{$order->id} created successfully!)";
                    $this->sendTelegramAlert($clientId, $senderId, "✅ Order Placed: #{$order->id} - {$order->total_amount} Tk");
                } catch (\Exception $e) {
                    $instruction = "Technical error creating order.";
                    Log::error("❌ Order Error: " . $e->getMessage());
                }
            }

            // Context Loading
            $inventoryData = $this->getInventoryData($clientId, $userMessage); 
            $orderHistory = $this->buildOrderContext($clientId, $senderId);
            $currentTime = now()->format('l, h:i A');

            // Prompt Generation
            $systemPrompt = $this->generateSystemPrompt($instruction, $contextData, $orderHistory, $inventoryData, $currentTime);
            Log::info("📝 Prompt Generated with Inventory Data.");

            // Message Building
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            
            $history = $session->customer_info['history'] ?? [];
            foreach (array_slice($history, -4) as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            
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
     * [OPTIMIZED] স্মার্ট ইনভেন্টরি সার্চ (With Caching)
     * এটি গ্লোবাল প্রম্পটের জন্য দরকার, তাই এখানে রাখা হলো।
     */
    private function getInventoryData($clientId, $userMessage)
    {
        $cacheKey = "inv_{$clientId}_" . md5(Str::limit($userMessage, 20));

        return Cache::remember($cacheKey, 60, function () use ($clientId, $userMessage) {
            // 🔥 STOP WORDS: These words will NOT trigger a name search.
            $stopWords = ['product', 'products', 'item', 'items', 'offer', 'offers', 'collection', 'list', 'show', 'dekhann', 'janan', 'bolen', 'ki', 'ace', 'ase', 'store', 'shop', 'kicu', 'kichu', 'stock', 'available'];
            
            $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2 && !in_array(strtolower($w), $stopWords));
            
            $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');
            
            if (!empty($keywords)) {
                // Specific Search
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
                // 🔥 GENERIC FALLBACK: If keywords are empty (e.g. "ki ki product ace"), show RANDOM products
                $query->inRandomOrder();
            }

            $products = $query->limit(5)->get(['id', 'name', 'sale_price', 'stock_quantity', 'thumbnail']);
            
            if ($products->isEmpty()) {
                // If specific search failed, fallback to random suggestion
                return Product::where('client_id', $clientId)
                    ->where('stock_status', 'in_stock')
                    ->inRandomOrder()
                    ->limit(3)
                    ->get(['id', 'name', 'sale_price', 'stock_quantity', 'thumbnail'])
                    ->toJson();
            }

            return $products->toJson();
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

**Context:**
- Product Context: {$prodCtx}
- Inventory Match (Available Products): {$invData}
- History: {$ordCtx}

**Rules:**
1. **CHECK INVENTORY FIRST:** The [Inventory Match] list contains the products we ACTUALLY have. 
2. If user asks "ki ace", "offer ki", or generic questions, LIST the items from [Inventory Match].
3. Show [CAROUSEL: id] for any product you mention.
4. If [Inventory Match] is empty, assume we are out of stock.
5. Be concise and friendly.

Reply in Bangla/English mix.
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
                'max_tokens' => 350,
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