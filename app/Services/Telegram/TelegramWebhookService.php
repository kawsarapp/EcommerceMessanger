<?php

namespace App\Services\Telegram;

use Illuminate\Support\Str;
use App\Models\Conversation;
use App\Services\ChatbotService;

class TelegramWebhookService
{
    protected $api;
    protected $adminService;
    protected $chatbot;

    public function __construct(TelegramApiService $api, TelegramAdminService $adminService, ChatbotService $chatbot)
    {
        $this->api = $api;
        $this->adminService = $adminService;
        $this->chatbot = $chatbot;
    }

    public function processPayload($data, $token, $client)
    {
        // ২. বাটন ক্লিক হ্যান্ডলিং
        if (isset($data['callback_query'])) {
            $this->adminService->handleCallback($data['callback_query'], $client);
            return;
        }

        // ৩. টেক্সট মেসেজ ও মেনু হ্যান্ডলিং
        if (isset($data['message']['text'])) {
            $incomingChatId = (string) $data['message']['chat']['id'];
            $text = trim($data['message']['text']);

            // 🔥 অটোমেটিক অ্যাডমিন চ্যাট আইডি রেজিস্ট্রেশন
            if ($text === '/start' && empty($client->telegram_chat_id)) {
                $client->update(['telegram_chat_id' => $incomingChatId]);
                $this->api->sendMessage($token, $incomingChatId, "✅ **বট সেটআপ সফল!**\nআপনার চ্যাট আইডি সংযুক্ত করা হয়েছে। এখন আপনি মেনু দেখতে পারবেন।");
                return;
            }

            $adminChatId = (string) $client->telegram_chat_id;

            // ==========================================
            // 🤖 OMNICHANNEL AI LOGIC (কাস্টমারের জন্য)
            // ==========================================
            if ($incomingChatId !== $adminChatId) {
                if ($client->is_telegram_active) {
                    $aiResponse = $this->chatbot->handleMessage($client, $incomingChatId, $text, null);
                    if ($aiResponse) {
                        app(\App\Services\NotificationService::class)->sendTelegramCustomerReply($token, $incomingChatId, $aiResponse);
                        Conversation::create([
                            'client_id' => $client->id, 
                            'sender_id' => $incomingChatId, 
                            'platform' => 'telegram', 
                            'user_message' => $text, 
                            'bot_response' => $aiResponse, 
                            'status' => 'success'
                        ]);
                    }
                }
                return;
            }

            // ==========================================
            // 👨‍💻 ADMIN COMMANDS & MENU (সেলার/অ্যাডমিনের জন্য)
            // ==========================================
            $chatId = $adminChatId; 

            if (Str::startsWith($text, '/order ')) {
                $this->adminService->searchOrderById($token, $chatId, $client->id, Str::after($text, '/order '));
                return;
            }
            if (Str::startsWith($text, '/search ')) {
                $this->adminService->searchCustomerByPhone($token, $chatId, $client->id, Str::after($text, '/search '));
                return;
            }
            if (Str::startsWith($text, '/stock ')) {
                $this->adminService->searchProductStock($token, $chatId, $client->id, Str::after($text, '/stock '));
                return;
            }
            if (Str::startsWith($text, '/reply ')) {
                $parts = explode(' ', $text, 3);
                if (count($parts) >= 3) {
                    $this->adminService->sendManualReply($client, $parts[1], $parts[2], $token, $chatId);
                } else {
                    $this->api->sendMessage($token, $chatId, "⚠️ ফরম্যাট ভুল। লিখুন: `/reply [User_ID] [Message]`");
                }
                return;
            }

            // 📋 মেনু কমান্ড হ্যান্ডলিং
            switch ($text) {
                case '/start':
                case '/menu':
                    $this->adminService->showMainMenu($token, $chatId, $client->shop_name);
                    break;
                case '📊 আজকের রিপোর্ট':
                    $this->adminService->showDailyReport($token, $chatId, $client);
                    break;
                case '📦 পেন্ডিং অর্ডার':
                    $this->adminService->showPendingOrders($token, $chatId, $client->id);
                    break;
                case '❌ বাতিল অর্ডার':
                    $this->adminService->showCancelledOrders($token, $chatId, $client->id);
                    break;
                case '🚚 শিপিং স্ট্যাটাস':
                    $this->adminService->showShippingStatus($token, $chatId, $client->id);
                    break;
                case '⚙️ সেটিংস / স্টপ লিস্ট':
                    $this->adminService->showStoppedUsers($token, $chatId, $client->id);
                    break;
                default:
                    if (Str::startsWith($text, '/')) {
                        $this->api->sendMessage($token, $chatId, "⚠️ কমান্ডটি সঠিক নয়। মেনু দেখতে `/menu` লিখুন।");
                    }
                    break;
            }
        }
    }
}