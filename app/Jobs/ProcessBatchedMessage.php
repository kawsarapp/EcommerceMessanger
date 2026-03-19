<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\OrderSession;
use App\Services\ChatbotService;
use App\Services\Messenger\MessengerResponseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessBatchedMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries   = 2;
    public int    $timeout = 120;

    public function __construct(
        public int    $clientId,
        public string $senderId,
        public string $platform,       // 'whatsapp' | 'messenger' | 'instagram'
        public string $dispatchedAt,   // microtime(true) when this job was dispatched
        public string $instanceId = '', // WhatsApp only
    ) {}

    public function handle(ChatbotService $chatbot): void
    {
        $tsKey   = "batch_last_ts_{$this->clientId}_{$this->senderId}";
        $msgsKey = "batch_msgs_{$this->clientId}_{$this->senderId}";
        $imgKey  = "batch_img_{$this->clientId}_{$this->senderId}";

        // ── স্টেলনেস চেক: নতুন message এলে অন্য job handle করবে ──────────
        $lastTs = (float) Cache::get($tsKey, 0);
        if ($lastTs > (float) $this->dispatchedAt) {
            Log::info("⏩ BatchJob skipped (stale) | {$this->platform} | Sender: {$this->senderId}");
            return;
        }

        // ── সব pending message একসাথে নাও ────────────────────────────────
        $pendingMsgs = Cache::get($msgsKey, []);
        $attachmentUrl = Cache::get($imgKey);

        if (empty($pendingMsgs) && !$attachmentUrl) {
            return;
        }

        // Cache clear করে দাও (duplicate processing এড়াতে)
        Cache::forget($msgsKey);
        Cache::forget($tsKey);
        Cache::forget($imgKey);

        // Messages join করো — একটা coherent message বানাও
        $combinedMessage = implode(' ', array_filter($pendingMsgs));

        Log::info("🔀 Batched [{$this->platform}] | Sender: {$this->senderId} | Parts: " . count($pendingMsgs) . " | Combined: " . substr($combinedMessage, 0, 100));

        $client = Client::find($this->clientId);
        if (!$client) return;

        try {
            $aiReply = $chatbot->handleMessage($client, $this->senderId, $combinedMessage, $attachmentUrl);
            if (!$aiReply) return;

            match ($this->platform) {
                'whatsapp'  => $this->sendWhatsApp($client, $aiReply, $combinedMessage, $attachmentUrl),
                'messenger' => $this->sendMessenger($client, $aiReply, $combinedMessage, $attachmentUrl),
                'instagram' => $this->sendInstagram($client, $aiReply, $combinedMessage, $attachmentUrl),
                default     => null,
            };

        } catch (\Exception $e) {
            Log::error("❌ BatchJob Error [{$this->platform}] | {$this->senderId}: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WhatsApp send
    // ─────────────────────────────────────────────────────────────────────────
    private function sendWhatsApp(Client $client, string $aiReply, string $userMsg, ?string $attachmentUrl): void
    {
        $outgoingImages = [];

        if (preg_match_all('/\[ATTACH_IMAGE:\s*(https?:\/\/[^\]]+)\]/i', $aiReply, $m)) {
            $outgoingImages = array_map('trim', $m[1]);
            $aiReply = preg_replace('/\[ATTACH_IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
        }
        $aiReply = trim(preg_replace('/\[(IMAGE|CAROUSEL|QUICK_REPLIES):[^\]]+\]/i', '', $aiReply));

        $apiUrl = config('services.whatsapp.api_url') . '/api/send-message';

        if (!empty($aiReply)) {
            Http::post($apiUrl, ['instance_id' => $this->instanceId, 'to' => $this->senderId, 'message' => $aiReply]);
        }

        foreach ($outgoingImages as $imgUrl) {
            $ch = curl_init($imgUrl);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0]);
            $imgData  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($imgData && $httpCode < 400) {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imgData);
                Http::post($apiUrl, ['instance_id' => $this->instanceId, 'to' => $this->senderId, 'message' => '', 'media' => ['mimetype' => $mime, 'data' => base64_encode($imgData), 'filename' => 'product']]);
            } else {
                Http::post($apiUrl, ['instance_id' => $this->instanceId, 'to' => $this->senderId, 'message' => "📸 " . $imgUrl]);
            }
        }

        Conversation::create(['client_id' => $this->clientId, 'sender_id' => $this->senderId, 'platform' => 'whatsapp', 'user_message' => $userMsg, 'bot_response' => $aiReply, 'attachment_url' => $attachmentUrl]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Messenger send
    // ─────────────────────────────────────────────────────────────────────────
    private function sendMessenger(Client $client, string $aiReply, string $userMsg, ?string $attachmentUrl): void
    {
        $responseService = app(MessengerResponseService::class);

        $outgoingImages = [];
        if (preg_match_all('/\[ATTACH_IMAGE:\s*(https?:\/\/[^\]]+)\]/i', $aiReply, $m)) {
            $outgoingImages = array_map('trim', $m[1]);
            $aiReply = preg_replace('/\[ATTACH_IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
        }
        $aiReply = trim(preg_replace('/\[(IMAGE|CAROUSEL|QUICK_REPLIES):[^\]]+\]/i', '', $aiReply));

        if (!empty($aiReply)) {
            $responseService->sendMessengerMessage($this->senderId, $aiReply, $client->fb_page_token);
        }
        foreach ($outgoingImages as $imgUrl) {
            $responseService->sendMessengerMessage($this->senderId, '', $client->fb_page_token, $imgUrl);
        }

        $responseService->logConversation($this->clientId, $this->senderId, $userMsg, $aiReply, $attachmentUrl);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Instagram send
    // ─────────────────────────────────────────────────────────────────────────
    private function sendInstagram(Client $client, string $aiReply, string $userMsg, ?string $attachmentUrl): void
    {
        $outgoingImages = [];
        if (preg_match_all('/\[ATTACH_IMAGE:\s*(https?:\/\/[^\]]+)\]/i', $aiReply, $m)) {
            $outgoingImages = array_map('trim', $m[1]);
            $aiReply = preg_replace('/\[ATTACH_IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
        }
        $aiReply = trim(preg_replace('/\[(IMAGE|CAROUSEL|QUICK_REPLIES):[^\]]+\]/i', '', $aiReply));

        $token = $client->fb_page_token;
        $apiUrl = "https://graph.facebook.com/v19.0/me/messages?access_token={$token}";

        $send = function (array $body) use ($apiUrl) {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
            curl_exec($ch);
            curl_close($ch);
        };

        if (!empty($aiReply)) {
            $send(['recipient' => ['id' => $this->senderId], 'message' => ['text' => $aiReply], 'messaging_type' => 'RESPONSE']);
        }
        foreach ($outgoingImages as $imgUrl) {
            $send(['recipient' => ['id' => $this->senderId], 'message' => ['attachment' => ['type' => 'image', 'payload' => ['url' => $imgUrl, 'is_reusable' => true]]], 'messaging_type' => 'RESPONSE']);
        }

        Conversation::create(['client_id' => $this->clientId, 'sender_id' => $this->senderId, 'platform' => 'instagram', 'user_message' => $userMsg, 'bot_response' => $aiReply, 'attachment_url' => $attachmentUrl]);
    }
}
