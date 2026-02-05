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

    public function handle(Request $request, ChatbotService $chatbot)
    {
        $data = $request->all();

        if (isset($data['entry'][0]['messaging'][0])) {
            $messaging = $data['entry'][0]['messaging'][0];
            $senderId = $messaging['sender']['id'] ?? null;
            $pageId = $data['entry'][0]['id'] ?? null;
            $messageText = $messaging['message']['text'] ?? null;
            $mid = $messaging['message']['mid'] ?? null;

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

    private function processIncomingMessage($senderId, $pageId, $messageText, $chatbot, $incomingImageUrl)
    {
        $client = Client::where('fb_page_id', $pageId)->where('status', 'active')->first();
        if (!$client) return;

        try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_on'); } catch (\Exception $e) {}

        $finalText = $messageText ?? "Sent an image";
        $reply = $chatbot->getAiResponse($finalText, $client->id, $senderId, $incomingImageUrl);

        if (str_contains($reply, '[ORDER_DATA:')) {
            $reply = $this->finalizeOrder($reply, $client, $senderId);
        } elseif (str_contains($reply, '[ADD_NOTE:')) {
            $reply = $this->handleOrderNote($reply, $client, $senderId);
        } elseif (str_contains($reply, '[UPDATE_ORDER:')) {
            $reply = $this->handleOrderUpdate($reply, $client);
        } elseif (str_contains($reply, '[CANCEL_ORDER:')) {
            $reply = $this->handleOrderCancellation($reply, $client, $senderId);
        } elseif (str_contains($reply, '[NOTIFY_ADMIN:')) {
            $reply = str_replace(['[NOTIFY_ADMIN]', '{', '}', '"message":'], '', $reply);
        }

        $outgoingImage = null;
        if (preg_match('/(https?:\/\/[^\s]+?\.(?:jpg|jpeg|png|gif|webp))/i', $reply, $matches)) {
            $outgoingImage = $matches[1];
            $reply = str_replace($outgoingImage, '', $reply);
            $reply = str_replace(['(ছবি:', '[ছবি]', 'Image Link:', 'Link:'], '', $reply);
            $reply = trim($reply);
        }

        $this->logConversation($client->id, $senderId, $finalText, $reply, $incomingImageUrl);
        $this->sendMessengerMessage($senderId, $reply, $client->fb_page_token, $outgoingImage);
        
        try { $this->sendTypingAction($senderId, $client->fb_page_token, 'typing_off'); } catch (\Exception $e) {}
    }

    // [NEW] বাংলা নম্বর কনভার্টার
    private function convertBanglaToEnglish($str) {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        return str_replace($bn, $en, $str);
    }

    private function finalizeOrder($reply, $client, $senderId)
    {
        preg_match('/\[ORDER_DATA: (.*?)\]/', $reply, $matches);
        if (!isset($matches[1])) return $reply;

        $data = json_decode($matches[1], true);
        if (!$data) return str_replace($matches[0], "", $reply);

        // [FIX] বাংলা নম্বর কনভার্ট এবং ক্লিন করা
        $phoneRaw = $this->convertBanglaToEnglish($data['phone'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $phoneRaw);

        if (strlen($phone) < 11) {
            return str_replace($matches[0], "", $reply) . "\n(দুঃখিত, মোবাইল নম্বরটি সঠিক নয়। ১১ ডিজিট হতে হবে।)";
        }

        try {
            return DB::transaction(function () use ($data, $client, $senderId, $phone, $reply, $matches) {
                $product = Product::find($data['product_id']);
                if (!$product) return "দুঃখিত, পণ্যটি পাওয়া যাচ্ছে না।";

                $price = $product->sale_price ?? $product->regular_price ?? 0;
                $isDhaka = ($data['is_dhaka'] ?? false) === true;
                $delivery = $isDhaka ? ($client->delivery_charge_inside ?? 80) : ($client->delivery_charge_outside ?? 150);
                $totalAmount = $price + $delivery;

                $orderData = [
                    'client_id' => $client->id,
                    'sender_id' => $senderId,
                    'customer_name' => $data['name'] ?? 'Guest',
                    'customer_phone' => $phone,
                    'shipping_address' => $data['address'] ?? '',
                    'total_amount' => $totalAmount,
                    'order_status' => 'processing',
                    'payment_status' => 'pending'
                ];

                if (Schema::hasColumn('orders', 'notes')) {
                    $orderData['notes'] = $data['note'] ?? null;
                }

                $order = Order::create($orderData);
                OrderSession::where('sender_id', $senderId)->update(['customer_info' => ['step' => 'completed']]);

                $cleanReply = str_replace($matches[0], "", $reply);
                $locText = $isDhaka ? "Inside Dhaka" : "Outside Dhaka";

                return $cleanReply . "\n\n✅ অর্ডার কনফার্ম!\nID: #{$order->id}\nTotal: {$totalAmount} Tk ({$locText})";
            });
        } catch (\Exception $e) {
            Log::error("Order Error: " . $e->getMessage());
            return "অর্ডার প্রসেসিং এ সমস্যা হয়েছে।";
        }
    }

    private function handleOrderNote($reply, $client, $senderId)
    {
        preg_match('/\[ADD_NOTE: (.*?)\]/', $reply, $matches);
        if (!isset($matches[1])) return $reply;
        $data = json_decode($matches[1], true);
        
        if (!Schema::hasColumn('orders', 'notes')) return str_replace($matches[0], "", $reply);

        $order = Order::where('client_id', $client->id)->where('sender_id', $senderId)->where('order_status', 'processing')->latest()->first();
        if ($order) {
            $order->update(['notes' => ($order->notes ? $order->notes . " | " : "") . ($data['note'] ?? '')]);
            return str_replace($matches[0], "", $reply) . "\n📝 নোট যুক্ত হয়েছে।";
        }
        return str_replace($matches[0], "", $reply);
    }

    private function handleOrderUpdate($reply, $client)
    {
        preg_match('/\[UPDATE_ORDER: (.*?)\]/', $reply, $matches);
        if (!isset($matches[1])) return $reply;
        $data = json_decode($matches[1], true);
        
        $order = Order::where('id', $data['order_id'])->where('client_id', $client->id)->first();
        if ($order && in_array($order->order_status, ['processing', 'pending'])) {
            $update = [];
            if (!empty($data['new_address'])) $update['shipping_address'] = $data['new_address'];
            if (!empty($data['new_phone'])) {
                $rawPhone = $this->convertBanglaToEnglish($data['new_phone']);
                $update['customer_phone'] = preg_replace('/[^0-9]/', '', $rawPhone);
            }
            $order->update($update);
            return str_replace($matches[0], "", $reply) . "\n✅ আপডেট হয়েছে।";
        }
        return "অর্ডারটি পরিবর্তন সম্ভব নয়।";
    }

    private function handleOrderCancellation($reply, $client, $senderId)
    {
        preg_match('/\[CANCEL_ORDER: (.*?)\]/', $reply, $matches);
        if (!isset($matches[1])) return $reply;
        $data = json_decode($matches[1], true);
        $order = Order::where('client_id', $client->id)->where('sender_id', $senderId)->latest()->first();

        if ($order && in_array($order->order_status, ['processing', 'pending'])) {
            $order->update(['order_status' => 'cancelled', 'admin_note' => $data['reason'] ?? 'User Request']);
            return str_replace($matches[0], "", $reply) . "\n🚫 অর্ডার বাতিল হয়েছে।";
        }
        return "অর্ডারটি বাতিল সম্ভব নয়।";
    }

    private function sendTypingAction($recipientId, $token, $action) {
        Http::post("https://graph.facebook.com/v19.0/me/messages?access_token=$token", ['recipient' => ['id' => $recipientId], 'sender_action' => $action]);
    }

    private function sendMessengerMessage($recipientId, $message, $token, $imageUrl = null) {
        $url = "https://graph.facebook.com/v19.0/me/messages?access_token=$token";
        $sent = false;
        if ($imageUrl) {
            try {
                $res = Http::post($url, ['recipient' => ['id' => $recipientId], 'message' => ['attachment' => ['type' => 'image', 'payload' => ['url' => $imageUrl, 'is_reusable' => true]]]]);
                if ($res->successful()) $sent = true;
            } catch (\Exception $e) {}
        }
        if ($imageUrl && !$sent) $message .= "\n(ছবি: $imageUrl)";
        if (!empty(trim($message))) Http::post($url, ['recipient' => ['id' => $recipientId], 'message' => ['text' => trim($message)]]);
    }

    private function logConversation($clientId, $senderId, $userMsg, $botMsg, $imgUrl) {
        try { Conversation::create(['client_id' => $clientId, 'sender_id' => $senderId, 'platform' => 'messenger', 'user_message' => $userMsg, 'bot_response' => $botMsg, 'attachment_url' => $imgUrl, 'status' => 'success']); } catch (\Exception $e) {}
    }
}