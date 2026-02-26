<?php

namespace App\Services\Shop;

use App\Models\Order;

class ShopOrderTrackingService
{
    /**
     * ফোন নম্বর দিয়ে অর্ডার ট্র্যাক করা
     */
    public function trackOrder($clientId, $phoneInput)
    {
        // বাংলা নম্বরকে ইংরেজিতে রূপান্তর
        $phone = str_replace(
            ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"], 
            ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"], 
            $phoneInput
        );

        return Order::where('client_id', $clientId)
            ->where('customer_phone', 'LIKE', "%{$phone}%")
            ->with('orderItems.product')
            ->latest()
            ->take(5)
            ->get();
    }
}