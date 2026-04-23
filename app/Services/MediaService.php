<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MediaService
{
    /**
     * 📷 ইমেজ ডাউনলোড করে Base64 এ কনভার্ট করা (AI Vision এর জন্য)
     */
    public function processImage($imageUrl)
    {
        if (empty($imageUrl)) return null;

        try {
            $ch = curl_init($imageUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $content = curl_exec($ch);
            curl_close($ch);

            if ($content === false || strlen($content) === 0) return null;

            $ext  = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            $mime = match($ext) {
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };

            return "data:{$mime};base64," . base64_encode($content);
        } catch (\Exception $e) {
            Log::error("Image Processing Error: " . $e->getMessage());
        }
        return null;
    }

    /**
     * 🎤 ভয়েস মেসেজ → টেক্সটে কনভার্ট (Whisper + Gemini fallback)
     *
     * ✅ FIX: Content-Type header দেখে সঠিক audio extension ঠিক করা হয়েছে।
     * আগে সব audio blindly .mp4 হিসেবে save হত — Whisper reject করত।
     * এখন ogg/mp3/m4a/aac সব সঠিকভাবে save হবে।
     */
    public function convertVoiceToText(string $audioUrl): ?string
    {
        if (empty($audioUrl)) return null;

        try {
            // ── Step 1: Download audio with headers ─────────────────────────
            $ch = curl_init($audioUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; AiCommerceBot/1.0)',
                CURLOPT_HEADER         => true,
                CURLOPT_SSL_VERIFYPEER => false,  // Fix: self-signed cert on hosting servers
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

            $raw        = curl_exec($ch);
            $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers    = substr($raw, 0, $headerSize);
            $body       = substr($raw, $headerSize);
            curl_close($ch);

            if ($httpCode >= 400 || empty($body)) {
                Log::error("🎤 Voice Download Failed: HTTP {$httpCode}");
                return null;
            }

            // ── Step 2: Detect correct extension from Content-Type ──────────
            $ext      = $this->detectAudioExtension($headers, $audioUrl);
            $tempPath = storage_path("app/voice_" . uniqid() . ".{$ext}");
            file_put_contents($tempPath, $body);

            Log::info("🎤 Voice Downloaded | ext:.{$ext} | size:" . strlen($body) . " bytes");

            // ── Step 3: Whisper first, Gemini fallback ──────────────────────
            $result = $this->transcribeWithWhisper($tempPath, $ext)
                   ?? $this->transcribeWithGemini($body, $ext);

            @unlink($tempPath);

            if ($result) {
                Log::info("🎤 Transcribed: " . substr($result, 0, 100));
            } else {
                Log::warning("🎤 Transcription failed for: {$audioUrl}");
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("🎤 Voice Exception: " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Detect audio extension from Content-Type response header or URL.
     */
    private function detectAudioExtension(string $headers, string $url): string
    {
        if (preg_match('/Content-Type:\s*([^\r\n;]+)/i', $headers, $m)) {
            $mime = strtolower(trim($m[1]));
            return match(true) {
                str_contains($mime, 'ogg')    => 'ogg',
                str_contains($mime, 'mpeg')   => 'mp3',
                str_contains($mime, 'mp3')    => 'mp3',
                str_contains($mime, 'aac')    => 'aac',
                str_contains($mime, 'wav')    => 'wav',
                str_contains($mime, 'webm')   => 'webm',
                str_contains($mime, 'x-m4a')  => 'm4a',
                str_contains($mime, 'mp4')    => 'mp4',
                str_contains($mime, 'audio')  => 'ogg',  // generic audio
                default                       => 'mp4',
            };
        }

        // Fallback: URL extension
        $urlExt = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($urlExt, ['ogg', 'mp3', 'mp4', 'm4a', 'aac', 'wav', 'webm', 'oga'])
            ? $urlExt
            : 'ogg'; // Facebook voice notes default = ogg
    }

    /**
     * Transcribe using OpenAI Whisper.
     */
    private function transcribeWithWhisper(string $tempPath, string $ext): ?string
    {
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
        if (!$apiKey || !file_exists($tempPath)) return null;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->attach('file', fopen($tempPath, 'r'), "voice.{$ext}")
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model'           => 'whisper-1',
                    'prompt'          => 'Bangla/Bengali e-commerce conversation about products, prices, orders.',
                    'response_format' => 'json',
                ]);

            if ($response->successful()) {
                $text = trim($response->json('text') ?? '');
                if (strlen($text) > 1) return $text;
            }
            Log::warning("🎤 Whisper Error: " . $response->status() . " | " . substr($response->body(), 0, 150));
        } catch (\Exception $e) {
            Log::warning("🎤 Whisper Exception: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Transcribe using Gemini 1.5 Flash (fallback — sends audio as inline_data).
     */
    private function transcribeWithGemini(string $audioBinary, string $ext): ?string
    {
        $apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
        if (!$apiKey || empty($audioBinary)) return null;

        $mime = match($ext) {
            'ogg', 'oga' => 'audio/ogg',
            'mp3'        => 'audio/mp3',
            'mp4'        => 'audio/mp4',
            'm4a'        => 'audio/mp4',
            'aac'        => 'audio/aac',
            'wav'        => 'audio/wav',
            'webm'       => 'audio/webm',
            default      => 'audio/ogg',
        };

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(40)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key={$apiKey}",
                [
                    'contents' => [[
                        'parts' => [
                            ['text' => 'এই audio টি transcribe করো। শুধু বলা কথাগুলো বাংলা বা ইংরেজিতে লিখে দাও। কোনো explanation দেবে না।'],
                            ['inline_data' => [
                                'mime_type' => $mime,
                                'data'      => base64_encode($audioBinary),
                            ]],
                        ]
                    ]],
                    'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 300],
                ]
            );

            if ($response->successful()) {
                $text = trim($response->json('candidates.0.content.parts.0.text') ?? '');
                if (strlen($text) > 1) return $text;
            }
            Log::warning("🎤 Gemini Transcription Error: " . substr($response->body(), 0, 150));
        } catch (\Exception $e) {
            Log::warning("🎤 Gemini Transcription Exception: " . $e->getMessage());
        }
        return null;
    }
}