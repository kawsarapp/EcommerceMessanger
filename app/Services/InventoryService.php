<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class InventoryService
{
    public function getFormattedInventory($client, $userMessage)
    {
        $safeMessage = trim((string) $userMessage);

        // 🛑 Cache রিমুভ করা হয়েছে রিয়েল-টাইম আপডেটের জন্য

        $stopWords = [
            'ki','ace','dam','koto','rate','price','show','product',
            'image','chobi','daw','brand','material','size','color',
            'আছে', 'কী', 'কি', 'দাও', 'কত', 'দাম', 'দেখাও', 'কিনবো', 'nibo', 'chai', 'চাই'
        ];

        $keywords = array_filter(
            explode(' ', strtolower($safeMessage)),
            fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords)
        );

        $query = Product::where('client_id', $client->id)
            ->where('stock_status', 'in_stock');

        if (!empty($keywords)) {
            $query->where(function($q) use ($keywords) {
                foreach ($keywords as $word) {

                    $safeWord = addcslashes($word, '%_');

                    $q->orWhere('name', 'like', "%{$safeWord}%")
                      ->orWhere('tags', 'like', "%{$safeWord}%")
                      ->orWhere('brand', 'like', "%{$safeWord}%")
                      ->orWhere('sku', 'like', "%{$safeWord}%")
                      ->orWhere('material', 'like', "%{$safeWord}%")
                      ->orWhereHas('category', function($cq) use ($safeWord) {
                          $cq->where('name', 'like', "%{$safeWord}%");
                      });
                }
            });
        } else {
            $query->inRandomOrder();
        }

        // 🔥 লিমিট বাড়ানো হয়েছে যাতে এআই বেশি প্রোডাক্ট পায়
        $products = $query->limit(8)->get();

        if ($products->isEmpty()) {
            $products = Product::where('client_id', $client->id)
                ->where('stock_status', 'in_stock')
                ->inRandomOrder()
                ->limit(5)
                ->get();
        }

        return $products->map(function($p) use ($client) {

            $hasDiscount = (
                $p->sale_price &&
                $p->regular_price &&
                $p->regular_price > $p->sale_price
            );

            $discountTxt = $hasDiscount
                ? "OFFER: " . ($p->regular_price - $p->sale_price) .
                  " Tk OFF! (Sale: {$p->sale_price})"
                : "Regular Price: {$p->regular_price}";

            // 🔥 Safe Variant Decode
            $colorsArray = is_array($p->colors)
                ? $p->colors
                : (is_string($p->colors) ? json_decode($p->colors, true) ?? [] : []);

            $sizesArray = is_array($p->sizes)
                ? $p->sizes
                : (is_string($p->sizes) ? json_decode($p->sizes, true) ?? [] : []);

            $colors = !empty($colorsArray) ? implode(', ', $colorsArray) : 'N/A';
            $sizes  = !empty($sizesArray) ? implode(', ', $sizesArray) : 'N/A';

            // 🔥 Gallery নিরাপদভাবে হ্যান্ডেল
            $extraImages = [];

            $galleryData = is_array($p->gallery)
                ? $p->gallery
                : (is_string($p->gallery) ? json_decode($p->gallery, true) ?? [] : []);

            if (is_array($galleryData)) {
                foreach ($galleryData as $img) {
                    if (!empty($img)) {
                        $extraImages[] = asset('storage/' . ltrim($img, '/'));
                    }
                }
            }

            if ($p->image && $p->image !== $p->thumbnail) {
                $extraImages[] = asset('storage/' . ltrim($p->image, '/'));
            }

            // 🔥 Duplicate Remove
            $extraImages = array_unique($extraImages);

            $mainImage = $p->thumbnail
                ? asset('storage/' . ltrim($p->thumbnail, '/'))
                : ($p->image
                    ? asset('storage/' . ltrim($p->image, '/'))
                    : null);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand ?? 'N/A',
                'material' => $p->material ?? 'N/A',
                'available_colors' => $colors,
                'available_sizes' => $sizes,
                'warranty_return_policy' =>
                    ($p->warranty ?? 'No Warranty') .
                    ' | ' .
                    ($p->return_policy ?? 'N/A'),
                'price_info' => $discountTxt,
                'stock' => $p->stock_quantity,
                'desc' => Str::limit(
                    strip_tags($p->description ?? $p->short_description),
                    250
                ),
                'link' => route(
                    'shop.product.details',
                    [$client->slug, $p->slug]
                ),
                // 🔥 AI এর প্রম্পট অনুযায়ী 'image_url' সেট করা হয়েছে
                'image_url' => $mainImage,
                'gallery_images' =>
                    empty($extraImages)
                        ? 'N/A'
                        : implode(', ', $extraImages)
            ];
        })->toJson();
    }
}