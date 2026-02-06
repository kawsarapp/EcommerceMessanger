<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderSession;
use App\Models\Conversation;
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
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
     * 2. Handle Incoming Messages
     */
    public function handle(Request $request, ChatbotService $chatbot)
    {
        $data = $request->all();

        if (isset($data['entry'][0]['messaging'][0])) {
            $messaging = $data['entry'][0]['messaging'][0];
            $senderId = $messaging['sender']['id'] ?? null;
            $pageId = $data['entry'][0]['id'] ?? null;
            $messageText = $messaging['message']['text'] ?? null;
            $mid = $messaging['message']['mid'] ?? null;

            // [Deduplication]
            if ($mid && Cache::has("fb_mid_{$mid}")) return response('OK', 200);
            if ($mid) Cache::put("fb_mid_{$mid}", true, 60);

            $incomingImageUrl = null;
            if (isset($messaging['message']['attachments'])) {
                foreach ($messaging['message']['attachments'] as $attachment) {
                    if ($attachment['type'] === 'image') {
                        $incomingImageUrl = $attachment['payload']['url'];
                        break;
                    }
                }
            }

            if ($senderId && $pageId && ($messageText || $incomingImageUrl)) {
                try {
                    $this->processIncomingMessage($senderId, $pageId, $messageText, $chatbot, $incomingImageUrl);
                } catch (\Exception $e) {
                    Log::error("Webhook Crash: " . $e->getMessage());
                }
            }
        }
        return response('EVENT_RECEIVED', 200);
    }

    /**
     * 3. Process Message
     */
    private function processIncomingMessage($senderId, $pageId, $messageText, $chatbot, $incomingImageUrl)
    {
        $client = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
        if (!$client) return;

        try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_on'); } catch (\Exception $e) {}

        $finalText = $messageText ?? "Sent an image";
        
        // AI Response Logic
        $reply = $chatbot->getAiResponse($finalText, $client->id, $senderId, $incomingImageUrl);

        if ($reply === null) {
            try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_off'); } catch (\Exception $e) {}
            return; 
        }

        // [TAG PROCESSING] - Order, Update, Cancel, Note
        // Regex 's' modifier allows multiline matching
        if (preg_match('/\[ORDER_DATA:\s*(\{.*?\})\]/s', $reply, $matches)) {
            $reply = $this->finalizeOrder($reply, $matches, $client, $senderId);
        } elseif (preg_match('/\[ADD_NOTE:\s*(\{.*?\})\]/s', $reply, $matches)) {
            $reply = $this->handleOrderNote($reply, $matches, $client, $senderId);
        } elseif (preg_match('/\[UPDATE_ORDER:\s*(\{.*?\})\]/s', $reply, $matches)) {
            $reply = $this->handleOrderUpdate($reply, $matches, $client);
        } elseif (preg_match('/\[CANCEL_ORDER:\s*(\{.*?\})\]/s', $reply, $matches)) {
            $reply = $this->handleOrderCancellation($reply, $matches, $client, $senderId);
        } elseif (str_contains($reply, '[NOTIFY_ADMIN:')) {
            $reply = str_replace(['[NOTIFY_ADMIN]', '{', '}', '"message":'], '', $reply);
        }

        // Clean up outgoing images from text
        $outgoingImage = null;
        if (preg_match('/(https?:\/\/[^\s]+?\.(?:jpg|jpeg|png|gif|webp))/i', $reply, $matches)) {
            $outgoingImage = $matches[1];
            $reply = str_replace($outgoingImage, '', $reply);
            $reply = str_replace(['(ছবি:', '[ছবি]', 'Image Link:', 'Link:', '()'], '', $reply);
            $reply = trim($reply);
        }

        $this->logConversation($client->id, $senderId, $finalText, $reply, $incomingImageUrl);
        $this->sendMessengerMessage($senderId, $reply, $client->fb_page_token, $outgoingImage);
        
        try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_off'); } catch (\Exception $e) {}
    }

    /**
     * [HELPER] বাংলা টু ইংরেজি কনভার্সন
     */
    private function convertBanglaToEnglish($str) {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        return str_replace($bn, $en, $str);
    }

    /**
     * [HELPER] ফোন নম্বর ভ্যালিডেশন এবং ক্লিনিং
     */
    private function validateAndCleanPhone($phoneRaw) {
        // ১. বাংলা টু ইংরেজি
        $phone = $this->convertBanglaToEnglish($phoneRaw);
        
        // ২. স্পেস, হাইফেন বা অন্য ক্যারেক্টার রিমুভ
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // ৩. +88 বা 88 রিমুভ (শুরুতে থাকলে)
        if (substr($phone, 0, 3) === '880') {
            $phone = substr($phone, 2);
        } elseif (substr($phone, 0, 2) === '88') {
            $phone = substr($phone, 2); // যদি শুধু 88 থাকে (rare case)
        }

        // ৪. বাংলাদেশী অপারেটর চেক (013, 014, 015, 016, 017, 018, 019)
        // এবং মোট ডিজিট ১১ হতে হবে
        if (preg_match('/^01[3-9]\d{8}$/', $phone)) {
            return $phone; // সঠিক নম্বর
        }

        return null; // ভুল নম্বর
    }

    /**
     * 4. Finalize Order (With Strict Phone Validation)
     */
    private function finalizeOrder($reply, $matches, $client, $senderId)
    {
        $jsonStr = $matches[1];
        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON Decode Error: " . json_last_error_msg() . " | Data: " . $jsonStr);
            return str_replace($matches[0], "", $reply) . "\n(সিস্টেম এরর: টেকনিক্যাল সমস্যার কারণে অর্ডারটি সেভ করা যায়নি।)";
        }

        // [STRICT PHONE VALIDATION]
        $validPhone = $this->validateAndCleanPhone($data['phone'] ?? '');

        if (!$validPhone) {
            // ট্যাগ রিমুভ করে এরর মেসেজ দেখানো
            return str_replace($matches[0], "", $reply) . "\n⚠️ দুঃখিত, মোবাইল নম্বরটি সঠিক নয়। দয়া করে ১১ ডিজিটের সঠিক বাংলাদেশী নম্বর দিন (যেমন: 017xxxxxxxx)।";
        }

        try {
            return DB::transaction(function () use ($data, $client, $senderId, $validPhone, $reply, $matches) {
                // প্রোডাক্ট ভেরিফিকেশন
                $product = Product::find($data['product_id']);
                if (!$product) return "দুঃখিত, পণ্যটি বর্তমানে স্টকে নেই বা পাওয়া যাচ্ছে না।";

                // প্রাইস ক্যালকুলেশন
                $price = $product->sale_price ?? $product->regular_price ?? 0;
                $isDhaka = ($data['is_dhaka'] ?? false) === true;
                $delivery = $isDhaka ? ($client->delivery_charge_inside ?? 80) : ($client->delivery_charge_outside ?? 150);
                $totalAmount = $price + $delivery;

                $orderData = [
                    'client_id' => $client->id,
                    'sender_id' => $senderId,
                    'customer_name' => $data['name'] ?? 'Guest',
                    'customer_phone' => $validPhone, // ক্লিন করা নম্বর
                    'shipping_address' => $data['address'] ?? 'N/A',
                    'total_amount' => $totalAmount,
                    'order_status' => 'processing',
                    'payment_status' => 'pending'
                ];

                if (Schema::hasColumn('orders', 'notes')) {
                    $orderData['notes'] = $data['note'] ?? null;
                }

                $order = Order::create($orderData);

                // সেশন আপডেট
                OrderSession::where('sender_id', $senderId)->update(['customer_info' => ['step' => 'completed']]);

                // ট্যাগ রিমুভ করে ক্লিন রিপ্লাই
                $cleanReply = str_replace($matches[0], "", $reply);
                $locText = $isDhaka ? "ঢাকার ভেতরে" : "ঢাকার বাইরে";

                // অর্ডার আইডি সহ ফাইনাল মেসেজ
                return trim($cleanReply) . "\n\n✅ অর্ডারটি সফলভাবে তৈরি হয়েছে!\nঅর্ডার আইডি: #{$order->id}\nমোট টাকা: {$totalAmount} Tk ({$locText})\nফোন: {$validPhone}";
            });
        } catch (\Exception $e) {
            Log::error("Finalize Order DB Error: " . $e->getMessage());
            return "দুঃখিত, অর্ডার প্রসেসিং এ একটি সমস্যা হয়েছে। এডমিনকে জানানো হয়েছে।";
        }
    }

    /**
     * 5. Handle Order Note
     */
    private function handleOrderNote($reply, $matches, $client, $senderId)
    {
        $data = json_decode($matches[1], true);
        
        if (Schema::hasColumn('orders', 'notes') && isset($data['note'])) {
            $order = Order::where('client_id', $client->id)
                          ->where('sender_id', $senderId)
                          ->where('order_status', 'processing')
                          ->latest()
                          ->first();

            if ($order) {
                $prevNote = $order->notes ? $order->notes . " | " : "";
                $order->update(['notes' => $prevNote . $data['note']]);
                return str_replace($matches[0], "", $reply) . "\n📝 নোট যুক্ত হয়েছে।";
            }
        }
        return str_replace($matches[0], "", $reply);
    }

    /**
     * 6. Handle Order Update
     */
    private function handleOrderUpdate($reply, $matches, $client)
    {
        $data = json_decode($matches[1], true);
        
        // এখানে AI যদি অর্ডার আইডি না দেয়, তবে লাস্ট অর্ডার ধরা হবে (অপশনাল লজিক)
        $orderId = $data['order_id'] ?? null;
        $order = null;

        if ($orderId) {
            $order = Order::where('id', $orderId)->where('client_id', $client->id)->first();
        } 

        if ($order && in_array($order->order_status, ['processing', 'pending'])) {
            $update = [];
            if (!empty($data['new_address'])) $update['shipping_address'] = $data['new_address'];
            if (!empty($data['new_phone'])) {
                $validPhone = $this->validateAndCleanPhone($data['new_phone']);
                if ($validPhone) {
                    $update['customer_phone'] = $validPhone;
                } else {
                    return str_replace($matches[0], "", $reply) . "\n⚠️ আপডেট হয়নি: নতুন ফোন নম্বরটি সঠিক নয়।";
                }
            }
            $order->update($update);
            return str_replace($matches[0], "", $reply) . "\n✅ অর্ডার আপডেট হয়েছে।";
        }
        return str_replace($matches[0], "", $reply) . "\n(দুঃখিত, অর্ডারটি আপডেট করা সম্ভব নয়।)";
    }

    /**
     * 7. Handle Order Cancellation
     */
    private function handleOrderCancellation($reply, $matches, $client, $senderId)
    {
        $data = json_decode($matches[1], true);
        
        $order = Order::where('client_id', $client->id)
                      ->where('sender_id', $senderId)
                      ->latest()
                      ->first();

        if ($order && in_array($order->order_status, ['processing', 'pending'])) {
            $order->update([
                'order_status' => 'cancelled',
                'admin_note' => "User Reason: " . ($data['reason'] ?? 'Not Specified')
            ]);
            return str_replace($matches[0], "", $reply) . "\n🚫 অর্ডারটি বাতিল করা হয়েছে।";
        }
        return str_replace($matches[0], "", $reply) . "\n(দুঃখিত, অর্ডারটি বাতিল করা সম্ভব নয় বা ইতিমধ্যে বাতিল হয়েছে।)";
    }

    /**
     * Messenger API Helpers
     */
    private function sendTypingAction($recipientId, $token, $action) {
        Http::post("https://graph.facebook.com/v19.0/me/messages?access_token=$token", [
            'recipient' => ['id' => $recipientId], 
            'sender_action' => $action
        ]);
    }

    private function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null) {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token=$token";
        $sentSuccessfully = false;

        // 1. Send Image First
        if ($imageUrl) {
            try {
                $res = Http::post($url, [
                    'recipient' => ['id' => $recipientId], 
                    'message' => [
                        'attachment' => [
                            'type' => 'image', 
                            'payload' => ['url' => $imageUrl, 'is_reusable' => true]
                        ]
                    ]
                ]);
                if ($res->successful()) $sentSuccessfully = true;
            } catch (\Exception $e) {
                Log::error("Image send error: " . $e->getMessage());
            }
        }

        // Fallback if image fails
        if ($imageUrl && !$sentSuccessfully) {
            $message .= "\n(ছবিটি এখানে দেখুন: $imageUrl)";
        }

        // 2. Send Text
        if (!empty(trim($message))) {
            Http::post($url, [
                'recipient' => ['id' => $recipientId], 
                'message' => ['text' => trim($message)]
            ]);
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
        } catch (\Exception $e) {}
    }
}