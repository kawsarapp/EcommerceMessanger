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

         if (is_array($userMessage)) {
            $userMessage = implode(' ', $userMessage);
        }
        
        if (!is_string($userMessage) || empty(trim($userMessage))) {
            Log::warning('Invalid user message received', [
                'userMessage' => $userMessage,
                'clientId' => $clientId,
                'senderId' => $senderId
            ]);
            return "দুঃখিত, আপনার বার্তাটি বুঝতে পারছি না।";
        }
            // ✅ Initialization (Variables defined safely)
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

            // Load session with null-safe history handling
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

            // ✅ Session reset logic: Clear completed sessions OR New Intents (User change mind)
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
            
            // সব সময় ইনভেন্টরি ডেটা লোড করে রাখা
            $inventoryData = $this->getInventoryData($clientId, $userMessage, $history);
            $productsJson = $inventoryData;

            // 1. Start Step or Searching
            if ($step === 'start' || !$currentProductId) {
                // Phone lookup check
                if ($this->isTrackingIntent($userMessage)) {
                    $phoneLookupResult = $this->lookupOrderByPhone($clientId, $userMessage);
                    if ($phoneLookupResult) return $phoneLookupResult;
                }

                // Systematic product search
                $product = $this->findProductSystematically($clientId, $userMessage);
                
                if ($product) {
                    // স্টক চেক সেফটি লেয়ার
                    $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
                    
                    if ($isOutOfStock) {
                        $systemInstruction = "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে অন্য কিছু দেখতে বলো। ইনভেন্টরি ডেটা: {$inventoryData}";
                        $productContext = json_encode(['id' => $product->id, 'name' => $product->name, 'stock' => 'Out of Stock']);
                    } else {
                        // Check Variants
                        $hasColor = $product->colors && strtolower($product->colors) !== 'n/a' && strtolower($product->colors) !== '[]';
                        $hasSize = $product->sizes && strtolower($product->sizes) !== 'n/a' && strtolower($product->sizes) !== '[]';

                        if ($hasColor || $hasSize) {
                            $nextStep = 'select_variant';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। কালার/সাইজ জিজ্ঞেস করো। স্টক: Available";
                        } else {
                            $nextStep = 'collect_info';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। সরাসরি নাম, ফোন এবং ঠিকানা চাও। স্টক: Available";
                        }

                        $session->update(['customer_info' => array_merge($customerInfo, ['step' => $nextStep, 'product_id' => $product->id])]);
                        
                        $productContext = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price, 'stock' => 'Available']);
                        // [NEW] Selected Info Lock (এআই যাতে ভুল না করে)
                        $selectedProductInfo = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price]);
                    }
                } else {
                    $systemInstruction = "কাস্টমার কিছু কিনতে চাচ্ছে কিন্তু আমরা প্রোডাক্টটি চিনতে পারছি না। বিনীতভাবে প্রোডাক্টের সঠিক নাম বা কোড জানতে চাও। ইনভেন্টরি ডেটা: {$inventoryData}";
                }
            } 
            // 2. Variant Selection Step
            elseif ($step === 'select_variant') {
                $product = Product::find($currentProductId);
                
                if ($product) {
                    // Lock Info
                    $selectedProductInfo = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price]);

                    if ($this->hasVariantInMessage($userMessage, $product)) {
                        $variant = $this->extractVariant($userMessage, $product);
                        $customerInfo['variant'] = $variant;

                        $session->update([
                            'customer_info' => array_merge($customerInfo, ['step' => 'collect_info'])
                        ]);

                        $systemInstruction = "ভেরিয়েশন কনফার্ম হয়েছে (" . json_encode($variant) . ")। এখন নাম, ফোন এবং ঠিকানা চাও।";
                    } else {
                        $systemInstruction = "কাস্টমার ভেরিয়েশন সিলেক্ট করছে। যদি সে কালার/সাইজ বলে থাকে, তবে এখন তার নাম, ফোন এবং ঠিকানা চাও। আর যদি না বলে থাকে, তবে আবার জিজ্ঞেস করো।";
                    }
                } else {
                    // Product deleted scenario
                    $session->update(['customer_info' => ['step' => 'start']]);
                    $systemInstruction = "দুঃখিত, প্রোডাক্টটি খুঁজে পাওয়া যাচ্ছে না। অনুগ্রহ করে আবার বলুন।";
                }
            }
            // 3. Info Collection Step
            elseif ($step === 'collect_info') {
                $variantInfo = $customerInfo['variant'] ?? [];
                $product = Product::find($currentProductId);
                $phone = $this->extractPhoneNumber($userMessage);
                
                if ($product) {
                    $selectedProductInfo = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price]);
                    
                    if ($phone) {
                        $noteStr = $deliveryNote ? " নোট: {$deliveryNote}" : "";

                        // [CRITICAL FIX] Strict Instruction for ID
                        $systemInstruction =
                            "কাস্টমার ফোন নম্বর ({$phone}) দিয়েছে। {$noteStr}\n" .
                            "এখন তুমি অর্ডারটি কনফার্ম করো।\n" .
                            "⚠️ গুরুত্বপূর্ণ: [ORDER_DATA] জেনারেট করার সময় product_id হিসেবে অবশ্যই '{$product->id}' ব্যবহার করবে। ভুলেও অন্য কোনো সংখ্যা দিবে না।\n" .
                            "ভেরিয়েশন তথ্য: " . json_encode($variantInfo);
                    } else {
                        $systemInstruction = "আমরা এখনো ফোন নম্বর পাইনি। অর্ডার কনফার্ম করতে বিনীতভাবে ফোন নম্বর এবং ঠিকানা চাও।";
                    }
                } else {
                    $session->update(['customer_info' => ['step' => 'start']]);
                    $systemInstruction = "সেশন এক্সপায়ার হয়েছে। অনুগ্রহ করে প্রোডাক্টটি আবার সিলেক্ট করুন।";
                }
            }
            elseif ($step === 'completed') {
                return "আপনার অর্ডারটি ইতিমধ্যে আমাদের সিস্টেমে জমা হয়েছে। ধন্যবাদ! নতুন অর্ডার দিতে চাইলে প্রোডাক্টের নাম বলুন।";
            }

            // ========================================
            // AI PROMPT CONSTRUCTION
            // ========================================
            $orderContext = $this->buildOrderContext($clientId, $senderId);
            
            // Safety checks
            $inventoryData = $inventoryData ?: "[]";
            $productContext = $productContext ?: "";

            $finalPrompt = <<<EOT
{$systemInstruction}

