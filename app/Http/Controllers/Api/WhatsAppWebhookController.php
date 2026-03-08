<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\OrderSession;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
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

        // ৩. ইউজারের মেসেজ ডাটাবেসে সেভ করা
        Conversation::create([
            'client_id' => $client->id,
            'sender_id' => $senderPhone,
            'platform' => 'whatsapp',
            'user_message' => $messageBody,
            'attachment_url' => $attachmentUrl,
            'metadata' => ['sender_name' => $senderName]
        ]);

        // ৪. সেশন এবং হিস্ট্রি ম্যানেজমেন্ট (Human/AI Mode Check)
        $session = OrderSession::firstOrCreate(
            ['client_id' => $client->id, 'sender_id' => $senderPhone],
            ['is_human_agent_active' => false, 'customer_info' => ['history' => []]]
        );

        $customerInfo = $session->customer_info ?? ['history' => []];
        $history = $customerInfo['history'] ?? [];
        $history[] = ['user' => $messageBody, 'ai' => null, 'time' => time()];

        // যদি Human Mode অন থাকে, তাহলে AI রিপ্লাই দিবে না!
        if ($session->is_human_agent_active) {
            $customerInfo['history'] = array_slice($history, -20);
            $session->update(['customer_info' => $customerInfo]);
            return response()->json(['success' => true, 'message' => 'Human mode active. AI skipped.']);
        }

        // ==========================================
        // 🤖 ৫. AI Chatbot Logic (Auto Reply)
        // ==========================================
        if (env('OPENAI_API_KEY')) {
            try {
                // দোকানের প্রোডাক্টের লিস্ট আনা
                $products = Product::where('client_id', $client->id)->where('stock_status', 'in_stock')->limit(10)->get();
                $productDetails = "";
                foreach($products as $p) {
                    $link = url('/shop/'.$client->slug.'/product/'.$p->slug);
                    $productDetails .= "- {$p->name} (Price: {$p->sale_price}TK). Link: {$link}\n";
                }

                // AI এর ব্রেইন তৈরি করা (System Prompt)
                $systemPrompt = "You are a helpful sales assistant for '{$client->shop_name}'. Reply briefly in friendly Bengali.\n";
                if ($client->custom_prompt) $systemPrompt .= "Persona: {$client->custom_prompt}\n";
                if ($client->knowledge_base) $systemPrompt .= "Rules/FAQs:\n{$client->knowledge_base}\n";
                if ($productDetails) $systemPrompt .= "Available Products:\n{$productDetails}\n(Recommend products from this list with their exact links if customer asks).";

                // চ্যাট হিস্ট্রি সাজানো
                $messages = [['role' => 'system', 'content' => $systemPrompt]];
                foreach (array_slice($history, -5) as $h) { // শেষের ৫টি মেসেজ মনে রাখবে
                    if ($h['user']) $messages[] = ['role' => 'user', 'content' => $h['user']];
                    if ($h['ai']) $messages[] = ['role' => 'assistant', 'content' => $h['ai']];
                }

                // OpenAI API কল
                $response = Http::withToken(env('OPENAI_API_KEY'))
                    ->timeout(15)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'messages' => $messages,
                        'temperature' => 0.7,
                        'max_tokens' => 250,
                    ]);

                if ($response->successful()) {
                    $aiReply = $response->json()['choices'][0]['message']['content'];

                    // 🚀 Node সার্ভারকে মেসেজটি সেন্ড করতে বলা
                    Http::post('http://127.0.0.1:3001/api/send-message', [
                        'instance_id' => $instanceId,
                        'to' => $senderPhone,
                        'message' => $aiReply
                    ]);

                    // ডাটাবেসে AI এর রিপ্লাই সেভ করা
                    Conversation::create([
                        'client_id' => $client->id,
                        'sender_id' => $senderPhone,
                        'platform' => 'whatsapp',
                        'bot_response' => $aiReply
                    ]);

                    // হিস্ট্রি আপডেট করা
                    $history[count($history)-1]['ai'] = $aiReply;
                }
            } catch (\Exception $e) {
                Log::error("AI Webhook Error: " . $e->getMessage());
            }
        }

        // হিস্ট্রি সেভ
        $customerInfo['history'] = array_slice($history, -20);
        $session->update(['customer_info' => $customerInfo]);

        return response()->json(['success' => true]);
    }
}