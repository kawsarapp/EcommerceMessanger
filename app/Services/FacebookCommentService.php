<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Services\Chatbot\ChatbotUtilityService;

class FacebookCommentService
{
    protected $aiService;

    public function __construct(ChatbotUtilityService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function handleComment($clientId, $commentId, $commentText, $senderId, $senderName)
    {
        $client = Client::find($clientId);
        
        // সেলার যদি ফিচার অফ করে রাখে, তাহলে কাজ করবে না
        if (!$client || (!$client->auto_comment_reply && !$client->auto_private_reply)) {
            return;
        }

        // AI-কে দিয়ে শুধু দরকারি কমেন্টের উত্তর বানানো
        $messages = [
            [
                'role' => 'system', 
                'content' => "তুমি {$client->shop_name} এর স্মার্ট সেলস এক্সিকিউটিভ। একজন কাস্টমার (নাম: {$senderName}) তোমার ফেসবুক পোস্টে কমেন্ট করেছে: '{$commentText}'। 
                
                গুরুত্বপূর্ণ নিয়ম: যদি কাস্টমারের কমেন্টটি শুধু 'ok', 'hmm', 'hi', 'hello', 'nice', 'good' বা এমন কোনো ছোট/অপ্রয়োজনীয় শব্দ হয় যার রিপ্লাই দেওয়ার দরকার নেই, তবে তুমি উত্তরে শুধুমাত্র 'IGNORE' লিখবে (অন্য কোনো শব্দ নয়)।
                
                আর যদি কমেন্টটি প্রাসঙ্গিক হয় (যেমন দাম, সাইজ বা বিস্তারিত জানতে চাওয়া, প্রশংসা করা), তবে তুমি তাকে দুটি রিপ্লাই দিবে:
                ১. কমেন্টের রিপ্লাই (খুব ছোট, ১ লাইনে। যেমন: 'ইনবক্সে চেক করুন' বা 'বিস্তারিত ইনবক্সে দিয়েছি')।
                ২. প্রাইভেট মেসেজ (ইনবক্সে বিস্তারিত উত্তর দিবে)।
                
                তোমার উত্তর ঠিক নিচের ফরম্যাটে দিবে:
                COMMENT_REPLY: [এখানে কমেন্টের ছোট রিপ্লাই]
                PRIVATE_REPLY: [এখানে ইনবক্সের বিস্তারিত রিপ্লাই]"
            ]
        ];

        $aiResponse = $this->aiService->callLlmChain($messages);

        if (!$aiResponse) return;

        // যদি AI বলে যে এটি ইগনোর করতে হবে, তবে এখানেই প্রসেস শেষ (কোনো রিপ্লাই যাবে না)
        if (strpos(trim($aiResponse), 'IGNORE') !== false) {
            Log::info("🛑 AI decided to IGNORE irrelevant comment: {$commentText}");
            return;
        }

        // AI এর রেসপন্স থেকে Comment Reply এবং Private Reply আলাদা করা
        $commentReply = "আপনার ইনবক্স চেক করুন।"; 
        $privateReply = "হ্যালো! আপনার কমেন্টের জন্য ধন্যবাদ। আমরা কীভাবে সাহায্য করতে পারি?";

        if (preg_match('/COMMENT_REPLY:\s*(.+)/', $aiResponse, $cMatch)) {
            $commentReply = trim($cMatch[1]);
        }
        if (preg_match('/PRIVATE_REPLY:\s*(.+)/s', $aiResponse, $pMatch)) {
            $privateReply = trim($pMatch[1]);
        }

        // 🟢 সমাধান: ডাটাবেসের সঠিক কলামের নাম 'fb_page_token' ব্যবহার করা হলো
        $token = trim($client->fb_page_token); 

        // ১. পাবলিক কমেন্ট রিপ্লাই করা
        if ($client->auto_comment_reply) {
            $this->replyToComment($commentId, $commentReply, $token);
        }

        // ২. প্রাইভেট মেসেজ (ইনবক্সে) পাঠানো
        if ($client->auto_private_reply) {
            $this->sendPrivateReply($commentId, $privateReply, $token);
        }
    }

    private function replyToComment($commentId, $message, $token)
    {
        $response = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
            'message' => $message,
            'access_token' => $token
        ]);

        if ($response->successful()) {
            Log::info("✅ Replied to comment: {$commentId}");
        } else {
            Log::error("❌ Failed to reply. Error: " . $response->body());
        }
    }

    private function sendPrivateReply($commentId, $message, $token)
    {
        $response = Http::post("https://graph.facebook.com/v24.0/{$commentId}/private_replies", [
            'message' => $message,
            'access_token' => $token
        ]);

        if ($response->successful()) {
            Log::info("📩 Sent Private Message for comment: {$commentId}");
        } else {
            Log::error("❌ Failed to send private message: {$commentId}. Error: " . $response->body());
        }
    }
}