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
    public function finalizeOrderFromSession($clientId, $senderId, $clientModel)
    {
        $session = OrderSession::where('sender_id', $senderId)->first();
        
        if (!$session || empty($session->customer_info)) {
            throw new \Exception("Session expired or empty.");
        }

        $info = $session->customer_info;
        
        $product = Product::find($info['product_id'] ?? null);
        if (!$product) throw new \Exception("Product not found or removed.");

        return DB::transaction(function () use ($info, $clientId, $senderId, $product, $clientModel, $session) {
            
            if ($product->manage_stock && ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0)) {
                throw new \Exception("Stock finished just now! Cannot process order.");
            }

            $qty = 1; 
            
            // 🔥 ডেলিভারি চার্জ ক্যালকুলেশন (Inside/Outside)
            $locationType = $info['location_type'] ?? null;
            $delivery = 120; // Default

            if ($locationType === 'inside_dhaka') {
                $delivery = $clientModel->delivery_charge_inside ?? 80;
            } elseif ($locationType === 'outside_dhaka') {
                $delivery = $clientModel->delivery_charge_outside ?? 150;
            } else {
                $isDhaka = str_contains(strtolower($info['address'] ?? ''), 'dhaka') || str_contains($info['address'] ?? '', 'ঢাকা');
                $delivery = $isDhaka ? ($clientModel->delivery_charge_inside ?? 80) : ($clientModel->delivery_charge_outside ?? 150);
            }

            // পণ্যের দাম + ডেলিভারি চার্জ = টোটাল বিল
            $price = $product->sale_price ?? $product->regular_price;
            $total = ($price * $qty) + $delivery;

            $orderData = [
                'client_id'       => $clientId,
                'sender_id'       => $senderId,
                'customer_name'   => $info['name'] ?? 'Messenger Guest',
                'customer_phone'  => $info['phone'] ?? '',
                'shipping_address'=> $info['address'] ?? '',
                'total_amount'    => $total, // ডেলিভারি চার্জ সহ একদম সঠিক হিসাব
                'order_status'    => 'processing',
                'payment_status'  => 'pending',
                'payment_method'  => $info['payment_method'] ?? 'cod',
            ];

            // ডাটাবেসে কলাম থাকলে তবেই সেভ হবে, নতুবা ক্র্যাশ করবে না
            if (Schema::hasColumn('orders', 'delivery_charge')) {
                $orderData['delivery_charge'] = $delivery;
            }
            if (Schema::hasColumn('orders', 'district')) {
                $orderData['district'] = $info['district'] ?? null;
            }
            if (Schema::hasColumn('orders', 'division')) {
                $orderData['division'] = $info['division'] ?? null;
            }
            
            // নোটস যুক্ত করা
            $notes = [];
            $notes[] = "Delivery Charge: ৳{$delivery}"; // নোটেও ডেলিভারি চার্জ লিখে রাখা হলো

            if (!empty($info['variant'])) {
                $vText = is_array($info['variant']) ? implode(', ', array_filter($info['variant'])) : $info['variant'];
                $notes[] = "Variant: " . $vText;
            }
            if (!empty($info['user_note'])) {
                $notes[] = "User Note: " . $info['user_note'];
            }

            if (!empty($notes)) {
                $finalNote = implode(" | ", $notes);
                if (Schema::hasColumn('orders', 'admin_note')) {
                    $orderData['admin_note'] = $finalNote;
                } elseif (Schema::hasColumn('orders', 'notes')) {
                    $orderData['notes'] = $finalNote;
                }
            }

            $order = Order::create($orderData);

            // অর্ডার আইটেম সেভ করা
            $itemData = [
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'subtotal'   => $price * $qty
            ];

            if (Schema::hasColumn('order_items', 'unit_price')) {
                $itemData['unit_price'] = $price;
            }
            if (Schema::hasColumn('order_items', 'price')) {
                $itemData['price'] = $price;
            }
            if (Schema::hasColumn('order_items', 'variant')) {
                $itemData['variant'] = isset($info['variant']) ? (is_array($info['variant']) ? json_encode($info['variant']) : $info['variant']) : null;
            }

            OrderItem::create($itemData);

            // স্টক ম্যানেজমেন্ট
            if ($product->manage_stock) {
                $product->decrement('stock_quantity', $qty);
                if ($product->stock_quantity <= 0) {
                    $product->update(['stock_status' => 'out_of_stock']);
                }
            }

            $session->update([
                'customer_info' => [
                    'step' => 'completed', 
                    'last_order_id' => $order->id, 
                    'history' => $info['history'] ?? []
                ]
            ]);

            Log::info("✅ Order #{$order->id} Created Successfully via Chatbot. Total: ৳{$total} (Inc. Delivery: ৳{$delivery})");

            return $order;
        });
    }
}