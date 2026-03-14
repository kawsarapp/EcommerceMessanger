<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\OrderSession;
use App\Models\Client;
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

class ChatbotService
{
    use OrderTraits; 

    protected $orderService, $notify, $media, $inventory, $safety, $promptService, $utility;

    public function __construct(
        OrderService $orderService, NotificationService $notify, MediaService $media,
        InventoryService $inventory, SafetyGuardService $safety,
        ChatbotPromptService $promptService, ChatbotUtilityService $utility
    ) {
        $this->orderService = $orderService; $this->notify = $notify; $this->media = $media;
        $this->inventory = $inventory; $this->safety = $safety;
        $this->promptService = $promptService; $this->utility = $utility;
    }

    public function handleMessage($client, $senderId, $messageText, $incomingImageUrl = null) {
        return $this->getAiResponse($messageText, $client->id, $senderId, $incomingImageUrl);
    }

    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        $lock = Cache::lock("processing_user_{$senderId}", 5);
        $client = Client::find($clientId);
        $shopName = $client->shop_name ?? 'Unknown Shop';
        Log::info("🤖 AI Service Started | Shop: {$shopName} (ID:{$clientId}) | Customer: {$senderId}");

        $userMessage = $userMessage ?? '';
        $base64Image = null;

        if ($imageUrl) {
            $voiceText = $this->media->convertVoiceToText($imageUrl);
            if ($voiceText) {
                $userMessage = $voiceText . " [Voice Message Transcribed]";
                $imageUrl = null; 
            } else {
                $base64Image = $this->media->processImage($imageUrl);
            }
        }

        if ($base64Image) {
            $visionTags = $this->utility->analyzeImageWithGoogleVision($base64Image);
            $promptContext = $visionTags ? "[সিস্টেম নোট: কাস্টমার একটি ছবি পাঠিয়েছে। Google Vision API স্ক্যান করে ছবিতে এই জিনিসগুলো পেয়েছে: '{$visionTags}'। ⚠️ তুমি ছবির গায়ে লেখা কোনো টেক্সট (OCR) দিয়ে প্রোডাক্ট খুঁজবে না! শুধুমাত্র এই ভিজ্যুয়াল ট্যাগগুলো ব্যবহার করে ইনভেন্টরি থেকে সঠিক প্রোডাক্টটি খুঁজে বের করো।] " : "[সিস্টেম নোট: কাস্টমার একটি ছবি পাঠিয়েছে। ছবিটির ভিজ্যুয়াল প্যাটার্ন বুঝে ইনভেন্টরি থেকে সিমিলার প্রোডাক্ট খুঁজে বের করো।] ";
            $userMessage = empty(trim($userMessage)) ? $promptContext . "এই ছবির মতো কোনো প্রোডাক্ট কি আপনার স্টকে আছে?" : $promptContext . "কাস্টমারের মেসেজ: " . $userMessage;
        } elseif (empty(trim($userMessage)) && !$base64Image) return null;

        $safetyStatus = $this->safety->checkMessageSafety($senderId, $userMessage);
        if (!$client) $client = Client::find($clientId);

