<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Universal SMS Service
 * =====================
 * Supported Bangladesh Providers:
 *   - sslwireless  → SSL Wireless (SSLSMS) — most popular BD enterprise gateway
 *   - mimsms       → Mim IT / Mimsms — affordable, good Bangla SMS
 *   - bulksmsbd    → Bulksmsbd — ecommerce-focused, fast delivery
 *   - custom       → Any provider with a custom endpoint
 *
 * Usage:
 *   app(SmsService::class)->send($client, '01712345678', 'Your message here');
 *
 * Dashboard Keys (stored in client->widgets['sms']):
 *   provider   → sslwireless | mimsms | bulksmsbd | custom
 *   api_key    → Provider API key / token
 *   sender_id  → Masking name shown to recipient (e.g. MYSHOP)
 *   enabled    → boolean
 *   on_order_placed  → boolean
 *   on_status_change → boolean
 *   on_low_stock     → boolean
 *   custom_url       → For 'custom' provider
 */
class SmsService
{
    /**
     * Send an SMS via the client's configured provider
     */
    public function send(Client $client, string $phone, string $message): bool
    {
        $config = $client->widgets['sms'] ?? [];

        if (empty($config['enabled']) || empty($config['api_key']) || empty($phone)) {
            return false;
        }

        $provider = $config['provider'] ?? 'sslwireless';
        $phone    = $this->normalizePhone($phone);

        try {
            return match ($provider) {
                'sslwireless' => $this->sendViaSslWireless($config, $phone, $message),
                'mimsms'      => $this->sendViaMimsms($config, $phone, $message),
                'bulksmsbd'   => $this->sendViaBulkSmsbd($config, $phone, $message),
                'custom'      => $this->sendViaCustom($config, $phone, $message),
                default       => false,
            };
        } catch (\Exception $e) {
            Log::error("SMS Send Failed [{$provider}]: " . $e->getMessage(), [
                'phone'    => $phone,
                'client'   => $client->id,
                'provider' => $provider,
            ]);
            return false;
        }
    }

    /**
     * Send order confirmation SMS to customer
     */
    public function sendOrderConfirmation(Client $client, \App\Models\Order $order): bool
    {
        $config = $client->widgets['sms'] ?? [];
        if (empty($config['on_order_placed'])) return false;

        $currency = $client->currency ?? '৳';
        $msg = $config['order_placed_template']
            ?? "আপনার অর্ডার #{order_id} নিশ্চিত হয়েছে!\nমোট: {currency}{total}\nট্র্যাক করুন: {track_url}";

        $clean    = $client->custom_domain
            ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'))
            : null;
        $trackUrl = $clean
            ? 'https://' . $clean . '/track?order_id=' . $order->id
            : route('shop.track', $client->slug) . '?order_id=' . $order->id;

        $message = str_replace(
            ['{order_id}', '{total}', '{currency}', '{track_url}', '{shop_name}'],
            [$order->id, number_format((float)$order->total_amount, 0), $currency, $trackUrl, $client->shop_name],
            $msg
        );

        return $this->send($client, $order->customer_phone, $message);
    }

    /**
     * Send status change SMS to customer
     */
    public function sendStatusUpdate(Client $client, \App\Models\Order $order, string $newStatus): bool
    {
        $config = $client->widgets['sms'] ?? [];
        if (empty($config['on_status_change'])) return false;

        $statusLabels = [
            'processing' => 'প্রসেসিং শুরু হয়েছে',
            'shipped'    => 'কুরিয়ারে পাঠানো হয়েছে',
            'delivered'  => 'ডেলিভারি সম্পন্ন হয়েছে',
            'cancelled'  => 'বাতিল করা হয়েছে',
        ];
        $statusText = $statusLabels[$newStatus] ?? $newStatus;

        $msg = "অর্ডার #{$order->id} স্ট্যাটাস আপডেট: {$statusText}।";

        if ($newStatus === 'shipped' && !empty($order->tracking_code)) {
            $courier = ucfirst($order->courier_name ?? 'কুরিয়ার');
            $msg .= " {$courier} Tracking: {$order->tracking_code}";
        }

        return $this->send($client, $order->customer_phone, $msg);
    }