**পরিচয় ও পারসোনা:**
তুমি একজন স্মার্ট, অভিজ্ঞ এবং অত্যন্ত বিনয়ী "অনলাইন সেলস এক্সিকিউটিভ"। লক্ষ্য: কাস্টমারকে চমৎকার সার্ভিস দিয়ে তাদের পছন্দের প্রোডাক্টটি কিনতে সাহায্য করা।

[LOCKED CONTEXT - DO NOT HALLUCINATE]:
- **Selected Product:** {$selectedProductInfo} (অর্ডার কনফার্ম করার সময় শুধুমাত্র এই ID ব্যবহার করবে)
- Inventory: {$productsJson}
- Shop Info: Delivery: {$delivery}, Policies: {$shopPolicies}
- Current Time: {$currentTime}

[Customer History]: 
{$orderContext}

[আচরণের মূল নিয়মাবলী - স্মার্ট সেলসম্যান গাইড]:
১. **রোবটিক কথা এড়িয়ে চলো:** "নম্বর ক্লিন করছি", "স্টেপ ১"—এই ধরণের টেকনিক্যাল কথা একদম বলবে না। 
২. **নম্বর পেলে প্রতিক্রিয়া:** কাস্টমার ফোন নম্বর দিলে বলো— "ধন্যবাদ! আপনার নম্বরটি আমি নোট করে নিয়েছি।" 
৩. **প্রোডাক্টের প্রশংসা:** কাস্টমার কিছু কিনতে চাইলে উৎসাহ দাও।
৪. **অর্ডার প্রসেস:** কাস্টমারকে একসাথে সব প্রশ্ন না করে কথাচ্ছলে তথ্য নাও।

[১. অর্ডার কনফার্মেশন রুলস]:
- কাস্টমারের নাম, ফোন এবং পূর্ণ ঠিকানা পাওয়ার পর সব তথ্য একবার দেখাবে।
- সব ঠিক থাকলে শেষে এই ট্যাগটি দিবে: [ORDER_DATA: {"product_id": 123, "name": "...", "phone": "...", "address": "...", "is_dhaka": true, "note": "..."}]
- **সতর্কতা:** product_id অবশ্যই [Selected Product] এর ID হতে হবে।

