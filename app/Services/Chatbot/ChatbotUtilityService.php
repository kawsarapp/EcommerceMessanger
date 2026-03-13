<?php
namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotUtilityService
{
    public function lookupOrderByPhone($clientId, $message)
    {
        return null; // AI handle korbe
    }
    
    public function isTrackingIntent($msg) {
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অবস্থা', 'কবে পাব', 'tracking', 'order kobe', 'parsel', 'parcel'];
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos(mb_strtolower($msg), $kw) !== false) return true;
        }

        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $cleanMsg = trim(str_replace($bn, $en, $msg));
        
        if (preg_match('/^01[3-9]\d{8}$/', $cleanMsg)) return true;
        return false;
    }

    public function callLlmChain($messages) {
        $geminiKey = env('GEMINI_API_KEY');
        $openAiKey = env('OPENAI_API_KEY') ?? config('services.openai.api_key');

        // 🔥 STEP 1: Try Gemini API First
        if ($geminiKey) {
            try {
                $systemPrompt = "";
                $geminiContents = [];
                
                // OpenAI format theke Gemini format e convert
                foreach ($messages as $m) {
                    if ($m['role'] === 'system') {
                        $systemPrompt = is_array($m['content']) ? json_encode($m['content']) : $m['content'];
                        continue;
                    }
                    $role = $m['role'] === 'assistant' ? 'model' : 'user';
                    $parts = [];
                    
                    if (is_array($m['content'])) {
                        foreach ($m['content'] as $c) {
                            if ($c['type'] === 'text') $parts[] = ['text' => $c['text']];
                            elseif ($c['type'] === 'image_url') {
                                $url = $c['image_url']['url'];
                                if (preg_match('/^data:(image\/\w+);base64,(.*)$/', $url, $matches)) {
                                    $parts[] = ['inline_data' => ['mime_type' => $matches[1], 'data' => $matches[2]]];
                                }
                            }
                        }
                    } else {
                        $parts[] = ['text' => $m['content']];
                    }
                    $geminiContents[] = ['role' => $role, 'parts' => $parts];
                }

                $geminiPayload = [
                    'system_instruction' => ['parts' => ['text' => $systemPrompt]],
                    'contents' => $geminiContents,
                    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 800]
                ];

                $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}", $geminiPayload);

                if ($response->successful() && isset($response->json()['candidates'][0]['content']['parts'][0]['text'])) {
                    return $response->json()['candidates'][0]['content']['parts'][0]['text'];
                }
                Log::warning("Gemini API skipped/failed. Response: " . $response->body());
            } catch (\Exception $e) {
                Log::warning("Gemini API Error, falling back to OpenAI: " . $e->getMessage());
            }
        }

        // 🔥 STEP 2: Fallback to ChatGPT if Gemini fails or Key is missing
        if ($openAiKey) {
            try {
                $response = Http::withToken($openAiKey)->timeout(40)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'max_tokens' => 800, 
                    'temperature' => 0.1, 
                ]);
                return $response->json()['choices'][0]['message']['content'] ?? null;
            } catch (\Exception $e) {
                Log::error("OpenAI Fallback Error: " . $e->getMessage());
            }
        }

        return null;
    }

    public function analyzeImageWithGoogleVision($base64Image) {
        $apiKey = env('GOOGLE_VISION_API_KEY');
        if (!$apiKey) return null;

        try {
            $pureBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);

            $response = Http::post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
                'requests' => [
                    [
                        'image' => ['content' => $pureBase64],
                        'features' => [
                            ['type' => 'TEXT_DETECTION', 'maxResults' => 1], // SKU check
                            ['type' => 'LABEL_DETECTION', 'maxResults' => 5], // Visual check
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = [];
                $text = $response->json('responses.0.textAnnotations.0.description');
                if ($text) $data['detected_text'] = trim(preg_replace('/\s+/', ' ', $text));
                
                $labels = collect($response->json('responses.0.labelAnnotations', []))->pluck('description')->toArray();
                if (!empty($labels)) $data['visual_tags'] = implode(', ', $labels);
                
                $resultStr = "";
                if (isset($data['detected_text'])) $resultStr .= "ছবির গায়ে লেখা: '{$data['detected_text']}'. ";
                if (isset($data['visual_tags'])) $resultStr .= "ছবির ধরন: {$data['visual_tags']}.";
                
                return trim($resultStr);
            }
        } catch (\Exception $e) {
            Log::error("Google Vision API Error: " . $e->getMessage());
        }
        return null;
    }
}