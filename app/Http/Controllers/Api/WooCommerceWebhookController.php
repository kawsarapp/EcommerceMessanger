<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * WooCommerceWebhookController
 * 
 * Receives WooCommerce order notifications from the WordPress plugin.
 * When a new order is placed on WordPress, the plugin sends it here
 * so the seller can see it in their AI Commerce Bot dashboard.
 * 
 * POST /api/v1/wc-order-notify
 * Auth: X-Api-Key header
 */
class WooCommerceWebhookController extends Controller
{
    public function orderNotify(Request $request)
    {
        // ── 1. Auth ──────────────────────────────────────────────────────────
        $apiKey = $request->header('X-Api-Key')
            ?? $request->bearerToken()
            ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'API Key missing.'], 401);
        }

        /** @var Client|null $client */
        $client = Cache::remember("client_by_api_key_{$apiKey}", 300, function () use ($apiKey) {
            return Client::where('api_token', $apiKey)->first();
        });

        if (!$client) {
            return response()->json(['success' => false, 'error' => 'Invalid API Key.'], 401);
        }

        // ── 2. Validate payload ───────────────────────────────────────────────
        $request->validate([
            'wc_order_id'      => 'required|integer',
            'customer_name'    => 'nullable|string|max:200',
            'customer_phone'   => 'nullable|string|max:30',
            'customer_address' => 'nullable|string|max:500',
            'total'            => 'nullable|numeric',
            'items'            => 'nullable|array',
        ]);

        $wcOrderId = $request->input('wc_order_id');
        $name      = $request->input('customer_name', 'Unknown');
        $phone     = $request->input('customer_phone', '');
        $address   = $request->input('customer_address', '');
        $total     = (float) $request->input('total', 0);
        $items     = $request->input('items', []);
        $status    = $request->input('status', 'pending');

        // ── 3. Build order notes from items ───────────────────────────────────
        $itemSummary = collect($items)->map(function ($item) {
            $name  = $item['name'] ?? 'Product';
            $qty   = $item['qty']  ?? 1;
            $price = $item['price'] ?? 0;
            return "{$name} x{$qty} = ৳{$price}";
        })->implode(' | ');

        // ── 4. Create or upsert order in our system ───────────────────────────
        try {
            $order = Order::updateOrCreate(
                // Match by WC order ID and client
                ['client_id' => $client->id, 'reference_id' => 'wc-' . $wcOrderId],
                [
                    'customer_name'    => $name,
                    'customer_phone'   => $phone,
                    'customer_address' => $address,
                    'total_price'      => $total,
                    'status'           => $this->mapWcStatus($status),
                    'source'           => 'woocommerce',
                    'notes'            => "WooCommerce Order #{$wcOrderId}" . ($itemSummary ? " | Items: {$itemSummary}" : ''),
                ]
            );

            Log::info("📦 WooCommerce Order #{$wcOrderId} received for client #{$client->id} ({$client->shop_name})");

            return response()->json([
                'success'       => true,
                'message'       => 'Order received and saved.',
                'our_order_id'  => $order->id,
                'wc_order_id'   => $wcOrderId,
            ]);

        } catch (\Exception $e) {
            Log::error("WooCommerce Order Sync Error for client #{$client->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Server error.'], 500);
        }
    }

    /**
     * Map WooCommerce order status to our order status.
     */
    private function mapWcStatus(string $wcStatus): string
    {
        return match ($wcStatus) {
            'pending'    => 'pending',
            'processing' => 'confirmed',
            'on-hold'    => 'pending',
            'completed'  => 'delivered',
            'cancelled'  => 'cancelled',
            'refunded'   => 'cancelled',
            'failed'     => 'cancelled',
            default      => 'pending',
        };
    }
}