[২. প্রোডাক্ট ট্র্যাকিং রুলস]:
- নম্বর পেলে এই ট্যাগটি জেনারেট করবে: [TRACK_ORDER: "017XXXXXXXX"]

[৩. প্রোডাক্ট দেখানো ও ক্যারোসেল]:
- কাস্টমার কিছু দেখতে চাইলে সুন্দর করে বর্ণনা দিবে এবং শেষে ট্যাগ ব্যবহার করবে: [CAROUSEL: ID1, ID2]

[SYSTEM TAGS SUMMARY]:
- Show Products: [CAROUSEL: ID1, ID2]
- Finalize Order: [ORDER_DATA: {...}]
- Check Status: [TRACK_ORDER: "..."]

সবসময় বাংলা এবং প্রয়োজনীয় ইংরেজি শব্দ মিশিয়ে ন্যাচারাল ভাবে কথা বলবে।
EOT;

            // Build Messages
            $messages = [['role' => 'system', 'content' => $finalPrompt]];

            // Inject last 4 conversation exchanges
            $recentHistory = array_slice($history, -4);
            foreach ($recentHistory as $chat) {
                if (!empty($chat['user'])) {
                    $messages[] = ['role' => 'user', 'content' => $chat['user']];
                }
                if (!empty($chat['ai'])) {
                    $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
                }
            }

            // Add current query
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            // Execute AI call
            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            // Persist history
            if ($aiResponse) {
                $history[] = [
                    'user' => $userMessage,
                    'ai' => $aiResponse,
                    'time' => time()
                ];
                
                // Prevent history bloat
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
                'senderId' => $senderId
            ]);
            return "দুঃখিত, একটু সমস্যা হচ্ছে। অনুগ্রহ করে আবার চেষ্টা করুন।";
        }
    }

    // =====================================
    // HELPER METHODS
    // =====================================

    /**
     * [NEW] Detect if user wants to start over or change topic
     */


    private function detectNewIntent($msg) {
    if (is_array($msg)) {
        $msg = implode(' ', $msg);
    }
    
    if (!is_string($msg)) {
        return false;
    }
    
    $keywords = ['menu', 'start', 'suru', 'list', 'অন্য', 'change', 'bad', 'new', 'notun', 'kiccu na', 'cancel'];
    foreach($keywords as $kw) {
        if (stripos($msg, $kw) !== false && strlen($msg) < 20) return true;
    }
    return false;
}

    /**
     * [NEW] ইউজার কি অর্ডার ট্র্যাক করতে চাচ্ছে কি না তা চেক করা
     */

    /**
 * [FIXED] ইউজার কি অর্ডার ট্র্যাক করতে চাচ্ছে কি না তা চেক করা (array সাপোর্ট)
 */
private function isTrackingIntent($msg) {
    // ✅ FIX: Convert array to string if needed
    if (is_array($msg)) {
        $msg = implode(' ', $msg);
    }
    
    if (!is_string($msg)) {
        return false;
    }
    
    $trackingKeywords = [
        'track', 'status', 'অর্ডার কই', 'অর্ডার কি', 'অর্ডার চেক', 
        'অবস্থা', 'জানতে চাই', 'পৌঁছাবে', 'কবে পাব', 'tracking'
    ];
    $msgLower = mb_strtolower($msg, 'UTF-8');
    
    foreach ($trackingKeywords as $kw) {
        if (mb_strpos($msgLower, $kw) !== false) {
            return true;
        }
    }

    return false;
}

    /**
     * [NEW] অর্ডার রিলেটেড মেসেজ চেক করা
     */
    /**
 * [FIXED] অর্ডার রিলেটেড মেসেজ চেক করা (array সাপোর্ট)
 */
