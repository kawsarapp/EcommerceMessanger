<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use App\Services\Messenger\MessengerWebhookService;

class WebhookController extends Controller
{
    /**
     * 1. Facebook Webhook Verification
     */
    public function verify(Request $request)
    {
        Log::info("--- Webhook Verification Hit ---", $request->all());

        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token) {
            $client = Client::where('fb_verify_token', $token)->first();
            if ($client) {
                $client->update(['webhook_verified_at' => now()]);
                Log::info("✅ Webhook Verified for Client ID: " . $client->id);
                return response($challenge, 200);
            } else {
                Log::error("❌ Verification Failed: Token mismatch.");
            }
        }
        return response('Forbidden', 403);
    }

    /**
     * 2. Handle Incoming Messages & Comments
     */
    public function handle(Request $request, MessengerWebhookService $messengerService)
    {
        $data = $request->all();

        // সবার আগে লগ - ফেসবুক থেকে যাই আসুক, এখানে ধরা পড়বে
        Log::info("📸 Incoming Facebook Webhook Payload", $data);

        // 1. OMNICHANNEL ROUTING (Instagram)
        if (($data['object'] ?? '') === 'instagram') {
            return app(InstagramWebhookController::class)->process($request);
        }

        // 2. FACEBOOK MESSENGER LOGIC
        if (($data['object'] ?? '') === 'page') {
            
            // 🔴 BUG FIX: ডিফল্টভাবে false সেট করা হলো, যাতে undefined variable এরর না আসে
            $hasMessaging = false; 

            foreach ($data['entry'] as $entry) {
                $pageId = $entry['id'];

                // 💬 কমেন্ট রিসিভ করার লজিক (changes)
                if (isset($entry['changes'])) {
                    $client = Client::where('fb_page_id', $pageId)->first();
                    
                    if ($client) {
                        foreach ($entry['changes'] as $change) {
                            if (isset($change['field']) && $change['field'] === 'feed' && isset($change['value']['item']) && $change['value']['item'] === 'comment') {
                                $commentData = $change['value'];
                                
                                // যদি পেজ নিজেই রিপ্লাই দেয়, তবে ইগনোর করব
                                $senderId = $commentData['from']['id'] ?? null;
                                if ($senderId && $senderId != $pageId) {
                                    $commentId = $commentData['comment_id'];
                                    $commentText = $commentData['message'];
                                    $senderName = $commentData['from']['name'] ?? 'Customer';

                                    app(\App\Services\FacebookCommentService::class)->handleComment(
                                        $client->id, 
                                        $commentId, 
                                        $commentText, 
                                        $senderId, 
                                        $senderName
                                    );
                                }
                            }
                        }
                    } else {
                        Log::warning("❌ Facebook Comment Client not found for fb_page_id: {$pageId}");
                    }
                }

                // ✉️ ইনবক্স মেসেজ আছে কিনা চেক করা (messaging)
                if (isset($entry['messaging'])) {
                    $hasMessaging = true;
                }
            }

            // শুধুমাত্র যদি ইনবক্স মেসেজ থাকে, তবেই MessengerWebhookService কল হবে
            if ($hasMessaging) {
                Log::info("📨 Inbox Message Detected! Forwarding to MessengerWebhookService...");
                $messengerService->processPayload($request);
            } else {
                Log::warning("⚠️ Webhook Received but NO 'messaging' array found. Possible reasons: App is in Dev Mode, missing 'pages_messaging' scope, or this is just an echo/delivery event.");
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}