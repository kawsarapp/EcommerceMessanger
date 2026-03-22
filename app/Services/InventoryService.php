<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Store\StoreDriverFactory;
use Illuminate\Support\Str;

class InventoryService
{
    public function getFormattedInventory($client, $userMessage)
    {
        $safeMessage = trim((string) $userMessage);
        $cleanMessage = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $safeMessage);

        $stopWords = [
            'ki','ace','dam','koto','rate','price','show','product',
            'image','chobi','daw','brand','material','size','color',
            'want','buy','to',
            'আছে', 'কী', 'কি', 'দাও', 'কত', 'দাম', 'দেখাও', 'কিনবো', 'nibo', 'chai', 'চাই'
        ];

        $keywords = array_values(array_filter(
            explode(' ', mb_strtolower($cleanMessage, 'UTF-8')),
            fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords)
        ));
        $searchQuery = implode(' ', $keywords) ?: $safeMessage;

        // ══════════════════════════════════════════════════════════════════════
        // ✅ DRIVER-BASED ROUTING (Adapter Pattern)
        //    integration_type = 'external_api' → ExternalApiDriver (Plugin API)
        //    integration_type = 'hosted'        → HostedProductDriver (local DB)
        // ══════════════════════════════════════════════════════════════════════
        if ($client->integration_type === 'external_api') {
            try {
                $driver   = StoreDriverFactory::for($client);
                $products = $driver->searchProducts($searchQuery, ['limit' => 10]);

                if (!empty($products)) {
                    // Map external product format → inventory format
                    $mapped = array_map(fn($p) => $this->mapExternalProduct($p, $client), $products);
                    return json_encode($mapped, JSON_UNESCAPED_UNICODE);
                }

                // Fallback: get first 5 products (no search filter)
                $products = $driver->searchProducts('', ['limit' => 5]);
                $mapped   = array_map(fn($p) => $this->mapExternalProduct($p, $client), $products);
                return json_encode($mapped, JSON_UNESCAPED_UNICODE);

            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("InventoryService ExternalDriver failed for Client #{$client->id}: " . $e->getMessage());
                // Fall through to local DB
            }
        }

        // ══════════════════════════════════════════════════════════════════════
        // LOCAL DB SEARCH (hosted mode or external fallback)
        // ══════════════════════════════════════════════════════════════════════
        $query = Product::where('client_id', $client->id)->with('category');

        if (!empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $safeWord = addcslashes($word, '%_');
                    $q->orWhere('name',  'like', "%{$safeWord}%")
                      ->orWhere('tags',  'like', "%{$safeWord}%")
                      ->orWhere('brand', 'like', "%{$safeWord}%")
                      ->orWhere('sku',   'like', "%{$safeWord}%")
                      ->orWhere('short_description', 'like', "%{$safeWord}%")
                      ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$safeWord}%"));
                }
            });
        } else {
            $query->inRandomOrder();
        }

        $products = $query->limit(10)->get();

        if ($products->isEmpty()) {
            $products = Product::where('client_id', $client->id)
                ->with('category')
                ->where('stock_status', 'in_stock')
                ->inRandomOrder()
                ->limit(5)
                ->get();
        }

        return $products->map(fn($p) => $this->mapHostedProduct($p, $client))->toJson(JSON_UNESCAPED_UNICODE);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MAPPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * External API response → inventory format for AI
     */
    private function mapExternalProduct(array $p, $client): array
    {
        $price      = $p['sale_price'] ?? $p['price'] ?? 0;
        $stockLabel = ($p['in_stock'] ?? true)
            ? 'In Stock (' . ($p['stock'] ?? '?') . ' pcs)'
            : 'Out of Stock ❌';

        return [
            'id'            => $p['id'],
            'sku'           => $p['sku'] ?? 'N/A',
            'name'          => $p['title'] ?? $p['name'] ?? '',
            'brand'         => $p['brand'] ?? 'N/A',
            'category'      => $p['category'] ?? 'N/A',
            'price'         => "৳{$price}",
            'regular_price' => "৳" . ($p['price'] ?? $price),
            'sale_price'    => isset($p['sale_price']) && $p['sale_price'] ? "৳{$p['sale_price']}" : null,
            'stock'         => $stockLabel,
            'description'   => $p['description'] ?? '',
            'colors'        => 'N/A',
            'sizes'         => 'N/A',
            'main_image'    => $p['image'] ?? null,
            'gallery_images'=> [],
            'video_url'     => $p['video_url'] ?? null,
            'product_url'   => $p['url'] ?? null,
            'source'        => 'external_api', // AI context এর জন্য
        ];
    }

    /**
     * Hosted DB Product model → inventory format for AI
     */
    private function mapHostedProduct(Product $p, $client): array
    {
        $finalPrice = ($p->sale_price > 0 && $p->sale_price < $p->regular_price)
            ? $p->sale_price : $p->regular_price;

        $colorsArray = is_array($p->colors) ? $p->colors : (is_string($p->colors) ? json_decode($p->colors, true) ?? [] : []);
        $sizesArray  = is_array($p->sizes)  ? $p->sizes  : (is_string($p->sizes)  ? json_decode($p->sizes,  true) ?? [] : []);

        $mainImage = $p->thumbnail
            ? asset('storage/' . ltrim($p->thumbnail, '/'))
            : ($p->image ? asset('storage/' . ltrim($p->image, '/')) : null);

        $galleryUrls  = [];
        $galleryArray = is_array($p->gallery) ? $p->gallery : (is_string($p->gallery) ? json_decode($p->gallery, true) ?? [] : []);
        foreach ($galleryArray as $gPath) {
            if (!empty($gPath)) $galleryUrls[] = asset('storage/' . ltrim($gPath, '/'));
        }

        $shortDesc = strip_tags($p->short_description ?? '');
        $longDesc  = strip_tags($p->description ?? '');
        $desc = $shortDesc ?: Str::limit($longDesc, 300);

        $stockLabel = $p->stock_status === 'in_stock'
            ? ($p->stock_quantity > 0 ? "In Stock ({$p->stock_quantity} pcs)" : "In Stock")
            : "Out of Stock ❌";

        return [
            'id'             => $p->id,
            'sku'            => $p->sku           ?? 'N/A',
            'name'           => $p->name,
            'brand'          => $p->brand         ?? 'N/A',
            'category'       => $p->category?->name ?? 'N/A',
            'price'          => "৳{$finalPrice}",
            'regular_price'  => "৳{$p->regular_price}",
            'sale_price'     => $p->sale_price > 0 ? "৳{$p->sale_price}" : null,
            'stock'          => $stockLabel,
            'description'    => $desc,
            'colors'         => !empty($colorsArray) ? implode(', ', $colorsArray) : 'N/A',
            'sizes'          => !empty($sizesArray)  ? implode(', ', $sizesArray)  : 'N/A',
            'main_image'     => $mainImage,
            'gallery_images' => $galleryUrls,
            'video_url'      => $p->video_url     ?? null,
            'product_url'    => route('shop.product.details', [$client->slug, $p->slug]),
            'weight'         => $p->weight        ?? null,
        ];
    }
}