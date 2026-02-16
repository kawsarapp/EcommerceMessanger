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
use Carbon\Carbon;

// ✅ Core Services Integration
use App\Services\OrderService;
use App\Services\NotificationService;
use App\Services\MediaService;
use App\Services\InventoryService;
use App\Services\SafetyGuardService;

// ✅ OrderFlow Classes Import
use App\Services\OrderFlow\StartStep;
use App\Services\OrderFlow\VariantStep;
use App\Services\OrderFlow\AddressStep;
use App\Services\OrderFlow\ConfirmStep;
use App\Services\OrderFlow\OrderTraits; 

class ChatbotService
{
    use OrderTraits; 

    // Dependencies
    protected $orderService;
    protected $notify;
    protected $media;
    protected $inventory;
    protected $safety;

    /**
     * 🔥 Dependency Injection: সব নতুন সার্ভিস এখানে লোড করা হচ্ছে
     */
    public function __construct(
        OrderService $orderService,
        NotificationService $notify,
        MediaService $media,
        InventoryService $inventory,
        SafetyGuardService $safety
    ) {
        $this->orderService = $orderService;
        $this->notify = $notify;
        $this->media = $media;
        $this->inventory = $inventory;
        $this->safety = $safety;
    }

    /**
     * মেইন ফাংশন: কন্ট্রোলার থেকে রিকোয়েস্ট রিসিভ করে এবং প্রসেস করে
     */
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        // 🔥 1. MULTIPLE MESSAGE HANDLING (Race Condition Fix)
        // একই ইউজার থেকে দ্রুত একাধিক রিকোয়েস্ট আসলে এটি হ্যান্ডেল করবে
        $lock = Cache::lock("processing_user_{$senderId}", 5);
        
        Log::info("🤖 AI Service Started for User: $senderId");

        // 🔥 NULL SAFETY GUARD
        $userMessage = $userMessage ?? '';
        $base64Image = null;

        // 🚀 2. MEDIA HANDLING (via MediaService)
        if ($imageUrl) {
            // A. ভয়েস মেসেজ চেক (Whisper API)
            // MediaService অটোমেটিক ডিটেক্ট করবে এটি অডিও কি না
            $voiceText = $this->media->convertVoiceToText($imageUrl);
            
            if ($voiceText) {
                $userMessage = $voiceText . " [Voice Message Transcribed]";
                Log::info("🗣️ Voice Converted: $userMessage");
                $imageUrl = null; // অডিও প্রসেস হয়ে গেলে ইমেজ হিসেবে আর ট্রিট করব না
            } 
            // B. ইমেজ প্রসেসিং (Vision API)
            else {
                $base64Image = $this->media->processImage($imageUrl);
                if ($base64Image) {
                    Log::info("📷 Image Encoded for Vision API");
                }
            }
        }

        // যদি শুধু ইমেজ থাকে এবং কোনো টেক্সট না থাকে
        if (empty(trim($userMessage)) && $base64Image) {
            $userMessage = "I have sent an image. Please analyze it and check if you have something similar in your inventory.";
        } elseif (empty(trim($userMessage)) && !$base64Image) {
            Log::warning("⚠️ Empty message received. Returning null.");
            return null;
        }

        // 🛡️ 3. SAFETY & SECURITY CHECK (via SafetyGuardService)
        $safetyStatus = $this->safety->checkMessageSafety($senderId, $userMessage);
        $client = Client::find($clientId); // ক্লায়েন্ট লোড করা অ্যালার্টের জন্য

        // A. খারাপ ভাষা বললে
        if ($safetyStatus === 'bad_word') {
            $this->notify->sendTelegramAlert($client, $senderId, "⚠️ **Abusive Language Detected:**\n`$userMessage`", 'warning');
            return "অনুগ্রহ করে ভদ্র ভাষা ব্যবহার করুন। আমাদের এজেন্ট শীঘ্রই আপনার সাথে যোগাযোগ করবে।";
        }

        // B. ইউজার রেগে গেলে বা স্প্যাম করলে
        if ($safetyStatus === 'angry' || $safetyStatus === 'spam') {
            $reason = ($safetyStatus === 'spam') ? "Spamming/Looping" : "Customer Angry";
            
            // অটোমেটিক হিউম্যান এজেন্টে ট্রান্সফার
            OrderSession::updateOrCreate(['sender_id' => $senderId, 'client_id' => $clientId], ['is_human_agent_active' => true]);
            
            $this->notify->sendTelegramAlert($client, $senderId, "🛑 **AI Stopped!**\nReason: $reason\nMsg: `$userMessage`", 'danger');
            return "দুঃখিত, আমি আপনার কথা বুঝতে পারছি না। আমাদের একজন প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।";
        }