        if ($safetyStatus === 'bad_word') {
            $this->notify->sendTelegramAlert($client, $senderId, "⚠️ **Abusive Language Detected:**\n`$userMessage`", 'warning');
            return "অনুগ্রহ করে ভদ্র ভাষা ব্যবহার করুন। আমাদের এজেন্ট শীঘ্রই আপনার সাথে যোগাযোগ করবে।";
        }
        if ($safetyStatus === 'angry' || $safetyStatus === 'spam') {
            OrderSession::updateOrCreate(['sender_id' => $senderId, 'client_id' => $clientId], ['is_human_agent_active' => true]);
            $this->notify->sendTelegramAlert($client, $senderId, "🛑 **AI Stopped!**\nReason: " . ($safetyStatus === 'spam' ? "Spamming" : "Customer Angry") . "\nMsg: `$userMessage`", 'danger');
            return "দুঃখিত, আমি আপনার কথা বুঝতে পারছি না। আমাদের একজন প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।";
        }

        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image, $imageUrl, $client, $shopName) {
            $session = OrderSession::firstOrCreate(['sender_id' => $senderId], ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'history' => []]]);
            $session = OrderSession::where('sender_id', $senderId)->lockForUpdate()->first();
            if ($session->is_human_agent_active) return null;
            
            $session->refresh(); 
            $stepName = $session->customer_info['step'] ?? 'start';
            $isTracking = $this->utility->isTrackingIntent($userMessage);
            
            if ($stepName === 'collect_info' || $stepName === 'confirm_order') $isTracking = false;

            if ($isTracking) {
                $instruction = "কাস্টমার তার অর্ডারের অবস্থা (Tracking/Status) জানতে চাইছে। 'অর্ডার ইতিহাস' (Order History) থেকে সর্বশেষ অর্ডারের স্ট্যাটাস দেখে তাকে সুন্দর করে আপডেট দাও। \n- Shipped হলে: 'আপনার অর্ডারটি কুরিয়ারে দেওয়া হয়েছে। দ্রুত পেয়ে যাবেন।'\n- Pending/Processing হলে: 'আপনার অর্ডারটি প্রসেসিং এ আছে।'\n- Delivered হলে: 'অর্ডারটি ডেলিভারি সম্পন্ন হয়েছে।'\n- অর্ডার না থাকলে: 'আপনার কোনো অর্ডার পাওয়া যায়নি, অন্য নাম্বার দিয়ে চেক করতে পারেন।'\n⚠️ নতুন কোনো প্রোডাক্ট বিক্রির চেষ্টা করবে না।";
                $contextData = "[]";
            } else {
                if ($stepName !== 'confirm_order' && $stepName !== 'collect_info') {
                    $newProduct = $this->findProductSystematically($clientId, $userMessage);
                    if ($newProduct && $newProduct->id != ($session->customer_info['product_id'] ?? null)) {
                        $session->update(['customer_info' => ['step' => 'start', 'product_id' => $newProduct->id, 'history' => [], 'variant' => []]]);
                        $stepName = 'start'; 
                    } elseif (!$newProduct) {
                        foreach (['menu', 'start', 'offer', 'ki ace', 'home', 'suru'] as $word) {
                            if (stripos($userMessage, $word) !== false) {
                                $session->update(['customer_info' => ['step' => 'start', 'history' => []]]);
                                $stepName = 'start'; break;
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
                        $instruction = "অর্ডারটি সফলভাবে ডাটাবেসে সেভ হয়েছে! কাস্টমারকে অভিনন্দন জানাও এবং অর্ডার আইডি (#{$order->id}) জানিয়ে দাও।";
                        $this->notify->sendTelegramAlert($client, $senderId, "✅ **New Order Placed:**\nOrder #{$order->id}\nAmount: ৳{$order->total_amount}", 'success');
                        $stepName = 'completed';
                        $session->update(['customer_info' => array_merge($session->customer_info, ['step' => 'start', 'product_id' => null, 'variant' => []])]);
                    } catch (\Exception $e) {
                        Log::error("❌ Order Creation Failed: " . $e->getMessage());
                        $instruction = "Technical error creating order. Please apologize.";
                    }
                }
            }

            $inventoryData = $this->inventory->getFormattedInventory($client, $userMessage);
            $orderHistory = $this->promptService->buildOrderContext($clientId, $senderId, $userMessage);
            $systemPrompt = $this->promptService->generateDynamicSystemPrompt($client, $instruction, $contextData, $orderHistory, $inventoryData, now()->format('l, h:i A'), $session->customer_info['name'] ?? 'Sir/Ma\'am', $client->knowledge_base ?? "সাধারণ ই-কমার্স পলিসি ফলো করো।", "Inside Dhaka: {$client->delivery_charge_inside} Tk, Outside: {$client->delivery_charge_outside} Tk", $stepName);
            
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            $history = $session->customer_info['history'] ?? [];
            foreach (array_slice($history, -20) as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            if ($base64Image) $messages[] = ['role' => 'user', 'content' => [['type' => 'text', 'text' => $userMessage], ['type' => 'image_url', 'image_url' => ['url' => $base64Image]]]];
            else $messages[] = ['role' => 'user', 'content' => $userMessage];

            $aiResponse = $this->utility->callLlmChain($messages, $client);
            if (!$aiResponse) return "দুঃখিত, আমি এই মুহূর্তে উত্তর দিতে পারছি না। কিছুক্ষণ পর আবার চেষ্টা করুন।";

            Log::info("🤖 AI Response Generated | Shop: {$shopName} | Customer: {$senderId}");

            // 🔥 PRO LEVEL FIX: Extracting Custom Tags generated by AI
            $extractedData = [];
            if (preg_match('/\[NAME:\s*(.+?)\]/i', $aiResponse, $nameMatch)) $extractedData['name'] = trim($nameMatch[1]);
            if (preg_match('/\[PHONE:\s*(.+?)\]/i', $aiResponse, $phoneMatch)) $extractedData['phone'] = preg_replace('/[^0-9]/', '', trim($phoneMatch[1]));
            if (preg_match('/\[ADDRESS:\s*(.+?)\]/i', $aiResponse, $addressMatch)) $extractedData['address'] = trim($addressMatch[1]);

            if (!empty($extractedData)) {
                Log::info("✅ Address Data Auto-Extracted via AI Tags: ", $extractedData);
                $currentInfo = $session->customer_info ?? [];
                
                if (isset($extractedData['name'])) $currentInfo['name'] = $extractedData['name'];
                if (isset($extractedData['phone'])) $currentInfo['phone'] = $extractedData['phone'];
                if (isset($extractedData['address'])) {
                    $existingAddr = $currentInfo['address'] ?? '';
                    $currentInfo['address'] = (!empty($existingAddr) && !str_contains($existingAddr, $extractedData['address'])) 
                                              ? "$existingAddr, {$extractedData['address']}" 
                                              : $extractedData['address'];
                }
                
                $session->update(['customer_info' => $currentInfo]);
                
                // คাস্টমারকে দেখানোর আগে সিক্রেট ট্যাগগুলো মুছে ফেলা হচ্ছে
                $aiResponse = preg_replace('/\[NAME:.*?\]|\[PHONE:.*?\]|\[ADDRESS:.*?\]/i', '', $aiResponse);
                $aiResponse = trim($aiResponse);
            }

            $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
            $session->update(['customer_info' => array_merge($session->customer_info, ['history' => array_slice($history, -50)])]);

            Log::info("💬 CONVERSATION LOG | Shop: {$shopName} (ID:{$clientId}) | Customer: {$senderId}\n" .
                "   📥 Customer Said: " . substr($userMessage, 0, 200) . "\n" .
                "   🤖 AI Replied: " . substr($aiResponse, 0, 200));

            return $aiResponse;
        });
    }
}