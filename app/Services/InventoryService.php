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
            
            // কিওয়ার্ড ফিল্টারিং (AI যেন সহজে প্রোডাক্ট খুঁজে পায়)
            $stopWords = ['ki', 'ace', 'dam', 'koto', 'rate', 'price', 'show', 'product', 'image', 'chobi', 'daw', 'brand', 'material', 'size', 'color'];
            $keywords = array_filter(explode(' ', strtolower($userMessage)), fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords));
            
            $query = Product::where('client_id', $client->id)->where('stock_status', 'in_stock');
            
            if (!empty($keywords)) {
                $query->where(function($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%")
                          ->orWhere('brand', 'like', "%{$word}%")
                          ->orWhere('material', 'like', "%{$word}%")
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

                // 🎨 JSON কলামগুলো ডিকোড করে প্লেইন টেক্সট করা (যাতে AI বুঝতে পারে)
                $colors = is_array($p->colors) ? implode(', ', $p->colors) : (is_string($p->colors) ? implode(', ', json_decode($p->colors, true) ?? []) : 'N/A');
                $sizes = is_array($p->sizes) ? implode(', ', $p->sizes) : (is_string($p->sizes) ? implode(', ', json_decode($p->sizes, true) ?? []) : 'N/A');

                // 🖼️ গ্যালারির একাধিক ছবি প্রসেস করা
                $extraImages = [];
                $galleryData = is_array($p->gallery) ? $p->gallery : (is_string($p->gallery) ? json_decode($p->gallery, true) : []);
                
                if (is_array($galleryData)) {
                    foreach ($galleryData as $img) {
                        $extraImages[] = asset('storage/' . $img);
                    }
                }
                
                // যদি thumbnail এর পাশাপাশি main image থাকে, সেটাও গ্যালারিতে দিয়ে দেওয়া
                if ($p->image && $p->image !== $p->thumbnail) {
                    $extraImages[] = asset('storage/' . $p->image);
                }

                // AI-এর জন্য মেইন ইমেজ সিলেক্ট করা
                $mainImage = $p->thumbnail ? asset('storage/' . $p->thumbnail) : ($p->image ? asset('storage/' . $p->image) : null);

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'brand' => $p->brand ?? 'N/A',
                    'material' => $p->material ?? 'N/A',
                    'available_colors' => empty($colors) ? 'N/A' : $colors,
                    'available_sizes' => empty($sizes) ? 'N/A' : $sizes,
                    'warranty_return_policy' => ($p->warranty ?? 'No Warranty') . ' | ' . ($p->return_policy ?? 'N/A'),
                    'price_info' => $discountTxt,
                    'stock' => $p->stock_quantity,
                    'desc' => Str::limit(strip_tags($p->description ?? $p->short_description), 250),
                    'link' => route('shop.product.details', [$client->slug, $p->slug]),
                    'image' => $mainImage, // কাস্টমার ১টি ছবি চাইলে এটি দিবে
                    'gallery_images' => empty($extraImages) ? 'N/A' : implode(', ', $extraImages) // কাস্টমার আরও ছবি চাইলে এগুলো দিবে
                ];
            })->toJson();
        });
    }
}