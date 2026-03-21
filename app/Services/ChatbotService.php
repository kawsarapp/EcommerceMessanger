<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\OrderSession;
use App\Models\Client;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\NotificationService;
use App\Services\MediaService;
use App\Services\InventoryService;
use App\Services\SafetyGuardService;
use App\Services\Chatbot\ChatbotPromptService;
use App\Services\Chatbot\ChatbotUtilityService;
use App\Services\OrderFlow\StartStep;
use App\Services\OrderFlow\VariantStep;
use App\Services\OrderFlow\AddressStep;
use App\Services\OrderFlow\ConfirmStep;
use App\Services\OrderFlow\OrderTraits;
use App\Services\Chatbot\ChatbotFeatureService;


class ChatbotService
{
    use OrderTraits;

    protected $orderService, $notify, $media, $inventory, $safety, $promptService, $utility, $features;

    public function __construct(
        OrderService $orderService, NotificationService $notify, MediaService $media,
        InventoryService $inventory, SafetyGuardService $safety,
        ChatbotPromptService $promptService, ChatbotUtilityService $utility,
        ChatbotFeatureService $features
        )
    {
        $this->orderService = $orderService;
        $this->notify = $notify;
        $this->media = $media;
        $this->inventory = $inventory;
        $this->safety = $safety;
        $this->promptService = $promptService;
        $this->utility = $utility;
        $this->features = $features;
    }

