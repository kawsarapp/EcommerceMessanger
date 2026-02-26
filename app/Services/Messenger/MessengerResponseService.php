<?php

namespace App\Services\Messenger;

use App\Models\Product;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerResponseService
{
    /**
     * ইউজারের কাছে টাইপিং ইন্ডিকেটর বা 'Mark as Read' পাঠানো
     */
    public function sendSenderAction($recipientId, $token, $action) 
    {
        try {
            Http::timeout(5)->post("https://graph.facebook.com/v19.0/me/messages?access_token={$token}", [
                'recipient' => ['id' => $recipientId],
                'sender_action' => $action
            ]);
        } catch (\Exception $e) {
            Log::warning("⚠️ Sender Action Failed: " . $e->getMessage());
        }
    }

    /**
     * মেসেঞ্জারে টেক্সট, ছবি এবং কুইক রিপ্লাই পাঠানো
     */
    public function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null, $quickReplies = []) 
    {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
        
        // ১. ছবি পাঠানো (যদি থাকে)
        if (!empty($imageUrl)) {
            try {
                $response = Http::timeout(10)->post($url, [
                    'messaging_type' => 'RESPONSE', 
                    'recipient' => ['id' => $recipientId],
                    'message' => [
                        'attachment' => [
                            'type' => 'image', 
                            'payload' => ['url' => $imageUrl, 'is_reusable' => true]
                        ]
                    ]
                ]);

                if ($response->failed()) Log::error("❌ Failed to send image: " . $response->body());
            } catch (\Exception $e) {
                Log::error("❌ Image Send Error: " . $e->getMessage());
            }
        }

        // ২. টেক্সট এবং কুইক রিপ্লাই পাঠানো
        $messageText = trim($message);
        
        if (!empty($messageText)) {
            $payload = [
                'messaging_type' => 'RESPONSE',
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $messageText]
            ];

            if (!empty($quickReplies) && is_array($quickReplies)) {
                $payload['message']['quick_replies'] = $quickReplies;
            }

            try {
                $response = Http::timeout(10)->post($url, $payload);
                if ($response->failed()) Log::error("❌ Message Send Error: " . $response->body());
                else Log::info("✅ Message sent successfully to {$recipientId}.");
            } catch (\Exception $e) {
                Log::error("❌ Message Exception: " . $e->getMessage());
            }
        }
    }

    /**
     * মেসেঞ্জারে প্রোডাক্ট ক্যারোসেল পাঠানো
     */
    public function sendMessengerCarousel($recipientId, $productIds, $token) 
    {
        $products = Product::whereIn('id', $productIds)->get();
        if ($products->isEmpty()) {
            Log::warning("Carousel: No products found for IDs " . implode(',', $productIds));
            return;
        }

        $elements = [];
        foreach ($products as $product) {
            $elements[] = [
                'title' => $product->name,
                'image_url' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
                'subtitle' => "Price: ৳" . number_format($product->sale_price ?? $product->regular_price),
                'buttons' => [
                    [
                        'type' => 'postback',
                        'title' => 'অর্ডার করুন',
                        'payload' => "ORDER_PRODUCT_" . $product->id
                    ],
                    [
                        'type' => 'web_url',
                        'url' => url('/shop/' . $product->client->slug),
                        'title' => 'ওয়েবসাইটে দেখুন'
                    ]
                ]
            ];
        }

        $elements = array_slice($elements, 0, 10);
        Log::info("Sending Carousel with " . count($elements) . " elements.");

        try {
            $response = Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$token}", [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'generic',
                            'elements' => $elements
                        ]
                    ]
                ]
            ]);
            if ($response->failed()) Log::error("❌ Failed to send carousel: " . $response->body());
        } catch (\Exception $e) {
            Log::error("❌ Carousel Error: " . $e->getMessage());
        }
    }

    /**
     * AI এর কথোপকথন ডাটাবেসে সেভ করা
     */
    public function logConversation($clientId, $senderId, $userMsg, $botMsg, $imgUrl) 
    {
        try {
            Conversation::create([
                'client_id' => $clientId, 
                'sender_id' => $senderId, 
                'platform' => 'messenger', 
                'user_message' => $userMsg, 
                'bot_response' => $botMsg, 
                'attachment_url' => $imgUrl, 
                'status' => 'success'
            ]); 
            Log::info("✅ Conversation Logged.");
        } catch (\Exception $e) {
            Log::error("❌ Conversation Log Error: " . $e->getMessage());
        }
    }
}