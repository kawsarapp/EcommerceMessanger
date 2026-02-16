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
        // অডিও ফাইল চেক
        if (!preg_match('/\.(mp4|aac|m4a|wav|mp3|ogg)(\?.*)?$/i', $audioUrl)) {
            return null;
        }

        try {
            $audioResponse = Http::get($audioUrl);
            if (!$audioResponse->successful()) return null;

            $tempFileName = 'voice_' . uniqid() . '.mp3';
            $tempPath = storage_path('app/' . $tempFileName);
            file_put_contents($tempPath, $audioResponse->body());

            $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
            
            $response = Http::withToken($apiKey)
                ->attach('file', fopen($tempPath, 'r'), $tempFileName)
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'language' => 'bn', // বাংলা ডিটেকশন
                    'response_format' => 'json'
                ]);

            @unlink($tempPath); // ক্লিনআপ

            return $response->successful() ? ($response->json()['text'] ?? null) : null;

        } catch (\Exception $e) {
            Log::error("Voice Conversion Error: " . $e->getMessage());
            return null;
        }
    }
}