<?php

namespace App\Services\Messenger;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Product;
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MessengerWebhookService
{
    protected $responseService;
    protected $chatbot;

    public function __construct(MessengerResponseService $responseService, ChatbotService $chatbot)
    {
        $this->responseService = $responseService;
        $this->chatbot = $chatbot;
    }

    public function processPayload(Request $request)
    {
        $data = $request->all();
        $content = $request->getContent(); 

        // 🔒 WEBHOOK SIGNATURE SECURITY CHECK
        $firstPageId = $data['entry'][0]['id'] ?? null;
        if ($firstPageId) {
            $clientForVerification = Client::where('fb_page_id', $firstPageId)->where('status', 'active')->first();
            
            if ($clientForVerification && !empty($clientForVerification->fb_app_secret)) {
                $signature = $request->header('X-Hub-Signature');
                $expected = 'sha1=' . hash_hmac('sha1', $content, $clientForVerification->fb_app_secret);
                
                if (!hash_equals($expected, $signature ?? '')) {
                    Log::warning("⚠️ Security Warning: Invalid Signature for Page ID: $firstPageId");
                    return response('Forbidden', 403);
                }
            }
        }

        // 📩 MESSAGE PROCESSING LOOP
        foreach ($data['entry'] as $entry) {
            
            $pageId = $entry['id'] ?? null;
            $client = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
            
            if (!$client) {
                Log::error("❌ Client not found or inactive for Page ID: $pageId");
                continue;
            }

            if (!isset($entry['messaging'])) continue;

            foreach ($entry['messaging'] as $messaging) {
                $senderId = $messaging['sender']['id'] ?? null;
                
                // 🛑 1. EVENT TYPE FILTERS
                if (isset($messaging['delivery']) || isset($messaging['read']) || ($messaging['message']['is_echo'] ?? false)) continue;

                // 🛑 2. REACTION HANDLING
                if (isset($messaging['reaction'])) {
                    Log::info("👍 User reacted to a message. Ignoring.");
                    continue;
                }

                // 🔄 3. DEDUPLICATION (Cache Check)
                $mid = $messaging['message']['mid'] ?? $messaging['postback']['mid'] ?? null;
                if ($mid) {
                    if (Cache::has("fb_mid_{$mid}")) {
                        Log::info("⏭️ Skipped Duplicate Message ID: $mid");
                        continue;
                    }
                    Cache::put("fb_mid_{$mid}", true, 300);
                }

                // 👁️ 4. MARK SEEN & TYPING ON
                $this->responseService->sendSenderAction($senderId, $client->fb_page_token, 'mark_seen');
                $this->responseService->sendSenderAction($senderId, $client->fb_page_token, 'typing_on');

                // 📦 5. PAYLOAD EXTRACTION & ANALYSIS
                $messageText = null;
                $incomingImageUrl = null;
                
                if (isset($messaging['postback'])) {
                    $messageText = $messaging['postback']['payload'];
                    $title = $messaging['postback']['title'] ?? 'Menu Click';
                    Log::info("🔙 Postback: $title ($messageText)");
                    
                    if (isset($messaging['postback']['referral'])) {
                        $ref = $messaging['postback']['referral']['ref'] ?? '';
                        $source = $messaging['postback']['referral']['source'] ?? 'ad';
                        $messageText .= " [System Note: User came from Referral/Ad: $ref, Source: $source]";
                        Log::info("📢 User came from AD/Referral: $ref");
                    }

                    if ($messageText === 'GET_STARTED') $messageText = "Hi, I want to start shopping.";
                } 
                elseif (isset($messaging['message']['quick_reply'])) {
                    $messageText = $messaging['message']['quick_reply']['payload'];
                    Log::info("🔘 Quick Reply: $messageText");
                } 
                elseif (isset($messaging['message']['text'])) {
                    $messageText = $messaging['message']['text'];
                    Log::info("📝 Text Message: " . Str::limit($messageText, 50));
                } 
                elseif (isset($messaging['message']['attachments'])) {
                    foreach ($messaging['message']['attachments'] as $attachment) {
                        $type = $attachment['type'];
                        $url = $attachment['payload']['url'] ?? null;

                        if ($type === 'image') {
                            $incomingImageUrl = $url;
                            $messageText = $messageText ? $messageText . " [Image Attached]" : "[User sent an Image]"; 
                            Log::info("📷 Image Received: $url");
                        } 
                        elseif ($type === 'audio') {
                            Log::info("🎤 Audio Received: Converting...");
                            $convertedText = $this->chatbot->convertVoiceToText($url);
                            
                            if ($convertedText) {
                                $messageText = $convertedText . " [Voice Message]";
                                Log::info("🗣️ Audio Converted: $messageText");
                            } else {
                                $this->responseService->sendMessengerMessage($senderId, "দুঃখিত, আপনার ভয়েস মেসেজটি পরিষ্কার বোঝা যাচ্ছে না। দয়া করে লিখে জানান।", $client->fb_page_token);
                                continue 2; 
                            }
                        } 
                        elseif ($type === 'video') $messageText = "[User sent a Video. URL: $url]";
                        elseif ($type === 'file') $messageText = "[User sent a File/Document]";
                        elseif ($type === 'location') {
                            $lat = $attachment['payload']['coordinates']['lat'] ?? 0;
                            $long = $attachment['payload']['coordinates']['long'] ?? 0;
                            $messageText = "My Location: Lat: $lat, Long: $long";
                        } 
                        else $messageText = "[User sent an unknown attachment]";
                    }
                }

                if (Str::startsWith($messageText, 'ORDER_PRODUCT_')) {
                    $productId = str_replace('ORDER_PRODUCT_', '', $messageText);
                    $product = Product::find($productId);
                    $productName = $product ? $product->name : 'এই পণ্যটি';
                    $messageText = "আমি {$productName} অর্ডার করতে চাই।";
                    Log::info("🛒 Product Selection Intent: $messageText");
                }

                // 🤖 6. AI PROCESSING & RESPONSE
                if ($messageText || $incomingImageUrl) {
                    
                    $reply = $this->chatbot->handleMessage($client, $senderId, $messageText, $incomingImageUrl);

                    $this->responseService->sendSenderAction($senderId, $client->fb_page_token, 'typing_off');

                    if ($reply) {
                        $outgoingImage = null;
                        $quickReplies = [];
                        $carouselIds = null;

                        if (preg_match('/(https?:\/\/[^\s]+?\.(?:jpg|jpeg|png|gif|webp))/i', $reply, $matches)) {
                            $outgoingImage = $matches[1];
                            $reply = str_replace($outgoingImage, '', $reply);
                        }

                        if (preg_match('/\[CAROUSEL:\s*([\d,\s]+)\]/', $reply, $matches)) {
                            $carouselIds = explode(',', $matches[1]);
                            $reply = str_replace($matches[0], "", $reply);
                        }

                        if (preg_match('/\[QUICK_REPLIES:\s*([^\]]+)\]/', $reply, $matches)) {
                            $reply = str_replace($matches[0], "", $reply);
                            $options = explode(',', $matches[1]);
                            foreach ($options as $opt) {
                                $cleanOpt = trim(str_replace(['"', "'"], '', $opt));
                                if (!empty($cleanOpt)) {
                                    $quickReplies[] = [
                                        'content_type' => 'text',
                                        'title' => Str::limit($cleanOpt, 20),
                                        'payload' => $cleanOpt
                                    ];
                                }
                            }
                        }

                        $reply = trim($reply);

                        if ($carouselIds) {
                            if (!empty($reply)) {
                                $this->responseService->sendMessengerMessage($senderId, $reply, $client->fb_page_token);
                            }
                            $this->responseService->sendMessengerCarousel($senderId, $carouselIds, $client->fb_page_token);
                        } else {
                            $this->responseService->sendMessengerMessage($senderId, $reply, $client->fb_page_token, $outgoingImage, $quickReplies);
                        }

                        $this->responseService->logConversation($client->id, $senderId, $messageText, $reply, $incomingImageUrl);
                    } else {
                        Log::info("⚠️ No reply from AI (Human agent active or empty response).");
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}