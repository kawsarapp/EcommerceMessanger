<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\NotificationService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        // স্ট্যাটাস চেঞ্জ হয়েছে কিনা এবং কাস্টমারের মেসেঞ্জার ID আছে কিনা চেক করা
        if ($order->isDirty('order_status') && !empty($order->sender_id)) {
            $client = $order->client;
            
            // সেলার এই ফিচারটি অন রেখেছে কিনা
            if ($client && $client->auto_status_update_msg) {
                
                $statusMap = [
                    'processing' => 'প্রসেসিং (Processing) শুরু হয়েছে',
                    'shipped' => 'কুরিয়ারে পাঠানো (Shipped) হয়েছে',
                    'delivered' => 'সফলভাবে ডেলিভারি (Delivered) হয়েছে',
                    'cancelled' => 'ক্যানসেল (Cancelled) করা হয়েছে'
                ];
                
                $statusText = $statusMap[$order->order_status] ?? $order->order_status;
                
                $msg = "হ্যালো! আপনার অর্ডার #{$order->id} এর বর্তমান স্ট্যাটাস আপডেট হয়ে '{$statusText}'। আমাদের সাথে থাকার জন্য ধন্যবাদ!";
                
                // ট্র্যাকিং কোড থাকলে সেটাও মেসেজে যুক্ত করা
                if ($order->order_status === 'shipped' && !empty($order->tracking_code)) {
                    $courierName = ucfirst($order->courier_name ?? 'কুরিয়ার');
                    $msg .= "\n\n{$courierName} Tracking Code: {$order->tracking_code}";
                }

                // মেসেঞ্জারে সেন্ড করা
                app(NotificationService::class)->sendMessengerReply($client, $order->sender_id, $msg);
            }
        }
    }
}