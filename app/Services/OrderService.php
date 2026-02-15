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
     * সেশন থেকে অর্ডার কনফার্ম এবং ডাটাবেসে সেভ করার মেথড
     */
    public function finalizeOrderFromSession($clientId, $senderId, $clientModel)
    {
        $session = OrderSession::where('sender_id', $senderId)->first();
        
        if (!$session || empty($session->customer_info)) {
            throw new \Exception("Session expired or empty.");
        }

        $info = $session->customer_info;
        
        $product = Product::find($info['product_id'] ?? null);
        if (!$product) throw new \Exception("Product not found or removed.");

        // 🔥 DATABASE TRANSACTION (নিরাপদ অর্ডার প্রসেসিং)
        return DB::transaction(function () use ($info, $clientId, $senderId, $product, $clientModel, $session) {
            
            // 🛑 1. STOCK GUARD (Advanced Feature)
            // অর্ডার কনফার্ম করার ঠিক আগ মুহূর্তে স্টক চেক করা
            if ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0) {
                throw new \Exception("Stock finished just now! Cannot process order.");
            }

            $qty = 1; // বর্তমানে ১টি করে অর্ডার হচ্ছে, ভবিষ্যতে এটি ডাইনামিক করা যাবে
            
            // 🔥 2. ADVANCED DELIVERY CALCULATION
            // AddressStep থেকে আসা লোকেশন টাইপ চেক করা (সবচেয়ে নির্ভুল)
            $locationType = $info['location_type'] ?? null;
            $delivery = 0;

            if ($locationType === 'inside_dhaka') {
                $delivery = $clientModel->delivery_charge_inside;
            } elseif ($locationType === 'outside_dhaka') {
                $delivery = $clientModel->delivery_charge_outside;
            } else {
                // Fallback: যদি লোকেশন টাইপ না থাকে, টেক্সট সার্চ করা (Legacy Support)
                $isDhaka = str_contains(strtolower($info['address'] ?? ''), 'dhaka');
                $delivery = $isDhaka ? $clientModel->delivery_charge_inside : $clientModel->delivery_charge_outside;
            }

            $price = $product->sale_price ?? $product->regular_price;
            $total = ($price * $qty) + $delivery;

            // ১. অর্ডার ডাটা প্রস্তুত (Smart Mapping)
            $orderData = [
                'client_id'       => $clientId,
                'sender_id'       => $senderId,
                'customer_name'   => $info['name'] ?? 'Messenger Guest',
                'customer_phone'  => $info['phone'],
                'shipping_address'=> $info['address'],
                'total_amount'    => $total,
                'order_status'    => 'processing',
                'payment_status'  => 'pending',
                // 🔥 New Fields Mapping
                'district'        => $info['district'] ?? null,
                'division'        => $info['division'] ?? null,
            ];

            // SQL FIX: কলাম চেক করে ডাটা বসানো (Future Proof)
            if (Schema::hasColumn('orders', 'payment_method')) {
                // যদি সেশনে পেমেন্ট মেথড থাকে তবে সেটা, নাহলে COD
                $orderData['payment_method'] = $info['payment_method'] ?? 'cod';
            }
            
            // নোট হ্যান্ডলিং (Variant & User Note)
            $notes = [];
            if (isset($info['variant'])) {
                $variantStr = is_array($info['variant']) ? implode(', ', array_filter($info['variant'])) : $info['variant'];
                $notes[] = "Variant: " . $variantStr;
            }
            if (isset($info['user_note'])) {
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

            // ২. অর্ডার তৈরি
            $order = Order::create($orderData);

            // ৩. আইটেম তৈরি
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_price' => $price,
                'price'      => $price
            ]);

            // ৪. স্টক আপডেট (Decrement)
            $product->decrement('stock_quantity', $qty);

            // স্টক যদি ০ হয়ে যায়, স্ট্যাটাস আপডেট করা
            if ($product->stock_quantity <= 0) {
                $product->update(['stock_status' => 'out_of_stock']);
            }

            // ৫. সেশন আপডেট (অর্ডার কমপ্লিট)
            $session->update([
                'customer_info' => [
                    'step' => 'completed', 
                    'last_order_id' => $order->id, // ফর ফিউচার রেফারেন্স
                    'history' => $info['history'] ?? []
                ]
            ]);

            Log::info("✅ Order #{$order->id} Created Successfully via Chatbot.");

            return $order;
        });
    }
}