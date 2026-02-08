<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\OrderSession;
use App\Models\Conversation;
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
        Log::info("-------------- WEBHOOK HIT --------------");

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
                Log::info("Duplicate Message Skipped: $mid");
                return response('OK', 200);
            }
            if ($mid) Cache::put("fb_mid_{$mid}", true, 300); // 5 minutes

            /* ================= MESSAGE & ATTACHMENT DETECTION ================= */

            $messageText = null;
            $incomingImageUrl = null;

            // Text
            if (isset($messaging['message']['text'])) {
                $messageText = $messaging['message']['text'];
            }
            // Quick Reply
            elseif (isset($messaging['message']['quick_reply']['payload'])) {
                $messageText = $messaging['message']['quick_reply']['payload'];
            }
            // Postback
            elseif (isset($messaging['postback']['payload'])) {
                $messageText = $messaging['postback']['payload'];
            }

            // Attachment (Image / Audio)
            if (isset($messaging['message']['attachments'][0])) {
                $attachment = $messaging['message']['attachments'][0];
                $type = $attachment['type'] ?? null;
                $url  = $attachment['payload']['url'] ?? null;

                if ($type === 'image') {
                    $incomingImageUrl = $url;
                }
                elseif ($type === 'audio') {
                    // Voice → Text
                    $messageText = $chatbot->convertVoiceToText($url);

                    if (!$messageText) {
                        // Get client for this page to send error message
                        $clientForAudio = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
                        if ($clientForAudio) {
                            $this->sendMessengerMessage(
                                $senderId,
                                "দুঃখিত, আমি আপনার ভয়েসটি বুঝতে পারছি না। দয়া করে টাইপ করে বলুন বা আবার ভয়েস দিন।",
                                $clientForAudio->fb_page_token
                            );
                        }
                        return response('OK', 200);
                    }
                }
            }

            /* ================= PROCESS MESSAGE ================= */

            if ($senderId && $pageId && ($messageText || $incomingImageUrl)) {
                try {
                    $this->processIncomingMessage(
                        $senderId,
                        $pageId,
                        $messageText,
                        $chatbot,
                        $incomingImageUrl
                    );
                } catch (\Throwable $e) {
                    Log::error("CRITICAL ERROR in processIncomingMessage: " . $e->getMessage());
                    Log::error("Stack Trace: " . $e->getTraceAsString());
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }



    /**
     * 3. Process Message Logic & Tag Handling
     */
    private function processIncomingMessage($senderId, $pageId, $messageText, $chatbot, $incomingImageUrl)
    {
        $client = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
        if (!$client) {
            Log::warning("Client inactive or not found for Page ID: $pageId");
            return;
        }

        // Session Handling
        $session = OrderSession::firstOrCreate(['sender_id' => $senderId], ['client_id' => $client->id]);
        if ($session->is_human_agent_active) return;

        // Carousel Click Handling
        if (Str::startsWith($messageText, 'ORDER_PRODUCT_')) {
            $productId = str_replace('ORDER_PRODUCT_', '', $messageText);
            $product = Product::find($productId);
            $messageText = "আমি " . ($product->name ?? 'এই প্রোডাক্টটি') . " অর্ডার করতে চাই।";
        }

        try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_on'); } catch (\Exception $e) {}

        $finalText = $messageText ?? "Sent an image";
        Log::info("User ($senderId) Said: $finalText");

        // AI Response Call
        $reply = $chatbot->getAiResponse($finalText, $client->id, $senderId, $incomingImageUrl);
        Log::info("AI Full Response for $senderId: " . $reply);

        if ($reply === null) {
            try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_off'); } catch (\Exception $e) {}
            return; 
        }

        // =====================================================
        // TAG PROCESSING LOGIC
        // =====================================================

        // 1. Check for New Order Creation
        if (preg_match('/\[ORDER_DATA:\s*(\{.*?\})\]/s', $reply, $matches)) {
            Log::info("TAG DETECTED: [ORDER_DATA]");
            $reply = $this->finalizeOrder($reply, $matches, $client, $senderId, $chatbot);
        }
        // 2. Check for Note Addition
        elseif (preg_match('/\[ADD_NOTE:\s*(\{.*?\})\]/s', $reply, $matches)) {
            Log::info("TAG DETECTED: [ADD_NOTE]");
            $reply = $this->handleOrderNote($reply, $matches, $client, $senderId);
        }
        // 3. Check for Order Cancellation
        elseif (preg_match('/\[CANCEL_ORDER:\s*(\{.*?\})\]/s', $reply, $matches)) {
            Log::info("TAG DETECTED: [CANCEL_ORDER]");
            $reply = $this->handleOrderCancellation($reply, $matches, $client, $senderId, $chatbot);
        }
        // 4. Check for Order Tracking
        elseif (preg_match('/\[TRACK_ORDER:\s*\"?(\d+)\"?\]/', $reply, $matches)) {
            Log::info("TAG DETECTED: [TRACK_ORDER]");
            $phoneNumber = $this->validateAndCleanPhone($matches[1]);
            if ($phoneNumber) {
                $trackingResult = $this->trackOrderDetails($phoneNumber, $client->id);
                $reply = str_replace($matches[0], $trackingResult, $reply);
            } else {
                $reply = str_replace($matches[0], "\n⚠️ নম্বরটি সঠিক নয়।", $reply);
            }
        }
        // 5. Clean Admin Tags
        elseif (str_contains($reply, '[NOTIFY_ADMIN:')) {
            $reply = str_replace(['[NOTIFY_ADMIN]', '{', '}', '"message":'], '', $reply);
        }

        // Carousel Generation
        if (preg_match('/\[CAROUSEL:\s*([\d,\s]+)\]/', $reply, $matches)) {
            $productIds = explode(',', $matches[1]);
            $productIds = array_map('trim', $productIds);
            $reply = str_replace($matches[0], "", $reply);
            $this->sendMessengerCarousel($senderId, $productIds, $client->fb_page_token);
        }

        // Quick Replies Handling
        $quickReplies = [];
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

        // Image Cleanup
        $outgoingImage = null;
        if (preg_match('/(https?:\/\/[^\s]+?\.(?:jpg|jpeg|png|gif|webp))/i', $reply, $matches)) {
            $outgoingImage = $matches[1];
            $reply = str_replace($outgoingImage, '', $reply);
            $reply = str_replace(['(ছবি:', '[ছবি]', 'Image Link:', 'Link:', '()'], '', $reply);
            $reply = trim($reply);
        }

        // Send Final Response
        $this->logConversation($client->id, $senderId, $finalText, $reply, $incomingImageUrl);
        $this->sendMessengerMessage($senderId, $reply, $client->fb_page_token, $outgoingImage, $quickReplies);
        
        try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_off'); } catch (\Exception $e) {}
    }

    /**
     * 4. Finalize Order Logic (DB Error Fix Added)
     */
    private function finalizeOrder($reply, $matches, $client, $senderId, $chatbot)
    {
        $jsonStr = $matches[1];
        Log::info("AI JSON received: " . $jsonStr);

        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON Parsing Failed: " . json_last_error_msg());
            return str_replace($matches[0], "", $reply) . "\n(সিস্টেম এরর: অর্ডার ডাটা রিড করা সম্ভব হয়নি।)";
        }

        $productId = $data['product_id'] ?? null;
        
        // Fix race condition: Lock product for update
        $product = Product::where('id', $productId)->lockForUpdate()->first();

        if (!$product) {
            Log::error("Order Failed: Product ID {$productId} not found.");
            return str_replace($matches[0], "", $reply) . "\n⚠️ দুঃখিত, টেকনিক্যাল সমস্যার কারণে পণ্যটি শনাক্ত করা যায়নি।";
        }

        $validPhone = $this->validateAndCleanPhone($data['phone'] ?? null);
        if (!$validPhone) {
            return str_replace($matches[0], "", $reply) . "\n⚠️ দুঃখিত, মোবাইল নম্বরটি সঠিক নয়। ১১ ডিজিট হতে হবে।";
        }

        try {
            return DB::transaction(function () use ($data, $client, $senderId, $validPhone, $reply, $matches, $product, $chatbot) {

                $qty = isset($data['quantity']) && is_numeric($data['quantity']) ? (int) $data['quantity'] : 1;
                
                // Check stock before proceeding
                if ($product->stock_quantity < $qty) {
                    return str_replace($matches[0], "", $reply) . "\n⚠️ দুঃখিত, এই পণ্যটি বর্তমানে স্টক আউট।";
                }

                $price = $product->sale_price ?? $product->regular_price ?? 0;
                $isDhaka = ($data['is_dhaka'] ?? false) === true;
                $delivery = $isDhaka ? ($client->delivery_charge_inside ?? 80) : ($client->delivery_charge_outside ?? 150);
                $totalAmount = ($price * $qty) + $delivery;

                // অর্ডার ডাটা তৈরি
                $orderData = [
                    'client_id'       => $client->id,
                    'sender_id'       => $senderId,
                    'customer_name'   => !empty($data['name']) && $data['name'] !== $product->name ? $data['name'] : 'Valued Customer',
                    'customer_phone'  => $validPhone,
                    'shipping_address'=> $data['address'] ?? 'N/A',
                    'total_amount'    => $totalAmount,
                    'order_status'    => 'processing',
                    'payment_status'  => 'pending',
                ];

                // ডাইনামিক কলাম চেক
                if (Schema::hasColumn('orders', 'payment_method')) $orderData['payment_method'] = 'cod';
                if (Schema::hasColumn('orders', 'customer_email')) $orderData['customer_email'] = $data['email'] ?? null;
                if (Schema::hasColumn('orders', 'division')) $orderData['division'] = $isDhaka ? 'Dhaka' : 'Outside Dhaka';
                if (Schema::hasColumn('orders', 'district')) $orderData['district'] = $data['district'] ?? null;
                if (Schema::hasColumn('orders', 'admin_note')) $orderData['admin_note'] = $data['note'] ?? null;
                elseif (Schema::hasColumn('orders', 'notes')) $orderData['notes'] = $data['note'] ?? null;

                // অর্ডার তৈরি
                $order = Order::create($orderData);
                Log::info("Order Created Successfully. ID: {$order->id}");

                // অর্ডার আইটেম তৈরি
                if (Schema::hasTable('order_items')) {
                    $itemData = [
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'quantity'   => $qty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('order_items', 'unit_price')) $itemData['unit_price'] = $price;
                    if (Schema::hasColumn('order_items', 'price')) $itemData['price'] = $price;

                    DB::table('order_items')->insert($itemData);
                }

                $product->decrement('stock_quantity', $qty);

                // সেশন আপডেট
                // অর্ডার সেভ হওয়ার পর সেশন ক্লিয়ার বা আপডেট করুন
                $session = OrderSession::where('sender_id', $senderId)->first();
                if ($session) {
                    $session->update([
                        'customer_info' => [
                            'step' => 'start', // আবার শুরুতে পাঠিয়ে দিন
                            'product_id' => null,
                            'history' => []
                        ]
                    ]);
                }

                // টেলিগ্রাম অ্যালার্ট
                try {
                    $telegramMsg = "🛍️ **নতুন অর্ডার কনফার্ম হয়েছে!**\n\n" .
                                   "আইডি: #{$order->id}\n" .
                                   "পণ্য: {$product->name}\n" .
                                   "কাস্টমার: {$order->customer_name}\n" .
                                   "ফোন: {$order->customer_phone}\n" .
                                   "ঠিকানা: {$order->shipping_address}\n" .
                                   "মোট: {$totalAmount} Tk";
                    $chatbot->sendTelegramAlert($client->id, $senderId, $telegramMsg);
                } catch (\Exception $e) {
                    Log::error("Telegram Alert Failed: " . $e->getMessage());
                }

                $cleanReply = str_replace($matches[0], "", $reply);
                $locText = $isDhaka ? "ঢাকার ভেতরে" : "ঢাকার বাইরে";

                return trim($cleanReply)
                    . "\n\n✅ অর্ডার কনফার্ম!"
                    . "\nআইডি: #{$order->id}"
                    . "\nমোট: {$totalAmount} Tk ({$locText})";
            });
        } catch (\Throwable $e) {
            Log::error("DB Transaction Failed: " . $e->getMessage());
            Log::error("Stack Trace: " . $e->getTraceAsString());
            return "দুঃখিত, অর্ডার প্রসেসিং এ একটি কারিগরি সমস্যা হয়েছে।";
        }
    }

    /**
     * 5. Handle ADD NOTE Logic
     */
    private function handleOrderNote($reply, $matches, $client, $senderId)
    {
        $jsonStr = $matches[1];
        $data = json_decode($jsonStr, true);
        
        // JSON validation
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::error("Invalid JSON in [ADD_NOTE] tag: " . $jsonStr);
            return str_replace($matches[0], "", $reply);
        }

        $lastOrder = Order::where('sender_id', $senderId)->latest()->first();

        if ($lastOrder && !empty($data['note'])) {
            // ১. কোন কলামে নোট সেভ হবে তা নিশ্চিত করা
            $updateField = null;
            if (Schema::hasColumn('orders', 'admin_note')) $updateField = 'admin_note';
            elseif (Schema::hasColumn('orders', 'notes')) $updateField = 'notes';
            elseif (Schema::hasColumn('orders', 'customer_note')) $updateField = 'customer_note';

            if ($updateField) {
                $existingNote = $lastOrder->$updateField;
                $newNote = $data['note'];
                
                // আগের নোটের সাথে নতুন নোট যোগ করা
                $finalNote = $existingNote ? ($existingNote . " | " . $newNote) : $newNote;

                $lastOrder->update([$updateField => $finalNote]);
                Log::info("Order #{$lastOrder->id} note updated in column '$updateField'");
                
                return "ধন্যবাদ! আপনার অনুরোধটি (Friday Delivery) নোট করা হয়েছে।";
            } else {
                Log::error("No note column found in orders table!");
            }
        }
        return str_replace($matches[0], "", $reply);
    }

    /**
     * 6. Handle CANCEL ORDER Logic
     */
    private function handleOrderCancellation($reply, $matches, $client, $senderId, $chatbot)
    {
        $jsonStr = $matches[1];
        $data = json_decode($jsonStr, true);
        
        // JSON validation
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::error("Invalid JSON in [CANCEL_ORDER] tag: " . $jsonStr);
            return str_replace($matches[0], "", $reply) . "\n⚠️ অর্ডার বাতিল করতে সমস্যা হয়েছে।";
        }
        
        $order = Order::where('client_id', $client->id)
                      ->where('sender_id', $senderId)
                      ->whereIn('order_status', ['processing', 'pending'])
                      ->latest()
                      ->first();

        if ($order) {
            $reason = $data['reason'] ?? 'Customer requested cancellation';
            $updateData = ['order_status' => 'cancelled'];
            
            if (Schema::hasColumn('orders', 'admin_note')) {
                $updateData['admin_note'] = "Cancelled by AI. Reason: " . $reason;
            } elseif (Schema::hasColumn('orders', 'notes')) {
                $updateData['notes'] = "Cancelled by AI. Reason: " . $reason;
            }

            $order->update($updateData);

            // স্টক ফেরত দেওয়া
            if (Schema::hasTable('order_items')) {
                $items = DB::table('order_items')->where('order_id', $order->id)->get();
                foreach ($items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            // সেশন রিসেট
            OrderSession::where('sender_id', $senderId)->update(['customer_info' => ['step' => 'cancelled']]);

            // Telegram Alert
            try {
                $msg = "❌ **অর্ডার বাতিল করা হয়েছে!**\nআইডি: #{$order->id}\nকারণ: {$reason}";
                $chatbot->sendTelegramAlert($client->id, $senderId, $msg);
            } catch (\Exception $e) {
                Log::error("Telegram Notification Failed: " . $e->getMessage());
            }

            return "আপনার অর্ডার #{$order->id} সফলভাবে বাতিল করা হয়েছে। আমাদের সাথে থাকার জন্য ধন্যবাদ।";
        }

        return "দুঃখিত, বাতিল করার মতো কোনো প্রক্রিয়াধীন অর্ডার পাওয়া যায়নি।";
    }

    /**
     * 7. Track Order Logic
     */
    private function trackOrderDetails($phone, $clientId)
    {
        $order = Order::where('client_id', $clientId)
                      ->where('customer_phone', $phone)
                      ->latest()
                      ->first();

        if ($order) {
            $status = strtoupper($order->order_status);
            return "\n📦 অর্ডার স্ট্যাটাস: {$status}\nমোট: {$order->total_amount} টাকা\nআইডি: #{$order->id}";
        }
        return "\n❌ এই ফোন নম্বরে কোনো অর্ডার পাওয়া যায়নি।";
    }

    // --- Helpers ---

    private function validateAndCleanPhone($phoneRaw) {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $phone = str_replace($bn, $en, $phoneRaw);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 3) === '880') $phone = substr($phone, 2);
        elseif (substr($phone, 0, 2) === '88') $phone = substr($phone, 2);
        return preg_match('/^01[3-9]\d{8}$/', $phone) ? $phone : null;
    }

    private function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null, $quickReplies = []) {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
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
                if ($response->failed()) {
                    Log::error("Failed to send image via Messenger: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Exception sending image via Messenger: " . $e->getMessage());
            }
        }
        if (!empty(trim($message))) {
            $payload = ['recipient' => ['id' => $recipientId], 'message' => ['text' => trim($message)]];
            if (!empty($quickReplies)) $payload['message']['quick_replies'] = $quickReplies;
            try {
                $response = Http::post($url, $payload);
                if ($response->failed()) {
                    Log::error("Failed to send message via Messenger: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Exception sending message via Messenger: " . $e->getMessage());
            }
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
        try {
            $url = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";
            $response = Http::post($url, [
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
                Log::error("Failed to send carousel via Messenger: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Exception sending carousel via Messenger: " . $e->getMessage());
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
        } catch (\Exception $e) {
            Log::error("Conversation Log Error: " . $e->getMessage()); 
        }
    }
}