private function isOrderRelatedMessage($msg) {
    // ✅ FIX: Convert array to string if needed
    if (is_array($msg)) {
        $msg = implode(' ', $msg);
    }
    
    if (!is_string($msg)) {
        return false;
    }
    
    $orderKeywords = ['order', 'অর্ডার', 'buy', 'কিনবো', 'purchase', 'কেনা', 'product', 'প্রোডাক্ট', 'item', 'জিনিস', 'price', 'dam'];
    $msgLower = strtolower($msg);
    
    foreach ($orderKeywords as $kw) {
        if (stripos($msgLower, $kw) !== false) {
            return true;
        }
    }
    return false;
}
    /**
     * [NEW] ডেলিভারি নোট ডিটেক্ট করা
     */

    
/**
 * [FIXED] ডেলিভারি নোট ডিটেক্ট করা (array সাপোর্ট)
 */
private function detectDeliveryNote($msg) {
    // ✅ FIX: Convert array to string if needed
    if (is_array($msg)) {
        $msg = implode(' ', $msg);
    }
    
    if (!is_string($msg)) {
        return false;
    }
    
    $noteKeywords = [
        'friday', 'শুক্রবার', 'saturday', 'শনিবার', 'sunday', 'রবিবার',
        'monday', 'সোমবার', 'tuesday', 'মঙ্গলবার', 'wednesday', 'বুধবার', 'thursday', 'বৃহস্পতিবার',
        'delivery', 'ডেলিভারি', 'দিবেন', 'দিবে', 'দিয়েন', 'দিয়ে', 'পৌছে', 'পৌছাবেন',
        'tomorrow', 'আগামীকাল', 'next day', 'asap', 'জরুরি', 'urgent', 'দ্রুত', 'সকালে', 'রাতে',
        'evening', 'সন্ধ্যায়', 'morning', 'afternoon', 'time', 'সময়', 'before', 'পরে', 'আগে'
    ];
    
    $msgLower = strtolower($msg);
    foreach ($noteKeywords as $kw) {
        if (stripos($msgLower, $kw) !== false) {
            return true;
        }
    }
    return false;
}

    /**
     * [NEW] ডেলিভারি নোট এক্সট্রাক্ট করা
     */
    private function extractDeliveryNote($msg) {
        $commonWords = ['ami', 'amra', 'tumi', 'apni', 'she', 'i', 'you', 'we', 'they', 'want', 'need', 'please', 'kindly', 'দয়া', 'করে', 'চাই', 'লাগবে'];
        $words = explode(' ', strtolower($msg));
        $filtered = array_filter($words, function($w) use ($commonWords) {
            return !in_array(strtolower(trim($w)), $commonWords) && strlen(trim($w)) > 2;
        });
        return implode(' ', $filtered);
    }



    /**
 * [FIXED] অর্ডার ক্যানসেলেশন ডিটেক্ট করা (array সাপোর্ট)
 */
