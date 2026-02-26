<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\OrderSession;
use App\Models\Conversation;
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    protected $chatbot;

    public function __construct(ChatbotService $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    /**
     * ইনস্টাগ্রামের মেসেজ প্রসেস করার মেইন ফাংশন
     */
    public function process(Request $request)
    {
        $data = $request->all();
        Log::info("📸 Incoming Instagram Message", $data);

        foreach ($data['entry'] as $entry) {
            // ইনস্টাগ্রামে ID গুলো 'id' তেই থাকে
            $igAccountId = $entry['id']; 
            
            // ক্লায়েন্ট বের করা
            $client = Client::where('ig_account_id', $igAccountId)
                            ->where('is_instagram_active', true)
                            ->first();

            if (!$client) continue;

            if (isset($entry['messaging'])) {
                foreach ($entry['messaging'] as $messageEvent) {
                    $this->handleMessage($messageEvent, $client);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function handleMessage($event, $client)
    {
        $senderId = $event['sender']['id'];
        
        // নিজের পাঠানো মেসেজ ইগনোর করা
        if ($senderId === $client->ig_account_id) return;

        $messageText = $event['message']['text'] ?? '';
        
        if (empty($messageText)) return;

        // ১. AI Chatbot Service এ মেসেজ পাঠানো (আগের মতোই)
        $aiResponse = $this->chatbot->handleMessage(
            $client, 
            $senderId, 
            $messageText, 
            null // আপাতত ইনস্টাগ্রামে ইমেজ লিংক পাঠাচ্ছি না
        );

        if ($aiResponse) {
            // ২. কাস্টমারকে ইনস্টাগ্রামে রিপ্লাই দেওয়া
            app(\App\Services\NotificationService::class)->sendInstagramReply($client, $senderId, $aiResponse);

            // ৩. লগ সেভ করা
            Conversation::create([
                'client_id' => $client->id, 
                'sender_id' => $senderId, 
                'platform' => 'instagram', 
                'user_message' => $messageText, 
                'bot_response' => $aiResponse, 
                'status' => 'success'
            ]);
        }
    }
}