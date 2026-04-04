<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\StockAlertService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->isDirty('order_status')) {
            $client = $order->client;

            // 1️⃣ Messenger notification (existing)
            if ($client && $client->auto_status_update_msg && !empty($order->sender_id)) {
                $statusMap = [
                    'processing' => 'প্রসেসিং (Processing) শুরু হয়েছে',
                    'shipped'    => 'কুরিয়ারে পাঠানো (Shipped) হয়েছে',
                    'delivered'  => 'সফলভাবে ডেলিভারি (Delivered) হয়েছে',
                    'cancelled'  => 'ক্যানসেল (Cancelled) করা হয়েছে',
                ];

                $statusText = $statusMap[$order->order_status] ?? $order->order_status;
                $msg = "হ্যালো! আপনার অর্ডার #{$order->id} এর বর্তমান স্ট্যাটাস আপডেট হয়ে '{$statusText}'। আমাদের সাথে থাকার জন্য ধন্যবাদ!";

                if ($order->order_status === 'shipped' && !empty($order->tracking_code)) {
                    $courierName = ucfirst($order->courier_name ?? 'কুরিয়ার');
                    $msg .= "\n\n{$courierName} Tracking Code: {$order->tracking_code}";
                }

                app(NotificationService::class)->sendMessengerReply($client, $order->sender_id, $msg);
            }

            // 2️⃣ SMS notification (new)
            if ($client) {
                app(SmsService::class)->sendStatusUpdate($client, $order, $order->order_status);
            }
        }
    }

    public function created(Order $order): void
    {
        // SMS confirmation on new order
        $client = $order->client;
        if ($client) {
            app(SmsService::class)->sendOrderConfirmation($client, $order);
        }
    }
}