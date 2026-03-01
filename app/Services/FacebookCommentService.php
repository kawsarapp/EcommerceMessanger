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

        // AI-কে দিয়ে কমেন্টের উত্তর এবং ইনবক্স মেসেজ বানানো
        $messages = [
            [
                'role' => 'system', 
                'content' => "তুমি {$client->shop_name} এর স্মার্ট সেলস এক্সিকিউটিভ। একজন কাস্টমার (নাম: {$senderName}) তোমার ফেসবুক পোস্টে কমেন্ট করেছে: '{$commentText}'। 
                তুমি তাকে দুটি রিপ্লাই দিবে:
                ১. কমেন্টের রিপ্লাই (খুব ছোট, ১ লাইনে। যেমন: 'ইনবক্সে চেক করুন' বা 'বিস্তারিত ইনবক্সে দিয়েছি')।
                ২. প্রাইভেট মেসেজ (ইনবক্সে বিস্তারিত উত্তর দিবে)।
                
                তোমার উত্তর ঠিক নিচের ফরম্যাটে দিবে:
                COMMENT_REPLY: [এখানে কমেন্টের ছোট রিপ্লাই]
                PRIVATE_REPLY: [এখানে ইনবক্সের বিস্তারিত রিপ্লাই]"
            ]
        ];

        $aiResponse = $this->aiService->callLlmChain($messages);

        if (!$aiResponse) return;

        // AI এর রেসপন্স থেকে Comment Reply এবং Private Reply আলাদা করা
        $commentReply = "আপনার ইনবক্স চেক করুন।"; 
        $privateReply = "হ্যালো! আপনার কমেন্টের জন্য ধন্যবাদ। আমরা কীভাবে সাহায্য করতে পারি?";

        if (preg_match('/COMMENT_REPLY:\s*(.+)/', $aiResponse, $cMatch)) {
            $commentReply = trim($cMatch[1]);
        }
        if (preg_match('/PRIVATE_REPLY:\s*(.+)/s', $aiResponse, $pMatch)) {
            $privateReply = trim($pMatch[1]);
        }

        $token = $client->page_access_token; // সেলারের পেজ টোকেন

        // ১. পাবলিক কমেন্ট রিপ্লাই করা
        if ($client->auto_comment_reply) {
            $this->replyToComment($commentId, $commentReply, $token);
        }

        // ২. প্রাইভেট মেসেজ (ইনবক্সে) পাঠানো
        if ($client->auto_private_reply) {
            $this->sendPrivateReply($commentId, $privateReply, $token);
        }
    }

    
   // private function replyToComment($commentId, $message, $token)
    //{
     //   $response = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
     //       'message' => $message,
     //       'access_token' => $token
     //   ]);

     //   if ($response->successful()) {
     //       Log::info("✅ Replied to comment: {$commentId}");
      //  } else {
       //     // ফেসবুকের আসল এররটি লগে সেভ হবে
       //     Log::error("❌ Failed to reply to comment: {$commentId}. Error: " . $response->body());
       // }
    //}


    private function replyToComment($commentId, $message, $token)
    {
        // ডাটাবেসের টোকেন বাদ দিয়ে সরাসরি আসল টোকেনটি এখানে বসান
        $realToken = "EAAW6iDsWtMgBQZBgfC4jyfvIZAzcCnc498SnVoGsQOXaKsVmH3R0N4c3ZCzALo5WE2BiMaM59nC5vmGl2s44bEbAw948fExbojew6cpQ4FHyqORkdVKt6baz3G7gK6wLpiuL1ZBpx3p8DKgZAQuUs9E4JDpsTLLjmyUG2Pt2dZAY3aUXfAItXTxucqAsv7G7VrleQy9TRHg4AwgO0ZBbjbZA"; // টার্মিনালে কাজ করা টোকেনটি দিন
        
        $response = Http::post("https://graph.facebook.com/v24.0/{$commentId}/comments", [
            'message' => $message,
            'access_token' => $realToken
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
            // ফেসবুকের আসল এররটি লগে সেভ হবে
            Log::error("❌ Failed to send private message: {$commentId}. Error: " . $response->body());
        }
    }
}