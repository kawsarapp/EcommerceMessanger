<?php

namespace App\Services;

use App\Models\Client;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookDispatchService
{
    /**
     * যেকোনো event এর জন্য সব active webhooks fire করো
     * 
     * Usage: app(WebhookDispatchService::class)->dispatch('order.created', ['order_id'=>1, ...])
     */
    public function dispatch(int $clientId, string $event, array $payload): void
    {
        $endpoints = WebhookEndpoint::where('client_id', $clientId)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($endpoints as $endpoint) {
            $this->fire($endpoint, $event, $payload);
        }
    }

    private function fire(WebhookEndpoint $endpoint, string $event, array $payload): void
    {
        $body = [
            'event'      => $event,
            'timestamp'  => now()->toIso8601String(),
            'payload'    => $payload,
        ];

        $headers = ['Content-Type' => 'application/json', 'X-Webhook-Event' => $event];

        // HMAC signature যোগ করো যদি secret থাকে
        if ($endpoint->secret) {
            $signature = hash_hmac('sha256', json_encode($body), $endpoint->secret);
            $headers['X-Webhook-Signature'] = "sha256={$signature}";
        }

        $attempts   = 0;
        $maxRetries = $endpoint->retry_count ?? 3;
        $success    = false;

        while ($attempts < $maxRetries && !$success) {
            try {
                $response = Http::timeout(10)->withHeaders($headers)->post($endpoint->url, $body);
                $success  = $response->status() < 400;
                $attempts++;

                if (!$success && $attempts < $maxRetries) {
                    sleep(1); // 1 second delay between retries
                }
            } catch (Exception $e) {
                $attempts++;
                Log::warning("Webhook retry {$attempts}/{$maxRetries} failed for {$endpoint->url}: " . $e->getMessage());
            }
        }

        $endpoint->update([
            'last_triggered_at' => now(),
            'last_status'       => $success ? 'success' : 'failed',
        ]);

        Log::info("🔗 Webhook [{$event}] → {$endpoint->url}: " . ($success ? '✅ success' : '❌ failed'));
    }
}
