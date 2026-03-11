<?php
namespace App\Services\Chatbot;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotUtilityService
{
    public function lookupOrderByPhone($clientId, $message)
    {
        // এই ফাংশনটি এখন আর আলাদা করে দরকার নেই, কারণ AI নিজে থেকেই উত্তর দিবে। 
        // তবে সেফটির জন্য এটি রেখে দেওয়া হলো।
        return null;
    }
    
    public function isTrackingIntent($msg) {
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অবস্থা', 'কবে পাব', 'tracking', 'order kobe', 'parsel', 'parcel'];
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos(mb_strtolower($msg), $kw) !== false) return true;
        }

        // 🔥 FIX: কাস্টমার যদি মেসেজে শুধু ফোন নাম্বার দেয়, তবে সেটাকেও ট্র্যাকিং হিসেবে ধরবে
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $cleanMsg = trim(str_replace($bn, $en, $msg));
        
        if (preg_match('/^01[3-9]\d{8}$/', $cleanMsg)) {
            return true;
        }

        return false;
    }

    public function callLlmChain($messages) {
        try {
            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            $response = Http::withToken($apiKey)->timeout(40)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'max_tokens' => 600, 
                'temperature' => 0.0, // 🔥 Zero Hallucination Mode 
            ]);
            return $response->json()['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error("LLM Error: " . $e->getMessage());
            return null;
        }
    }
}