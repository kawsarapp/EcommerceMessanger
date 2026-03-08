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
use App\Services\ChatbotService; // 🔥 Advanced AI Service Included
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    protected $chatbot;

    // 🔥 কনস্ট্রাক্টরের মাধ্যমে ChatbotService ইনজেক্ট করা হলো
    public function __construct(ChatbotService $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function updateStatus(Request $request)
    {
        $instanceId = $request->instance_id;
        $status = $request->status;
        $client = Client::where('wa_instance_id', $instanceId)->first();
        if ($client) {
            $client->update(['wa_status' => $status]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    public function receiveMessage(Request $request)
    {
        $instanceId = $request->instance_id;
        $senderPhone = $request->from;
        $messageBody = $request->body;
        $senderName = $request->sender_name ?? 'Customer';
        $attachmentBase64 = $request->attachment; 

        // ১. ক্লায়েন্ট চেক করা
        $client = Client::where('wa_instance_id', $instanceId)->where('is_whatsapp_active', true)->first();
        if (!$client) return response()->json(['success' => false, 'message' => 'Bot is offline']);

        // ২. মিডিয়া ফাইল প্রসেস করা
        $attachmentUrl = null;
        if ($attachmentBase64) {
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
            }
        }

        // ৩. ইউজারের মেসেজ ডাটাবেসে সেভ করা (ইনিশিয়াল)
        $conversation = Conversation::create([
            'client_id' => $client->id,
            'sender_id' => $senderPhone,
            'platform' => 'whatsapp',
            'user_message' => $messageBody,
            'attachment_url' => $attachmentUrl,
            'metadata' => ['sender_name' => $senderName]
        ]);

        // ৪. সেশন চেক করা (Human Mode Check)
        $session = OrderSession::firstOrCreate(
            ['client_id' => $client->id, 'sender_id' => $senderPhone],
            ['is_human_agent_active' => false, 'customer_info' => ['history' => []]]
        );

        // যদি Human Mode অন থাকে, তাহলে AI কল হবে না!
        if ($session->is_human_agent_active) {
            return response()->json(['success' => true, 'message' => 'Human mode active. AI skipped.']);
        }

        // ==========================================
        // 🤖 ৫. Advanced AI Processing (ChatbotService)
        // ==========================================
        try {
            // 🔥 মেসেঞ্জারের মতো সরাসরি ChatbotService কে কল করা হলো
            $aiReply = $this->chatbot->handleMessage($client, $senderPhone, $messageBody, $attachmentUrl);

            if ($aiReply) {
                $outgoingImages = [];

                // AI এর রিপ্লাই থেকে ছবির লিংক আলাদা করা (যেমন মেসেঞ্জারে করা হয়েছিল)
                if (preg_match_all('/\[IMAGE:\s*(https?:\/\/[^\]]+)\]/i', $aiReply, $imgMatches)) {
                    foreach ($imgMatches[1] as $imgUrl) {
                        $outgoingImages[] = trim($imgUrl);
                    }
                    $aiReply = preg_replace('/[0-9]+\.?\s*\[IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
                    $aiReply = preg_replace('/-\s*\[IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
                    $aiReply = preg_replace('/\[IMAGE:\s*https?:\/\/[^\]]+\]/i', '', $aiReply);
                }

                // AI এর রিপ্লাই থেকে Quick Replies এবং Carousel ট্যাগ রিমুভ করা (যেহেতু হোয়াটসঅ্যাপে এগুলো বাটন হিসেবে কাজ করে না)
                $aiReply = preg_replace('/\[CAROUSEL:\s*([^\]]+)\]/i', '', $aiReply);
                $aiReply = preg_replace('/\[QUICK_REPLIES:\s*([^\]]+)\]/i', '', $aiReply);
                
                $aiReply = trim($aiReply);

                // 🚀 Node সার্ভারকে মেসেজটি সেন্ড করতে বলা
                if (!empty($aiReply)) {
                    Http::post('http://127.0.0.1:3001/api/send-message', [
                        'instance_id' => $instanceId,
                        'to' => $senderPhone,
                        'message' => $aiReply
                    ]);
                }

                // 🚀 যদি AI ছবির লিংক দিয়ে থাকে, সেগুলোও পরপর পাঠিয়ে দেওয়া
                foreach ($outgoingImages as $imgUrl) {
                    // হোয়াটসঅ্যাপে ছবি পাঠাতে হলে Node.js কে Base64 করে পাঠাতে হয়
                    try {
                        $imageContent = file_get_contents($imgUrl);
                        if ($imageContent !== false) {
                            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageContent);
                            $base64Image = base64_encode($imageContent);
                            
                            Http::post('http://127.0.0.1:3001/api/send-message', [
                                'instance_id' => $instanceId,
                                'to' => $senderPhone,
                                'message' => '', // ছবির সাথে ক্যাপশন দিতে চাইলে এখানে দেওয়া যায়
                                'media' => [
                                    'mimetype' => $mimeType,
                                    'data' => $base64Image,
                                    'filename' => 'product_image'
                                ]
                            ]);
                        }
                    } catch (\Exception $imgEx) {
                        Log::error("WA Image Send Error: " . $imgEx->getMessage());
                    }
                }

                // ডাটাবেসে AI এর রিপ্লাই আপডেট করা
                $conversation->update(['bot_response' => $aiReply]);
            }
        } catch (\Exception $e) {
            Log::error("AI Webhook Error (WA): " . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }
}