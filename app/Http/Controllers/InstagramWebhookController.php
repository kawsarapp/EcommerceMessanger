<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Conversation;
use App\Services\ChatbotService;
use App\Services\NotificationService;
use App\Services\InstagramCommentService;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    protected $chatbot;
    protected $notificationService;
    protected $commentService;

    public function __construct(ChatbotService $chatbot, NotificationService $notificationService, InstagramCommentService $commentService)
    {
        $this->chatbot = $chatbot;
        $this->notificationService = $notificationService;
        $this->commentService = $commentService;
    }

    /**
     * ইনস্টাগ্রামের মেসেজ এবং কমেন্ট প্রসেস করার মেইন ফাংশন
     */
    public function process(Request $request)
    {
        $data = $request->all();
        Log::info("📸 Incoming Instagram Webhook", $data);

        foreach ($data['entry'] as $entry) {
            $igAccountId = $entry['id']; 
            
            // ক্লায়েন্ট বের করা (page_id এর বদলে fb_page_id করা হয়েছে)
            $client = Client::where('instagram_page_id', $igAccountId)
                            ->orWhere('ig_account_id', $igAccountId)
                            ->orWhere('fb_page_id', $igAccountId)
                            ->first();

            if (!$client) {
                Log::warning("❌ Instagram Client not found for ID: {$igAccountId}");
                continue;
            }

            // 💬 ১. ইনস্টাগ্রাম কমেন্ট রিসিভ করার লজিক (changes)
            if (isset($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    if (isset($change['field']) && $change['field'] === 'comments') {
                        $commentData = $change['value'];
                        $senderId = $commentData['from']['id'] ?? null;
                        
                        // নিজের করা কমেন্ট ইগনোর করা হবে
                        if ($senderId && $senderId !== $igAccountId) {
                            $commentId = $commentData['id'];
                            $commentText = $commentData['text'] ?? '';
                            $senderName = $commentData['from']['username'] ?? 'Customer';

                            // কমেন্ট সার্ভিসে ডাটা পাঠানো
                            $this->commentService->handleComment(
                                $client->id, 
                                $commentId, 
                                $commentText, 
                                $senderId, 
                                $senderName
                            );
                        }
                    }
                }
            }

            // 💬 ২. ইনবক্স মেসেজ রিসিভ করার লজিক (messaging)
            if (isset($entry['messaging'])) {
                foreach ($entry['messaging'] as $messageEvent) {
                    $this->handleMessage($messageEvent, $client, $igAccountId);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function handleMessage($event, $client, $igAccountId)
    {
        $senderId = $event['sender']['id'] ?? null;
        
        // নিজের পাঠানো মেসেজ ইগনোর করা
        if (!$senderId || $senderId === $igAccountId) return;

        $messageText = $event['message']['text'] ?? '';
        
        if (empty($messageText)) return;

        // ১. AI Chatbot Service এ মেসেজ পাঠানো
        $aiResponse = $this->chatbot->handleMessage($client, $senderId, $messageText, null);

        if ($aiResponse) {
            // ২. কাস্টমারকে ইনস্টাগ্রামে রিপ্লাই দেওয়া
            $this->notificationService->sendInstagramReply($client, $senderId, $aiResponse);

            // ৩. লগ সেভ করা
            Conversation::create([
                'client_id' => $client->id, 
                'sender_id' => $senderId, 
                'platform' => 'instagram', 
                'user_message' => $messageText, 
                'bot_response' => $aiResponse, 
                'status' => 'success'
            ]);
            
            Log::info("✅ Instagram Reply Sent to {$senderId}");
        }
    }
}