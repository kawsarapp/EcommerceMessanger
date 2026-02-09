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
     */
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        try {
            // [CRASH FIX] ইনপুট অ্যারে হলে স্ট্রিং এ কনভার্ট করা
            if (is_array($userMessage)) {
                $userMessage = implode(' ', $userMessage);
            }
            
            // খালি বা নাল মেসেজ চেক
            if (!is_string($userMessage) || empty(trim($userMessage))) {
                Log::warning('Invalid user message received', [
                    'clientId' => $clientId, 'senderId' => $senderId
                ]);
                // ইমেজ থাকলে প্রসেস চলবে, না থাকলে রিটার্ন
                if (!$imageUrl) return "দুঃখিত, আপনার বার্তাটি বুঝতে পারছি না।";
                $userMessage = "Sent an image";
            }

            // ✅ Initialization
            $inventoryData = "[]";
            $productsJson = "[]";
            $currentTime = now()->format('l, h:i A');
            $delivery = 'Standard Delivery (2-4 days)';
            $paymentMethods = 'COD, bKash, Nagad';
            $shopPolicies = '7 days return, No warranty';
            $activeOffers = 'No active offers';
            $productContext = "";
            $systemInstruction = "";
            $selectedProductInfo = "NONE"; // [NEW] নির্দিষ্ট প্রোডাক্ট ফিক্স করার জন্য

            // Load session
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]
            );

            if ($session->is_human_agent_active) return null;

            // ✅ Null-safe customer info extraction
            $customerInfo = $session->customer_info ?? ['step' => 'start', 'product_id' => null, 'history' => []];
            $step = $customerInfo['step'] ?? 'start';
            $currentProductId = $customerInfo['product_id'] ?? null;
            $history = $customerInfo['history'] ?? [];

            // ✅ Session Reset Logic (User change mind)
            if (($step === 'completed' && !$this->isOrderRelatedMessage($userMessage)) || $this->detectNewIntent($userMessage)) {
                $session->update(['customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]);
                $step = 'start';
                $currentProductId = null;
                $history = [];
                $customerInfo = ['step' => 'start', 'product_id' => null, 'history' => []];
            }

            // ✅ Critical early-exit checks
            if ($this->detectOrderCancellation($userMessage, $senderId)) {
                return "[CANCEL_ORDER: {\"reason\": \"Customer requested cancellation\"}]";
            }

            $deliveryNote = null;
            if ($step === 'collect_info' && $this->detectDeliveryNote($userMessage)) {
                $deliveryNote = $this->extractDeliveryNote($userMessage);
            }

            if ($this->detectHateSpeech($userMessage)) {
                return "দুঃখিত, আমরা শালীন আলোচনা করি। অন্য কোনো সাহায্য প্রয়োজন?";
            }

            // ========================================
            // ORDER FLOW LOGIC
            // ========================================
            
            $inventoryData = $this->getInventoryData($clientId, $userMessage, $history);
            $productsJson = $inventoryData;

            // 1. Start Step or Searching
            if ($step === 'start' || !$currentProductId) {
                if ($this->isTrackingIntent($userMessage)) {
                    $phoneLookupResult = $this->lookupOrderByPhone($clientId, $userMessage);
                    if ($phoneLookupResult) return $phoneLookupResult;
                }

                $product = $this->findProductSystematically($clientId, $userMessage);
                
                if ($product) {
                    // স্টক চেক
                    $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
                    
                    if ($isOutOfStock) {
                        $systemInstruction = "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে অন্য কিছু দেখতে বলো।";
                        $productContext = json_encode(['id' => $product->id, 'name' => $product->name, 'stock' => 'Out of Stock']);
                    } else {
                        // [CRITICAL FIX] Array Handling for Colors/Sizes
                        $colors = $product->colors;
                        $sizes = $product->sizes;
                        
                        // লারাভেল কাস্টিং হ্যান্ডেল করা (অ্যারে বা স্ট্রিং যাই হোক)
                        $hasColor = !empty($colors) && (is_array($colors) ? count($colors) > 0 : strtolower((string)$colors) !== 'n/a');
                        $hasSize = !empty($sizes) && (is_array($sizes) ? count($sizes) > 0 : strtolower((string)$sizes) !== 'n/a');

                        if ($hasColor || $hasSize) {
                            $nextStep = 'select_variant';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। কালার/সাইজ জিজ্ঞেস করো।";
                        } else {
                            $nextStep = 'collect_info';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। অর্ডার কনফার্ম করতে নাম, ফোন এবং ঠিকানা চাও।";
                        }

                        $session->update(['customer_info' => array_merge($customerInfo, ['step' => $nextStep, 'product_id' => $product->id])]);
                        
                        // [NEW] Lock Info for AI
                        $selectedProductInfo = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price]);
                    }
                } else {
                    $systemInstruction = "কাস্টমার কিছু কিনতে চাচ্ছে কিন্তু আমরা প্রোডাক্টটি চিনতে পারছি না। ইনভেন্টরি থেকে সাজেস্ট করো।";
                }
            } 
            // 2. Variant Selection Step
            elseif ($step === 'select_variant') {
                $product = Product::find($currentProductId);
                
                if ($product) {
                    $selectedProductInfo = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price]);
                    
                    if ($this->hasVariantInMessage($userMessage, $product)) {
                        $variant = $this->extractVariant($userMessage, $product);
                        $customerInfo['variant'] = $variant;
                        
                        $session->update(['customer_info' => array_merge($customerInfo, ['step' => 'collect_info'])]);
                        $systemInstruction = "ভেরিয়েশন কনফার্ম হয়েছে (" . json_encode($variant) . ")। এখন নাম, ফোন এবং ঠিকানা চাও।";
                    } else {
                        $systemInstruction = "কাস্টমার এখনো কালার/সাইজ বলেনি। '{$product->name}' এর কালার/সাইজ জিজ্ঞেস করো।";
                    }
                } else {
                    $session->update(['customer_info' => ['step' => 'start']]);
                    $systemInstruction = "প্রোডাক্টটি খুঁজে পাওয়া যাচ্ছে না। নতুন করে শুরু করো।";
                }
            }
            // 3. Info Collection Step
            elseif ($step === 'collect_info') {
                $product = Product::find($currentProductId);
                $phone = $this->extractPhoneNumber($userMessage);
                $variantInfo = $customerInfo['variant'] ?? [];

                if ($product) {
                    $selectedProductInfo = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price]);

                    if ($phone) {
                        $noteStr = $deliveryNote ? " (Note: {$deliveryNote})" : "";
                        $systemInstruction = 
                            "কাস্টমার ফোন নম্বর ({$phone}) দিয়েছে। {$noteStr}\n" .
                            "এখন অর্ডারটি কনফার্ম করো।\n" .
                            "Product ID: {$product->id} (MUST USE THIS ID)\n" .
                            "Variant: " . json_encode($variantInfo) . "\n" .
                            "Generate [ORDER_DATA] tag properly.";
                    } else {
                        $systemInstruction = "আমরা এখনো ফোন নম্বর পাইনি। অর্ডার কনফার্ম করতে বিনীতভাবে ফোন নম্বর এবং ঠিকানা চাও।";
                    }
                } else {
                     $session->update(['customer_info' => ['step' => 'start']]);
                     $systemInstruction = "প্রোডাক্ট ডাটা মিসিং। কাস্টমারকে আবার প্রোডাক্ট সিলেক্ট করতে বলো।";
                }
            }
            elseif ($step === 'completed') {
                return "আপনার অর্ডারটি ইতিমধ্যে আমাদের সিস্টেমে জমা হয়েছে।";
            }

            // ========================================
            // AI PROMPT CONSTRUCTION
            // ========================================
            $orderContext = $this->buildOrderContext($clientId, $senderId);

