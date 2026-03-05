<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class InventoryService
{
    public function getFormattedInventory($client, $userMessage)
    {
        $safeMessage = trim((string) $userMessage);

        $stopWords = [
            'ki','ace','dam','koto','rate','price','show','product',
            'image','chobi','daw','brand','material','size','color',
            'আছে', 'কী', 'কি', 'দাও', 'কত', 'দাম', 'দেখাও', 'কিনবো', 'nibo', 'chai', 'চাই'
        ];

        $keywords = array_filter(
            explode(' ', strtolower($safeMessage)),
            fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords)
        );

        $query = Product::where('client_id', $client->id)->where('stock_status', 'in_stock');

        if (!empty($keywords)) {
            $query->where(function($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $safeWord = addcslashes($word, '%_');
                    $q->orWhere('name', 'like', "%{$safeWord}%")
                      ->orWhere('tags', 'like', "%{$safeWord}%")
                      ->orWhere('brand', 'like', "%{$safeWord}%")
                      ->orWhere('sku', 'like', "%{$safeWord}%")
                      ->orWhereHas('category', function($cq) use ($safeWord) {
                          $cq->where('name', 'like', "%{$safeWord}%");
                      });
                }
            });
        } else {
            $query->inRandomOrder();
        }

        $products = $query->limit(8)->get();

        if ($products->isEmpty()) {
            $products = Product::where('client_id', $client->id)
                ->where('stock_status', 'in_stock')
                ->inRandomOrder()
                ->limit(5)
                ->get();
        }

        return $products->map(function($p) use ($client) {

            // 🔥 FIX: এআইকে শুধু ফাইনাল দাম দেওয়া হচ্ছে। কোনো বিয়োগের সুযোগ নেই!
            $finalPrice = ($p->sale_price > 0 && $p->sale_price < $p->regular_price) 
                ? $p->sale_price 
                : $p->regular_price;

            $colorsArray = is_array($p->colors) ? $p->colors : (is_string($p->colors) ? json_decode($p->colors, true) ?? [] : []);
            $sizesArray = is_array($p->sizes) ? $p->sizes : (is_string($p->sizes) ? json_decode($p->sizes, true) ?? [] : []);

            $colors = !empty($colorsArray) ? implode(', ', $colorsArray) : 'N/A';
            $sizes  = !empty($sizesArray) ? implode(', ', $sizesArray) : 'N/A';

            $mainImage = $p->thumbnail ? asset('storage/' . ltrim($p->thumbnail, '/')) : ($p->image ? asset('storage/' . ltrim($p->image, '/')) : null);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'available_colors' => $colors,
                'available_sizes' => $sizes,
                'price' => $finalPrice . " Tk", // Only exact price goes to AI
                'stock' => $p->stock_quantity,
                'desc' => Str::limit(strip_tags($p->description ?? $p->short_description), 150),
                'link' => route('shop.product.details', [$client->slug, $p->slug]),
                'image_url' => $mainImage
            ];
        })->toJson();
    }
}