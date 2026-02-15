<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\OrderSession;
use App\Models\Conversation;
use App\Models\Product; // Carousel এর জন্য
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * 1. Facebook Webhook Verification
     * (ফেসবুক যখন প্রথমবার আপনার ইউআরএল ভেরিফাই করবে)
     */
    public function verify(Request $request)
    {
        Log::info("--- Webhook Verification Hit ---", $request->all());

        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token) {
            $client = Client::where('fb_verify_token', $token)->first();
            if ($client) {
                $client->update(['webhook_verified_at' => now()]);
                Log::info("✅ Webhook Verified for Client ID: " . $client->id);
                return response($challenge, 200);
            } else {
                Log::error("❌ Verification Failed: Token mismatch.");
            }
        }
        return response('Forbidden', 403);
    }

    /**
     * 2. Handle Incoming Messages (All Types)
     * (মেসেজ প্রসেসিং এর মেইন ফাংশন)
     */
    public function handle(Request $request, ChatbotService $chatbot)
    {
        // Log::info("-------------- WEBHOOK MESSAGE RECEIVED --------------"); 
        
        $data = $request->all();
        $content = $request->getContent(); // Raw content for signature check

        // =====================================
        // 🔒 WEBHOOK SIGNATURE SECURITY CHECK
        // =====================================
        $pageId = $data['entry'][0]['id'] ?? null;
        
        if ($pageId) {
            $clientForVerification = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
            
            // যদি ক্লায়েন্ট অ্যাপ সিক্রেট সেট করে রাখে তবেই চেক হবে (Security Upgrade)
            if ($clientForVerification && !empty($clientForVerification->fb_app_secret)) {
                $signature = $request->header('X-Hub-Signature');
                $appSecret = $clientForVerification->fb_app_secret;
                
                // SHA1 হ্যাশ তৈরি করে চেক করা হচ্ছে
                $expected = 'sha1=' . hash_hmac('sha1', $content, $appSecret);
                
                if (!hash_equals($expected, $signature ?? '')) {
                    Log::warning("⚠️ Security Warning: Invalid Signature for Page ID: $pageId");
                    return response('Forbidden', 403);
                }
            }
        }

        // =====================================
        // 📩 MESSAGE PROCESSING LOOP
        // =====================================
        if (isset($data['entry'][0]['messaging'])) {
            
            foreach ($data['entry'][0]['messaging'] as $messaging) {
                
                $senderId = $messaging['sender']['id'] ?? null;
                $recipientId = $messaging['recipient']['id'] ?? null; // Page ID (From FB)
                
                // 🛑 1. SELF-REPLY & SYSTEM MESSAGE CHECK (Loop Prevention)
                // ডেলিভারি রিপোর্ট, রিড রিসিপ্ট বা পেজের নিজের মেসেজ (is_echo) ইগনোর করা
                if (isset($messaging['delivery']) || isset($messaging['read']) || ($messaging['message']['is_echo'] ?? false)) {
                    continue;
                }

                // ক্লায়েন্ট ভেরিফিকেশন (Double Check)
                $client = Client::where('fb_page_id', $recipientId)->where('status', 'active')->first();
                if (!$client) {
                    Log::error("❌ Client not found or inactive for Page ID: $recipientId");
                    continue;
                }

                // 🔄 2. DEDUPLICATION (Cache Check)
                // একই মেসেজ দুইবার আসলে আটকানো হবে
                $mid = $messaging['message']['mid'] ?? $messaging['postback']['mid'] ?? null;
                if ($mid) {
                    if (Cache::has("fb_mid_{$mid}")) {
                        Log::info("⏭️ Skipped Duplicate Message ID: $mid");
                        continue;
                    }
                    Cache::put("fb_mid_{$mid}", true, 300); // 5 minutes cache
                }

                // 👁️ 3. MARK SEEN & TYPING ON (User Experience Upgrade)
                // মেসেজ পাওয়ার সাথে সাথে 'Seen' এবং 'Typing...' দেখাবে
                $this->sendSenderAction($senderId, $client->fb_page_token, 'mark_seen');
                $this->sendSenderAction($senderId, $client->fb_page_token, 'typing_on');

                // 📦 4. PAYLOAD EXTRACTION
                $messageText = null;
                $incomingImageUrl = null;
                
                // A. Postback Buttons (Get Started / Menu)
                if (isset($messaging['postback'])) {
                    $messageText = $messaging['postback']['payload'];
                    $title = $messaging['postback']['title'] ?? 'Menu Click';
                    Log::info("🔙 Postback: $title ($messageText)");
                    
                    // 🔥 Referral Handling (Ads click -> Get Started) - [NEW FEATURE]
                    if (isset($messaging['postback']['referral'])) {
                        $ref = $messaging['postback']['referral']['ref'] ?? '';
                        $source = $messaging['postback']['referral']['source'] ?? 'ad';
                        $messageText .= " [Referral: $ref, Source: $source]";
                        Log::info("📢 User came from AD/Referral: $ref");
                    }
                }
                // B. Quick Replies
                elseif (isset($messaging['message']['quick_reply'])) {
                    $messageText = $messaging['message']['quick_reply']['payload'];
                    Log::info("🔘 Quick Reply: $messageText");
                }
                // C. Normal Text
                elseif (isset($messaging['message']['text'])) {
                    $messageText = $messaging['message']['text'];
                    Log::info("📝 Text Message: " . Str::limit($messageText, 50));
                }
                // D. Attachments (Image/Audio/File)
                elseif (isset($messaging['message']['attachments'])) {
                    foreach ($messaging['message']['attachments'] as $attachment) {
                        $type = $attachment['type'];
                        $url = $attachment['payload']['url'] ?? null;

                        if ($type === 'image') {
                            $incomingImageUrl = $url;
                            // টেক্সট না থাকলে [Image] স্ট্রিং যোগ করা, যাতে AI বুঝতে পারে
                            $messageText = $messageText ? $messageText . " [Image]" : "[Image]"; 
                            Log::info("📷 Image Received: $url");
                        } elseif ($type === 'audio') {
                            Log::info("🎤 Audio Received: Converting...");
                            // Voice to Text Conversion Call
                            $convertedText = $chatbot->convertVoiceToText($url);
                            
                            if ($convertedText) {
                                $messageText = $convertedText;
                                Log::info("🗣️ Audio Converted: $messageText");
                            } else {
                                $this->sendMessengerMessage($senderId, "দুঃখিত, ভয়েসটি বুঝতে পারিনি। দয়া করে টাইপ করুন।", $client->fb_page_token);
                                return response('OK', 200);
                            }
                        } elseif ($type === 'fallback' || $type === 'file' || $type === 'video') {
                            $messageText = "[Sent an Attachment/Sticker]";
                            Log::info("📂 Other Attachment Received: $type");
                        }
                    }
                }

                // E. Carousel Button Click (Custom Payload Logic)
                if (Str::startsWith($messageText, 'ORDER_PRODUCT_')) {
                    $productId = str_replace('ORDER_PRODUCT_', '', $messageText);
                    $product = Product::find($productId);
                    $productName = $product ? $product->name : 'এই পণ্যটি';
                    $messageText = "আমি {$productName} অর্ডার করতে চাই।";
                    Log::info("🛒 Product Selection Intent: $messageText");
                }

                // =====================================
                // 🤖 AI PROCESSING & RESPONSE
                // =====================================
                
                // NULL SAFETY: Ensure we have something to process
                if ($messageText || $incomingImageUrl) {
                    
                    // Call AI Service
                    $reply = $chatbot->getAiResponse($messageText, $client->id, $senderId, $incomingImageUrl);

                    // Stop Typing Indicator
                    $this->sendSenderAction($senderId, $client->fb_page_token, 'typing_off');

                    if ($reply) {
                        $outgoingImage = null;
                        $quickReplies = [];
                        $carouselIds = null;

                        // ১. টেক্সট থেকে ইমেজ লিংক আলাদা করা (Regex Upgrade)
                        if (preg_match('/(https?:\/\/[^\s]+?\.(?:jpg|jpeg|png|gif|webp))/i', $reply, $matches)) {
                            $outgoingImage = $matches[1];
                            $reply = str_replace($outgoingImage, '', $reply);
                            Log::info("🖼️ Image Response Detected: $outgoingImage");
                        }

                        // ২. ক্যারোসেল ডিটেকশন [CAROUSEL: 1, 2, 3]
                        if (preg_match('/\[CAROUSEL:\s*([\d,\s]+)\]/', $reply, $matches)) {
                            $carouselIds = explode(',', $matches[1]);
                            $reply = str_replace($matches[0], "", $reply);
                            Log::info("🖼️ Carousel Triggered: " . $matches[1]);
                        }

                        // ৩. কুইক রিপ্লাই ডিটেকশন [QUICK_REPLIES: Yes, No]
                        if (preg_match('/\[QUICK_REPLIES:\s*([^\]]+)\]/', $reply, $matches)) {
                            $reply = str_replace($matches[0], "", $reply);
                            $options = explode(',', $matches[1]);
                            foreach ($options as $opt) {
                                $cleanOpt = trim(str_replace(['"', "'"], '', $opt));
                                $quickReplies[] = [
                                    'content_type' => 'text',
                                    'title' => Str::limit($cleanOpt, 20),
                                    'payload' => $cleanOpt
                                ];
                            }
                            Log::info("🔘 Quick Replies Triggered.");
                        }

                        // ৪. মেসেজ পাঠানো (Priority: Carousel > Text+Image)
                        if ($carouselIds) {
                            // যদি ক্যারোসেলের আগে কোনো টেক্সট থাকে, সেটা আগে পাঠানো
                            if (!empty(trim($reply))) {
                                $this->sendMessengerMessage($senderId, $reply, $client->fb_page_token);
                            }
                            $this->sendMessengerCarousel($senderId, $carouselIds, $client->fb_page_token);
                        } else {
                            // সাধারণ মেসেজ (ইমেজ সহ বা ছাড়া)
                            $this->sendMessengerMessage($senderId, $reply, $client->fb_page_token, $outgoingImage, $quickReplies);
                        }

                        // ৫. লগ সংরক্ষণ
                        $this->logConversation($client->id, $senderId, $messageText, $reply, $incomingImageUrl);
                    } else {
                        Log::info("⚠️ No reply from AI (Human agent active or empty response).");
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    // ==========================================
    // 🛠️ HELPER METHODS (Optimized & Robust)
    // ==========================================

    private function sendSenderAction($recipientId, $token, $action) {
        try {
            Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$token}", [
                'recipient' => ['id' => $recipientId],
                'sender_action' => $action
            ]);
        } catch (\Exception $e) {
            // অ্যাকশন ফেইল করলে লগ করার দরকার নেই, ইউজার এক্সপেরিয়েন্স নষ্ট হবে না
        }
    }

    private function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null, $quickReplies = []) {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
        
        // আগে ছবি পাঠাই (যদি থাকে)
        if ($imageUrl) {
            try {
                $response = Http::post($url, [
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

        // এরপর টেক্সট পাঠাই
        if (!empty(trim($message))) {
            $payload = [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => trim($message)]
            ];

            if (!empty($quickReplies)) {
                $payload['message']['quick_replies'] = $quickReplies;
            }

            try {
                $response = Http::post($url, $payload);
                if ($response->failed()) {
                    Log::error("❌ Message Send Error: " . $response->body());
                } else {
                    Log::info("✅ Message sent successfully.");
                }
            } catch (\Exception $e) {
                Log::error("❌ Message Exception: " . $e->getMessage());
            }
        }
    }

    private function sendMessengerCarousel($recipientId, $productIds, $token) {
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

        // Facebook Carousel Limit is 10 elements
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
            if ($response->failed()) {
                Log::error("❌ Failed to send carousel: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("❌ Carousel Error: " . $e->getMessage());
        }
    }

    private function logConversation($clientId, $senderId, $userMsg, $botMsg, $imgUrl) {
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