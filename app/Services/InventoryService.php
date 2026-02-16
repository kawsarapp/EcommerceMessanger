<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InventoryService
{
    /**
     * 📦 AI-এর জন্য ইনভেন্টরি ডাটা ফরম্যাট করা
     */
    public function getFormattedInventory($client, $userMessage)
    {
        $cacheKey = "inv_{$client->id}_" . md5(Str::limit($userMessage, 20));

        return Cache::remember($cacheKey, 60, function () use ($client, $userMessage) {
            
            // কিওয়ার্ড ফিল্টারিং
            $stopWords = ['ki', 'ace', 'dam', 'koto', 'rate', 'price', 'show', 'product'];
            $keywords = array_filter(explode(' ', strtolower($userMessage)), fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords));
            
            $query = Product::where('client_id', $client->id)->where('stock_status', 'in_stock');
            
            if (!empty($keywords)) {
                $query->where(function($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%")
                          ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$word}%"));
                    }
                });
            } else {
                $query->inRandomOrder();
            }

            $products = $query->limit(5)->get();
            
            // ফলব্যাক: যদি কিছু না পাওয়া যায়, তবে রেন্ডম ৩টি দেখাও
            if ($products->isEmpty()) {
                $products = Product::where('client_id', $client->id)
                    ->where('stock_status', 'in_stock')
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
            }

            return $products->map(function($p) use ($client) {
                // ডিসকাউন্ট লজিক
                $hasDiscount = ($p->sale_price && $p->regular_price > $p->sale_price);
                $discountTxt = $hasDiscount 
                    ? "OFFER: " . ($p->regular_price - $p->sale_price) . " Tk OFF! (Sale: {$p->sale_price})" 
                    : "Regular Price: {$p->regular_price}";

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price_info' => $discountTxt,
                    'stock' => $p->stock_quantity,
                    'desc' => Str::limit(strip_tags($p->description ?? $p->short_description), 250),
                    'video' => $p->video_url ? $p->video_url : 'N/A',
                    'link' => route('shop.product.details', [$client->slug, $p->slug]),
                    'image' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null
                ];
            })->toJson();
        });
    }
}