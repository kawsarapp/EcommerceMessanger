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
     * মেসেঞ্জারে টেক্সট, ছবি এবং કুইক রিপ্লাই/বাটন পাঠানো
     */
    public function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null, $quickReplies = [], $btnText = null, $btnUrl = null) 
    {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
        
        // ১. ছবি পাঠানো (যদি থাকে)
        if (!empty($imageUrl)) {
            try {
                $response = Http::withOptions(['timeout' => 10])->post($url, [
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

        // ২. টেক্সট এবং কুইক রিপ্লাই / বাটন পাঠানো
        $messageText = trim($message);
        
        if (!empty($messageText)) {
            $payload = [
                'messaging_type' => 'MESSAGE_TAG', // Using message_tag to improve broadcast deliverability
                'tag' => 'POST_PURCHASE_UPDATE', // Default broadcast tag allowed
                'recipient' => ['id' => $recipientId],
            ];

            // If a button is requested, send as button template
            if (!empty($btnText) && !empty($btnUrl)) {
                $payload['message'] = [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'button',
                            'text' => mb_substr($messageText, 0, 640),
                            'buttons' => [
                                [
                                    'type' => 'web_url',
                                    'url' => $btnUrl,
                                    'title' => mb_substr($btnText, 0, 20)
                                ]
                            ]
                        ]
                    ]
                ];
            } else {
                $payload['message'] = ['text' => $messageText];
                // Add Quick Replies if present
                if (!empty($quickReplies) && is_array($quickReplies)) {
                    $payload['message']['quick_replies'] = $quickReplies;
                }
            }

            try {
                $response = Http::withOptions(['timeout' => 10])->post($url, $payload);
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
            $buttons = [
                [
                    'type' => 'postback',
                    'title' => 'অর্ডার করুন',
                    'payload' => "ORDER_PRODUCT_" . $product->id
                ]
            ];

            if ($product->client && !empty($product->client->slug)) {
                $buttons[] = [
                    'type' => 'web_url',
                    'url' => url('/shop/' . $product->client->slug . '/product/' . $product->slug),
                    'title' => 'ওয়েবসাইটে দেখুন'
                ];
            }

            $elements[] = [
                'title' => mb_substr($product->name, 0, 80),
                'image_url' => $product->thumbnail ? asset('storage/' . ltrim($product->thumbnail, '/')) : null,
                'subtitle' => mb_substr("Price: ৳" . number_format($product->sale_price ?? $product->regular_price), 0, 80),
                'buttons' => $buttons
            ];
        }

        $elements = array_slice($elements, 0, 10);
        Log::info("Sending Carousel with " . count($elements) . " elements.");

        try {
            $response = Http::withOptions(['timeout' => 10])->post("https://graph.facebook.com/v19.0/me/messages?access_token={$token}", [
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