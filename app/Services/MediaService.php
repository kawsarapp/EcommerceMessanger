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
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0'
            ])->timeout(15)->get($imageUrl);

            if ($response->successful()) {
                $mime = $response->header('Content-Type') ?: 'image/jpeg';
                return "data:" . $mime . ";base64," . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            Log::error("Image Processing Error: " . $e->getMessage());
        }
        return null;
    }

    /**
     * 🎤 ভয়েস মেসেজ টেক্সটে কনভার্ট করা (Whisper API)
     */
    public function convertVoiceToText($audioUrl)
    {
        if (empty($audioUrl)) return null;

        try {
            // ফেসবুক থেকে অডিও ডাউনলোড
            $audioResponse = Http::timeout(20)->get($audioUrl);
            
            if (!$audioResponse->successful()) {
                Log::error("Voice Download Failed: HTTP " . $audioResponse->status());
                return null;
            }

            // 🔥 FIX: Facebook এর লিংকে এক্সটেনশন থাকে না, তাই জোর করে .mp4 এ সেভ করছি
            $tempFileName = 'voice_' . uniqid() . '.mp4';
            $tempPath = storage_path('app/' . $tempFileName);
            file_put_contents($tempPath, $audioResponse->body());

            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->attach('file', fopen($tempPath, 'r'), $tempFileName)
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => 'bn', // বাংলা ডিটেকশন
                    'response_format' => 'json'
                ]);

            @unlink($tempPath); // ক্লিনআপ

            if ($response->successful()) {
                return $response->json()['text'] ?? null;
            } else {
                Log::error("Whisper API Error: " . $response->body());
                return null;
            }

        } catch (\Exception $e) {
            Log::error("Voice Conversion Error: " . $e->getMessage());
            return null;
        }
    }
}