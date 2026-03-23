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

    public function callLlmChain($messages, $client = null) {
        $selectedModel = $client ? ($client->ai_model ?? 'gemini-pro') : 'gemini-pro';

        // Fetch keys from Client first (if provided), otherwise fallback to .env configuration
        $geminiKey    = ($client->gemini_api_key ?? null) ?: config('services.gemini.api_key');
        $openAiKey    = ($client->openai_api_key ?? null) ?: config('services.openai.api_key');
        $anthropicKey = ($client->claude_api_key ?? null) ?: config('services.anthropic.api_key');
        $deepseekKey  = ($client->deepseek_api_key ?? null) ?: config('services.deepseek.api_key');
        $groqKey      = ($client->groq_api_key ?? null) ?: config('services.groq.api_key', '');
        
        Log::info("🚀 AI Requesting [Model: {$selectedModel}] for client: " . ($client->id ?? 'N/A'));

        // ==========================================
        // 🚀 ROUTE 1: Google Gemini Execution
        // ==========================================
        if (str_contains($selectedModel, 'gemini')) {
            if (!$geminiKey) return "⚠️ Gemini API Key is missing in server configuration.";
            
            try {
                $systemPrompt = "";
                $geminiContents = [];
                
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

                // মডেল নাম ডাইনামিক করা হলো
                $modelIdentifier = match($selectedModel) {
                    'gemini-pro'      => 'gemini-1.5-flash',        // Fast & free
                    'gemini-pro-full' => 'gemini-2.0-flash-lite',   // Latest & powerful (not deprecated)
                    'gemini-2.5-flash'=> 'gemini-2.5-flash',
                    'gemini-2.5-pro'  => 'gemini-2.5-pro',
                    'gemini-2.5-flash-lite'         => 'gemini-2.5-flash-lite',
                    'gemini-3.1-pro-preview'        => 'gemini-3.1-pro-preview',
                    'gemini-3.1-flash-lite-preview' => 'gemini-3.1-flash-lite-preview',
                    'gemini-2.5-flash-image'        => 'gemini-2.5-flash-image',
                    default           => 'gemini-1.5-flash',
                };

                $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelIdentifier}:generateContent?key={$geminiKey}", [
                    'system_instruction' => ['parts' => ['text' => $systemPrompt]],
                    'contents' => $geminiContents,
                    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 800]
                ]);

                if ($response->successful() && isset($response->json()['candidates'][0]['content']['parts'][0]['text'])) {
                    $aiText = $response->json()['candidates'][0]['content']['parts'][0]['text'];
                    Log::info("✅ Gemini Success: " . substr($aiText, 0, 100) . "...");
                    return $aiText;
                }
                Log::warning("Gemini Error: " . $response->body());
                return "Gemini API Temporary Error. Please try again.";
            } catch (\Exception $e) {
                Log::error("Gemini Critical Error: " . $e->getMessage());
                return "Error connecting to AI Server.";
            }
        }

        // ==========================================
        // 🚀 ROUTE 2: OpenAI (ChatGPT) Execution
        // ==========================================
        if (str_contains($selectedModel, 'gpt')) {
            if (!$openAiKey) return "⚠️ OpenAI API Key is missing in server configuration.";
            
            try {
                $response = Http::withToken($openAiKey)->timeout(40)->post('https://api.openai.com/v1/chat/completions', [
                    // ডাইনামিক GPT মডেল
                    'model' => $selectedModel, 
                    'messages' => $messages,
                    'max_tokens' => 800, 
                    'temperature' => 0.1, 
                ]);
                
                if ($response->successful() && isset($response->json()['choices'][0]['message']['content'])) {
                    $aiText = $response->json()['choices'][0]['message']['content'];
                    Log::info("✅ OpenAI Success: " . substr($aiText, 0, 100) . "...");
                    return $aiText;
                }
                Log::warning("OpenAI Error: " . $response->body());
                return "OpenAI API returned an error.";
            } catch (\Exception $e) {
                Log::error("OpenAI Exception: " . $e->getMessage());
                return "OpenAI Connection failed.";
            }
        }

        // ==========================================
        // 🚀 ROUTE 3: Anthropic (Claude) Execution
        // ==========================================
        if (str_contains($selectedModel, 'claude')) {
            if (!$anthropicKey) return "⚠️ Claude API Key is missing in server configuration.";
            
            try {
                // Claude এর System prompt আলাদা হ্যান্ডেল করতে হয়
                $systemPrompt = "";
                $claudeMessages = [];
                
                foreach ($messages as $m) {
                    if ($m['role'] === 'system') {
                        $systemPrompt = $m['content'];
                        continue;
                    }
                    $claudeMessages[] = [
                        'role' => $m['role'],
                        'content' => is_array($m['content']) ? "Image Processing Not Supported Yet" : $m['content']
                    ];
                }

                $response = Http::withHeaders([
                    'x-api-key' => $anthropicKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json'
                ])->timeout(40)->post('https://api.anthropic.com/v1/messages', [
                ])->timeout(40)->post('https://api.anthropic.com/v1/messages', [
                    'model' => str_replace('-full', '', $selectedModel), // fallbacks if any modifier is used
                    'system' => $systemPrompt,
                    'messages' => $claudeMessages,
                    'max_tokens' => 800,
                    'temperature' => 0.1
                ]);

                if ($response->successful() && isset($response->json()['content'][0]['text'])) {
                    $aiText = $response->json()['content'][0]['text'];
                    Log::info("✅ Claude Success: " . substr($aiText, 0, 100) . "...");
                    return $aiText;
                }
                Log::warning("Claude Error: " . $response->body());
                return "Claude API returned an error.";
            } catch (\Exception $e) {
                Log::error("Claude Exception: " . $e->getMessage());
                return "Claude Connection failed.";
            }
        }

        // ==========================================
        // 🚀 ROUTE 4: DeepSeek Execution
        // ==========================================
        if (str_contains($selectedModel, 'deepseek')) {
            if (!$deepseekKey) return "⚠️ DeepSeek API Key is missing in server configuration.";

            try {
                $response = Http::withToken($deepseekKey)
                    ->timeout(40)
                    ->post('https://api.deepseek.com/v1/chat/completions', [
                        'model'       => $selectedModel, // e.g. 'deepseek-chat'
                        'messages'    => $messages,
                        'max_tokens'  => 800,
                        'temperature' => 0.1,
                    ]);

                if ($response->successful() && isset($response->json()['choices'][0]['message']['content'])) {
                    $aiText = $response->json()['choices'][0]['message']['content'];
                    Log::info("✅ DeepSeek Success: " . substr($aiText, 0, 100) . "...");
                    return $aiText;
                }
                Log::warning("DeepSeek Error: " . $response->body());
                return "DeepSeek API returned an error.";
            } catch (\Exception $e) {
                Log::error("DeepSeek Exception: " . $e->getMessage());
                return "DeepSeek Connection failed.";
            }
        }

        // ==========================================
        // 🚀 ROUTE 5: Groq Execution (Llama / Mixtral)
        // ==========================================
        if (str_contains($selectedModel, 'groq')) {
            if (!$groqKey) return "⚠️ Groq API Key is missing in admin dashboard or server configuration.";

            try {
                // Determine model name since we probably prefixed 'groq-' in some UI options
                $groqModel = str_replace('groq-', '', $selectedModel);
                if ($groqModel === 'groq') $groqModel = 'llama3-8b-8192'; // Default

                $response = Http::withToken($groqKey)
                    ->timeout(20)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model'       => $groqModel,
                        'messages'    => $messages,
                        'max_tokens'  => 800,
                        'temperature' => 0.1,
                    ]);

                if ($response->successful() && isset($response->json()['choices'][0]['message']['content'])) {
                    $aiText = $response->json()['choices'][0]['message']['content'];
                    Log::info("✅ Groq Success: " . substr($aiText, 0, 100) . "...");
                    return $aiText;
                }
                Log::warning("Groq Error: " . $response->body());
                return "Groq API returned an error.";
            } catch (\Exception $e) {
                Log::error("Groq Exception: " . $e->getMessage());
                return "Groq Connection failed.";
            }
        }

        return "⚠️ Selected AI Model is invalid or not configured properly.";
    }

    public function analyzeImageWithGoogleVision($base64Image) {
        $apiKey = config('services.google_vision.api_key');
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