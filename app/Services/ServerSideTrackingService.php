<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServerSideTrackingService
{
    /**
     * Dispatch Purchase Conversion mapping standard fields securely.
     */
    public function dispatchPurchase(Order $order): void
    {
        try {
            $client = $order->client;
            if (!$client) return;

            $tracking = $client->tracking_settings ?? [];

            // 1. Facebook Meta Conversion API 
            $fbPixel = $tracking['fb_pixel_id'] ?? $client->fb_pixel_id ?? null;
            $fbCapiToken = $tracking['fb_capi_token'] ?? null;
            
            if ($fbPixel && $fbCapiToken) {
                $this->sendFacebookPurchaseRequest($order, $fbPixel, $fbCapiToken);
            }

            // 2. Google Analytics 4 (GA4) Measurement Protocol Event
            $ga4Id = $tracking['ga4_measurement_id'] ?? null;
            $ga4Secret = $tracking['ga4_api_secret'] ?? null;

            if ($ga4Id && $ga4Secret) {
                $this->sendGoogleAnalyticsPurchaseRequest($order, $ga4Id, $ga4Secret);
            }

        } catch (\Exception $e) {
            Log::error('Server-Side Tracking Dispatch Failure: ' . $e->getMessage());
        }
    }

    private function sendFacebookPurchaseRequest(Order $order, string $pixelId, string $token): void
    {
        $userData = [
            'client_user_agent' => request()->userAgent() ?? '',
            'client_ip_address' => request()->ip() ?? ''
        ];

        if (!empty($order->customer_email)) {
            $userData['em'] = [hash('sha256', strtolower(trim($order->customer_email)))];
        }

        if (!empty($order->customer_phone)) {
            $userData['ph'] = [hash('sha256', preg_replace('/[^0-9]/', '', $order->customer_phone))];
        }

        $payload = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => time(),
                    'action_source' => 'website',
                    'user_data' => $userData,
                    'custom_data' => [
                        'currency' => 'BDT',
                        'value' => (float) $order->total_amount,
                        'order_id' => $order->order_number ?? (string) $order->id,
                        'content_ids' => $order->items->pluck('product_id')->toArray(),
                        'content_type' => 'product',
                    ]
                ]
            ]
        ];

        // Fire & Forget HTTP Request synchronously mapping the payload securely.
        Http::withToken($token)
            ->timeout(3) // Do not hang checkout processes
            ->post("https://graph.facebook.com/v19.0/{$pixelId}/events", $payload);
    }

    private function sendGoogleAnalyticsPurchaseRequest(Order $order, string $ga4Id, string $ga4Secret): void
    {
        // Extract clientId from GA cookies `_ga` if available, otherwise fallback uniquely per session natively.
        $clientIdGa = request()->cookie('_ga') ? preg_replace('/^GA[0-9]\.[0-9]\./', '', request()->cookie('_ga')) : (session()->getId() ?? uniqid());

        $items = $order->items->map(function($item) {
            return [
                'item_id' => (string) $item->product_id,
                'item_name' => $item->product_name ?? 'Unknown Item',
                'quantity' => $item->quantity,
                'price' => (float) $item->unit_price
            ];
        })->toArray();

        $payload = [
            'client_id' => $clientIdGa,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'currency' => 'BDT',
                        'value' => (float) $order->total_amount,
                        'transaction_id' => $order->order_number ?? (string) $order->id,
                        'shipping' => (float) $order->delivery_charge,
                        'items' => $items
                    ]
                ]
            ]
        ];

        Http::timeout(3)
            ->post("https://www.google-analytics.com/mp/collect?measurement_id={$ga4Id}&api_secret={$ga4Secret}", $payload);
    }
}
