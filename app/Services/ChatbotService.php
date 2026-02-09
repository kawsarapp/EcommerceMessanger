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
            // Initializing variables safely
            $inventoryData = "[]";
            $productContext = "";
            $systemInstruction = "";
            $currentTime = now()->format('l, h:i A');

            // Load session with null-safe history handling
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]
            );

            // Human agent check
            if ($session->is_human_agent_active) return null;

            // ✅ FIX: Null-safe customer info extraction
            $customerInfo = $session->customer_info ?? ['step' => 'start', 'product_id' => null, 'history' => []];
            $step = $customerInfo['step'] ?? 'start';
            $currentProductId = $customerInfo['product_id'] ?? null;
            $history = $customerInfo['history'] ?? [];

            // ✅ Session reset logic
            if ($step === 'completed' && !$this->isOrderRelatedMessage($userMessage)) {
                $session->update(['customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]);
                $step = 'start';
                $currentProductId = null;
                $history = [];
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
            
            // ✅ Optimization: Load inventory once smartly
            $inventoryData = $this->getInventoryData($clientId, $userMessage, $history);
            $productsJson = $inventoryData;

            if ($step === 'start' || !$currentProductId) {
                // Tracking Intent Check
                if ($this->isTrackingIntent($userMessage)) {
                    $phoneLookupResult = $this->lookupOrderByPhone($clientId, $userMessage);
                    if ($phoneLookupResult) {
                        return $phoneLookupResult;
                    }
                }

                // Systematic product search
                $product = $this->findProductSystematically($clientId, $userMessage);
                
                if ($product) {
                    $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
                    
                    if ($isOutOfStock) {
                        $systemInstruction = "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে অন্য কিছু দেখতে বলো। ইনভেন্টরি ডেটা: {$inventoryData}";
                        $productContext = json_encode(['id' => $product->id, 'name' => $product->name, 'stock' => 'Out of Stock']);
                    } else {
                        $hasColor = $product->colors && strtolower($product->colors) !== 'n/a';
                        $hasSize = $product->sizes && strtolower($product->sizes) !== 'n/a';

                        if ($hasColor || $hasSize) {
                            $nextStep = 'select_variant';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। কালার/সাইজ জিজ্ঞেস করো। স্টক: Available";
                        } else {
                            $nextStep = 'collect_info';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। সরাসরি নাম, ফোন এবং ঠিকানা চাও। স্টক: Available";
                        }

                        $session->update(['customer_info' => array_merge($customerInfo, ['step' => $nextStep, 'product_id' => $product->id])]);
                        $productContext = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price, 'stock' => 'Available']);
                    }
                } else {
                    $systemInstruction = "কাস্টমার কিছু কিনতে চাচ্ছে কিন্তু আমরা প্রোডাক্টটি চিনতে পারছি না। বিনীতভাবে প্রোডাক্টের সঠিক নাম বা কোড জানতে চাও। ইনভেন্টরি ডেটা: {$inventoryData}";
                }
            } 
            elseif ($step === 'select_variant') {
                $product = Product::find($currentProductId);
                $systemInstruction = "কাস্টমার ভেরিয়েশন সিলেক্ট করছে। যদি সে কালার/সাইজ বলে থাকে, তবে এখন তার নাম, ফোন এবং ঠিকানা চাও। আর যদি না বলে থাকে, তবে আবার জিজ্ঞেস করো।";
                
                if ($product && $this->hasVariantInMessage($userMessage, $product)) {
                    $variant = $this->extractVariant($userMessage, $product);
                    $customerInfo['variant'] = $variant;
                    $session->update(['customer_info' => array_merge($customerInfo, ['step' => 'collect_info'])]);
                    $systemInstruction = "ভেরিয়েশন কনফার্ম হয়েছে (" . json_encode($variant) . ")। এখন নাম, ফোন এবং ঠিকানা চাও।";
                }
            }
            elseif ($step === 'collect_info') {
                $variantInfo = $customerInfo['variant'] ?? [];
                $product = Product::find($currentProductId);
                $phone = $this->extractPhoneNumber($userMessage);
                
                if ($phone) {
                    if ($product) {
                        $productContext = json_encode([
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->sale_price
                        ]);
                    }
                    $noteStr = $deliveryNote ? " নোট: {$deliveryNote}" : "";

                    $systemInstruction = "কাস্টমার ফোন নম্বর ({$phone}) দিয়েছে। {$noteStr} এখন তুমি অর্ডারটি কনফার্ম করো। 
                    ভেরিয়েশন তথ্য: " . json_encode($variantInfo) . "
                    এখন তুমি অর্ডারটি কনফার্ম করো এবং অবশ্যই [ORDER_DATA] এর ভিতরে variant ফিল্ড হিসেবে এই তথ্য পাঠাবে। product_id এর জায়গায় আসল নাম্বার বসাবে।";
                } else {
                    $systemInstruction = "আমরা এখনো ফোন নম্বর পাইনি। অর্ডার কনফার্ম করতে বিনীতভাবে ফোন নম্বর এবং ঠিকানা চাও।";
                }
            }
            elseif ($step === 'completed') {
                return "আপনার অর্ডারটি ইতিমধ্যে আমাদের সিস্টেমে জমা হয়েছে। ধন্যবাদ! নতুন অর্ডার দিতে চাইলে প্রোডাক্টের নাম বলুন।";
            }

            // ========================================
            // AI CONTEXT & PROMPT GENERATION
            // ========================================
            $orderContext = $this->buildOrderContext($clientId, $senderId);
            $productContext = $productContext ?: "";
            
            // Generate clean prompt using helper method
            $finalPrompt = $this->generateSystemPrompt(
                $systemInstruction, 
                $productContext, 
                $orderContext, 
                $inventoryData, 
                $currentTime, 
                $productsJson
            );

            // Build message history
            $messages = [['role' => 'system', 'content' => $finalPrompt]];

            // Context continuity (Last 4 exchanges)
            $recentHistory = array_slice($history, -4);
            foreach ($recentHistory as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }

            // Add current query
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            // Execute AI call
            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            // Persist conversation history
            if ($aiResponse) {
                $history[] = [
                    'user' => $userMessage,
                    'ai' => $aiResponse,
                    'time' => time()
                ];
                
                // Keep history size manageable
                if (count($history) > 20) {
                    $history = array_slice($history, -20);
                }
                
                $customerInfo['history'] = $history;
                $session->update(['customer_info' => $customerInfo]);
            }

            return $aiResponse;

        } catch (\Exception $e) {
            Log::error('ChatbotService Error: ' . $e->getMessage(), [
                'userMessage' => $userMessage,
                'clientId' => $clientId,
                'senderId' => $senderId
            ]);
            return "দুঃখিত, একটু সমস্যা হচ্ছে। অনুগ্রহ করে আবার চেষ্টা করুন।";
        }
    }

    // =====================================
    // HELPER METHODS
    // =====================================

    /**
     * [OPTIMIZED] প্রম্পট জেনারেশন লজিক আলাদা ফাংশনে
     */
    private function generateSystemPrompt($instruction, $prodCtx, $ordCtx, $invData, $time, $prodJson)
    {
        return <<<EOT
{$instruction}

**পরিচয় ও পারসোনা:**
তুমি একজন স্মার্ট, অভিজ্ঞ এবং অত্যন্ত বিনয়ী "অনলাইন সেলস এক্সিকিউটিভ"। তোমার লক্ষ্য হলো কাস্টমারকে চমৎকার সার্ভিস দিয়ে তাদের পছন্দের প্রোডাক্টটি কিনতে সাহায্য করা।

[DATA CONTEXT]:
[Product Info]: {$prodCtx}
[Customer History]: {$ordCtx}
[Product Inventory]: {$invData}
- Current Time: {$time}
- Delivery: Standard Delivery (2-4 days)
- Payment: COD, bKash, Nagad
- Policy: 7 days return, No warranty
- Offers: No active offers
- Products Inventory: {$prodJson}

[আচরণের মূল নিয়মাবলী - স্মার্ট সেলসম্যান গাইড]:
১. **রোবটিক কথা এড়িয়ে চলো:** টেকনিক্যাল কথা বলবে না।
২. **নম্বর পেলে প্রতিক্রিয়া:** কাস্টমার নম্বর দিলে ধন্যবাদ জানাবে।
৩. **প্রোডাক্টের প্রশংসা:** কাস্টমারকে কিনতে উৎসাহ দাও।
৪. **অর্ডার প্রসেস:** কাস্টমারকে একসাথে সব প্রশ্ন না করে কথাচ্ছলে তথ্য নাও।
৫. **স্টক:** স্টক না থাকলে অন্য ভালো প্রোডাক্ট সাজেস্ট করো।

[১. অর্ডার কনফার্মেশন রুলস]:
- সব তথ্য পাওয়ার পর কনফার্ম করবে।
- শেষে ট্যাগ: [ORDER_DATA: {"product_id": 101, "name": "...", "phone": "...", "address": "...", "is_dhaka": true, "note": "..."}]

[২. প্রোডাক্ট ট্র্যাকিং রুলস]:
- নম্বর পেলে ট্যাগ: [TRACK_ORDER: "017XXXXXXXX"]

[৩. প্রোডাক্ট দেখানো]:
- ট্যাগ: [CAROUSEL: ID1, ID2]

[SYSTEM TAGS]: [CAROUSEL: ...], [ORDER_DATA: ...], [TRACK_ORDER: "..."]
সবসময় বাংলা এবং ইংরেজি শব্দ মিশিয়ে প্রফেশনাল কথা বলবে।
EOT;
    }

    private function isTrackingIntent($msg) {
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অর্ডার কি', 'অর্ডার চেক', 'অবস্থা', 'জানতে চাই', 'পৌঁছাবে', 'কবে পাব', 'tracking'];
        $msgLower = mb_strtolower($msg, 'UTF-8');
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function isOrderRelatedMessage($msg) {
        $orderKeywords = ['order', 'অর্ডার', 'buy', 'কিনবো', 'purchase', 'কেনা', 'product', 'প্রোডাক্ট', 'item', 'জিনিস'];
        $msgLower = strtolower($msg);
        foreach ($orderKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    private function detectDeliveryNote($msg) {
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

    private function extractDeliveryNote($msg) {
        $commonWords = ['ami', 'amra', 'tumi', 'apni', 'she', 'i', 'you', 'we', 'they', 'want', 'need', 'please', 'kindly', 'দয়া', 'করে', 'চাই', 'লাগবে'];
        $words = explode(' ', strtolower($msg));
        $filtered = array_filter($words, function($w) use ($commonWords) {
            return !in_array(strtolower(trim($w)), $commonWords) && strlen(trim($w)) > 2;
        });
        return implode(' ', $filtered);
    }

    private function detectOrderCancellation($msg, $senderId) {
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

    private function detectHateSpeech($message) {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'mamla', 'cheat', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud', 'fuck', 'shit', 'bitch', 'asshole'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

    private function lookupOrderByPhone($clientId, $message) {
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

    /**
     * [OPTIMIZED] স্মার্ট ইনভেন্টরি সার্চ (Loop এর ভেতর Query বন্ধ করা হয়েছে)
     */
    private function getInventoryData($clientId, $userMessage, $history)
    {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');
        
        $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2);
        
        // Contextual keyword merge
        $genericWords = ['price', 'details', 'dam', 'koto', 'eta', 'atar', 'size', 'color', 'picture', 'img', 'kemon', 'product', 'available', 'stock', 'kinbo', 'order', 'chai', 'lagbe', 'nibo', 'টাকা', 'দাম', 'কেমন', 'ছবি'];
        $isFollowUp = Str::contains(strtolower($userMessage), $genericWords) || count($keywords) < 2;

        if ($isFollowUp && !empty($history)) {
            $lastUserMsg = end($history)['user'] ?? '';
            $lastKeywords = array_filter(explode(' ', $lastUserMsg), fn($w) => mb_strlen($w) > 3);
            $keywords = array_unique(array_merge($keywords, $lastKeywords));
        }

        // Optimized Query Building
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
    }

    private function extractVariant($msg, $product) {
        $msg = strtolower($msg);
        $variant = [];

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

    private function buildOrderContext($clientId, $senderId) {
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

            $context .= "- Order #{$order->id} ({$timeAgo}):\n  Product: {$productNames}\n  Status: [{$status}] | Amount: {$order->total_amount} Tk\n  Info: {$customerInfo}{$noteInfo}\n  -----------------------------\n";
        }
        return $context;
    }

    /**
     * [FIXED & OPTIMIZED] Voice to Text with Cleanup
     */
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
            // ✅ Cleanup temp file
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function extractPhoneNumber($msg) {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $msg = str_replace($bn, $en, $msg);
        $msg = preg_replace('/[^0-9]/', '', $msg); // Keep only digits

        // Handle +880 or 880 prefix
        if (str_starts_with($msg, '8801')) {
            $msg = substr($msg, 2);
        }
        
        // Strict 11 digit BD format check
        if (preg_match('/^01[3-9]\d{8}$/', $msg)) {
            return $msg;
        }
        return null;
    }

    /**
     * [OPTIMIZED] Systematic Product Search (Improved Logic)
     */
    private function findProductSystematically($clientId, $message) {
        $keywords = array_filter(explode(' ', $message), function($word) {
            return mb_strlen(trim($word)) >= 3 && !in_array(strtolower($word), ['ami', 'kinbo', 'chai', 'korte', 'jonno', 'কিনবো', 'চাই', 'জন্য', 'দিবেন']);
        });

        if (empty($keywords)) return null;

        // Try to match SKU first (Exact Match Priority)
        foreach($keywords as $word) {
            $product = Product::where('client_id', $clientId)
                ->where('sku', 'LIKE', "%".strtoupper(trim($word))."%")
                ->first();
            if($product) return $product;
        }

        // Single Query Name Search (Performance Fix)
        return Product::where('client_id', $clientId)
            ->where(function($q) use ($keywords) {
                foreach($keywords as $word) {
                    $q->orWhere('name', 'LIKE', "%".trim($word)."%");
                }
            })
            ->first();
    }

    private function hasVariantInMessage($msg, $product) {
        $msgLower = strtolower($msg);
        
        $check = function($data) use ($msgLower) {
            $items = is_string($data) ? json_decode($data, true) : $data;
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (stripos($msgLower, strtolower($item)) !== false) return true;
                }
            }
            return false;
        };

        if ($check($product->colors) || $check($product->sizes)) return true;

        $variantKeywords = ['red', 'blue', 'black', 'white', 'green', 'yellow', 'xl', 'xxl', 'l', 'm', 's', 'লাল', 'কালো', 'সাদা', 'সবুজ', 'হলুদ', 'এক্সএল', 'এল', 'এম', 'এস', 'large', 'medium', 'small'];
        foreach ($variantKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }
        return false;
    }

    /**
     * [CORE] LLM Call (Robust Error Handling)
     */
    private function callLlmChain($messages, $imageUrl = null)
    {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            if (empty($apiKey)) {
                Log::error("OpenAI API Key missing!");
                return null;
            }

            // Image Processing
            if ($imageUrl) {
                $base64Image = null;
                try {
                    $imageResponse = Http::timeout(10)->get($imageUrl);
                    if ($imageResponse->successful()) {
                        $contentType = $imageResponse->header('Content-Type') ?? 'image/jpeg';
                        $base64Image = "data:{$contentType};base64," . base64_encode($imageResponse->body());
                    }
                } catch (\Exception $e) {
                    Log::error("Image fetch error: " . $e->getMessage());
                }

                if ($base64Image) {
                    $lastMessage = array_pop($messages);
                    if ($lastMessage['role'] === 'user') {
                        $messages[] = [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => is_string($lastMessage['content']) ? $lastMessage['content'] : json_encode($lastMessage['content'])],
                                ['type' => 'image_url', 'image_url' => ['url' => $base64Image]]
                            ]
                        ];
                    }
                }
            }

            $response = Http::withToken($apiKey)
                ->timeout($imageUrl ? 60 : 30)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
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