private function detectOrderCancellation($msg, $senderId) {
    // ✅ FIX: Convert array to string if needed
    if (is_array($msg)) {
        $msg = implode(' ', $msg);
    }
    
    if (empty($msg) || !is_string($msg)) {
        return false;
    }
    
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
            // চেক করব কোনো পেন্ডিং অর্ডার আছে কিনা
            $pendingOrder = Order::where('sender_id', $senderId)
                ->whereIn('order_status', ['processing', 'pending'])
                ->latest()
                ->first();
            
            return $pendingOrder ? true : false;
        }
    }
    return false;
}

    /**
     * [LOGIC] মেসেজে ফোন নম্বর থাকলে অর্ডার স্ট্যাটাস বের করা
     */
    private function lookupOrderByPhone($clientId, $message)
    {
        // বাংলা নাম্বার ইংরেজিতে কনভার্ট
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $message = str_replace($bn, $en, $message);
        
        // ১১ ডিজিটের বিডি নম্বর প্যাটার্ন (01xxxxxxxxx)
        if (preg_match('/01[3-9]\d{8,9}/', $message, $matches)) {
            $phone = substr($matches[0], 0, 11); // ১১ ডিজিট নিব
            $order = Order::where('client_id', $clientId)
                          ->where('customer_phone', $phone)
                          ->latest()
                          ->first();

            if ($order) {
                $status = strtoupper($order->order_status);
                $note = $order->admin_note ?? $order->notes ?? '';
                $noteInfo = $note ? " (Note: {$note})" : "";
                
                return "FOUND_ORDER: Phone {$phone} matched Order #{$order->id}. Status: {$status}{$noteInfo}. Total: {$order->total_amount} Tk.";
            } else {
                return "NO_ORDER_FOUND: Phone {$phone} provided but no order exists.";
            }
        }
        return null;
    }

    /**
     * [LOGIC] স্মার্ট ইনভেন্টরি সার্চ
     */
    private function getInventoryData($clientId, $userMessage, $history)
    {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        // সাধারণ সার্চ লজিক
        $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2);
        $genericWords = ['price', 'details', 'dam', 'koto', 'eta', 'atar', 'size', 'color', 'picture', 'img', 'kemon', 'product', 'available', 'stock', 'kinbo', 'order', 'chai', 'lagbe', 'nibo', 'টাকা', 'দাম', 'কেমন', 'ছবি'];
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
                ->where('stock_quantity', '>', 0)
                ->latest()->limit(5)->get();
        }

        // প্রোডাক্ট ডাটা ম্যাপিং
        return $products->map(function ($p) {
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

            if ($colorsStr && strtolower($colorsStr) !== 'n/a') $data['Colors'] = $colorsStr;
            if ($sizesStr && strtolower($sizesStr) !== 'n/a') $data['Sizes'] = $sizesStr;

            return $data;
        })->toJson();
    }

    /**
     * [UPGRADED] স্মার্ট অর্ডার কনটেক্সট বিল্ডার
     */
    private function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::with('items.product')
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
            $productNames = $order->items->map(function($item) {
                return $item->product->name ?? 'Unknown Product';
            })->implode(', ');

            if (empty($productNames)) {
                $productNames = "Product ID: " . ($order->product_id ?? 'N/A');
            }

            $timeAgo = $order->created_at->diffForHumans();
            $status = strtoupper($order->order_status);
            $note = $order->admin_note ?? $order->notes ?? $order->customer_note ?? '';
            $noteInfo = $note ? " | Note: [{$note}]" : "";
            
            $context .= "- Order #{$order->id} ({$timeAgo}):\n";
            $context .= "  Product: {$productNames}\n";
            $context .= "  Status: [{$status}] | Amount: {$order->total_amount} Tk\n";
            $context .= "  -----------------------------\n";
        }
        
        return $context;
    }

    /**
     * [LOGIC] হেট স্পিচ ডিটেকশন
     */


    /**
 * [LOGIC] হেট স্পিচ ডিটেকশন (ফিক্সড - array সাপোর্ট)
 */
