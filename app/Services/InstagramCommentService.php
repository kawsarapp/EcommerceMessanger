<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\SocialComment;
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
        // ডুপ্লিকেট কমেন্ট চেক (একই কমেন্ট ২ বার আসলে ইগনোর করবে)
        $existing = SocialComment::where('comment_id', $commentId)->first();
        if ($existing) return;

        $client = Client::find($clientId);
        if (!$client) return;

        // ১. ডাটাবেসে প্রথমে কমেন্ট সেভ করা (Status: Pending)
        $socialComment = SocialComment::create([
            'client_id' => $clientId,
            'platform' => 'instagram',
            'comment_id' => $commentId,
            'sender_id' => $senderId,
            'sender_name' => $senderName,
            'comment_text' => $commentText,
            'status' => 'pending'
        ]);

        // সেলার যদি অটো রিপ্লাই অফ করে রাখে, তবে শুধু ড্যাশবোর্ডে সেভ হয়েই থাকবে।
        if (!$client->auto_comment_reply && !$client->auto_private_reply) {
            return;
        }

        // ২. AI-কে ফিল্টার এবং রিপ্লাই করার প্রম্পট দেওয়া
        $messages = [
            [
                'role' => 'system', 
                'content' => "তুমি {$client->shop_name} এর স্মার্ট সেলস এক্সিকিউটিভ। একজন কাস্টমার (ইউজারনেম: {$senderName}) তোমার ইনস্টাগ্রাম পোস্টে কমেন্ট করেছে: '{$commentText}'। 
                
                🚨 STRICT RULE: যদি এই কমেন্টটি কোনো প্রোডাক্ট কেনা, দাম, ডেলিভারি, সাইজ, কালার বা শপ সম্পর্কিত কোনো প্রশ্ন না হয় (যেমন: কেউ শুধু 'wow', 'nice', 'hi' লিখেছে বা বন্ধুদের মেনশন করেছে), তাহলে তুমি শুধু একটি শব্দ আউটপুট দিবে: IGNORE
                
                আর যদি এটি সেলস রিলেটেড হয়, তবে নিচের ফরম্যাটে উত্তর দিবে:
                ১. কমেন্টের রিপ্লাই (খুব ছোট, ১ লাইনে। যেমন: 'ইনবক্সে চেক করুন')।
                ২. প্রাইভেট মেসেজ (ইনবক্সে বিস্তারিত উত্তর দিবে)।
                
                তোমার উত্তর ঠিক নিচের ফরম্যাটে দিবে:
                COMMENT_REPLY: [ছোট রিপ্লাই]
                PRIVATE_REPLY: [বিস্তারিত ইনবক্স মেসেজ]"
            ]
        ];

        $aiResponse = $this->aiService->callLlmChain($messages);

        if (!$aiResponse) return;

        // ৩. ইগনোর লজিক চেক (অপ্রয়োজনীয় কমেন্টে রিপ্লাই দেবে না)
        if (trim($aiResponse) === 'IGNORE' || str_contains($aiResponse, 'IGNORE')) {
            $socialComment->update(['status' => 'ignored']);
            Log::info("🚫 AI Ignored non-sales Instagram comment: {$commentId}");
            return;
        }

        // ৪. সেলস কমেন্ট হলে রিপ্লাই এক্সট্রাক্ট করা
        $commentReply = "আপনার ইনবক্স চেক করুন।"; 
        $privateReply = "হ্যালো! আপনার কমেন্টের জন্য ধন্যবাদ। আমরা কীভাবে সাহায্য করতে পারি?";

        if (preg_match('/COMMENT_REPLY:\s*(.+)/', $aiResponse, $cMatch)) {
            $commentReply = trim($cMatch[1]);
        }
        if (preg_match('/PRIVATE_REPLY:\s*(.+)/s', $aiResponse, $pMatch)) {
            $privateReply = trim($pMatch[1]);
        }

        $token = $client->fb_page_token; // ইনস্টাগ্রামেও ফেসবুক পেজের টোকেন ব্যবহার হয়

        // ৫. পাবলিক কমেন্ট রিপ্লাই করা (Instagram API)
        if ($client->auto_comment_reply) {
            $this->replyToComment($commentId, $commentReply, $token);
        }

        // ৬. প্রাইভেট মেসেজ (DM) পাঠানো
        if ($client->auto_private_reply) {
            $this->notificationService->sendInstagramReply($client, $senderId, $privateReply);
            Log::info("📩 Sent Instagram DM for comment: {$commentId}");
        }

        // ৭. ডাটাবেসে স্ট্যাটাস আপডেট
        $socialComment->update([
            'reply_text' => $commentReply,
            'status' => 'auto_replied'
        ]);
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
            Log::error("❌ Failed to reply to Instagram comment: " . $response->body());
        }
    }
}