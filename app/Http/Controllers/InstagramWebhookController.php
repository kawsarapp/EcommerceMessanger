<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\OrderSession;
use App\Services\ChatbotService;
use App\Services\NotificationService;
use App\Services\InstagramCommentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Instagram Webhook Controller
 * 
 * Handles:
 *  - Instagram Direct Messages (DM)
 *  - Instagram Story Replies / Mentions
 *  - Instagram Post Comments
 * 
 * Meta sends all Instagram events through the Facebook webhook
 * (same /api/webhook endpoint, object = 'instagram').
 */
class InstagramWebhookController extends Controller
{
    protected ChatbotService $chatbot;
    protected NotificationService $notificationService;
    protected InstagramCommentService $commentService;

    public function __construct(
        ChatbotService $chatbot,
        NotificationService $notificationService,
        InstagramCommentService $commentService
    ) {
        $this->chatbot           = $chatbot;
        $this->notificationService = $notificationService;
        $this->commentService    = $commentService;
    }

    /**
     * Main entry point — called from WebhookController when object = 'instagram'
     */
    public function process(Request $request): \Illuminate\Http\Response
    {
        $data = $request->all();
        Log::info('📸 Instagram Webhook Received', ['object' => $data['object'] ?? '?', 'entries' => count($data['entry'] ?? [])]);

        foreach ($data['entry'] ?? [] as $entry) {
            $igAccountId = $entry['id'];

            // ── Resolve Client ────────────────────────────────────────────────
            $client = Client::where('ig_account_id', $igAccountId)
                ->orWhere('instagram_page_id', $igAccountId)
                ->orWhere('fb_page_id', $igAccountId)
                ->first();

            if (!$client) {
                Log::warning("❌ Instagram: No client found for account ID: {$igAccountId}");
                continue;
            }

            // ── 1. Handle Direct Messages (messaging) ─────────────────────────
            foreach ($entry['messaging'] ?? [] as $event) {
                $this->handleDirectMessage($event, $client, $igAccountId);
            }

            // ── 2. Handle Comments & Story Mentions (changes) ─────────────────
            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                match ($field) {
                    'comments'        => $this->handlePostComment($value, $client, $igAccountId),
                    'story_insights'  => null, // insight only, no reply needed
                    'mentions'        => $this->handleMention($value, $client, $igAccountId),
                    default           => Log::info("📸 Instagram change ignored: {$field}"),
                };
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 1. DIRECT MESSAGE HANDLER
    // ═══════════════════════════════════════════════════════════════════════════
    private function handleDirectMessage(array $event, Client $client, string $igAccountId): void
    {
        $senderId = $event['sender']['id'] ?? null;

        // Ignore echoed messages (sent by the page itself)
        if (!$senderId || $senderId === $igAccountId) return;

        // Extract message content
        $messageText  = $event['message']['text'] ?? '';
        $attachments  = $event['message']['attachments'] ?? [];
        $storyReply   = $event['message']['reply_to'] ?? null; // Story reply
        $isDeleted    = $event['message']['is_deleted'] ?? false;
        $isUnsent     = isset($event['message']['is_unsent']) ? true : false;

        // Skip deleted/unsent messages
        if ($isDeleted || $isUnsent) return;

        // ── Download attachment if any ────────────────────────────────────────
        $attachmentUrl = null;
        if (!empty($attachments)) {
            $firstAttachment = $attachments[0];
            $type            = $firstAttachment['type'] ?? 'unknown';
            $payloadUrl      = $firstAttachment['payload']['url'] ?? null;

            if ($payloadUrl) {
                // Save image/audio to storage for chatbot to process
                try {
                    /** @var \Illuminate\Http\Client\Response $response */
                    $response = Http::timeout(20)->get($payloadUrl);
                    if ($response->ok()) {
                        $ext      = match ($type) { 'image' => 'jpg', 'audio' => 'ogg', 'video' => 'mp4', default => 'bin' };
                        $path     = "chat_attachments/ig_{$senderId}_" . uniqid() . ".{$ext}";
                        Storage::disk('public')->put($path, $response->body());
                        $attachmentUrl = asset('storage/' . $path);
                    }
                } catch (\Exception $e) {
                    Log::warning("📸 Instagram attachment download failed: " . $e->getMessage());
                }

                // If no text, describe what was received
                if (empty($messageText)) {
                    $messageText = match ($type) {
                        'image' => '[Customer sent an image]',
                        'audio' => '[Customer sent a voice note]',
                        'video' => '[Customer sent a video]',
                        default => '[Customer sent an attachment]',
                    };
                }
            }
        }

        // ── Handle Story Reply ────────────────────────────────────────────────
        if ($storyReply && empty($messageText)) {
            $messageText = '[Customer replied to your story]';
        }

        if (empty($messageText)) return;

        Log::info("📸 Instagram DM | Shop: {$client->shop_name} | Sender: {$senderId} | Msg: " . substr($messageText, 0, 80));

        // ── Manage Order Session (Human Agent check) ──────────────────────────
        try {
            $session = OrderSession::firstOrCreate(
                ['client_id' => $client->id, 'sender_id' => $senderId],
                ['is_human_agent_active' => false, 'customer_info' => ['history' => []]]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $session = OrderSession::where('client_id', $client->id)->where('sender_id', $senderId)->first();
        }

        // If human agent has taken over, AI stays silent
        if ($session?->is_human_agent_active) {
            Log::info("🤝 Instagram: Human agent active for {$senderId}, AI skipping.");
            return;
        }

        // ── Run AI ────────────────────────────────────────────────────────────
        try {
            $aiReply = $this->chatbot->handleMessage($client, $senderId, $messageText, $attachmentUrl);

            if (empty($aiReply)) return;

            // ── Extract ATTACH_IMAGE / IMAGE tags (send as real DM images) ────
            $outgoingImages = [];

            if (preg_match_all('/\[ATTACH_IMAGE:\s*(https?:\/\/[^\]]+)\]/i', $aiReply, $imgMatches)) {
                foreach ($imgMatches[1] as $imgUrl) $outgoingImages[] = trim($imgUrl);
                $aiReply = preg_replace('/\[ATTACH_IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
            }
            if (preg_match_all('/\[IMAGE:\s*(https?:\/\/[^\]]+)\]/i', $aiReply, $imgMatches)) {
                foreach ($imgMatches[1] as $imgUrl) $outgoingImages[] = trim($imgUrl);
                $aiReply = preg_replace('/\[IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
            }
            // Also catch bare image URLs in reply
            if (empty($outgoingImages) && preg_match_all('/(https?:\/\/[^\s]+?\.(?:jpg|jpeg|png|gif|webp))/i', $aiReply, $rawMatches)) {
                foreach ($rawMatches[1] as $imgUrl) { $outgoingImages[] = trim($imgUrl); $aiReply = str_replace($imgUrl, '', $aiReply); }
            }

            $cleanReply = trim(preg_replace('/\[CAROUSEL:[^\]]+\]/i', '', $aiReply));

            // ── Send text reply first ─────────────────────────────────────────
            if (!empty($cleanReply)) {
                $this->sendInstagramDM($client, $senderId, $cleanReply);
            }

            // ── Send image attachments via Graph API ──────────────────────────
            foreach ($outgoingImages as $imgUrl) {
                $this->sendInstagramImageDM($client, $senderId, $imgUrl);
            }

            // ── Log conversation ──────────────────────────────────────────────
            Conversation::create([
                'client_id'      => $client->id,
                'sender_id'      => $senderId,
                'platform'       => 'instagram',
                'user_message'   => $messageText,
                'bot_response'   => $cleanReply,
                'attachment_url' => $attachmentUrl,
                'status'         => 'success',
            ]);

            Log::info("✅ Instagram DM Sent | To: {$senderId} | Images: " . count($outgoingImages) . " | " . substr($cleanReply, 0, 80));

        } catch (\Exception $e) {
            Log::error("❌ Instagram AI Error for client #{$client->id}: " . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 2. POST COMMENT HANDLER
    // ═══════════════════════════════════════════════════════════════════════════
    private function handlePostComment(array $commentData, Client $client, string $igAccountId): void
    {
        $senderId = $commentData['from']['id'] ?? null;

        // Ignore own comments
        if (!$senderId || $senderId === $igAccountId) return;

        $commentId   = $commentData['id'] ?? null;
        $commentText = $commentData['text'] ?? '';
        $senderName  = $commentData['from']['username'] ?? 'Customer';

        if (empty($commentId) || empty($commentText)) return;

        Log::info("💬 Instagram Comment | Shop: {$client->shop_name} | @{$senderName}: {$commentText}");

        try {
            $this->commentService->handleComment(
                $client->id,
                $commentId,
                $commentText,
                $senderId,
                $senderName
            );
        } catch (\Exception $e) {
            Log::error("❌ Instagram Comment Service error: " . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 3. MENTION HANDLER (when someone @mentions the shop)
    // ═══════════════════════════════════════════════════════════════════════════
    private function handleMention(array $mentionData, Client $client, string $igAccountId): void
    {
        $senderId = $mentionData['sender']['id'] ?? null;
        if (!$senderId) return;

        Log::info("📌 Instagram Mention | Shop: {$client->shop_name} | Sender: {$senderId}");

        // Send a polite thank-you DM when someone mentions the shop
        try {
            $this->sendInstagramDM(
                $client,
                $senderId,
                "আপনাকে অনেক ধন্যবাদ আমাদের mention করার জন্য! 🙏 কোনো প্রশ্ন থাকলে এখানেই জানান।"
            );
        } catch (\Exception $e) {
            Log::error("❌ Instagram Mention reply error: " . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SEND INSTAGRAM DM via Graph API
    // ═══════════════════════════════════════════════════════════════════════════
    private function sendInstagramDM(Client $client, string $recipientId, string $message): void
    {
        if (empty($client->fb_page_token)) {
            Log::warning("📸 Instagram: No fb_page_token for client #{$client->id}");
            return;
        }

        // Send via Graph API
        $url  = "https://graph.facebook.com/v22.0/me/messages?access_token={$client->fb_page_token}";
        $body = json_encode([
            'recipient'      => ['id' => $recipientId],
            'message'        => ['text' => $message],
            'messaging_type' => 'RESPONSE',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            Log::error("❌ Instagram DM send failed | Recipient: {$recipientId} | HTTP: {$httpCode} | Response: {$result}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SEND IMAGE ATTACHMENT via Instagram DM
    // ═══════════════════════════════════════════════════════════════════════════
    private function sendInstagramImageDM(Client $client, string $recipientId, string $imageUrl): void
    {
        if (empty($client->fb_page_token) || empty($imageUrl)) return;

        $url  = "https://graph.facebook.com/v22.0/me/messages?access_token={$client->fb_page_token}";
        $body = json_encode([
            'recipient'      => ['id' => $recipientId],
            'message'        => [
                'attachment' => [
                    'type'    => 'image',
                    'payload' => [
                        'url'         => $imageUrl,
                        'is_reusable' => true,
                    ],
                ],
            ],
            'messaging_type' => 'RESPONSE',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            Log::error("❌ Instagram Image DM failed | Recipient: {$recipientId} | HTTP: {$httpCode} | Response: {$result}");
        } else {
            Log::info("✅ Instagram Image Sent | Recipient: {$recipientId} | URL: " . substr($imageUrl, 0, 80));
        }
    }
}