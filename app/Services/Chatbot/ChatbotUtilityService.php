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


    // 🔥 NEW: Google Cloud Vision API Integration
    public function analyzeImageWithGoogleVision($base64Image) {
        $apiKey = env('GOOGLE_VISION_API_KEY');
        if (!$apiKey) return null;

        try {
            // Base64 স্ট্রিংয়ের শুরুতে 'data:image/jpeg;base64,' থাকলে তা রিমুভ করতে হবে
            $pureBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);

            $response = Http::post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
                'requests' => [
                    [
                        'image' => ['content' => $pureBase64],
                        'features' => [
                            ['type' => 'LABEL_DETECTION', 'maxResults' => 6], // কী ধরনের প্রোডাক্ট (T-shirt, Dress)
                            ['type' => 'OBJECT_LOCALIZATION', 'maxResults' => 3], // মূল অবজেক্ট
                            ['type' => 'IMAGE_PROPERTIES', 'maxResults' => 1] // ছবির কালার
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $labels = collect($response->json('responses.0.labelAnnotations', []))->pluck('description')->toArray();
                $objects = collect($response->json('responses.0.localizedObjectAnnotations', []))->pluck('name')->toArray();
                
                $tags = array_unique(array_merge($labels, $objects));
                $tagString = implode(', ', $tags);
                
                Log::info("👁️ Google Vision Tags: " . $tagString);
                return $tagString;
            }
        } catch (\Exception $e) {
            Log::error("Google Vision API Error: " . $e->getMessage());
        }
        return null;
    }
}