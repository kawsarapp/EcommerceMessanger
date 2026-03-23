<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\OrderSession;
use App\Services\ChatbotService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Widget Chat Controller
 * 
 * Handles real-time AI chat for embedded chatbot widgets on external websites.
 * Endpoint: POST /api/v1/chat/widget
 * 
 * Authentication: X-Api-Key header or ?api_key= query param
 * SaaS: Multi-tenant — each client's API key routes to their own product data & AI config.
 */
class WidgetChatController extends Controller
{
    protected ChatbotService $chatbot;

    public function __construct(ChatbotService $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function handle(Request $request)
    {
        // ─── 0. CORS — Allow any external website to call this endpoint ───────────
        $corsHeaders = [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Api-Key, Authorization, Accept',
        ];

        // Handle browser OPTIONS preflight request
        if ($request->isMethod('OPTIONS')) {
            return response()->json('OK', 200, $corsHeaders);
        }


        $apiKey = $request->header('X-Api-Key')
            ?? $request->bearerToken()
            ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json(['error' => 'API Key is required.'], 401, $corsHeaders);
        }

        /** @var Client|null $client */
        $client = Cache::remember("client_by_api_key_{$apiKey}", 300, function () use ($apiKey) {
            return Client::where('api_token', $apiKey)->first();
        });

        if (!$client) {
            return response()->json(['error' => 'Invalid API Key.'], 401, $corsHeaders);
        }

        // ─── 2. Domain Whitelist Security Check ──────────────────────────────────
        // If client has set allowed_domains, only those domains can use this key.
        // This prevents others from stealing the key and embedding on their sites.
        $allowedDomains = $client->widget_allowed_domains ?? null;
        if (!empty($allowedDomains)) {
            $origin = $request->header('Origin') ?? $request->header('Referer') ?? '';
            // Normalize: strip protocol, www, trailing slashes
            $originHost = preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($origin, '/'));
            $originHost = explode('/', $originHost)[0]; // strip path

            $allowed = array_map(
                fn($d) => strtolower(trim(preg_replace('/^https?:\/\/(www\.)?/', '', $d))),
                explode(',', $allowedDomains)
            );
            $allowed = array_filter($allowed);

            if (!empty($allowed) && !in_array(strtolower($originHost), $allowed)) {
                Log::warning("Widget blocked for domain: {$originHost} (client #{$client->id})");
                return response()->json([
                    'error' => 'This API key is not authorized for this domain.'
                ], 403, array_merge($corsHeaders, [
                    'Access-Control-Allow-Origin' => $origin ?: '*',
                ]));
            }
        }

        if (!$client->hasActivePlan()) {
            return response()->json(['error' => 'Your plan has expired. Please renew to continue using the chatbot.'], 403, $corsHeaders);
        }

        if (!$client->is_ai_enabled) {
            return response()->json(['error' => 'AI Chatbot is disabled for this shop.'], 403, $corsHeaders);
        }


        // ─── 3. Validate Request ─────────────────────────────────────────────────
        $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'required|string|max:100',
        ]);

        $message   = trim($request->input('message'));
        $sessionId = $request->input('session_id');

        // ─── 4. Process with Chatbot Service ─────────────────────────────────────
        try {
            $senderId = 'widget_' . $sessionId;

            // ✅ Ensure OrderSession has platform='widget' so it appears in Conversations dashboard
            \App\Models\OrderSession::where('sender_id', $senderId)
                ->where('client_id', $client->id)
                ->update(['platform' => 'widget', 'last_interacted_at' => now()]);

            // Use session_id as the "sender_id" so conversation continues per visitor
            $reply = $this->chatbot->handleMessage($client, $senderId, $message, null);

            // Strip any image tags from widget reply (widgets don't support inline images easily)
            $reply = preg_replace('/\[ATTACH_IMAGE:[^\]]+\]/i', '', $reply ?? '');
            $reply = preg_replace('/\[IMAGE:[^\]]+\]/i', '', $reply);
            $reply = preg_replace('/\[CAROUSEL:[^\]]+\]/i', '', $reply);
            $reply = trim($reply);

            if (empty($reply)) {
                $reply = 'দুঃখিত, এই মুহূর্তে সাড়া দিতে পারছি না। একটু পরে চেষ্টা করুন।';
            }

            return response()->json(['reply' => $reply], 200, $corsHeaders);

        } catch (\Exception $e) {
            Log::error("WidgetChat Error for client #{$client->id}: " . $e->getMessage());
            return response()->json([
                'reply' => 'সাময়িক সমস্যা হচ্ছে। একটু পরে আবার চেষ্টা করুন।',
            ], 200, $corsHeaders);
        }
    }

    /**
     * GET /api/v1/chat/widget/poll?api_key=XXX&session_id=YYY&since=TIMESTAMP
     * Widget polls this every 4 seconds to receive seller (human agent) replies.
     */
    public function poll(Request $request)
    {
        $cors = [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Api-Key, Authorization, Accept',
        ];

        $apiKey    = $request->header('X-Api-Key') ?? $request->query('api_key');
        $sessionId = $request->query('session_id');
        $since     = (int) $request->query('since', 0); // Unix timestamp

        if (!$apiKey || !$sessionId) {
            return response()->json(['messages' => []], 200, $cors);
        }

        $client = \Illuminate\Support\Facades\Cache::remember("client_by_api_key_{$apiKey}", 300, function () use ($apiKey) {
            return \App\Models\Client::where('api_token', $apiKey)->first();
        });

        if (!$client) {
            return response()->json(['messages' => []], 200, $cors);
        }

        $session = \App\Models\OrderSession::where('sender_id', 'widget_' . $sessionId)
            ->where('client_id', $client->id)
            ->first();

        if (!$session) {
            return response()->json(['messages' => [], 'human_agent' => false], 200, $cors);
        }

        $history  = $session->customer_info['history'] ?? [];
        $newMsgs  = [];

        // Return only seller messages newer than last poll
        foreach ($history as $msg) {
            if (($msg['role'] ?? '') === 'seller' && ($msg['time'] ?? 0) > $since) {
                $newMsgs[] = [
                    'text' => $msg['ai'],
                    'time' => $msg['time'],
                    'role' => 'seller',
                ];
            }
        }

        return response()->json([
            'messages'     => $newMsgs,
            'human_agent'  => (bool) $session->is_human_agent_active,
            'server_time'  => time(),
        ], 200, $cors);
    }
}