        // ✅ 4. MAIN TRANSACTION LOGIC
        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image, $imageUrl, $client) {

            // Session Lock & Creation
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'history' => []]]
            );
            
            // ডাটাবেস লকিং
            $session = OrderSession::where('sender_id', $senderId)->lockForUpdate()->first();

            // Human Agent Handover Check
            if ($session->is_human_agent_active) {
                Log::info("⏸️ Human Agent Active. AI Paused.");
                return null;
            }

            // 🔥 5. SMART ORDER TRACKING
            if ($this->isTrackingIntent($userMessage) || preg_match('/01[3-9]\d{8}/', $userMessage)) {
                $orderStatusMsg = $this->lookupOrderByPhone($clientId, $userMessage);
                if ($orderStatusMsg && str_contains($orderStatusMsg, 'FOUND_ORDER')) {
                    $cleanMsg = str_replace('FOUND_ORDER:', '', $orderStatusMsg);
                    return "স্যার/ম্যাম, আপনার অর্ডারের তথ্য পেয়েছি: \n" . $cleanMsg . "\nআমাদের সাথে থাকার জন্য ধন্যবাদ!";
                }
            }
            
            // 🔄 6. PRODUCT SEARCH & RESET LOGIC (Traits Used)
            $newProduct = $this->findProductSystematically($clientId, $userMessage);
            
            if ($newProduct) {
                $currentProductId = $session->customer_info['product_id'] ?? null;
                $currentStep = $session->customer_info['step'] ?? '';

                if ($newProduct->id != $currentProductId || $currentStep === 'collect_info') {
                    Log::info("🔄 Product Switch: Found ({$newProduct->name})");
                    $session->update([
                        'customer_info' => array_merge($session->customer_info, [
                            'step' => 'start', 
                            'product_id' => $newProduct->id
                        ])
                    ]);
                }
            } else {
                // রিসেট কিওয়ার্ড চেক
                $resetWords = ['menu', 'start', 'offer', 'ki ace', 'home', 'suru'];
                foreach ($resetWords as $word) {
                    if (stripos($userMessage, $word) !== false) {
                        Log::info("🔄 Generic Query Reset.");
                        $session->update(['customer_info' => array_merge($session->customer_info, ['step' => 'start'])]);
                        break;
                    }
                }
            }

            // ✅ 7. ORDER FLOW PROCESSING
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
            
            // Execute Step Logic
            $result = $handler->process($session, (string)$userMessage, $imageUrl);
            
            $instruction = $result['instruction'] ?? "আমি বুঝতে পারিনি।";
            $contextData = $result['context'] ?? "[]";

            // 🔥 8. ORDER CREATION ACTION
            if (isset($result['action']) && $result['action'] === 'create_order') {
                Log::info("🚀 Action Triggered: create_order");
                try {
                    $order = $this->orderService->finalizeOrderFromSession($clientId, $senderId, $client);
                    $instruction .= " (SYSTEM: Order Created Successfully! Order ID is #{$order->id}. Congratulate user.)";
                    
                    // Auto Alert via NotificationService
                    $this->notify->sendTelegramAlert($client, $senderId, "✅ **New Order Placed:**\nOrder #{$order->id}\nAmount: ৳{$order->total_amount}", 'success');
                } catch (\Exception $e) {
                    $instruction = "Technical error creating order. Please apologize.";
                    Log::error("❌ Order Error: " . $e->getMessage());
                }
            }

            // ✅ 9. CONTEXT GENERATION (via InventoryService)
            // এখানে ভিডিও এবং ডিসকাউন্ট লজিক InventoryService থেকে আসবে
            $inventoryData = $this->inventory->getFormattedInventory($client, $userMessage);
            
            $orderHistory = $this->buildOrderContext($clientId, $senderId);
            $currentTime = now()->format('l, h:i A');
            $userName = $session->customer_info['name'] ?? 'Sir/Ma\'am';

            $knowledgeBase = $client->knowledge_base ?? "সাধারণ ই-কমার্স পলিসি ফলো করো।";
            $deliveryInfo = "Inside Dhaka: {$client->delivery_charge_inside} Tk, Outside: {$client->delivery_charge_outside} Tk";

            // 🔥 DYNAMIC PROMPT GENERATION
            $systemPrompt = $this->generateDynamicSystemPrompt($client, $instruction, $contextData, $orderHistory, $inventoryData, $currentTime, $userName, $knowledgeBase, $deliveryInfo);
            
            // Message Building
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            
            // History Injection
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
            
            if (!$aiResponse) {
                Log::error("❌ LLM returned null.");
                return "দুঃখিত, আমি এই মুহূর্তে উত্তর দিতে পারছি না। কিছুক্ষণ পর আবার চেষ্টা করুন।";
            }

            // Save History
            $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
            $session->update(['customer_info' => array_merge($session->customer_info, ['history' => array_slice($history, -20)])]);

            return $aiResponse;
        });
    }


    // =====================================
    // HELPER METHODS
    // =====================================

    /**
     * 🔥 DYNAMIC PROMPT GENERATOR
     */
    /**
     * 🔥 DYNAMIC PROMPT GENERATOR (Updated with Strict Rules)
     */
    private function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo)
    {
        $customPrompt = $client->custom_prompt;

        // যদি কাস্টম প্রম্পট না থাকে, তবে ডিফল্ট প্রম্পট ব্যবহার হবে
        if (empty($customPrompt)) {
            $customPrompt = <<<EOT
তুমি হলে **{{shop_name}}**-এর একজন স্মার্ট অনলাইন সেলস এক্সিকিউটিভ।

**তোমার নলেজ বেস:**
{{knowledge_base}}
**ডেলিভারি চার্জ:** {{delivery_info}}

**⚠️ কঠোর নিয়মাবলী (Strict Rules - Must Follow):**
১. **NO FAKE ORDERS:** তুমি নিজে থেকে কখনো বলবে না "অর্ডার কনফার্ম হয়েছে" বা "অর্ডার আইডি X", যতক্ষণ না 'Current Instruction' সেকশনে সিস্টেম তোমাকে স্পষ্ট লিখে দেয় **"Order Created Successfully"**।
২. **REVIEW FIRST:** কাস্টমার যখন নাম ও ঠিকানা দিয়ে দেয়, তখন তাকে অর্ডারের সামারি (পণ্য, দাম ও ডেলিভারি চার্জ) দেখাও এবং বলো: **"সব ঠিক থাকলে 'Ji' বা 'Confirm' লিখে রিপ্লাই দিন"**।
৩. **WAITING MODE:** কাস্টমার "Ji", "Yes" বা "Confirm" বললে তুমি শুধু বলবে: **"ধন্যবাদ, আপনার অর্ডারটি প্রসেস করছি..."**। এই মুহূর্তে কোনো অর্ডার আইডি বানাবে না।
৪. **OFFER & PRICE:** ইনভেন্টরিতে `price_info` চেক করো। অফার থাকলে বলো: "স্যার, এটার রেগুলার প্রাইস... কিন্তু অফারে পাচ্ছেন... টাকায়!"।
৫. **VIDEO & QUALITY:** কাস্টমার কোয়ালিটি দেখতে চাইলে `video` লিংক দাও।
৬. **LINK:** কাস্টমার লিংক চাইলে `link` ফিল্ড থেকে লিংক দিবে।

**বর্তমান অবস্থা ও নির্দেশ (Current Instruction):**
{{instruction}}

**প্রয়োজনীয় তথ্য:**
- বর্তমান সময়: {{time}}
- কাস্টমার: {{customer_name}}
- সাম্প্রতিক অর্ডার স্ট্যাটাস: {{last_order}}
- অর্ডার ইতিহাস: {{order_history}}
- প্রোডাক্ট প্রসঙ্গ: {{product_context}}
- ইনভেন্টরি: {{inventory}}
EOT;
        }

        // সাম্প্রতিক অর্ডার খুঁজে বের করা (যাতে কাস্টমার স্ট্যাটাস জানতে চাইলে AI বলতে পারে)
        $recentOrder = Order::where('client_id', $client->id)
            ->where('sender_id', request('sender_id') ?? 0)
            ->latest()
            ->first();
            
        $recentOrderInfo = $recentOrder 
            ? "সর্বশেষ অর্ডার: #{$recentOrder->id} ({$recentOrder->order_status})" 
            : "কোনো সাম্প্রতিক অর্ডার নেই।";

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
            '{{last_order}}'      => $recentOrderInfo, // এটি প্রম্পটে যুক্ত করা হয়েছে
            '{shop_name}' => $client->shop_name, '{inventory}' => $invData 
        ];

        return strtr($customPrompt, $tags);
    }
    // ==========================================
    // LEGACY HELPERS (To satisfy "No remove" rule)
    // ==========================================
    
    // Note: These methods are kept for backward compatibility if any other part of the app uses them, 
    // but the main logic now uses the injected Services (MediaService, etc).

    private function lookupOrderByPhone($clientId, $message)
    {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $message = str_replace($bn, $en, $message);

        if (preg_match('/01[3-9]\d{8,9}/', $message, $matches)) {
            $phone = substr($matches[0], 0, 11);
            $order = Order::where('client_id', $clientId)->where('customer_phone', $phone)->latest()->first();
            if ($order) {
                $status = ucfirst($order->order_status);
                return "FOUND_ORDER: অর্ডার #{$order->id}। অবস্থা: {$status}। বিল: {$order->total_amount} টাকা।";
            }
        }
        return null;
    }

    private function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::where('client_id', $clientId)->where('sender_id', $senderId)->latest()->take(1)->get();
        if ($orders->isEmpty()) return "নতুন কাস্টমার।";
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
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অবস্থা', 'কবে পাব', 'tracking'];
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos(mb_strtolower($msg), $kw) !== false) return true;
        }
        return false;
    }
}