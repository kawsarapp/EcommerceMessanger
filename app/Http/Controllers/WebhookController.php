<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\OrderSession;
use App\Models\Conversation;
use App\Models\Product; // Carousel এর জন্য লাগতে পারে
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
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token) {
            $client = Client::where('fb_verify_token', $token)->first();
            if ($client) {
                $client->update(['webhook_verified_at' => now()]);
                return response($challenge, 200);
            }
        }
        return response('Forbidden', 403);
    }

    /**
     * 2. Handle Incoming Messages (All Types)
     */
    public function handle(Request $request, ChatbotService $chatbot)
    {
        // Log::info("-------------- WEBHOOK HIT --------------"); // লগ কমানোর জন্য কমেন্ট করতে পারো

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
                    Log::warning("Invalid webhook signature from Page ID: $pageId");
                    return response('Forbidden', 403);
                }
            }
        }

        if (isset($data['entry'][0]['messaging'][0])) {
            $messaging = $data['entry'][0]['messaging'][0];
            $senderId  = $messaging['sender']['id'] ?? null;
            $pageId    = $data['entry'][0]['id'] ?? null;
            $mid       = $messaging['message']['mid'] ?? null;

            // [Deduplication]
            if ($mid && Cache::has("fb_mid_{$mid}")) {
                return response('OK', 200);
            }
            if ($mid) Cache::put("fb_mid_{$mid}", true, 300);

            /* ================= MESSAGE PRE-PROCESSING ================= */

            $client = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
            if (!$client) return response('OK', 200);

            $messageText = null;
            $incomingImageUrl = null;

            // Text / Payload Extraction
            if (isset($messaging['message']['text'])) {
                $messageText = $messaging['message']['text'];
            } elseif (isset($messaging['message']['quick_reply']['payload'])) {
                $messageText = $messaging['message']['quick_reply']['payload'];
            } elseif (isset($messaging['postback']['payload'])) {
                $messageText = $messaging['postback']['payload'];
            }

            // Image / Audio Extraction
            if (isset($messaging['message']['attachments'][0])) {
                $attachment = $messaging['message']['attachments'][0];
                $type = $attachment['type'] ?? null;
                $url  = $attachment['payload']['url'] ?? null;

                if ($type === 'image') {
                    $incomingImageUrl = $url;
                } elseif ($type === 'audio') {
                    $messageText = $chatbot->convertVoiceToText($url); // ChatbotService-এ এই মেথড থাকতে হবে
                    if (!$messageText) {
                        $this->sendMessengerMessage($senderId, "দুঃখিত, ভয়েসটি বুঝতে পারিনি। টাইপ করুন।", $client->fb_page_token);
                        return response('OK', 200);
                    }
                }
            }

            // Carousel Click Handling (Simple logic keeps here is fine)
            if (Str::startsWith($messageText, 'ORDER_PRODUCT_')) {
                $productId = str_replace('ORDER_PRODUCT_', '', $messageText);
                $product = Product::find($productId);
                $messageText = "আমি " . ($product->name ?? 'এই প্রোডাক্টটি') . " অর্ডার করতে চাই।";
            }

            /* ================= MAIN PROCESS ================= */

            if ($senderId && ($messageText || $incomingImageUrl)) {
                
                // Typing Indicator ON
                try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_on'); } catch (\Exception $e) {}

                // 🔥 MAIN CHANGE: All logic is now inside ChatbotService
                $reply = $chatbot->getAiResponse($messageText, $client->id, $senderId, $incomingImageUrl);

                // Typing Indicator OFF
                try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_off'); } catch (\Exception $e) {}

                // Send Response
                if ($reply) {
                    // Check for internal tags strictly for UI elements (Carousel/Quick Replies only)
                    // Note: ORDER_DATA is NOT checked here anymore because Service handles it.
                    
                    $outgoingImage = null;
                    $quickReplies = [];

                    // Carousel
                    if (preg_match('/\[CAROUSEL:\s*([\d,\s]+)\]/', $reply, $matches)) {
                        $productIds = explode(',', $matches[1]);
                        $reply = str_replace($matches[0], "", $reply);
                        $this->sendMessengerCarousel($senderId, $productIds, $client->fb_page_token);
                    }

                    // Quick Replies
                    if (preg_match('/\[QUICK_REPLIES:\s*([^\]]+)\]/', $reply, $matches)) {
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

                    // Log & Send
                    $this->logConversation($client->id, $senderId, $messageText, $reply, $incomingImageUrl);
                    $this->sendMessengerMessage($senderId, $reply, $client->fb_page_token, $outgoingImage, $quickReplies);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    // ==========================================
    // HELPER METHODS (Keep strictly for API calls)
    // ==========================================

    private function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null, $quickReplies = []) {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
        
        // Image Send logic (if AI sends image link) - keeping simplified
        if ($imageUrl) {
             Http::post($url, [
                'recipient' => ['id' => $recipientId],
                'message' => ['attachment' => ['type' => 'image', 'payload' => ['url' => $imageUrl, 'is_reusable' => true]]]
            ]);
        }

        if (!empty(trim($message))) {
            $payload = ['recipient' => ['id' => $recipientId], 'message' => ['text' => trim($message)]];
            if (!empty($quickReplies)) $payload['message']['quick_replies'] = $quickReplies;
            Http::post($url, $payload);
        }
    }

    private function sendMessengerCarousel($recipientId, $productIds, $token) {
        $products = Product::whereIn('id', $productIds)->get();
        if ($products->isEmpty()) return;
        
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

        Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$token}", [
            'recipient' => ['id' => $recipientId],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => ['template_type' => 'generic', 'elements' => $elements]
                ]
            ]
        ]);
    }

    private function sendTypingAction($recipientId, $token, $action) {
        Http::post("https://graph.facebook.com/v19.0/me/messages?access_token={$token}", [
            'recipient' => ['id' => $recipientId], 'sender_action' => $action
        ]);
    }

    private function logConversation($clientId, $senderId, $userMsg, $botMsg, $imgUrl) {
        Conversation::create([
            'client_id' => $clientId, 'sender_id' => $senderId, 'platform' => 'messenger',
            'user_message' => $userMsg, 'bot_response' => $botMsg, 'attachment_url' => $imgUrl, 'status' => 'success'
        ]);
    }
}