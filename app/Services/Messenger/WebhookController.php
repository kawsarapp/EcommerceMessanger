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
     * (ফেসবুক যখন প্রথমবার আপনার ইউআরএল ভেরিফাই করবে)
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
     * 2. Handle Incoming Messages (Clean & Short Controller)
     */
    public function handle(Request $request, MessengerWebhookService $messengerService)
    {
        $data = $request->all();

        // 1. OMNICHANNEL ROUTING (Instagram)
        if (($data['object'] ?? '') === 'instagram') {
            return app(InstagramWebhookController::class)->process($request);
        }

        // 2. FACEBOOK MESSENGER LOGIC (Passed to Service)
        if (($data['object'] ?? '') === 'page') {
            return $messengerService->processPayload($request);
        }

        return response('EVENT_RECEIVED', 200);
    }
}