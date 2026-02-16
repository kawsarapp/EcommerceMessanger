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
            if ($product->manage_stock && ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0)) {
                throw new \Exception("Stock finished just now! Cannot process order.");
            }

            $qty = 1; // বর্তমানে ১টি করে অর্ডার হচ্ছে, ভবিষ্যতে এটি ডাইনামিক করা যাবে
            
            // 🔥 2. ADVANCED DELIVERY CALCULATION
            // AddressStep থেকে আসা লোকেশন টাইপ চেক করা (সবচেয়ে নির্ভুল)
            $locationType = $info['location_type'] ?? null;
            $delivery = 120; // Default fallback

            if ($locationType === 'inside_dhaka') {
                $delivery = $clientModel->delivery_charge_inside ?? 80;
            } elseif ($locationType === 'outside_dhaka') {
                $delivery = $clientModel->delivery_charge_outside ?? 150;
            } else {
                // Fallback: যদি লোকেশন টাইপ না থাকে, টেক্সট সার্চ করা (Legacy Support)
                $isDhaka = str_contains(strtolower($info['address'] ?? ''), 'dhaka') || str_contains($info['address'] ?? '', 'ঢাকা');
                $delivery = $isDhaka ? ($clientModel->delivery_charge_inside ?? 80) : ($clientModel->delivery_charge_outside ?? 150);
            }

            $price = $product->sale_price ?? $product->regular_price;
            $total = ($price * $qty) + $delivery;

            // ১. অর্ডার ডাটা প্রস্তুত (Smart Mapping)
            // নোট: Schema::hasColumn চেক করে ডাটা বসাচ্ছি যাতে মাইগ্রেশন না থাকলেও এরর না দেয়
            $orderData = [
                'client_id'       => $clientId,
                'sender_id'       => $senderId,
                'customer_name'   => $info['name'] ?? 'Messenger Guest',
                'customer_phone'  => $info['phone'] ?? '',
                'shipping_address'=> $info['address'] ?? '',
                'total_amount'    => $total,
                'delivery_charge' => $delivery,
                'order_status'    => 'processing',
                'payment_status'  => 'pending',
                'payment_method'  => $info['payment_method'] ?? 'cod',
            ];

            // 🔥 Optional Columns Mapping (যদি ডাটাবেসে থাকে তবেই বসাবে)
            if (Schema::hasColumn('orders', 'district')) {
                $orderData['district'] = $info['district'] ?? null;
            }
            if (Schema::hasColumn('orders', 'division')) {
                $orderData['division'] = $info['division'] ?? null;
            }
            
            // নোট হ্যান্ডলিং (Variant & User Note)
            $notes = [];
            // ভেরিয়েন্ট টেক্সট তৈরি
            if (!empty($info['variant'])) {
                $vText = is_array($info['variant']) ? implode(', ', array_filter($info['variant'])) : $info['variant'];
                $notes[] = "Variant: " . $vText;
            }
            // ইউজার নোট
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

            // ২. অর্ডার তৈরি
            $order = Order::create($orderData);

            // ৩. আইটেম তৈরি
            // OrderItem টেবিলে কলামের নাম ভিন্ন হতে পারে, তাই চেক করে নেওয়া ভালো
            $itemData = [
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_price' => $price,
                'subtotal'   => $price * $qty // অনেক সিস্টেমে subtotal বা total_price থাকে
            ];
            
            // যদি variant কলাম থাকে
            if (Schema::hasColumn('order_items', 'variant')) {
                $itemData['variant'] = isset($info['variant']) ? (is_array($info['variant']) ? json_encode($info['variant']) : $info['variant']) : null;
            }

            OrderItem::create($itemData);

            // ৪. স্টক আপডেট (Decrement)
            if ($product->manage_stock) {
                $product->decrement('stock_quantity', $qty);

                // স্টক যদি ০ হয়ে যায়, স্ট্যাটাস আপডেট করা
                if ($product->stock_quantity <= 0) {
                    $product->update(['stock_status' => 'out_of_stock']);
                }
            }

            // ৫. সেশন আপডেট (অর্ডার কমপ্লিট - ক্লিনআপ)
            // চ্যাট হিস্ট্রি রাখা হচ্ছে যাতে কাস্টমার কনফার্মেশন মেসেজ দেখতে পায়
            $session->update([
                'customer_info' => [
                    'step' => 'completed', 
                    'last_order_id' => $order->id, 
                    'history' => $info['history'] ?? []
                ]
            ]);

            Log::info("✅ Order #{$order->id} Created Successfully via Chatbot.");

            return $order;
        });
    }
}