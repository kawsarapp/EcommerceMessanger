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

        $keywords = array_filter(
            explode(' ', mb_strtolower($cleanMessage, 'UTF-8')),
            fn($w) => mb_strlen($w) > 2 && !in_array($w, $stopWords)
        );

        // ── External API fallback ────────────────────────────────────────────
        if (!empty($client->external_api_url)) {
            try {
                $searchQuery = implode(' ', $keywords);
                $queryStr    = http_build_query(['q' => $searchQuery, 'buyer_message' => $safeMessage]);
                $url         = rtrim($client->external_api_url, '/') . '?' . $queryStr;

                $req = \Illuminate\Support\Facades\Http::timeout(10);
                if (!empty($client->external_product_api_key)) {
                    $req = $req->withToken($client->external_product_api_key);
                }
                $response = $req->get($url);
                if ($response->successful() && is_array($response->json())) {
                    return json_encode(array_slice($response->json(), 0, 8), JSON_UNESCAPED_UNICODE);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("External API failed for Client #{$client->id}: " . $e->getMessage());
            }
        }

        // ── Local DB search ──────────────────────────────────────────────────
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

        // Return both in-stock AND out-of-stock so AI can suggest alternatives
        $products = $query->limit(10)->get();

        if ($products->isEmpty()) {
            $products = Product::where('client_id', $client->id)
                ->with('category')
                ->where('stock_status', 'in_stock')
                ->inRandomOrder()
                ->limit(5)
                ->get();
        }

        return $products->map(function ($p) use ($client) {

            $finalPrice = ($p->sale_price > 0 && $p->sale_price < $p->regular_price)
                ? $p->sale_price
                : $p->regular_price;

            // ── Variants ────────────────────────────────────────────────────
            $colorsArray = is_array($p->colors) ? $p->colors : (is_string($p->colors) ? json_decode($p->colors, true) ?? [] : []);
            $sizesArray  = is_array($p->sizes)  ? $p->sizes  : (is_string($p->sizes)  ? json_decode($p->sizes,  true) ?? [] : []);
            $colors = !empty($colorsArray) ? implode(', ', $colorsArray) : 'N/A';
            $sizes  = !empty($sizesArray)  ? implode(', ', $sizesArray)  : 'N/A';

            // ── Images ──────────────────────────────────────────────────────
            $mainImage = $p->thumbnail
                ? asset('storage/' . ltrim($p->thumbnail, '/'))
                : ($p->image ? asset('storage/' . ltrim($p->image, '/')) : null);

            $galleryUrls  = [];
            $galleryArray = is_array($p->gallery) ? $p->gallery : (is_string($p->gallery) ? json_decode($p->gallery, true) ?? [] : []);
            foreach ($galleryArray as $gPath) {
                if (!empty($gPath)) {
                    $galleryUrls[] = asset('storage/' . ltrim($gPath, '/'));
                }
            }

            // ── Description (full short_desc + truncated long desc) ─────────
            $shortDesc = strip_tags($p->short_description ?? '');
            $longDesc  = strip_tags($p->description ?? '');
            $desc = $shortDesc ?: Str::limit($longDesc, 300);

            // ── Stock label ─────────────────────────────────────────────────
            $stockLabel = $p->stock_status === 'in_stock'
                ? ($p->stock_quantity > 0 ? "In Stock ({$p->stock_quantity} pcs)" : "In Stock")
                : "Out of Stock ❌";

            return [
                'id'              => $p->id,
                'sku'             => $p->sku            ?? 'N/A',
                'name'            => $p->name,
                'brand'           => $p->brand          ?? 'N/A',
                'category'        => $p->category?->name ?? 'N/A',
                'price'           => "৳{$finalPrice}",
                'regular_price'   => "৳{$p->regular_price}",
                'sale_price'      => $p->sale_price > 0 ? "৳{$p->sale_price}" : null,
                'stock'           => $stockLabel,
                'description'     => $desc,
                'colors'          => $colors,
                'sizes'           => $sizes,
                'main_image'      => $mainImage,
                // ✅ Gallery: AI uses [ATTACH_IMAGE: url] for each of these
                'gallery_images'  => $galleryUrls,
                // ✅ Video: AI sends direct link when customer asks
                'video_url'       => $p->video_url ?? null,
                'product_url'     => route('shop.product.details', [$client->slug, $p->slug]),
                'weight'          => $p->weight         ?? null,
            ];
        })->toJson(JSON_UNESCAPED_UNICODE);
    }
}