private function detectHateSpeech($message)
    {
        if (is_array($message)) {
            $message = implode(' ', $message);
        }
        
        if (!$message || !is_string($message)) {
            return false;
        }
        
        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'mamla', 'cheat', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud', 'fuck', 'shit', 'bitch', 'asshole'];
        $lowerMsg = strtolower($message);
        
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) {
                return true;
            }
        }
        
        return false;
    }


    // =====================================
    // VOICE TO TEXT
    // =====================================

    public function convertVoiceToText($audioUrl)
    {
        try {
            Log::info("Starting Voice Transcription for: " . $audioUrl);

            // ১. অডিও ফাইলটি ডাউনলোড করা
            $audioResponse = Http::get($audioUrl);
            if (!$audioResponse->successful()) return null;

            // অডিও ফাইলের কনটেন্ট-টাইপ চেক করে এক্সটেনশন সেট করা
            $contentType = $audioResponse->header('Content-Type');
            $extension = 'mp3'; // default

            if (strpos($contentType, 'audio/mp4') !== false || strpos($contentType, 'video/mp4') !== false) {
                $extension = 'mp4';
            } elseif (strpos($contentType, 'audio/ogg') !== false) {
                $extension = 'ogg';
            } elseif (strpos($contentType, 'audio/mpeg') !== false) {
                $extension = 'mp3';
            } elseif (strpos($contentType, 'audio/x-m4a') !== false) {
                $extension = 'm4a';
            }

            $tempFileName = 'voice_' . time() . '.' . $extension;
            $tempPath = storage_path('app/' . $tempFileName);
            file_put_contents($tempPath, $audioResponse->body());

            // ২. OpenAI Whisper API কল করা
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

                $response = Http::withToken($apiKey)
                    ->attach('file', fopen($tempPath, 'r'), $tempFileName)
                    ->post('https://api.openai.com/v1/audio/transcriptions', [
                        'model' => 'whisper-1',
                        'prompt' => 'This is a Bengali voice message about ordering products, potentially containing phone numbers in Bengali or English.',
                    ]);

            // ৩. ফাইলটি ডিলিট করে দেওয়া
            if (file_exists($tempPath)) unlink($tempPath);

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

    // =====================================
    // PHONE NUMBER EXTRACTION (FIXED)
    // =====================================

    private function extractPhoneNumber($msg) {
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

    // =====================================
    // PRODUCT SEARCH & VARIANT HANDLING
    // =====================================

    private function findProductSystematically($clientId, $message) {
        $keywords = array_filter(explode(' ', $message), function($word) {
            return mb_strlen(trim($word)) >= 3 && !in_array(strtolower($word), ['ami', 'ei', 'ta', 'kinbo', 'chai', 'korte', 'chachi', 'theke', 'er', 'jonno', 'টা', 'কিনবো', 'চাই', 'জন্য', 'দেন', 'দিবেন', 'দিবে']);
        });
        
        // SKU দিয়ে খোঁজা
        foreach($keywords as $word) {
            $product = Product::where('client_id', $clientId)
                ->where('sku', 'LIKE', "%".strtoupper(trim($word))."%")
                ->first();
            if($product) return $product;
        }
        
        // নাম দিয়ে খোঁজা
        $query = Product::where('client_id', $clientId);
        foreach($keywords as $word) {
            $query->orWhere('name', 'LIKE', "%".trim($word)."%");
        }
        
        return $query->latest()->first();
    }

    private function extractVariant($msg, $product)
    {
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

    private function hasVariantInMessage($msg, $product) {
        $msgLower = strtolower($msg);
        
        $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
        if (is_array($colors)) {
            foreach ($colors as $color) {
                if (str_contains($msgLower, strtolower($color))) return true;
            }
        }
        
        $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                if (str_contains($msgLower, strtolower($size))) return true;
            }
        }
        
        $variantKeywords = ['red', 'blue', 'black', 'white', 'green', 'yellow', 'xl', 'xxl', 'l', 'm', 's', 'লাল', 'কালো', 'সাদা', 'সবুজ', 'হলুদ', 'এক্সএল', 'এল', 'এম', 'এস', 'xlarge', 'large', 'medium', 'small', 'গোলাপি', 'নীল', 'বেগুনি'];
        foreach ($variantKeywords as $keyword) {
            if (stripos($msgLower, $keyword) !== false) return true;
        }
        
        return false;
    }

    // =====================================
    // CORE LLM & NOTIFICATION
    // =====================================

    private function callLlmChain($messages, $imageUrl = null)
    {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

            if (empty($apiKey)) {
                Log::error("OpenAI API Key missing!");
                return null;
            }

            if ($imageUrl) {
                $base64Image = null;
                try {
                    // ১. ইমেজটি ডাউনলোড করা
                    $imageResponse = Http::get($imageUrl);
                    
                    if ($imageResponse->successful()) {
                        // ২. কন্টেন্ট টাইপ এবং Base64 এনকোডিং
                        $contentType = $imageResponse->header('Content-Type') ?? 'image/jpeg';
                        $base64Data = base64_encode($imageResponse->body());
                        $base64Image = "data:{$contentType};base64,{$base64Data}";
                    } else {
                        Log::error("Failed to download image from URL: $imageUrl");
                    }
                } catch (\Exception $e) {
                    Log::error("Image conversion error: " . $e->getMessage());
                }

                // ৩. যদি ইমেজ সফলভাবে কনভার্ট হয়, মেসেজে অ্যাড করা
                if ($base64Image) {
                    $lastMessage = array_pop($messages);

                    if ($lastMessage && $lastMessage['role'] === 'user') {
                        $messages[] = [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => is_array($lastMessage['content'])
                                        ? json_encode($lastMessage['content'])
                                        : $lastMessage['content']
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $base64Image
                                    ]
                                ]
                            ]
                        ];
                    }
                }
            }

            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                Log::info("OpenAI API Success - Model: " . ($imageUrl ? 'gpt-4o' : 'gpt-4o-mini'));
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error("OpenAI API Error: {$response->status()} - {$response->body()}");
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

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

        } catch (\Exception $e) {
            Log::error("Telegram Notification Error: " . $e->getMessage());
        }
    }
}