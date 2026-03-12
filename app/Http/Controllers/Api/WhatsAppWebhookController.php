<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\OrderSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\ChatbotService;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    protected $chatbot;

    public function __construct(ChatbotService $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function updateStatus(Request $request)
    {
        Log::info("WA Status Update Received", $request->all());
        $instanceId = $request->instance_id;
        $status = $request->status;
        $client = Client::where('wa_instance_id', $instanceId)->first();
        
        if ($client) {
            $client->update(['wa_status' => $status]);
            Log::info("WA Status Updated for Client {$client->id}: {$status}");
            return response()->json(['success' => true]);
        }
        
        Log::warning("WA Status Update Failed - Instance ID {$instanceId} not found.");
        return response()->json(['success' => false], 404);
    }

    public function receiveMessage(Request $request)
    {
        Log::info("========================================");
        Log::info("WA Webhook - Incoming Request: ", $request->all());
        
        $instanceId = $request->instance_id;
        $senderPhone = $request->from;
        $messageBody = $request->body;
        $senderName = $request->sender_name ?? 'Customer';
        $attachmentBase64 = $request->attachment; 

        // ১. ক্লায়েন্ট চেক করা
        $client = Client::where('wa_instance_id', $instanceId)->where('is_whatsapp_active', true)->first();
        if (!$client) {
            Log::error("WA Webhook - Client not found or WhatsApp inactive for instance: {$instanceId}");
            return response()->json(['success' => false, 'message' => 'Bot is offline']);
        }

        // ২. মিডিয়া ফাইল প্রসেস করা
        $attachmentUrl = null;
        if ($attachmentBase64) {
            try {
                Log::info("WA Webhook - Processing Attachment for {$senderPhone}");
                if (preg_match('/^data:([^;]+);base64,(.+)$/', $attachmentBase64, $matches)) {
                    $mimeType = $matches[1];
                    $base64Data = $matches[2];
                    
                    $extension = 'file';
                    if (str_contains($mimeType, 'image')) $extension = 'jpg';
                    elseif (str_contains($mimeType, 'video')) $extension = 'mp4';
                    elseif (str_contains($mimeType, 'audio')) $extension = 'ogg'; 
                    else $extension = 'bin';

                    $fileName = 'wa_' . time() . '_' . uniqid() . '.' . $extension;
                    $filePath = 'chat_attachments/' . $fileName;
                    Storage::disk('public')->put($filePath, base64_decode($base64Data));
                    $attachmentUrl = asset('storage/' . $filePath);
                    
                    if(empty($messageBody) || str_starts_with($messageBody, '[Received a')) {
                        $messageBody = "[User sent an attachment]"; 
                    }
                    Log::info("WA Webhook - Attachment saved: {$attachmentUrl}");
                }
            } catch (\Exception $e) {
                Log::error("WA Webhook - Attachment Processing Error: " . $e->getMessage());
            }
        }

        // ৩. ইউজারের মেসেজ ডাটাবেসে সেভ করা
        try {
            $conversation = Conversation::create([
                'client_id' => $client->id,
                'sender_id' => $senderPhone,
                'platform' => 'whatsapp',
                'user_message' => $messageBody,
                'attachment_url' => $attachmentUrl,
                'metadata' => ['sender_name' => $senderName]
            ]);
            Log::info("WA Webhook - Conversation Logged. ID: {$conversation->id}");
        } catch (\Exception $e) {
            Log::error("WA Webhook - Conversation Save Error: " . $e->getMessage());
        }

        // ৪. সেশন ম্যানেজমেন্ট (🔥 PERFECT ISOLATION FOR MULTI-VENDOR)
        try {
            Log::info("WA Webhook - Checking Session for Shop ID: {$client->id}, Phone: {$senderPhone}");
            
            // Ekhon shudhu phone number diye noy, Shop ID + Phone Number diye khujbe
            $session = OrderSession::where('client_id', $client->id)
                                   ->where('sender_id', $senderPhone)
                                   ->first();
            
            if (!$session) {
                // Oi nirdishto shop e jodi ei customer er session na thake, tahole notun toiri korbe
                $session = OrderSession::create([
                    'client_id' => $client->id,
                    'sender_id' => $senderPhone,
                    'is_human_agent_active' => false,
                    'customer_info' => ['history' => []] // Ekdom faka history diye shuru hobe
                ]);
                Log::info("WA Webhook - New Session Created for Shop ID: {$client->id}, Phone: {$senderPhone}");
            }
            
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Eke bare same millisecond e 2ta msg asle crash thekate
            $session = OrderSession::where('client_id', $client->id)->where('sender_id', $senderPhone)->first();
        } catch (\Exception $e) {
            Log::error("WA Webhook - Session Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Database Session Error']);
        }
        // ৫. Human Mode Check
        if ($session && $session->is_human_agent_active) {
            Log::info("WA Webhook - Human mode active for {$senderPhone}. AI Skipped.");
            return response()->json(['success' => true, 'message' => 'Human mode active.']);
        }

        // ==========================================
        // 🤖 ৬. Advanced AI Processing (ChatbotService)
        // ==========================================
        try {
            Log::info("WA Webhook - Sending to ChatbotService...");
            $aiReply = $this->chatbot->handleMessage($client, $senderPhone, $messageBody, $attachmentUrl);
            Log::info("WA Webhook - AI Reply Raw: " . $aiReply);

            if ($aiReply) {
                $outgoingImages = [];

                // Extract Image Links
                if (preg_match_all('/\[IMAGE:\s*(https?:\/\/[^\]]+)\]/i', $aiReply, $imgMatches)) {
                    foreach ($imgMatches[1] as $imgUrl) {
                        $outgoingImages[] = trim($imgUrl);
                    }
                    $aiReply = preg_replace('/[0-9]+\.?\s*\[IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
                    $aiReply = preg_replace('/-\s*\[IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
                    $aiReply = preg_replace('/\[IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
                }

                // Remove Quick Replies & Carousels
                $aiReply = preg_replace('/\[CAROUSEL:\s*([^\]]+)\]/i', '', $aiReply);
                $aiReply = preg_replace('/\[QUICK_REPLIES:\s*([^\]]+)\]/i', '', $aiReply);
                
                $aiReply = trim($aiReply);

                // Send Text Message to Node.js Server
                if (!empty($aiReply)) {
                    Log::info("WA Webhook - Sending Text to Node.js for {$senderPhone}");
                    $textResponse = Http::post('http://127.0.0.1:3001/api/send-message', [
                        'instance_id' => $instanceId,
                        'to' => $senderPhone,
                        'message' => $aiReply
                    ]);
                    Log::info("WA Webhook - Node.js Text Send Status: " . $textResponse->status());
                }

                // Send Images to Node.js Server
                foreach ($outgoingImages as $index => $imgUrl) {
                    try {
                        Log::info("WA Webhook - Fetching Image {$index} from URL: {$imgUrl}");
                        $imageContent = file_get_contents($imgUrl);
                        
                        if ($imageContent !== false) {
                            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageContent);
                            $base64Image = base64_encode($imageContent);
                            
                            Log::info("WA Webhook - Sending Image {$index} to Node.js");
                            $imgResponse = Http::post('http://127.0.0.1:3001/api/send-message', [
                                'instance_id' => $instanceId,
                                'to' => $senderPhone,
                                'message' => '', 
                                'media' => [
                                    'mimetype' => $mimeType,
                                    'data' => $base64Image,
                                    'filename' => 'product_image_' . $index
                                ]
                            ]);
                            Log::info("WA Webhook - Node.js Image Send Status: " . $imgResponse->status());
                        }
                    } catch (\Exception $imgEx) {
                        Log::error("WA Webhook - Image Send Error: " . $imgEx->getMessage());
                    }
                }

                // Update Conversation Record with Bot Reply
                if (isset($conversation)) {
                    $conversation->update(['bot_response' => $aiReply]);
                }
            }
        } catch (\Exception $e) {
            Log::error("WA Webhook - AI / ChatbotService Error: " . $e->getMessage() . " in " . $e->getFile() . " at line " . $e->getLine());
        }

        Log::info("WA Webhook - Execution Completed for {$senderPhone}");
        Log::info("========================================");
        
        return response()->json(['success' => true]);
    }
}