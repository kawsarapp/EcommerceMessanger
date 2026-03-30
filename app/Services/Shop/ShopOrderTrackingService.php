<?php

namespace App\Services\Shop;

use App\Models\Order;

class ShopOrderTrackingService
{
    /**
     * Order ID দিয়ে অর্ডার ট্র্যাক করা (security-friendly)
     * Phone number expose করা হবে না — শুধু Order ID দিলেই হবে।
     */
    public function trackOrder($clientId, $orderId)
    {
        // শুধুমাত্র exact Order ID match — partial search নয়
        $order = Order::where('client_id', $clientId)
            ->where('id', (int) $orderId)
            ->with('orderItems.product')
            ->first();

        return $order ? collect([$order]) : collect([]);
    }
}