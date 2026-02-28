<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\OrderSession;
use App\Models\Client;

// Core Services
use App\Services\OrderService;
use App\Services\NotificationService;
use App\Services\MediaService;
use App\Services\InventoryService;
use App\Services\SafetyGuardService;

// Extracted Chatbot Services
use App\Services\Chatbot\ChatbotPromptService;
use App\Services\Chatbot\ChatbotUtilityService;

// OrderFlow Classes
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
        OrderService $orderService,
        NotificationService $notify,
        MediaService $media,
        InventoryService $inventory,
        SafetyGuardService $safety,
        ChatbotPromptService $promptService,
        ChatbotUtilityService $utility
    ) {
        $this->orderService = $orderService;
        $this->notify = $notify;
        $this->media = $media;
        $this->inventory = $inventory;
        $this->safety = $safety;
        $this->promptService = $promptService;
        $this->utility = $utility;
    }

    /**
     * 🌐 Omnichannel Wrapper: Telegram ও Instagram যেন এই ফাংশনটি কল করতে পারে
     */
    public function handleMessage($client, $senderId, $messageText, $incomingImageUrl = null) {
        return $this->getAiResponse($messageText, $client->id, $senderId, $incomingImageUrl);
    }

    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        $lock = Cache::lock("processing_user_{$senderId}", 5);
        Log::info("🤖 AI Service Started for User: $senderId");

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

        if (empty(trim($userMessage)) && $base64Image) {
            $userMessage = "I have sent an image. Please analyze it and check if you have something similar in your inventory.";
        } elseif (empty(trim($userMessage)) && !$base64Image) {
            return null;
        }

        $safetyStatus = $this->safety->checkMessageSafety($senderId, $userMessage);
        $client = Client::find($clientId);

        if ($safetyStatus === 'bad_word') {
            $this->notify->sendTelegramAlert($client, $senderId, "⚠️ **Abusive Language Detected:**\n`$userMessage`", 'warning');
            return "অনুগ্রহ করে ভদ্র ভাষা ব্যবহার করুন। আমাদের এজেন্ট শীঘ্রই আপনার সাথে যোগাযোগ করবে।";
        }

        if ($safetyStatus === 'angry' || $safetyStatus === 'spam') {
            $reason = ($safetyStatus === 'spam') ? "Spamming/Looping" : "Customer Angry";
            OrderSession::updateOrCreate(['sender_id' => $senderId, 'client_id' => $clientId], ['is_human_agent_active' => true]);
            $this->notify->sendTelegramAlert($client, $senderId, "🛑 **AI Stopped!**\nReason: $reason\nMsg: `$userMessage`", 'danger');
            return "দুঃখিত, আমি আপনার কথা বুঝতে পারছি না। আমাদের একজন প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।";
        }

        return DB::transaction(function () use ($userMessage, $clientId, $senderId, $base64Image, $imageUrl, $client) {

            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['step' => 'start', 'history' => []]]
            );
            
            $session = OrderSession::where('sender_id', $senderId)->lockForUpdate()->first();

            if ($session->is_human_agent_active) return null;

            if ($this->utility->isTrackingIntent($userMessage) || preg_match('/01[3-9]\d{8}/', $userMessage)) {
                $orderStatusMsg = $this->utility->lookupOrderByPhone($clientId, $userMessage);
                if ($orderStatusMsg && str_contains($orderStatusMsg, 'FOUND_ORDER')) {
                    return "স্যার/ম্যাম, আপনার অর্ডারের তথ্য পেয়েছি: \n" . str_replace('FOUND_ORDER:', '', $orderStatusMsg) . "\nআমাদের সাথে থাকার জন্য ধন্যবাদ!";
                }
            }
            
            $session->refresh(); 
            $stepName = $session->customer_info['step'] ?? 'start';

            // 🔥 HISTORY RESET LOGIC (Prevent Loop/Confusion)
            if ($stepName !== 'confirm_order' && $stepName !== 'collect_info') {
                $newProduct = $this->findProductSystematically($clientId, $userMessage);
                
                if ($newProduct && $newProduct->id != ($session->customer_info['product_id'] ?? null)) {
                    // কাস্টমার নতুন প্রোডাক্ট সিলেক্ট করেছে, তাই আগের হিস্ট্রি এবং ভেরিয়েন্ট মুছে ফেলা হচ্ছে
                    $session->update([
                        'customer_info' => ['step' => 'start', 'product_id' => $newProduct->id, 'history' => [], 'variant' => []]
                    ]);
                    $stepName = 'start'; 
                } elseif (!$newProduct) {
                    foreach (['menu', 'start', 'offer', 'ki ace', 'home', 'suru'] as $word) {
                        if (stripos($userMessage, $word) !== false) {
                            $session->update(['customer_info' => ['step' => 'start', 'history' => []]]); // Reset
                            $stepName = 'start';
                            break;
                        }
                    }
                }
            }

            $steps = [
                'start' => new StartStep(),
                'select_variant' => new VariantStep(),
                'collect_info' => new AddressStep(),
                'confirm_order' => new ConfirmStep(),
                'completed' => new StartStep(),
            ];

            $handler = $steps[$stepName] ?? $steps['start'];
            $result = $handler->process($session, (string)$userMessage, $imageUrl);
            
            $instruction = $result['instruction'] ?? "আমি বুঝতে পারিনি।";
            $contextData = $result['context'] ?? "[]";

            if (isset($result['action']) && $result['action'] === 'create_order') {
                try {
                    $order = $this->orderService->finalizeOrderFromSession($clientId, $senderId, $client);
                    $instruction .= " (SYSTEM: Order Created Successfully! Order ID is #{$order->id}.)";
                    $this->notify->sendTelegramAlert($client, $senderId, "✅ **New Order Placed:**\nOrder #{$order->id}\nAmount: ৳{$order->total_amount}", 'success');
                } catch (\Exception $e) {
                    $instruction = "Technical error creating order. Please apologize.";
                }
            }

            $inventoryData = $this->inventory->getFormattedInventory($client, $userMessage);
            $orderHistory = $this->promptService->buildOrderContext($clientId, $senderId);
            
            // 🔥 এখানে $stepName পাস করা হয়েছে
            $systemPrompt = $this->promptService->generateDynamicSystemPrompt(
                $client, $instruction, $contextData, $orderHistory, $inventoryData, 
                now()->format('l, h:i A'), $session->customer_info['name'] ?? 'Sir/Ma\'am', 
                $client->knowledge_base ?? "সাধারণ ই-কমার্স পলিসি ফলো করো।", 
                "Inside Dhaka: {$client->delivery_charge_inside} Tk, Outside: {$client->delivery_charge_outside} Tk",
                $stepName 
            );
            
            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            
            $history = $session->customer_info['history'] ?? [];
            
            // 🔥 AI যেন লুপে না পড়ে, তাই শুধুমাত্র শেষের ৪টি মেসেজ পাঠানো হচ্ছে (আগে ৬টি ছিল)
            foreach (array_slice($history, -20) as $chat) {
                if (!empty($chat['user'])) $messages[] = ['role' => 'user', 'content' => $chat['user']];
                if (!empty($chat['ai'])) $messages[] = ['role' => 'assistant', 'content' => $chat['ai']];
            }
            
            if ($base64Image) {
                $messages[] = ['role' => 'user', 'content' => [['type' => 'text', 'text' => $userMessage], ['type' => 'image_url', 'image_url' => ['url' => $base64Image]]]];
            } else {
                $messages[] = ['role' => 'user', 'content' => $userMessage];
            }

            $aiResponse = $this->utility->callLlmChain($messages);
            if (!$aiResponse) return "দুঃখিত, আমি এই মুহূর্তে উত্তর দিতে পারছি না। কিছুক্ষণ পর আবার চেষ্টা করুন।";

            $history[] = ['user' => $userMessage, 'ai' => $aiResponse, 'time' => time()];
            
            // DB-তেও মাত্র ১০টি মেসেজ সেভ হবে (আগে ২০টি ছিল), যাতে মেমোরি ক্লিয়ার থাকে
            $session->update(['customer_info' => array_merge($session->customer_info, ['history' => array_slice($history, -50)])]);

            return $aiResponse;
        });
    }
}