$finalPrompt = <<<EOT
{$systemInstruction}

**ভূমিকা:** তুমি একজন স্মার্ট এবং বিনয়ী "অনলাইন শপ এক্সিকিউটিভ"। লক্ষ্য: কাস্টমারকে সাহায্য করা এবং অর্ডার কনফার্ম করা।

[LOCKED CONTEXT - DO NOT HALLUCINATE]:
- Selected Product: {$selectedProductInfo} (অর্ডার করার সময় শুধুমাত্র এই ID ব্যবহার করবে)
- Inventory: {$productsJson}
- Shop Info: Delivery: {$delivery}, Policies: {$shopPolicies}
- Current Time: {$currentTime}

[Customer History]:
{$orderContext}

[নির্দেশাবলী]:
১. **সতর্কতা:** [Selected Product] এ যদি "NONE" থাকে, তবে [ORDER_DATA] তৈরি করবে না। আগে প্রোডাক্ট সিলেক্ট করতে বলো।
২. **অর্ডার কনফার্মেশন:** কাস্টমার নাম, ফোন ও ঠিকানা দিলে এবং প্রোডাক্ট সিলেক্ট করা থাকলে তবেই অর্ডার কনফার্ম করো।
৩. **Wrong ID Prevention:** [ORDER_DATA] তে product_id হিসেবে শুধুমাত্র [Selected Product] এর ID বসাবে। নিজের মনগড়া ID (যেমন 1, 13) বসাবে না।
৪. **আচরণ:** খুব ছোট এবং টু-দ্য-পয়েন্ট উত্তর দিবে। রোবটিক কথা (যেমন "আমি প্রসেস করছি") বলবে না।