    /**
     * Send low stock alert to admin/seller phone
     */
    public function sendLowStockAlert(Client $client, \App\Models\Product $product): bool
    {
        $config = $client->widgets['sms'] ?? [];
        if (empty($config['on_low_stock'])) return false;

        // Alert seller's phone
        $sellerPhone = $config['admin_phone'] ?? $client->phone;
        if (!$sellerPhone) return false;

        $msg = "⚠️ স্টক কম! '{$product->name}' — মাত্র {$product->stock_quantity}টি বাকি আছে। দ্রুত রিস্টক করুন।";

        return $this->send($client, $sellerPhone, $msg);
    }

    /**
     * Test SMS — from dashboard "Test" button
     */
    public function sendTest(Client $client, string $toPhone): bool
    {
        $msg = "✅ Test SMS from {$client->shop_name}! SMS setup সফল হয়েছে।";
        return $this->send($client, $toPhone, $msg);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Provider Implementations
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * SSL Wireless (SSLSMS) — https://sslwireless.com
     * Docs: https://developer.sslwireless.com/sms/
     */
    private function sendViaSslWireless(array $config, string $phone, string $message): bool
    {
        $response = Http::asForm()->post('https://msms.sslwireless.com/api/v3/send-sms', [
            'api_token' => $config['api_key'],
            'sid'       => $config['sender_id'] ?? 'SMSINFO',
            'sms'       => $message,
            'msisdn'    => $phone,
            'csms_id'   => uniqid('ssl_'),
        ]);

        $body = $response->json();
        if ($body['status_code'] ?? null === 1000) return true;

        Log::warning('SSL Wireless SMS failed', ['response' => $body]);
        return false;
    }

    /**
     * Mimsms / Mim IT — https://mimsms.com
     * Docs: https://mimsms.com/api-doc
     */
    private function sendViaMimsms(array $config, string $phone, string $message): bool
    {
        $response = Http::get('https://mimsms.com/smsapi', [
            'api_key'     => $config['api_key'],
            'senderid'    => $config['sender_id'] ?? 'MIMSMS',
            'number'      => $phone,
            'message'     => $message,
            'type'        => 'text',
        ]);

        // Mimsms returns "1002" or similar code
        $body = $response->body();
        if (str_contains($body, '1002') || str_contains($body, 'success')) return true;

        Log::warning('Mimsms SMS failed', ['response' => $body]);
        return false;
    }

    /**
     * Bulksmsbd — https://bulksmsbd.net
     * Docs: https://bulksmsbd.net/api-doc
     */
    private function sendViaBulkSmsbd(array $config, string $phone, string $message): bool
    {
        $response = Http::post('https://bulksmsbd.net/api/smsapi', [
            'api_key' => $config['api_key'],
            'senderid'=> $config['sender_id'] ?? 'BULK',
            'number'  => $phone,
            'message' => $message,
            'type'    => 'text',
        ]);

        $body = $response->json();
        if (($body['response_code'] ?? null) === 202) return true;

        Log::warning('Bulksmsbd SMS failed', ['response' => $body]);
        return false;
    }

    /**
     * Custom Provider — any REST API endpoint
     * Replaces: {api_key}, {sender_id}, {phone}, {message}
     */
    private function sendViaCustom(array $config, string $phone, string $message): bool
    {
        if (empty($config['custom_url'])) return false;

        $url = str_replace(
            ['{api_key}', '{sender_id}', '{phone}', '{message}'],
            [$config['api_key'], $config['sender_id'] ?? '', $phone, urlencode($message)],
            $config['custom_url']
        );

        // If URL has placeholders replaced, it's a GET request style
        if (str_contains($config['custom_url'], '{phone}')) {
            $response = Http::get($url);
        } else {
            // POST body style
            $response = Http::post($url, [
                'api_key'   => $config['api_key'],
                'sender_id' => $config['sender_id'] ?? '',
                'phone'     => $phone,
                'message'   => $message,
            ]);
        }

        return $response->successful();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Normalize phone to BD format (880XXXXXXXXXX)
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '880')) return $phone;
        if (str_starts_with($phone, '0'))  return '880' . substr($phone, 1);
        if (strlen($phone) === 10)          return '880' . $phone;

        return $phone;
    }
}
