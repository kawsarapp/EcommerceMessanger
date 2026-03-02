<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\SocialComment;
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
        // ডুপ্লিকেট কমেন্ট চেক (ফেসবুক অনেক সময় একই ওয়েবহুক ২ বার পাঠায়)
        $existing = SocialComment::where('comment_id', $commentId)->first();
        if ($existing) return;

        $client = Client::find($clientId);
        if (!$client) return;

        // ১. ডাটাবেসে প্রথমে কমেন্ট সেভ করা (Status: Pending)
        $socialComment = SocialComment::create([
            'client_id' => $clientId,
            'platform' => 'facebook',
            'comment_id' => $commentId,
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'comment_text' => $commentText,
            'status' => 'pending'
        ]);

        // সেলার যদি অটো রিপ্লাই অফ করে রাখে, তবে শুধু সেভ হয়েই থাকবে।
        if (!$client->auto_comment_reply && !$client->auto_private_reply) {
            return;
        }

        // ২. AI-কে ফিল্টার এবং রিপ্লাই করার প্রম্পট দেওয়া
        $messages = [
            [
                'role' => 'system', 
                'content' => "তুমি {$client->shop_name} এর সেলস এক্সিকিউটিভ। কাস্টমার কমেন্ট করেছে: '{$commentText}'। 
                
                🚨 STRICT RULE: যদি এই কমেন্টটি কোনো প্রোডাক্ট কেনা, দাম, ডেলিভারি, সাইজ, কালার বা শপ সম্পর্কিত কোনো প্রশ্ন না হয় (যেমন: কেউ শুধু 'wow', 'nice', 'hi' লিখেছে বা বন্ধুদের মেনশন করেছে), তাহলে তুমি শুধু একটি শব্দ আউটপুট দিবে: IGNORE
                
                আর যদি এটি সেলস রিলেটেড হয়, তবে আগের মত নিচের ফরম্যাটে উত্তর দিবে:
                COMMENT_REPLY: [ছোট রিপ্লাই]
                PRIVATE_REPLY: [বিস্তারিত ইনবক্স মেসেজ]"
            ]
        ];

        $aiResponse = $this->aiService->callLlmChain($messages);

        if (!$aiResponse) return;

        // ৩. ইগনোর লজিক চেক
        if (trim($aiResponse) === 'IGNORE' || str_contains($aiResponse, 'IGNORE')) {
            $socialComment->update(['status' => 'ignored']);
            Log::info("🚫 AI Ignored non-sales comment: {$commentId}");
            return;
        }

        // ৪. সেলস কমেন্ট হলে রিপ্লাই এক্সট্রাক্ট করা
        $commentReply = "আপনার ইনবক্স চেক করুন।"; 
        $privateReply = "হ্যালো! আপনার কমেন্টের জন্য ধন্যবাদ।";

        if (preg_match('/COMMENT_REPLY:\s*(.+)/', $aiResponse, $cMatch)) {
            $commentReply = trim($cMatch[1]);
        }
        if (preg_match('/PRIVATE_REPLY:\s*(.+)/s', $aiResponse, $pMatch)) {
            $privateReply = trim($pMatch[1]);
        }

        $token = $client->fb_page_token;

        if ($client->auto_comment_reply) {
            $this->replyToComment($commentId, $commentReply, $token);
        }

        if ($client->auto_private_reply) {
            $this->sendPrivateReply($commentId, $privateReply, $token);
        }

        // ডাটাবেসে স্ট্যাটাস আপডেট
        $socialComment->update([
            'reply_text' => $commentReply,
            'status' => 'auto_replied'
        ]);
    }

    private function replyToComment($commentId, $message, $token)
    {
        Http::post("https://graph.facebook.com/v19.0/{$commentId}/comments", [
            'message' => $message,
            'access_token' => $token
        ]);
    }

    private function sendPrivateReply($commentId, $message, $token)
    {
        Http::post("https://graph.facebook.com/v19.0/{$commentId}/private_replies", [
            'message' => $message,
            'access_token' => $token
        ]);
    }
}