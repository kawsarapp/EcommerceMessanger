```php
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
            
            // ✅ Session reset logic (অর্ডার কমপ্লিট হলে রিসেট, কিন্তু নোট হ্যান্ডলিং এর সুযোগ রাখা)
            if ($step === 'completed' && !$this->isOrderRelatedMessage($userMessage)) {
                // রিসেট করার আগে চেক করি এটা কোনো নোট কি না
                if ($this->detectDeliveryNote($userMessage)) {
                    $note = $this->extractDeliveryNote($userMessage);
                    // যদি গত ১০ মিনিটে কোনো অর্ডার হয়ে থাকে, সেখানে নোট আপডেট করো
                    if ($this->updateRecentOrderNote($clientId, $senderId, $note)) {
                        return "ধন্যবাদ! আপনার নোটটি ('$note') অর্ডারে যুক্ত করা হয়েছে।";
                    }
                }
                $session->update(['customer_info' => ['step' => 'start', 'product_id' => null, 'history' => []]]);
                $step = 'start';
                $currentProductId = null;
                $history = [];
            }
            
            // ✅ Critical early-exit checks
            if ($this->detectOrderCancellation($userMessage, $senderId)) {
                return "[CANCEL_ORDER: {\"reason\": \"Customer requested cancellation\"}]";
            }
            
            // নোট ডিটেকশন (যেকোনো স্টেপে)
            $deliveryNote = null;
            if ($this->detectDeliveryNote($userMessage)) {
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
            
            // ----------------------------------------
            // STEP: START (পণ্য খোঁজা)
            // ----------------------------------------
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
                        // ✅ UPGRADE: Extract EXACT variants
                        $colors = $this->decodeVariants($product->colors);
                        $sizes = $this->decodeVariants($product->sizes);
                        $hasVariants = !empty($colors) || !empty($sizes);
                        
                        if ($hasVariants) {
                            $nextStep = 'select_variant';
                            $colorStr = !empty($colors) ? implode(', ', $colors) : 'N/A';
                            $sizeStr = !empty($sizes) ? implode(', ', $sizes) : 'N/A';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে।
                            আমাদের কাছে এই কালারগুলো আছে: [{$colorStr}] এবং এই সাইজগুলো আছে: [{$sizeStr}]।
                            কাস্টমারকে শুধুমাত্র এই লিস্ট থেকেই কালার/সাইজ বেছে নিতে বলো।";
                            $productContext = json_encode([
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => $product->sale_price,
                                'available_options' => ['colors' => $colors, 'sizes' => $sizes]
                            ]);
                        } else {
                            $nextStep = 'collect_info';
                            $systemInstruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। এই প্রোডাক্টের কোনো কালার/সাইজ ভেরিয়েশন নেই। সরাসরি নাম, ফোন এবং ঠিকানা চাও।";
                            $productContext = json_encode([
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => $product->sale_price,
                                'stock' => 'Available'
                            ]);
                        }
                        $session->update(['customer_info' => array_merge($customerInfo, ['step' => $nextStep, 'product_id' => $product->id])]);
                    }
                } else {
                    $systemInstruction = "কাস্টমার কিছু কিনতে চাচ্ছে কিন্তু আমরা প্রোডাক্টটি চিনতে পারছি না। বিনীতভাবে প্রোডাক্টের সঠিক নাম বা কোড জানতে চাও। ইনভেন্টরি ডেটা: {$inventoryData}";
                }
            }
            
            // ----------------------------------------
            // STEP: SELECT VARIANT
            // ----------------------------------------
            elseif ($step === 'select_variant') {
                $product = Product::find($currentProductId);
                if ($product) {
                    if ($this->hasVariantInMessage($userMessage, $product)) {
                        $variant = $this->extractVariant($userMessage, $product);
                        $customerInfo['variant'] = $variant;
                        // ✅ UPGRADE: Variant empty guard (from second version)
                        if (empty($variant)) {
                            $systemInstruction = "আপনি যে ভেরিয়েশন বলেছেন তা আমাদের অপশনের মধ্যে নেই। দয়া করে সঠিক কালার/সাইজ দিন।";
                        } else {
                            // ✅ STRICT TRANSITION: ভেরিয়েশন পেলে সরাসরি ফোন নম্বর চাইতে হবে
                            $session->update(['customer_info' => array_merge($customerInfo, ['step' => 'collect_info'])]);
                            $systemInstruction = "ভেরিয়েশন কনফার্ম হয়েছে (" . json_encode($variant) . ")। এখন অর্ডারটি প্রসেস করতে কাস্টমারের নাম, ফোন নম্বর এবং ঠিকানা চাও। (এখনই অর্ডার কনফার্ম করবে না, আগে তথ্য নাও)";
                        }
                    } else {
                        // Re-inject variants
                        $colors = $this->decodeVariants($product->colors);
                        $sizes = $this->decodeVariants($product->sizes);
                        $colorStr = !empty($colors) ? implode(', ', $colors) : 'None';
                        $sizeStr = !empty($sizes) ? implode(', ', $sizes) : 'None';
                        $systemInstruction = "কাস্টমার এখনো ভেরিয়েশন সিলেক্ট করেনি।
                        আমাদের কাছে আছে - Colors: [{$colorStr}], Sizes: [{$sizeStr}]।
                        কাস্টমারকে এই অপশনগুলো থেকে বেছে নিতে বলো।";
                    }
                }
            }
            
            // ----------------------------------------
            // STEP: COLLECT INFO (নাম, ফোন, ঠিকানা)
            // ----------------------------------------
            elseif ($step === 'collect_info') {
                // ✅ UPGRADE: Address extraction added (from second version) - no removal
                if (!empty($userMessage) && strlen($userMessage) > 10) {
                    $customerInfo['address'] = $customerInfo['address'] ?? $userMessage;
                }
                
                $variantInfo = $customerInfo['variant'] ?? [];
                $product = Product::find($currentProductId);
                $phone = $this->extractPhoneNumber($userMessage);
                
                if ($phone) {
                    if ($deliveryNote) $customerInfo['note'] = $deliveryNote;
                    $customerInfo['phone'] = $phone;
                    // ✅ UPGRADE: সরাসরি Confirm Order এ পাঠাও
                    $session->update(['customer_info' => array_merge($customerInfo, ['step' => 'confirm_order'])]);
                    if ($product) {
                        $productContext = json_encode([
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->sale_price,
                            'variant' => $variantInfo
                        ]);
                    }
                    $systemInstruction = "কাস্টমার ফোন নম্বর ({$phone}) দিয়েছে।
                    এখন অর্ডারটি ফাইনাল করার আগে:
                    ১. অর্ডারের সামারি দাও।
                    ২. [CAROUSEL: {$product->id}] ট্যাগ ব্যবহার করে ছবি দেখাও।
                    ৩. কাস্টমারকে বলো সব ঠিক থাকলে 'Confirm' করতে।";
                } else {
                    // ✅ STRICT: ফোন না পেলে কোনোভাবেই অর্ডার কনফার্মেশন বা ট্যাগ জেনারেট করা যাবে না
                    $systemInstruction = "আমরা এখনো ফোন নম্বর পাইনি। অর্ডার কনফার্ম করতে বিনীতভাবে ফোন নম্বর এবং ঠিকানা চাও। সাবধান: ফোন নম্বর না পাওয়া পর্যন্ত [ORDER_DATA] জেনারেট করবে না।";
                }
            }
            
            // ----------------------------------------
            // STEP: CONFIRM ORDER
            // ----------------------------------------
            elseif ($step === 'confirm_order') {
                if ($this->isPositiveConfirmation($userMessage)) {
                    $product = Product::find($currentProductId);
                    $phone = $customerInfo['phone'] ?? '';
                    $address = $customerInfo['address'] ?? null; // Added from second version
                    $variant = $customerInfo['variant'] ?? [];
                    $savedNote = $customerInfo['note'] ?? '';
                    
                    if ($deliveryNote) {
                        $savedNote = $savedNote ? "$savedNote. $deliveryNote" : $deliveryNote;
                    }
                    
                    // ✅ FINAL CHECK: ফোন নম্বর এবং ঠিকানা আছে কিনা (from second version)
                    if (empty($phone) || empty($address)) {
                        // যদি কোনো কারণে ফোন নম্বর বা ঠিকানা হারিয়ে যায়
                        $session->update(['customer_info' => array_merge($customerInfo, ['step' => 'collect_info'])]);
                        $systemInstruction = "অর্ডার কনফার্ম করতে ফোন এবং ঠিকানা দুটোই প্রয়োজন।";
                    } else {
                        // ✅ HARD ORDER_DATA CONTRACT (from second version)
                        $systemInstruction = "
                        কাস্টমার অর্ডার কনফার্ম করেছে।
                        শুধু নিচের exact schema ব্যবহার করে [ORDER_DATA] দাও:
                        {
                        \"product_id\": {$currentProductId},
                        \"phone\": \"{$phone}\",
                        \"address\": \"{$address}\",
                        \"variant\": " . json_encode($variant) . ",
                        \"note\": \"{$savedNote}\",
                        \"status\": \"PROCESSING\"
                        }
                        এই schema ছাড়া কিছু দেবে না।
                        ";
                        $productContext = json_encode([
                            'id' => $product->id,
                            'name' => $product->name,
                            'phone' => $phone,
                            'address' => $address, // Added address
                            'variant' => $variant,
                            'note' => $savedNote
                        ]);
                    }
                } else {
                    $systemInstruction = "কাস্টমার এখনো কনফার্ম করেনি। সে হয়তো কিছু জানতে চায়। প্রশ্নের উত্তর দাও এবং শেষে আবার কনফার্ম করতে বলো। [CAROUSEL: {$currentProductId}] দেখাও।";
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
            $finalPrompt = $this->generateSystemPrompt(
                $systemInstruction,
                $productContext,
                $orderContext,
                $inventoryData,
                $currentTime,
                $productsJson
            );
            
            $messages = [['role' => 'system', 'content' => $finalPrompt]];
            $recentHistory = array_slice($history, -4);
            foreach ($recentHistory as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];
            
            // ========================================
            // IMAGE HANDLING (from first version)
            // ========================================
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
                    if ($lastMessage && $lastMessage['role'] === 'user') {
                        $messages[] = [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => is_string($lastMessage['content']) ? $lastMessage['content'] : json_encode($lastMessage['content'])],
                                ['type' => 'image_url', 'image_url' => ['url' => $base64Image]]
                            ]
                        ];
                    } else {
                        $messages[] = $lastMessage;
                    }
                }
            }
            
            $aiResponse = $this->callLlmChain($messages, $imageUrl);
            
            // Persist history
            if ($aiResponse) {
                $history[] = [
                    'user' => $userMessage,
                    'ai' => $aiResponse,
                    'time' => time()
                ];
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
    // HELPER METHODS (from first version - untouched)
    // =====================================
    
    /**
    * [NEW] Variants Decoder (Handles JSON, String, N/A)
    */
    private function decodeVariants($data) {
        if (empty($data)) return [];
        // If it's already an array
        if (is_array($data)) {
            // Filter out 'N/A' or empty strings
            return array_filter($data, fn($item) => strtolower($item) !== 'n/a' && !empty($item));
        }
        // Try decoding JSON
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_filter($decoded, fn($item) => strtolower($item) !== 'n/a' && !empty($item));
        }
        // Treat as simple string (e.g., "Red" or "Red, Blue")
        if (is_string($data)) {
            if (strtolower($data) === 'n/a') return [];
            // If comma separated
            if (str_contains($data, ',')) {
                return array_map('trim', explode(',', $data));
            }
            return [$data];
        }
        return [];
    }
    
    /**
    * [NEW] সাম্প্রতিক অর্ডারের নোট আপডেট করা
    */
    private function updateRecentOrderNote($clientId, $senderId, $note)
    {
        // গত ১৫ মিনিটের মধ্যে করা অর্ডার চেক
        $recentOrder = Order::where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->latest()
            ->first();
        
        if ($recentOrder) {
            $existingNote = $recentOrder->admin_note ?? $recentOrder->notes ?? '';
            $newNote = $existingNote ? "$existingNote | $note" : $note;
            
            // ডাটাবেস ভেদে কলামের নাম ভিন্ন হতে পারে, তাই সেফ চেক
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
    
    private function generateSystemPrompt($instruction, $prodCtx, $ordCtx, $invData, $time, $prodJson)
    {
        return <<<EOT
{$instruction}
**Role:** Smart Sales Executive.
**Strict Rules (DO NOT BREAK):**
1. **Require Phone:** NEVER generate [ORDER_DATA] if you do not have the customer's phone number.
2. **Real Options:** Only offer variants listed in [Product Info]. If empty, say "No options".
3. **Carousel:** Always show [CAROUSEL: ID] before asking for confirmation.
[DATA CONTEXT]:
[Product Info]: {$prodCtx}
[Customer History]: {$ordCtx}
[Product Inventory]: {$invData}
- Current Time: {$time}
- Delivery: Standard Delivery (2-4 days)
[System Tags]:
- [CAROUSEL: Product_ID]
- [ORDER_DATA: {"product_id": 123, "name": "Prod Name", "phone": "017...", "address": "...", "variant": "...", "note": "..."}]
- [TRACK_ORDER: "017..."]
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
    * [OPTIMIZED] স্মার্ট ইনভেন্টরি সার্চ
    */
    private function getInventoryData($clientId, $userMessage, $history)
    {
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
            
            $context .= "- Order #{$order->id} ({$timeAgo}):\n  Product: {$productNames}\n  Status: [{$status}] | Amount: {$order->total_amount} Tk\n  Info: {$customerInfo}{$noteInfo}\n  -----------------------------\n";
        }
        return $context;
    }
    
    /**
    * [FIXED & OPTIMIZED] Voice to Text
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
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
    
    private function extractPhoneNumber($msg) {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $msg = str_replace($bn, $en, $msg);
        $msg = preg_replace('/[^0-9]/', '', $msg);
        
        if (str_starts_with($msg, '8801')) {
            $msg = substr($msg, 2);
        }
        
        if (preg_match('/^01[3-9]\d{8}$/', $msg)) {
            return $msg;
        }
        return null;
    }
    
    /**
    * [OPTIMIZED] Systematic Product Search
    */
    private function findProductSystematically($clientId, $message) {
        $keywords = array_filter(explode(' ', $message), function($word) {
            return is_string($word) && mb_strlen(trim($word)) >= 3 && !in_array(strtolower($word), ['ami', 'kinbo', 'chai', 'korte', 'jonno', 'কিনবো', 'চাই', 'জন্য', 'দিবেন']);
        });
        
        if (empty($keywords)) return null;
        
        foreach($keywords as $word) {
            $product = Product::where('client_id', $clientId)
                ->where('sku', 'LIKE', "%".strtoupper(trim($word))."%")
                ->first();
            if($product) return $product;
        }
        
        return Product::where('client_id', $clientId)
            ->where(function($q) use ($keywords) {
                foreach($keywords as $word) {
                    $q->orWhere('name', 'LIKE', "%".trim($word)."%");
                }
            })
            ->first();
    }
    
    /**
    * [FIXED] Array Crash Fix in strtolower
    */
    private function hasVariantInMessage($msg, $product) {
        $msgLower = strtolower($msg);
        
        $check = function($data) use ($msgLower) {
            $items = is_string($data) ? json_decode($data, true) : $data;
            if (!is_array($items)) {
                $items = is_string($data) ? [$data] : [];
            }
            foreach ($items as $item) {
                if (is_string($item) && stripos($msgLower, strtolower(trim($item))) !== false) {
                    return true;
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
    * [NEW] Check if product has variants safely
    */
    private function productHasVariants($product) {
        $check = function($data) {
            if (empty($data)) return false;
            $items = is_string($data) ? json_decode($data, true) : $data;
            if (is_string($data) && json_last_error() !== JSON_ERROR_NONE) {
                return strlen($data) > 1 && strtolower($data) !== 'n/a';
            }
            if (is_array($items) && count($items) > 0) {
                return !(count($items) === 1 && strtolower($items[0] ?? '') === 'n/a');
            }
            return false;
        };
        
        return $check($product->colors) || $check($product->sizes);
    }
    
    /**
    * [NEW] Check Positive Confirmation
    */
    private function isPositiveConfirmation($msg) {
        $positiveWords = ['yes', 'ji', 'hmd', 'ok', 'confirm', 'thik ace', 'thik ase', 'koren', 'order koren', 'হ্যাঁ', 'জি', 'ঠিক আছে', 'কনফার্ম', 'করেন', 'done'];
        $msgLower = strtolower($msg);
        foreach ($positiveWords as $w) {
            if (str_contains($msgLower, $w)) return true;
        }
        return false;
    }
    
    /**
    * [CORE] LLM Call (from first version)
    */
    private function callLlmChain($messages, $imageUrl = null)
    {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            if (empty($apiKey)) {
                Log::error("OpenAI API Key missing!");
                return null;
            }
            
            // Note: Image handling is done in getAiResponse method before calling this function
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
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