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
            
            $inventoryData = null;
            $currentTime = now()->format('l, h:i A');
            $delivery = 'Standard Delivery (2-4 days)';
            $paymentMethods = 'COD, bKash, Nagad';
            $shopPolicies = '7 days return, No warranty';
            $activeOffers = 'No active offers';
            $productsJson = '[]';


            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]
            );

            if ($session->is_human_agent_active) return null;

            // ২. স্টেপ ভ্যারিয়েবলগুলো প্রথমে ডিফাইন করুন
            $step = $session->customer_info['step'] ?? 'start';
            $currentProductId = $session->customer_info['product_id'] ?? null;
            $history = $session->customer_info['history'] ?? [];

            // ✅ ৩. সেশন রিসেট চেক - 'completed' স্টেপে নতুন মেসেজ এলে রিসেট করব
            if ($step === 'completed' && !$this->isOrderRelatedMessage($userMessage)) {
                $session->update(['customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]);
                $step = 'start';
                $currentProductId = null;
            }

            // ✅ ৪. অর্ডার ক্যানসেলেশন চেক (সব স্টেপে)
            if ($this->detectOrderCancellation($userMessage, $senderId)) {
                return "[CANCEL_ORDER: {\"reason\": \"Customer requested cancellation\"}]";
            }

            // ✅ ৫. ডেলিভারি নোট ডিটেকশন (collect_info স্টেপে)
            $deliveryNote = null;
            if ($step === 'collect_info' && $this->detectDeliveryNote($userMessage)) {
                $deliveryNote = $this->extractDeliveryNote($userMessage);
            }

            // ✅ ৬. হেট স্পিচ বা নেগেটিভ কথা চেক
            if ($this->detectHateSpeech($userMessage)) {
                return "দুঃখিত, আমরা শালীন আলোচনা করি। অন্য কোনো সাহায্য প্রয়োজন?";
            }

            $systemInstruction = "";
            $productContext = "";

            // --- STEP 1: প্রোডাক্ট খোঁজা ---
            if ($step === 'start' || !$currentProductId) {
                // ফোন লুকআপ চেক
                $phoneLookupResult = $this->lookupOrderByPhone($clientId, $userMessage);
                if ($phoneLookupResult) {
                    return $phoneLookupResult;
                }

                // ইনভেন্টরি সার্চ (নতুন সিস্টেমেটিক লজিক)
                $product = $this->findProductSystematically($clientId, $userMessage);
                
                if ($product) {
                    // প্রোডাক্ট পাওয়া গেছে! এখন চেক করব ভেরিয়েশন আছে কি না
                    $hasColor = $product->colors && strtolower($product->colors) !== 'n/a';
                    $hasSize = $product->sizes && strtolower($product->sizes) !== 'n/a';

                    // লজিক: যদি ভেরিয়েশন থাকে, তবে স্টেপ হবে 'select_variant', না থাকলে সরাসরি 'collect_info'
                    if ($hasColor || $hasSize) {
                        $nextStep = 'select_variant';
                        $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। কিন্তু এটার কালার/সাইজ আছে ({$product->colors} / {$product->sizes})। তুমি এখন শুধু কালার বা সাইজ জিজ্ঞেস করো। অন্য কিছু না।";
                    } else {
                        $nextStep = 'collect_info'; // সরাসরি নাম ঠিকানায় জাম্প
                        $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। এই প্রোডাক্টের কোনো কালার বা সাইজ নেই (Single Variation)। তাই ভুলেও কালার/সাইজ চাইবে না। সরাসরি কাস্টমারের নাম, ফোন নম্বর এবং ঠিকানা চাও।";
                    }

                    // সেশন আপডেট
                    $session->update(['customer_info' => array_merge($session->customer_info ?? [], ['step' => $nextStep, 'product_id' => $product->id])]);
                    $productContext = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price, 'stock' => 'Available']);
                
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
                if ($product && $this->hasVariantInMessage($userMessage, $product)) {
                    $session->update(['customer_info' => array_merge($session->customer_info ?? [], ['step' => 'collect_info'])]);
                     $systemInstruction = "কাস্টমার ভেরিয়েশন কনফার্ম করেছে। এখন দ্রুত অর্ডার কনফার্ম করতে তার নাম, ফোন এবং ঠিকানা চাও।";
                }
            }

            // --- STEP 3: তথ্য সংগ্রহ ও অর্ডার কনফার্ম ---
            elseif ($step === 'collect_info') {
                $product = Product::find($currentProductId);
                
                // হার্ড-কোড চেক: মেসেজে ফোন নম্বর আছে কি না
                $phone = $this->extractPhoneNumber($userMessage);
                
                if ($phone) {
                    // ✅ ফিক্স: প্রোডাক্ট কনটেক্সটে আসল ID পাঠানো
                    if ($product) {
                        $productContext = json_encode([
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->sale_price
                        ]);
                    }
                    
                    // ফোন নম্বর পেলে আমরা ধরে নিব অর্ডার কনফার্ম
                    $noteStr = $deliveryNote ? " নোট: {$deliveryNote}" : "";
                    $systemInstruction = "কাস্টমার ফোন নম্বর ({$phone}) দিয়েছে।{$noteStr} এখন তুমি অর্ডারটি কনফার্ম করো এবং [ORDER_DATA] ট্যাগ জেনারেট করো। নাম না থাকলে 'Guest' ব্যবহার করো। অবশ্যই product_id এর জায়গায় আসল নাম্বার বসাবে, 'ID' স্ট্রিং বসাবে না।";
                } else {
                    $systemInstruction = "আমরা এখনো ফোন নম্বর পাইনি। অর্ডার কনফার্ম করতে বিনীতভাবে ফোন নম্বর এবং ঠিকানা চাও।";
                }
            }
            
            // --- STEP 4: অর্ডার কমপ্লিট ---
            elseif ($step === 'completed') {
                return "আপনার অর্ডারটি ইতিমধ্যে আমাদের সিস্টেমে জমা হয়েছে। ধন্যবাদ! নতুন অর্ডার দিতে চাইলে প্রোডাক্টের নাম বলুন।";
            }

            // ----------------------------------------
            // AI কল (এখন AI কন্ট্রোলড এনভায়রনমেন্টে আছে)
            // ----------------------------------------
            // কাস্টমার হিস্ট্রি বিল্ড করা (পুরানো লজিক ব্যবহার করব)
            $orderContext = $this->buildOrderContext($clientId, $senderId);
            // ✅ SAFETY FALLBACKS (must before prompt)
            $inventoryData  = $inventoryData  ?? 'No inventory data available';
            $productContext = $productContext ?? '';

            $finalPrompt = <<<EOT
                {$systemInstruction}

                তুমি একজন বন্ধুসুলভ, স্মার্ট এবং মানুষের মতো কথা বলা ইকমার্স সেলস অ্যাসিস্ট্যান্ট। তোমার নাম [আপনার পেজের নাম বা বটের নাম]। তুমি সবসময় বাংলায় উত্তর দিবে (তবে প্রয়োজনে ইংরেজি শব্দ ব্যবহার করতে পারো)।

                [DATA CONTEXT]:
                [Product Info]: {$productContext}
                [Customer History]: {$orderContext}
                [Product Inventory]: {$inventoryData}
                - Current Time: {$currentTime} (e.g., Sunday, 10 PM)
                - Delivery Info: {$delivery}
                - Payment Methods: {$paymentMethods} (e.g., COD, Bkash: 017...)
                - Shop Policies: {$shopPolicies} (Returns, Warranty)
                - Active Offers: {$activeOffers}
                - Products Inventory: {$productsJson}
                - Customer History: {$orderContext}


                [১. অর্ডার ট্র্যাকিং রুলস]:
                - কাস্টমার যদি অর্ডারের অবস্থা জানতে চায় (যেমন: "অর্ডার কই?", "ট্র্যাক করতে চাই"), তবে ভদ্রভাবে তার ফোন নম্বর চাও।
                - ফোন নম্বর পেলে সেটাকে ১১ ডিজিটে ক্লিন করো (স্পেস বা হাইফেন সরিয়ে)।
                - যদি নম্বর সঠিক থাকে, তবে এই ট্যাগটি জেনারেট করো: 
                [TRACK_ORDER: "017XXXXXXXX"]
                - কখনোই কাস্টমারকে ডাটাবেস চেক করার কথা বলবে না।

                [২. প্রোডাক্ট দেখানো ও ক্যারোসেল]:
                - কাস্টমার কোনো প্রোডাক্ট দেখতে চাইলে বা তুমি সাজেস্ট করলে, 'Products Inventory' থেকে মিল রেখে সর্বোচ্চ ৩টি প্রোডাক্টের আইডি দিয়ে ক্যারোসেল দেখাবে।
                - যদি ইনভেন্টরিতে প্রোডাক্ট না থাকে, তবে মিথ্যা আশ্বাস দিবে না।
                - ফরম্যাট (মেসেজের শেষে): [CAROUSEL: ID1, ID2]

                [৩. অর্ডার প্রসেস - কঠোর নিয়ম]:
                - স্টেপ ১: আগে নিশ্চিত হও কাস্টমার কোন প্রোডাক্টটি (ID) কিনতে চায়। প্রোডাক্ট কনফার্ম না হওয়া পর্যন্ত নাম/ঠিকানা চাইবে না।
                - স্টেপ ২: প্রোডাক্ট কনফার্ম হলে, কাস্টমারের নাম, ফোন নম্বর এবং পূর্ণ ঠিকানা (থানা/জেলা সহ) নাও।
                - স্টেপ ৩: সব তথ্য পেলে এবং কাস্টমার কনফার্ম করলে নিচের ট্যাগটি জেনারেট করো।
                - ঢাকার ভেতরে হলে is_dhaka=true, বাইরে false।
                - ফরম্যাট: 
                [ORDER_DATA: {"product_id": 101, "name": "Customer Name", "phone": "017XXXXXXXX", "address": "Full Address", "is_dhaka": true, "note": "Any special instruction"}]

                [৪. সাধারণ আচরণ]:
                - ছোট এবং সুন্দর উত্তর দাও।
                - একবারে একটার বেশি প্রশ্ন করবে না।
                - কাস্টমার রেগে গেলে শান্তভাবে হ্যান্ডেল করো।

                [SYSTEM TAGS SUMMARY]:
                - Show Products: [CAROUSEL: ID1, ID2, ID3]
                - Finalize Order: [ORDER_DATA: {...JSON...}]
                - Check Status: [TRACK_ORDER: "Phone Number"]

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

    // =====================================
    // NEW HELPER METHODS (ADDED)
    // =====================================

    /**
     * [NEW] অর্ডার রিলেটেড মেসেজ চেক করা
     */
    private function isOrderRelatedMessage($msg) {
        $orderKeywords = ['order', 'অর্ডার', 'buy', 'কিনবো', 'purchase', 'কেনা', 'product', 'প্রোডাক্ট', 'item', 'জিনিস'];
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
        // সাধারণ ফিল্টারিং
        $commonWords = ['ami', 'amra', 'tumi', 'apni', 'she', 'i', 'you', 'we', 'they', 'want', 'need', 'please', 'kindly', 'দয়া', 'করে', 'চাই', 'লাগবে'];
        $words = explode(' ', strtolower($msg));
        $filtered = array_filter($words, function($w) use ($commonWords) {
            return !in_array(strtolower(trim($w)), $commonWords) && strlen(trim($w)) > 2;
        });
        
        return implode(' ', $filtered);
    }

    /**
     * [NEW] অর্ডার ক্যানসেলেশন ডিটেক্ট করা
     */
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
     * [UPGRADED] নেগেটিভ ইন্টেন্ট ডিটেকশন
     */
    private function isNegativeIntent($msg) {
        if (empty($msg)) return false;
        
        $negativePhrases = [
            'bad', 'খারাপ', 'fals', 'মিথ্যা', 'scam', 'ঠকবাজি', 'cheat', 'প্রতারণা',
            'worst', 'সবচেয়ে খারাপ', 'terrible', 'ভয়ানক', 'hate', 'ঘৃণা', 'dislike', 'পছন্দ নেই'
        ];
        
        $msgLower = strtolower($msg);
        foreach ($negativePhrases as $phrase) {
            if (stripos($msgLower, $phrase) !== false) {
                return true;
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
                
                return "FOUND_ORDER: Phone {$phone} matched Order #{$order->id}. Status: {$status} {$noteInfo}. Total: {$order->total_amount} Tk.";
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

            // কেবল বৈধ কালার ও সাইজ দেখানো হবে
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
     * [UPGRADED] স্মার্ট অর্ডার কনটেক্সট বিল্ডার
     */
    private function buildOrderContext($clientId, $senderId)
    {
        // ১. রিলেশনসহ অর্ডার লোড করা
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
            // ২. প্রোডাক্টের নাম বের করা
            $productNames = $order->items->map(function($item) {
                return $item->product->name ?? 'Unknown Product';
            })->implode(', ');

            if (empty($productNames)) {
                $productNames = "Product ID: " . ($order->product_id ?? 'N/A');
            }

            // ৩. সময় বের করা
            $timeAgo = $order->created_at->diffForHumans();
            $status = strtoupper($order->order_status);
            
            // ৪. নোট হ্যান্ডলিং
            $note = $order->admin_note ?? $order->notes ?? $order->customer_note ?? '';
            $noteInfo = $note ? " | Note: [{$note}]" : "";

            // ৫. কাস্টমার ইনফো
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
     * [LOGIC] হেট স্পিচ ডিটেকশন
     */
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
                        'prompt' => 'This is a Bengali voice message about ordering products, potentially containing phone numbers in Bengali or English.', // প্রম্পট সাহায্য করবে
                    ]);

            // ৩. ফাইলটি ডিলিট করে দেওয়া
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

    // =====================================
    // PHONE NUMBER EXTRACTION (FIXED)
    // =====================================

    /**
     * [FIXED] ফোন নম্বর এক্সট্রাক্ট - ১১-১২ ডিজিট সাপোর্ট
     */
    private function extractPhoneNumber($msg) {
        // বাংলা নাম্বার ইংরেজিতে কনভার্ট
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $msg = str_replace($bn, $en, $msg);
        
        // সব নন-ডিজিট রিমুভ করে শুধু নাম্বার রাখা
        $msg = preg_replace('/[^0-9]/', '', $msg);
        
        // ১১ বা ১২ ডিজিটের বিডি নম্বর প্যাটার্ন (ইউজার অতিরিক্ত ডিজিট দিলেও হবে)
        if (preg_match('/01[3-9]\d{8,9}/', $msg, $matches)) {
            $phone = substr($matches[0], 0, 11); // প্রথম ১১ ডিজিট নিব
            return preg_match('/^01[3-9]\d{8}$/', $phone) ? $phone : null;
        }
        
        // যদি 880 দিয়ে শুরু হয়
        if (preg_match('/8801[3-9]\d{8,9}/', $msg, $matches)) {
            $phone = '0' . substr($matches[0], 3, 10);
            return preg_match('/^01[3-9]\d{8}$/', $phone) ? $phone : null;
        }
        
        return null;
    }

    // =====================================
    // PRODUCT SEARCH & VARIANT HANDLING
    // =====================================

    /**
     * [LOGIC] প্রোডাক্ট খোঁজার হার্ড লজিক
     */
    private function findProductSystematically($clientId, $message) {
        // কীওয়ার্ড এক্সট্রাক্ট করা
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
        
        // নাম দিয়ে খোঁজা (হাইব্রিড সার্চ)
        $query = Product::where('client_id', $clientId);
        
        foreach($keywords as $word) {
            $query->orWhere('name', 'LIKE', "%".trim($word)."%");
        }
        
        return $query->first();
    }

    /**
     * [LOGIC] ভেরিয়েশন চেক
     */
    private function hasVariantInMessage($msg, $product) {
        $msgLower = strtolower($msg);
        
        // কালার চেক
        $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
        if (is_array($colors)) {
            foreach ($colors as $color) {
                if (stripos($msgLower, strtolower($color)) !== false) {
                    return true;
                }
            }
        }
        
        // সাইজ চেক
        $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                if (stripos($msgLower, strtolower($size)) !== false) {
                    return true;
                }
            }
        }
        
        // কমন ভেরিয়েশন কীওয়ার্ড
        $variantKeywords = ['red', 'blue', 'black', 'white', 'green', 'yellow', 'xl', 'xxl', 'l', 'm', 's', 'লাল', 'কালো', 'সাদা', 'সবুজ', 'হলুদ', 'এক্সএল', 'এল', 'এম', 'এস', 'xlarge', 'large', 'medium', 'small', 'গোলাপি', 'নীল', 'বেগুনি'];
        
        foreach ($variantKeywords as $keyword) {
            if (stripos($msgLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    // =====================================
    // CORE LLM & NOTIFICATION
    // =====================================

    /**
     * [CORE] LLM কল
     */
 
    private function callLlmChain($messages, $imageUrl = null)
{
    try {
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

        if (empty($apiKey)) {
            Log::error("OpenAI API Key missing!");
            return null;
        }

        // 🔥 Image থাকলে last user message কে Vision format এ রূপান্তর
        if ($imageUrl) {
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
                                'url' => $imageUrl
                            ]
                        ]
                    ]
                ];
            }
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->retry(2, 500)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.3,
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


    /**
     * [LOGIC] টেলিগ্রাম অ্যালার্ট সেন্ড
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