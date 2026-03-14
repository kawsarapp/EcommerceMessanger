<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class InventoryService
{
    public function getFormattedInventory($client, $userMessage)
    {
        $safeMessage = trim((string) $userMessage);

        // Clean punctuation for better matching
        $cleanMessage = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $safeMessage);

        $stopWords = [
            'ki','ace','dam','koto','rate','price','show','product',
            'image','chobi','daw','brand','material','size','color',
            'want','buy', 'to',
            'আছে', 'কী', 'কি', 'দাও', 'কত', 'দাম', 'দেখাও', 'কিনবো', 'nibo', 'chai', 'চাই'
        ];

        // 🔥 Use mb_strtolower for Bengali support
        $keywords = array_filter(
            explode(' ', mb_strtolower($cleanMessage, 'UTF-8')),
            fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords)
        );

        // ==========================================
        // 🔥 SaaS Feature: REAL-TIME EXTERNAL API LOOKUP
        // ==========================================
        if (!empty($client->external_api_url)) {
            try {
                $searchQuery = implode(' ', $keywords);
                $queryStr = http_build_query(['q' => $searchQuery, 'buyer_message' => $safeMessage]);
                $url = rtrim($client->external_api_url, '/') . '?' . $queryStr;
                
                $pendingRequest = \Illuminate\Support\Facades\Http::timeout(10);
                if (!empty($client->external_product_api_key)) {
                    $pendingRequest = $pendingRequest->withToken($client->external_product_api_key);
                }

                $response = $pendingRequest->get($url);
                if ($response->successful() && is_array($response->json())) {
                    // API returns JSON, just convert arrays to JSON strings and return
                    // Assume the external API returns a format similar to our local mapped format
                    return json_encode(array_slice($response->json(), 0, 8), JSON_UNESCAPED_UNICODE);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("External API Sync failed for Client ID {$client->id}: " . $e->getMessage());
                // Fallback to local DB if API fails
            }
        }

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

            $finalPrice = ($p->sale_price > 0 && $p->sale_price < $p->regular_price) 
                ? $p->sale_price 
                : $p->regular_price;

            $colorsArray = is_array($p->colors) ? $p->colors : (is_string($p->colors) ? json_decode($p->colors, true) ?? [] : []);
            $sizesArray = is_array($p->sizes) ? $p->sizes : (is_string($p->sizes) ? json_decode($p->sizes, true) ?? [] : []);

            $colors = !empty($colorsArray) ? implode(', ', $colorsArray) : 'N/A';
            $sizes  = !empty($sizesArray) ? implode(', ', $sizesArray) : 'N/A';

            $mainImage = $p->thumbnail 
                ? asset('storage/' . ltrim($p->thumbnail, '/')) 
                : ($p->image ? asset('storage/' . ltrim($p->image, '/')) : null);

            return [
                'id' => $p->id,
                'sku' => $p->sku ?? 'N/A',
                'name' => $p->name,
                'available_colors' => $colors,
                'available_sizes' => $sizes,
                'price' => $finalPrice . " Tk",
                'stock' => $p->stock_quantity,
                'desc' => Str::limit(strip_tags($p->description ?? $p->short_description), 150),
                'link' => route('shop.product.details', [$client->slug, $p->slug]),
                'image_url' => $mainImage
            ];
        })->toJson();
    }
}