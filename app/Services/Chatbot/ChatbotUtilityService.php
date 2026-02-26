<?php
namespace App\Services\Chatbot;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotUtilityService
{
    public function lookupOrderByPhone($clientId, $message)
    {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $message = str_replace($bn, $en, $message);

        if (preg_match('/01[3-9]\d{8,9}/', $message, $matches)) {
            $phone = substr($matches[0], 0, 11);
            $order = Order::where('client_id', $clientId)->where('customer_phone', $phone)->latest()->first();
            if ($order) {
                $status = ucfirst($order->order_status);
                return "FOUND_ORDER: অর্ডার #{$order->id}। অবস্থা: {$status}। বিল: {$order->total_amount} টাকা।";
            }
        }
        return null;
    }

    public function isTrackingIntent($msg) {
        $trackingKeywords = ['track', 'status', 'অর্ডার কই', 'অবস্থা', 'কবে পাব', 'tracking'];
        foreach ($trackingKeywords as $kw) {
            if (mb_strpos(mb_strtolower($msg), $kw) !== false) return true;
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
                'temperature' => 0.4, 
            ]);
            return $response->json()['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error("LLM Error: " . $e->getMessage());
            return null;
        }
    }
}