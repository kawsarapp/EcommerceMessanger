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
     */
    public function handle(Request $request, ChatbotService $chatbot)
    {
        Log::info("-------------- WEBHOOK MESSAGE RECEIVED --------------");
        // Log::debug("Raw Payload:", $request->all()); // Uncomment for deep debug

        $data = $request->all();

        // =====================================
        // WEBHOOK SIGNATURE VERIFICATION
        // =====================================
        $pageId = $data['entry'][0]['id'] ?? null;
        
        if ($pageId) {
            $clientForVerification = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
            if ($clientForVerification && $clientForVerification->fb_app_secret) {
                $signature = $request->header('X-Hub-Signature');
                $body = $request->getContent();
                $appSecret = $clientForVerification->fb_app_secret;

                $expected = 'sha1=' . hash_hmac('sha1', $body, $appSecret);
                if (!hash_equals($expected, $signature ?? '')) {
                    Log::warning("⚠️ Invalid Signature for Page ID: $pageId");
                    return response('Forbidden', 403);
                }
            }
        } else {
            Log::warning("⚠️ Page ID missing in webhook data.");
        }

        if (isset($data['entry'][0]['messaging'][0])) {
            $messaging = $data['entry'][0]['messaging'][0];
            $senderId  = $messaging['sender']['id'] ?? null;
            $pageId    = $data['entry'][0]['id'] ?? null;
            $mid       = $messaging['message']['mid'] ?? null;

            Log::info("📩 Processing Message - Sender: $senderId | Page: $pageId");

            // [Deduplication]
            if ($mid && Cache::has("fb_mid_{$mid}")) {
                Log::info("⏭️ Duplicate Message Skipped: $mid");
                return response('OK', 200);
            }
            if ($mid) Cache::put("fb_mid_{$mid}", true, 300);

            /* ================= MESSAGE PRE-PROCESSING ================= */

            $client = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
            if (!$client) {
                Log::error("❌ Client not found or inactive for Page ID: $pageId");
                return response('OK', 200);
            }

            $messageText = null;
            $incomingImageUrl = null;

            // Text / Payload Extraction
            if (isset($messaging['message']['text'])) {
                $messageText = $messaging['message']['text'];
                Log::info("📝 Text Message: " . Str::limit($messageText, 50));
            } elseif (isset($messaging['message']['quick_reply']['payload'])) {
                $messageText = $messaging['message']['quick_reply']['payload'];
                Log::info("🔘 Quick Reply Payload: $messageText");
            } elseif (isset($messaging['postback']['payload'])) {
                $messageText = $messaging['postback']['payload'];
                Log::info("🔙 Postback Payload: $messageText");
            }

            // Image / Audio Extraction
            if (isset($messaging['message']['attachments'][0])) {
                $attachment = $messaging['message']['attachments'][0];
                $type = $attachment['type'] ?? null;
                $url  = $attachment['payload']['url'] ?? null;

                if ($type === 'image') {
                    $incomingImageUrl = $url;
                    Log::info("📷 Image Attachment Received: $url");
                } elseif ($type === 'audio') {
                    Log::info("🎤 Audio Attachment Received. Converting...");
                    $messageText = $chatbot->convertVoiceToText($url);
                    
                    if (!$messageText) {
                        Log::warning("⚠️ Audio conversion failed.");
                        $this->sendMessengerMessage($senderId, "দুঃখিত, ভয়েসটি বুঝতে পারিনি। দয়া করে টাইপ করুন।", $client->fb_page_token);
                        return response('OK', 200);
                    }
                    Log::info("🗣️ Audio Converted: $messageText");
                }
            }

            // Carousel Click Handling
            if (Str::startsWith($messageText, 'ORDER_PRODUCT_')) {
                $productId = str_replace('ORDER_PRODUCT_', '', $messageText);
                $product = Product::find($productId);
                $messageText = "আমি " . ($product->name ?? 'এই প্রোডাক্টটি') . " অর্ডার করতে চাই।";
                Log::info("🛒 Product Selection Intent: $messageText");
            }

            /* ================= MAIN PROCESS ================= */

            // NULL SAFETY: Ensure messageText is never null
            $finalMessage = $messageText ?? '';

            if ($senderId && ($finalMessage !== '' || $incomingImageUrl)) {
                
                // Typing Indicator ON
                try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_on'); } catch (\Exception $e) {}

                Log::info("🤖 Calling ChatbotService...");
                
                // 🔥 CALL AI SERVICE
                $reply = $chatbot->getAiResponse($finalMessage, $client->id, $senderId, $incomingImageUrl);
                
                Log::info("🤖 AI Reply Generated: " . Str::limit($reply, 100));

                // Typing Indicator OFF
                try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_off'); } catch (\Exception $e) {}

                // Send Response
                if ($reply) {
                    $outgoingImage = null;
                    $quickReplies = [];

                    // 🔥 FIX: IMAGE EXTRACTION (Link Detection)
                    // If AI replies with an image URL, extract it and send as attachment
                    if (preg_match('/(https?:\/\/[^\s]+?\.(?:jpg|jpeg|png|gif|webp))/i', $reply, $matches)) {
                        $outgoingImage = $matches[1];
                        $reply = str_replace($outgoingImage, '', $reply); // Remove URL from text
                        Log::info("🖼️ Image Response Detected: $outgoingImage");
                    }

                    // Carousel
                    if (preg_match('/\[CAROUSEL:\s*([\d,\s]+)\]/', $reply, $matches)) {
                        Log::info("🖼️ Carousel Triggered: " . $matches[1]);
                        $productIds = explode(',', $matches[1]);
                        $reply = str_replace($matches[0], "", $reply);
                        $this->sendMessengerCarousel($senderId, $productIds, $client->fb_page_token);
                    }

                    // Quick Replies
                    if (preg_match('/\[QUICK_REPLIES:\s*([^\]]+)\]/', $reply, $matches)) {
                        Log::info("🔘 Quick Replies Triggered.");
                        $reply = str_replace($matches[0], "", $reply);
                        $buttons = explode(',', $matches[1]);
                        foreach ($buttons as $btn) {
                            $cleanBtn = trim(str_replace(['"', "'"], '', $btn));
                            $quickReplies[] = [
                                'content_type' => 'text',
                                'title' => $cleanBtn,
                                'payload' => 'QR_' . strtoupper(Str::slug($cleanBtn, '_')),
                            ];
                        }
                    }

                    // Send Final Message
                    if (!empty(trim($reply)) || $outgoingImage) {
                        Log::info("📤 Sending Final Response.");
                        $this->sendMessengerMessage($senderId, $reply, $client->fb_page_token, $outgoingImage, $quickReplies);
                    }

                    // Log Conversation
                    $this->logConversation($client->id, $senderId, $finalMessage, $reply, $incomingImageUrl);
                } else {
                    Log::info("⚠️ No reply from AI (Human agent active or empty response).");
                }
            }
        }

        Log::info("-------------- WEBHOOK END --------------");
        return response('EVENT_RECEIVED', 200);
    }

    // ==========================================
    // HELPER METHODS (Keep strictly for API calls)
    // ==========================================

    private function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null, $quickReplies = []) {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
        
        // Image Send logic
        if ($imageUrl) {
            try { 
                $response = Http::post($url, [
                    'recipient' => ['id' => $recipientId],
                    'message' => ['attachment' => ['type' => 'image', 'payload' => ['url' => $imageUrl, 'is_reusable' => true]]]
                ]);
                if ($response->failed()) Log::error("❌ Failed to send image: " . $response->body());
            } catch (\Exception $e) {
                Log::error("❌ Exception sending image: " . $e->getMessage());
            }
        }

        if (!empty(trim($message))) {
            $payload = ['recipient' => ['id' => $recipientId], 'message' => ['text' => trim($message)]];
            if (!empty($quickReplies)) $payload['message']['quick_replies'] = $quickReplies;
            
            try {
                $response = Http::post($url, $payload);
                if ($response->failed()) {
                    Log::error("❌ Failed to send message: " . $response->body());
                } else {
                    Log::info("✅ Message sent successfully.");
                }
            } catch (\Exception $e) {
                Log::error("❌ Exception sending message: " . $e->getMessage());
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
                'image_url' => asset('storage/' . $product->thumbnail),
                'subtitle' => "Price: ৳" . number_format($product->sale_price),
                'buttons' => [
                    ['type' => 'postback', 'title' => 'অর্ডার করবো', 'payload' => "ORDER_PRODUCT_" . $product->id],
                    ['type' => 'web_url', 'url' => url('/shop/' . $product->client->slug), 'title' => 'বিস্তারিত দেখুন']
                ]
            ];
        }

        Log::info("Sending Carousel with " . count($elements) . " elements.");

        try {
            $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
            $response = Http::post($url, [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => ['template_type' => 'generic', 'elements' => $elements]
                    ]
                ]
            ]);
            if ($response->failed()) {
                Log::error("❌ Failed to send carousel: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("❌ Exception sending carousel: " . $e->getMessage());
        }
    }

    private function sendTypingAction($recipientId, $token, $action) {
        try {
            $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
            Http::post($url, [
                'recipient' => ['id' => $recipientId],
                'sender_action' => $action
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send typing action: " . $e->getMessage());
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