[RESPONSE FORMATS]:
- To Show Products: [CAROUSEL: ID1, ID2]
- To Finalize Order: [ORDER_DATA: {"product_id": 123, "name": "...", "phone": "017...", "address": "...", "is_dhaka": true, "note": "..."}]
- To Track Order: [TRACK_ORDER: "017XXXXXXXX"]

বাংলা এবং ইংরেজির মিশ্রণে ন্যাচারাল ভাবে কথা বলো।
EOT;

            // Message History Builder
            $messages = [['role' => 'system', 'content' => $finalPrompt]];
            
            $recentHistory = array_slice($history, -4);
            foreach ($recentHistory as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            // AI Call
            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            // Save History
            if ($aiResponse) {
                $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
                if (count($history) > 20) $history = array_slice($history, -20);
                
                $customerInfo['history'] = $history;
                $session->update(['customer_info' => $customerInfo]);
            }

            return $aiResponse;

        } catch (\Exception $e) {
            Log::error('ChatbotService Error: ' . $e->getMessage(), ['senderId' => $senderId]);
            return "দুঃখিত, একটু সমস্যা হচ্ছে। অনুগ্রহ করে আবার চেষ্টা করুন।";
        }
    }

    // =====================================
    // HELPER METHODS (ALL INCLUDED & FIXED)
    // =====================================

    private function detectNewIntent($msg) {
        if (is_array($msg)) $msg = implode(' ', $msg);
        if (!is_string($msg)) return false;

        $keywords = ['menu', 'start', 'suru', 'list', 'অন্য', 'change', 'bad', 'new', 'notun', 'kiccu na', 'cancel'];
        foreach($keywords as $kw) {
            if (stripos($msg, $kw) !== false && strlen($msg) < 20) return true;
        }
        return false;
    }

    private function isTrackingIntent($msg) {
        if (is_array($msg)) $msg = implode(' ', $msg);
        if (!is_string($msg)) return false;

        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অর্ডার কি', 'অর্ডার চেক', 'অবস্থা', 'জানতে চাই', 'পৌঁছাবে', 'কবে পাব', 'tracking'];
        $msgLower = mb_strtolower($msg, 'UTF-8');
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function isOrderRelatedMessage($msg) {
        if (is_array($msg)) $msg = implode(' ', $msg);
        if (!is_string($msg)) return false;

        $orderKeywords = ['order', 'অর্ডার', 'buy', 'কিনবো', 'purchase', 'কেনা', 'product', 'প্রোডাক্ট', 'item', 'জিনিস', 'price', 'dam'];
        $msgLower = strtolower($msg);
        foreach ($orderKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function detectDeliveryNote($msg) {
        if (is_array($msg)) $msg = implode(' ', $msg);
        if (!is_string($msg)) return false;

        $noteKeywords = [
            'friday', 'শুক্রবার', 'saturday', 'শনিবার', 'sunday', 'রবিবার',
            'monday', 'সোমবার', 'tuesday', 'মঙ্গলবার', 'wednesday', 'বুধবার', 'thursday', 'বৃহস্পতিবার',
            'delivery', 'ডেলিভারি', 'দিবেন', 'urgent', 'জরুরি', 'সকালে', 'রাতে'
        ];
        $msgLower = strtolower($msg);
        foreach ($noteKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function extractDeliveryNote($msg) {
        if (is_array($msg)) $msg = implode(' ', $msg);
        
        $commonWords = ['ami', 'amra', 'tumi', 'apni', 'she', 'i', 'you', 'we', 'want', 'need', 'please', 'kindly', 'দয়া', 'করে', 'চাই', 'লাগবে'];
        $words = explode(' ', strtolower((string)$msg));
        $filtered = array_filter($words, function($w) use ($commonWords) {
            return !in_array(strtolower(trim($w)), $commonWords) && strlen(trim($w)) > 2;
        });
        return implode(' ', $filtered);
    }

    private function detectOrderCancellation($msg, $senderId) {
        if (empty($msg)) return false;
        if (is_array($msg)) $msg = implode(' ', $msg);

        $cancelPhrases = [
            'cancel', 'বাতিল', 'নিবো না', 'লাগবে না', 'চাই না', 'দরকার নেই', 
            'না লাগবে', 'change mind', 'ভুল হয়েছে', 'ভুল অর্ডার'
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

    private function detectHateSpeech($message) {
        if (!$message) return false;
        if (is_array($message)) $message = implode(' ', $message);

        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud', 'fuck', 'shit', 'bitch', 'asshole'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

    private function lookupOrderByPhone($clientId, $message) {
        if (is_array($message)) $message = implode(' ', $message);
        
        $phone = $this->extractPhoneNumber($message);
        if ($phone) {
            $order = Order::where('client_id', $clientId)
                          ->where('customer_phone', $phone)
                          ->latest()
                          ->first();
            if ($order) {
                $status = strtoupper($order->order_status);
                return "FOUND_ORDER: Phone {$phone} matched Order #{$order->id}. Status: {$status}. Total: {$order->total_amount} Tk.";
            } else {
                return "NO_ORDER_FOUND: Phone {$phone} provided but no order exists.";
            }
        }
        return null;
    }

    // =====================================
    // INVENTORY & PRODUCT HELPERS
    // =====================================

    private function getInventoryData($clientId, $userMessage, $history) {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');
        
        if (is_array($userMessage)) $userMessage = implode(' ', $userMessage);
        $keywords = array_filter(explode(' ', (string)$userMessage), fn($w) => mb_strlen($w) > 2);
        
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
            return [
                'ID' => $p->id,
                'Name' => $p->name,
                'Price' => (int)$p->sale_price . ' Tk',
                'Stock' => $p->stock_quantity > 0 ? 'Available' : 'Out',
                'Image_URL' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null,
            ];
        })->toJson();
    }

    private function findProductSystematically($clientId, $message) {
        if (is_array($message)) $message = implode(' ', $message);
        
        $keywords = array_filter(explode(' ', $message), function($word) {
            return mb_strlen(trim($word)) >= 3 && !in_array(strtolower($word), ['ami', 'ei', 'ta', 'kinbo', 'chai', 'korte', 'chachi']);
        });

        // 1. Check SKU
        foreach($keywords as $word) {
            $product = Product::where('client_id', $clientId)
                ->where('sku', 'LIKE', "%".strtoupper(trim($word))."%")
                ->first();
            if($product) return $product;
        }

        // 2. Check Name
        $query = Product::where('client_id', $clientId);
        foreach($keywords as $word) {
            $query->orWhere('name', 'LIKE', "%".trim($word)."%");
        }
        return $query->latest()->first();
    }

    private function extractVariant($msg, $product) {
        if (is_array($msg)) $msg = implode(' ', $msg);
        $msg = strtolower($msg);
        $variant = [];
        
        // [FIX] Handle Array/String casts
        $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
        if (is_array($colors)) {
            foreach ($colors as $color) {
                if (str_contains($msg, strtolower($color))) $variant['color'] = $color;
            }
        }
        
        $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                if (str_contains($msg, strtolower($size))) $variant['size'] = $size;
            }
        }
        return $variant;
    }

    private function hasVariantInMessage($msg, $product) {
        $variant = $this->extractVariant($msg, $product);
        return !empty($variant);
    }

    // =====================================
    // EXTERNAL API (LLM, Voice, Telegram)
    // =====================================

    public function convertVoiceToText($audioUrl) {
        try {
            $audioResponse = Http::get($audioUrl);
            if (!$audioResponse->successful()) return null;

            $tempFileName = 'voice_' . time() . '.mp3'; // Simplify extension handling
            $tempPath = storage_path('app/' . $tempFileName);
            file_put_contents($tempPath, $audioResponse->body());

            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            $response = Http::withToken($apiKey)
                ->attach('file', fopen($tempPath, 'r'), $tempFileName)
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'prompt' => 'Bengali voice message about e-commerce order.',
                ]);

            if (file_exists($tempPath)) unlink($tempPath); // Safe cleanup

            return $response->successful() ? ($response->json()['text'] ?? null) : null;
        } catch (\Exception $e) {
            Log::error("Voice Error: " . $e->getMessage());
            return null;
        }
    }

    private function extractPhoneNumber($msg) {
        if (is_array($msg)) $msg = implode(' ', $msg);
        
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $msg = str_replace($bn, $en, $msg);
        $msg = preg_replace('/[^0-9]/', '', $msg);
        
        if (preg_match('/01[3-9]\d{8,9}/', $msg, $matches)) {
            $phone = substr($matches[0], 0, 11);
            return preg_match('/^01[3-9]\d{8}$/', $phone) ? $phone : null;
        }
        if (preg_match('/8801[3-9]\d{8,9}/', $msg, $matches)) {
            $phone = '0' . substr($matches[0], 3, 10);
            return preg_match('/^01[3-9]\d{8}$/', $phone) ? $phone : null;
        }
        return null;
    }

    private function callLlmChain($messages, $imageUrl = null) {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            if (empty($apiKey)) return null;

            if ($imageUrl) {
                // Image Handling (Optimized)
                $imageResponse = Http::get($imageUrl);
                if ($imageResponse->successful()) {
                    $base64 = base64_encode($imageResponse->body());
                    $mime = $imageResponse->header('Content-Type') ?? 'image/jpeg';
                    $last = array_pop($messages);
                    $messages[] = [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $last['content'] ?? 'Image sent'],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$base64}"]]
                        ]
                    ];
                }
            }

            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 400,
                ]);

            return $response->successful() ? ($response->json()['choices'][0]['message']['content'] ?? null) : null;
        } catch (\Exception $e) {
            Log::error("LLM Error: " . $e->getMessage());
            return null;
        }
    }

    private function buildOrderContext($clientId, $senderId) {
        $orders = Order::with('items.product')
            ->where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->latest()->take(3)->get();

        if ($orders->isEmpty()) return "No previous orders.";

        return $orders->map(function($order) {
            $pName = $order->items->map(fn($i) => $i->product->name ?? 'Item')->implode(', ');
            return "- Order #{$order->id} ({$order->created_at->format('d M')}) : {$pName} - {$order->order_status}";
        })->implode("\n");
    }

    public function sendTelegramAlert($clientId, $senderId, $message) {
        try {
            $token = config('services.telegram.bot_token');
            $chatId = config('services.telegram.chat_id');
            if (!$token || !$chatId) return;

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "🔔 Update (User: $senderId)\n$message",
                'parse_mode' => 'Markdown'
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram Error: " . $e->getMessage());
        }
    }
}