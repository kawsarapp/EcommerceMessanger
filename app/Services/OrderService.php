<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\OrderSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * চ্যাট সেশন থেকে অর্ডার তৈরি করা
     */
    public function finalizeOrderFromSession($clientId, $senderId, $clientModel)
    {
        $session = OrderSession::where('sender_id', $senderId)->first();
        $info = $session->customer_info;
        
        $product = Product::find($info['product_id']);
        if (!$product) throw new \Exception("Product not found.");

        return DB::transaction(function () use ($info, $clientId, $senderId, $product, $clientModel, $session) {
            $qty = 1;
            
            // ডেলিভারি চার্জ নির্ধারণ
            $isDhaka = str_contains(strtolower($info['address'] ?? ''), 'dhaka');
            $delivery = $isDhaka ? $clientModel->delivery_charge_inside : $clientModel->delivery_charge_outside;
            $price = $product->sale_price ?? $product->regular_price;
            $total = ($price * $qty) + $delivery;

            // ১. অর্ডার তৈরি
            $order = Order::create([
                'client_id'       => $clientId,
                'sender_id'       => $senderId,
                'customer_name'   => $info['name'] ?? 'Messenger Customer',
                'customer_phone'  => $info['phone'],
                'shipping_address'=> $info['address'],
                'total_amount'    => $total,
                'order_status'    => 'processing',
                'payment_status'  => 'pending',
            ]);

            // ২. অর্ডার আইটেম ও নোট (ডাইনামিক কলাম চেক)
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $price,
                'price' => $price
            ]);

            // নোট সেভ করা (যদি কলাম থাকে)
            if (isset($info['variant']) && Schema::hasColumn('orders', 'admin_note')) {
                $variantNote = json_encode($info['variant']);
                $order->update(['admin_note' => "Variant: $variantNote"]);
            }

            // ৩. স্টক আপডেট
            $product->decrement('stock_quantity', $qty);

            // ৪. সেশন ক্লিয়ার করা
            $session->update(['customer_info' => ['step' => 'completed', 'history' => $info['history'] ?? []]]);

            return $order;
        });
    }
}