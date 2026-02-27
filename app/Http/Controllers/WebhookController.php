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

        // 1. OMNICHANNEL ROUTING (Instagram)
        if (($data['object'] ?? '') === 'instagram') {
            return app(InstagramWebhookController::class)->process($request);
        }

        // 2. FACEBOOK MESSENGER & COMMENTS LOGIC
        if (($data['object'] ?? '') === 'page') {
            
            $entries = $data['entry'] ?? [];

            foreach ($entries as $entry) {
                $pageId = $entry['id'] ?? null;

                // 💬 [NEW]: কমেন্ট রিসিভ করার লজিক (ফেসবুক কমেন্ট changes এর ভেতরে পাঠায়)
                if (isset($entry['changes'])) {
                    $client = Client::where('page_id', $pageId)->first();
                    
                    if ($client) {
                        foreach ($entry['changes'] as $change) {
                            if (
                                isset($change['field']) && $change['field'] === 'feed' &&
                                isset($change['value']['item']) && $change['value']['item'] === 'comment' &&
                                isset($change['value']['verb']) && $change['value']['verb'] === 'add'
                            ) {
                                $commentData = $change['value'];
                                $senderId = $commentData['from']['id'] ?? null;
                                
                                // যদি পেইজ নিজে রিপ্লাই দেয়, তবে সেটি ইগনোর করব
                                if ($senderId && $senderId != $pageId) {
                                    $commentId = $commentData['comment_id'];
                                    $commentText = $commentData['message'];
                                    $senderName = $commentData['from']['name'] ?? 'Customer';

                                    // FacebookCommentService এ ডাটা পাঠিয়ে দেওয়া
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
                    }
                }
            }

            // আপনার আগের ইনবক্স মেসেজ হ্যান্ডেল করার সার্ভিস (এটি entry -> messaging এর জন্য কাজ করবে)
            return $messengerService->processPayload($request);
        }

        return response('EVENT_RECEIVED', 200);
    }
}