    public function handleMessage($client, $senderId, $messageText, $incomingImageUrl = null, $platform = 'messenger')
    {
        return $this->getAiResponse($messageText, $client->id, $senderId, $incomingImageUrl, $platform);
    }

    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null, $platform = 'messenger')
    {
        $lock = Cache::lock("processing_user_{$senderId}", 5);
        $client = Client::find($clientId);
        $shopName = $client->shop_name ?? 'Unknown Shop';
        Log::info("🤖 AI Service Started | Shop: {$shopName} (ID:{$clientId}) | Customer: {$senderId}");

        $userMessage = $userMessage ?? '';
        $base64Image = null;

        // ══════════════════════════════════════════════════════════════════════
        // MEDIA HANDLING: Voice + Image
        // ══════════════════════════════════════════════════════════════════════
        if ($imageUrl) {

            // ── Check: Is this a voice/audio attachment? ──────────────────────
            if ($this->isVoiceUrl($imageUrl)) {
                $voiceText = $this->media->convertVoiceToText($imageUrl);
                if ($voiceText) {
                    $userMessage = $voiceText . " [Voice Message]";
                    Log::info("🎤 Voice transcribed for customer {$senderId}: " . substr($voiceText, 0, 80));
                } else {
                    $userMessage = "[SYSTEM: Customer sent a voice message but transcription failed. Politely ask them to type their message instead.]";
                }
                $imageUrl = null;

            } else {
                // ── Image: Vision scan + SKU lookup ──────────────────────────
                $base64Image = $this->media->processImage($imageUrl);
            }
        }

        if ($base64Image) {
            $visionTags  = $this->utility->analyzeImageWithGoogleVision($base64Image);
            $skuProduct  = null;
            $skuContext  = '';

            // ✅ KEY FIX: Extract detected text, search DB by SKU/name immediately
            if ($visionTags && preg_match("/ছবির গায়ে লেখা:\s*'([^']+)'/u", $visionTags, $textMatch)) {
                $detectedText = trim($textMatch[1]);
                $skuProduct   = $this->findProductBySkuOrText($clientId, $detectedText);

                if ($skuProduct) {
                    $price      = $skuProduct->sale_price > 0 ? $skuProduct->sale_price : $skuProduct->regular_price;
                    $stockLabel = $skuProduct->stock_status === 'in_stock' ? "স্টকে আছে ✅" : "স্টকে নেই ❌";
                    $skuContext = "✅ SKU '{$detectedText}' দিয়ে DB-তে product পাওয়া গেছে → "
                        . "নাম: '{$skuProduct->name}', SKU: {$skuProduct->sku}, দাম: ৳{$price}, {$stockLabel}। "
                        . "এই exact তথ্যগুলো কাস্টমারকে দাও।";
                } else {
                    $skuContext = "⚠️ ছবিতে লেখা '{$detectedText}' দিয়ে কোনো product পাওয়া যায়নি। "
                        . "কাস্টমারকে বলো এই code/SKU-টি database-এ নেই।";
                }
            }

            $promptContext = "[সিস্টেম নোট: কাস্টমার ছবি পাঠিয়েছে।"
                . ($visionTags ? " Vision scan: '{$visionTags}'." : "")
                . ($skuContext  ? " {$skuContext}" : " Inventory থেকে এই ছবির মতো product খুঁজো।")
                . " ⚠️ শুধু Inventory-তে থাকা real tথ্য দেবে।] ";

            $userMessage = empty(trim($userMessage))
                ? $promptContext . "এই ছবির product স্টকে আছে?"
                : $promptContext . "কাস্টমার বলেছে: " . $userMessage;

            // Pre-select the found product in session
            if ($skuProduct) {
                $sess = OrderSession::firstOrCreate(
                    ['sender_id' => $senderId],
                    ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'history' => []]]
                );
                if (($sess->customer_info['product_id'] ?? null) !== $skuProduct->id) {
                    $sess->update(['customer_info' => array_merge($sess->customer_info, [
                        'step'       => 'start',
                        'product_id' => $skuProduct->id,
                    ])]);
                }
            }
        }
        elseif (empty(trim($userMessage)) && !$base64Image)
            return null;

        // ══════════════════════════════════════════════════════════════════════
        // SAFETY CHECK
        // ══════════════════════════════════════════════════════════════════════
        $safetyStatus = $this->safety->checkMessageSafety($senderId, $userMessage);
        if (!$client)
            $client = Client::find($clientId);

        if ($safetyStatus === 'bad_word') {
            $this->notify->sendTelegramAlert($client, $senderId, "⚠️ **Abusive Language Detected:**\n`$userMessage`", 'warning');
            return "অনুগ্রহ করে ভদ্র ভাষা ব্যবহার করুন। আমাদের এজেন্ট শীঘ্রই আপনার সাথে যোগাযোগ করবে।";
        }
        if ($safetyStatus === 'angry' || $safetyStatus === 'spam') {
            OrderSession::updateOrCreate(['sender_id' => $senderId, 'client_id' => $clientId], ['is_human_agent_active' => true]);
            $this->notify->sendTelegramAlert($client, $senderId, "🛑 **AI Stopped!**\nReason: " . ($safetyStatus === 'spam' ? "Spamming" : "Customer Angry") . "\nMsg: `$userMessage`", 'danger');
            return "দুঃখিত, আমি আপনার কথা বুঝতে পারছি না। আমাদের একজন প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।";
        }

        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image, $imageUrl, $client, $shopName, $platform) {
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'platform' => $platform, 'customer_info' => ['step' => 'start', 'history' => []]]
            );
            $session = OrderSession::where('sender_id', $senderId)->lockForUpdate()->first();
            // Backfill platform for existing sessions (পুরোনো sessions এ platform নেই)
            if (empty($session->platform)) {
                $session->update(['platform' => $platform]);
            }
            if ($session->is_human_agent_active)
                return null;

            $session->refresh();

            // ── Feature Detection: Coupon, Return, Flash Sale, Loyalty, Referral ──────
            $featureContext = '';

            // 🔴 Return/Refund Request
            if ($this->features->isReturnRequest($userMessage)) {
                $lastOrder = $this->features->getLastOrderForReturn($clientId, $senderId);
                if ($lastOrder) {
                    $this->features->createReturnRequest($clientId, $lastOrder, $senderId, $userMessage);
                    $featureContext .= "\n[SYSTEM: কাস্টমার Order #{$lastOrder->id} ফেরত দিতে চাইছে। Return request তৈরি হয়েছে। কাস্টমারকে জানাও যে তার request নিবন্ধিত হয়েছে এবং ২৪ ঘণ্টার মধ্যে যোগাযোগ করা হবে।]";
                } else {
                    $featureContext .= "\n[SYSTEM: কাস্টমার return চাইছে কিন্তু eligible কোনো order নেই। বিনয়ের সাথে জানাও।]";
                }
            }

            // 💎 Loyalty Points Query
            if ($this->features->isPointsQuery($userMessage)) {
                $featureContext .= $this->features->getPointsContext($clientId, $senderId);
            }

            // 🎁 Referral Code Query
            if ($this->features->isReferralQuery($userMessage)) {
                $featureContext .= $this->features->getReferralContext($clientId, $senderId);
            }

            // 🔥 Flash Sale Active Context
            $featureContext .= $this->features->getFlashSaleContext($clientId);

            // 🎟️ Coupon Detection — will run after $stepName is assigned below

            $stepName = $session->customer_info['step'] ?? 'start';
            $isTracking = $this->utility->isTrackingIntent($userMessage);

            // 🎟️ Coupon Detection (confirm_order step এ)
            if ($stepName === 'confirm_order') {
                $couponCode = $this->features->detectCouponCode($userMessage);
                if ($couponCode) {
                    $result = $this->features->validateCoupon($clientId, $couponCode);
                    $featureContext .= "\n[SYSTEM: কাস্টমার coupon code '{$couponCode}' দিয়েছে। " . $result['message'] . "]";
                    if ($result['valid']) {
                        $session->update(['customer_info' => array_merge($session->customer_info, ['coupon_code' => $couponCode, 'coupon_discount' => $result['discount']])]);
                    }
                }
            }

            if ($stepName === 'collect_info' || $stepName === 'confirm_order')
                $isTracking = false;

            if ($isTracking) {
                $instruction = "কাস্টমার তার অর্ডারের অবস্থা (Tracking/Status) জানতে চাইছে। 'অর্ডার ইতিহাস' (Order History) থেকে সর্বশেষ অর্ডারের স্ট্যাটাস দেখে তাকে সুন্দর করে আপডেট দাও। \n- Shipped হলে: 'আপনার অর্ডারটি কুরিয়ারে দেওয়া হয়েছে। দ্রুত পেয়ে যাবেন।'\n- Pending/Processing হলে: 'আপনার অর্ডারটি প্রসেসিং এ আছে।'\n- Delivered হলে: 'অর্ডারটি ডেলিভারি সম্পন্ন হয়েছে।'\n- অর্ডার না থাকলে: 'আপনার কোনো অর্ডার পাওয়া যায়নি, অন্য নাম্বার দিয়ে চেক করতে পারেন।'\n⚠️ নতুন কোনো প্রোডাক্ট বিক্রির চেষ্টা করবে না।";
                $contextData = "[]";
            }
            else {
                if ($stepName !== 'confirm_order' && $stepName !== 'collect_info') {
                    $newProduct = $this->findProductSystematically($clientId, $userMessage);
                    if ($newProduct && $newProduct->id != ($session->customer_info['product_id'] ?? null)) {
                        $session->update(['customer_info' => ['step' => 'start', 'product_id' => $newProduct->id, 'history' => [], 'variant' => []]]);
                        $stepName = 'start';
                    }
                    elseif (!$newProduct) {
                        $greetingKeywords = [
                            'menu', 'start', 'offer', 'ki ace', 'home', 'suru',
                            'hi', 'hello', 'হ্যালো', 'হ্যাল', 'হ্যা', 'salaam', 'salam',
                            'assalamu', 'assalam', 'আস্সালামু', 'আমি', 'alo', 'aloo',
                            'নমস্কার', 'শুভেচ্ছা', 'হাই',
                        ];
                        foreach ($greetingKeywords as $word) {
                            if (stripos($userMessage, $word) !== false) {
                                $session->update(['customer_info' => ['step' => 'start', 'history' => []]]);
                                $stepName = 'start';
                                break;
                            }
                        }
                    }
                }

                $steps = ['start' => new StartStep(), 'select_variant' => new VariantStep(), 'collect_info' => new AddressStep(), 'confirm_order' => new ConfirmStep(), 'completed' => new StartStep()];
                $handler = $steps[$stepName] ?? $steps['start'];
                $result = $handler->process($session, (string)$userMessage, $imageUrl);

                $instruction = $result['instruction'] ?? "আমি বুঝতে পারিনি।";
                $contextData = $result['context'] ?? "[]";

                if (isset($result['action']) && $result['action'] === 'create_order') {
                    try {
                        $order = $this->orderService->finalizeOrderFromSession($clientId, $senderId, $client);
                        $instruction = "আপনার অর্ডারটি সফলভাবে তৈরি করা হয়েছে (apanr order ta create kora hoice)। অর্ডার আইডি: #{$order->id}। কাস্টমারকে এই মেসেজটি হুবহু জানাও।";
                        $this->notify->sendTelegramAlert($client, $senderId, "✅ **New Order Placed:**\nOrder #{$order->id}\nAmount: ৳{$order->total_amount}", 'success');
                        $stepName = 'completed';
                        $session->update(['customer_info' => array_merge($session->customer_info, ['step' => 'start', 'product_id' => null, 'variant' => []])]);
                    }
                    catch (\Exception $e) {
                        Log::error("❌ Order Creation Failed: " . $e->getMessage());
                        $instruction = "অর্ডার তৈরি করার সময় একটি সমস্যা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর আবার চেষ্টা করুন। কাস্টমারকে এই মেসেজটি হুবহু জানাও।";
                    }
                }
            }

            $inventoryData = $this->inventory->getFormattedInventory($client, $userMessage);
            $orderHistory = $this->promptService->buildOrderContext($clientId, $senderId, $userMessage);
                // ── Session product context inject করো (collect_info/confirm step এ হারিয়ে যাওয়া product বাঁচাতে)
            $sessionProductContext = null;
            if (!empty($session->customer_info['product_id'])) {
                $sessionProduct = Product::find($session->customer_info['product_id']);
                if ($sessionProduct) {
                    $finalPrice = ($sessionProduct->sale_price > 0 && $sessionProduct->sale_price < $sessionProduct->regular_price)
                        ? $sessionProduct->sale_price : $sessionProduct->regular_price;
                    $variant = $session->customer_info['variant'] ?? [];
                    $variantStr = !empty($variant) ? ' [Variant: ' . implode(', ', array_filter($variant)) . ']' : '';
                    $sessionProductContext = "✅ কাস্টমার এই product টা select করেছে: '{$sessionProduct->name}' (দাম: ৳{$finalPrice}{$variantStr}). এই product সম্পর্কে কথা বলো — অন্য product suggest করো না।";
                    Log::info("✅ SELECTED_PRODUCT_CONTEXT injected: {$sessionProduct->name}");
                }
            }

            // Append feature context to instruction
            if (!empty($featureContext)) {
                $instruction .= $featureContext;
            }

            $systemPrompt = $this->promptService->generateDynamicSystemPrompt($client, $instruction, $contextData, $orderHistory, $inventoryData, now()->format('l, h:i A'), $session->customer_info['name'] ?? 'Customer', $client->knowledge_base ?? "সাধারণ ই-কমার্স পলিসি ফলো করো।", "Inside Dhaka: {$client->delivery_charge_inside} Tk, Outside: {$client->delivery_charge_outside} Tk", $stepName, $sessionProductContext);

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            $history = $session->customer_info['history'] ?? [];
            foreach (array_slice($history, -20) as $chat) {
                if (!empty($chat['user']))
                    $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai']))
                    $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            if ($base64Image)
                $messages[] = ['role' => 'user', 'content' => [['type' => 'text', 'text' => $userMessage], ['type' => 'image_url', 'image_url' => ['url' => $base64Image]]]];
            else
                $messages[] = ['role' => 'user', 'content' => $userMessage];

            $aiResponse = $this->utility->callLlmChain($messages, $client);
            if (!$aiResponse)
                return "দুঃখিত, আমি এই মুহূর্তে উত্তর দিতে পারছি না। কিছুক্ষণ পর আবার চেষ্টা করুন।";

            Log::info("🤖 AI Response Generated | Shop: {$shopName} | Customer: {$senderId}");

            // Extract custom data tags from AI response
            $extractedData = [];
            if (preg_match('/\[NAME:\s*(.+?)\]/i', $aiResponse, $nameMatch))
                $extractedData['name'] = trim($nameMatch[1]);
            if (preg_match('/\[PHONE:\s*(.+?)\]/i', $aiResponse, $phoneMatch))
                $extractedData['phone'] = preg_replace('/[^0-9]/', '', trim($phoneMatch[1]));
            if (preg_match('/\[ADDRESS:\s*(.+?)\]/i', $aiResponse, $addressMatch))
                $extractedData['address'] = trim($addressMatch[1]);

            if (!empty($extractedData)) {
                Log::info("✅ Address Data Auto-Extracted: ", $extractedData);
                $currentInfo = $session->customer_info ?? [];

                if (isset($extractedData['name']))    $currentInfo['name']    = $extractedData['name'];
                if (isset($extractedData['phone']))   $currentInfo['phone']   = $extractedData['phone'];
                if (isset($extractedData['address'])) {
                    $existingAddr = $currentInfo['address'] ?? '';
                    $currentInfo['address'] = (!empty($existingAddr) && !str_contains($existingAddr, $extractedData['address']))
                        ? "$existingAddr, {$extractedData['address']}"
                        : $extractedData['address'];
                }

                $session->update(['customer_info' => $currentInfo]);
                $aiResponse = preg_replace('/\[NAME:.*?\]|\[PHONE:.*?\]|\[ADDRESS:.*?\]/i', '', $aiResponse);
                $aiResponse = trim($aiResponse);
            }

            $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
            $session->update(['customer_info' => array_merge($session->customer_info, ['history' => array_slice($history, -50)])]);

            Log::info("💬 CONVERSATION | Shop: {$shopName} | Customer: {$senderId}\n" .
                "   📥 Customer: " . substr($userMessage, 0, 200) . "\n" .
                "   🤖 AI: " . substr($aiResponse, 0, 200));

            return $aiResponse;
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Detect if a URL is a voice/audio attachment.
     * Facebook: sends voice as audio/ogg; Instagram: similar.
     * WhatsApp: sends as .ogg or .m4a.
     */
    private function isVoiceUrl(string $url): bool
    {
        $lower = strtolower($url);

        // Extension-based detection
        $audioExtensions = ['ogg', 'oga', 'mp3', 'm4a', 'aac', 'wav', 'webm', 'amr', 'flac'];
        $ext = pathinfo(parse_url($lower, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (in_array($ext, $audioExtensions)) return true;

        // ✅ Fix: local storage audio URL detect (WhatsApp saves audio as .ogg locally)
        if (str_contains($lower, 'chat_attachments/') && in_array($ext, $audioExtensions)) return true;
        if (str_contains($lower, 'storage/') && str_contains($lower, 'wa_') && in_array($ext, $audioExtensions)) return true;

        // URL keyword-based detection (Facebook/WhatsApp voice URLs contain these)
        $audioKeywords = ['audio', 'voice', 'sound', 'speech', '.ogg', '.mp3', '.m4a', '.aac', '.wav'];
        foreach ($audioKeywords as $kw) {
            if (str_contains($lower, $kw)) return true;
        }

        // Check Content-Type header via HEAD request (fast)
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HEADER         => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,  // Fix: self-signed cert
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $headers  = curl_exec($ch);
            curl_close($ch);


            if ($headers && preg_match('/Content-Type:\s*([^\r\n;]+)/i', $headers, $m)) {
                return str_contains(strtolower($m[1]), 'audio');
            }
        } catch (\Exception $e) {
            // ignore — assume not audio
        }

        return false;
    }

    /**
     * Search product by SKU text detected in image via Google Vision OCR.
     * Tries exact SKU match first, then partial name match.
     */
    private function findProductBySkuOrText(int $clientId, string $detectedText): ?Product
    {
        if (empty(trim($detectedText))) return null;

        // Clean detected text — remove newlines, keep only useful chars
        $clean = trim(preg_replace('/\s+/', ' ', $detectedText));

        // Split into tokens (words/codes) and try each
        $tokens = array_filter(array_map('trim', preg_split('/[\s,|]+/', $clean)));

        foreach ($tokens as $token) {
            if (strlen($token) < 3) continue;

            $product = Product::where('client_id', $clientId)
                ->where(function ($q) use ($token) {
                    $q->where('sku', $token)
                      ->orWhere('sku', 'LIKE', "%{$token}%")
                      ->orWhere('name', 'LIKE', "%{$token}%");
                })
                ->first();

            if ($product) return $product;
        }

        // Last resort: search full detected text in product names
        return Product::where('client_id', $clientId)
            ->where('name', 'LIKE', '%' . $clean . '%')
            ->first();
    }
}