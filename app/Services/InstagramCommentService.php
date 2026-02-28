<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Services\Chatbot\ChatbotUtilityService;
use App\Services\NotificationService;

class InstagramCommentService
{
    protected $aiService;
    protected $notificationService;

    public function __construct(ChatbotUtilityService $aiService, NotificationService $notificationService)
    {
        $this->aiService = $aiService;
        $this->notificationService = $notificationService;
    }

    public function handleComment($clientId, $commentId, $commentText, $senderId, $senderName)
    {
        $client = Client::find($clientId);
        
        // সেলার যদি ফিচার অফ করে রাখে, তাহলে কাজ করবে না
        if (!$client || (!$client->auto_comment_reply && !$client->auto_private_reply)) {
            return;
        }

        // AI-কে দিয়ে কমেন্টের উত্তর এবং ইনবক্স মেসেজ বানানো
        $messages = [
            [
                'role' => 'system', 
                'content' => "তুমি {$client->shop_name} এর স্মার্ট সেলস এক্সিকিউটিভ। একজন কাস্টমার (ইউজারনেম: {$senderName}) তোমার ইনস্টাগ্রাম পোস্টে কমেন্ট করেছে: '{$commentText}'। 
                তুমি তাকে দুটি রিপ্লাই দিবে:
                ১. কমেন্টের রিপ্লাই (খুব ছোট, ১ লাইনে। যেমন: 'ইনবক্সে চেক করুন' বা 'বিস্তারিত ইনবক্সে দিয়েছি')।
                ২. প্রাইভেট মেসেজ (ইনবক্সে বিস্তারিত উত্তর দিবে)।
                
                তোমার উত্তর ঠিক নিচের ফরম্যাটে দিবে:
                COMMENT_REPLY: [এখানে কমেন্টের ছোট রিপ্লাই]
                PRIVATE_REPLY: [এখানে ইনবক্সের বিস্তারিত রিপ্লাই]"
            ]
        ];

        $aiResponse = $this->aiService->callLlmChain($messages);

        if (!$aiResponse) return;

        $commentReply = "আপনার ইনবক্স চেক করুন।"; 
        $privateReply = "হ্যালো! আপনার কমেন্টের জন্য ধন্যবাদ। আমরা কীভাবে সাহায্য করতে পারি?";

        if (preg_match('/COMMENT_REPLY:\s*(.+)/', $aiResponse, $cMatch)) {
            $commentReply = trim($cMatch[1]);
        }
        if (preg_match('/PRIVATE_REPLY:\s*(.+)/s', $aiResponse, $pMatch)) {
            $privateReply = trim($pMatch[1]);
        }

        $token = $client->fb_page_token; // ইনস্টাগ্রামেও ফেসবুক পেজের টোকেন ব্যবহার হয়

        // ১. পাবলিক কমেন্ট রিপ্লাই করা (Instagram API)
        if ($client->auto_comment_reply) {
            $this->replyToComment($commentId, $commentReply, $token);
        }

        // ২. প্রাইভেট মেসেজ (DM) পাঠানো
        if ($client->auto_private_reply) {
            $this->notificationService->sendInstagramReply($client, $senderId, $privateReply);
            Log::info("📩 Sent Instagram DM for comment: {$commentId}");
        }
    }

    private function replyToComment($commentId, $message, $token)
    {
        $response = Http::post("https://graph.facebook.com/v19.0/{$commentId}/replies", [
            'message' => $message,
            'access_token' => $token
        ]);
        
        if ($response->successful()) {
            Log::info("✅ Replied to Instagram comment: {$commentId}");
        } else {
            Log::error("❌ Failed to reply Instagram comment: " . $response->body());
        }
    }
}