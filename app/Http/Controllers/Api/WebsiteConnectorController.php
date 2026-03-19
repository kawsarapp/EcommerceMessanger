<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Generic Website Connector
 * 
 * Allows any website (WordPress, Shopify, custom HTML, React, etc.)
 * to connect to the AI Commerce Bot SaaS using just an API key.
 * 
 * Endpoints:
 *  GET  /api/connector/verify        — Test connection
 *  POST /api/connector/sync-products — Push products from any website
 *  GET  /api/connector/js-snippet    — Get embeddable JS snippet
 */
class WebsiteConnectorController extends Controller
{
    /**
     * Middleware: Validate API Key → return Client model
     */
    private function resolveClient(Request $request): Client|null
    {
        $apiKey = $request->header('X-Api-Key')
            ?? $request->bearerToken()
            ?? $request->query('api_key');

        if (!$apiKey) return null;

        return Cache::remember("client_by_api_key_{$apiKey}", 300, function () use ($apiKey) {
            return Client::where('api_token', $apiKey)->first();
        });
    }

    // =========================================================
    // 1. VERIFY CONNECTION
    // =========================================================
    /**
     * GET /api/connector/verify
     * Used by any website to test that their API key works.
     */
    public function verify(Request $request)
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API Key. Please copy your API key from the AI Commerce Bot dashboard.',
            ], 401);
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Connected successfully! 🎉',
            'shop'          => $client->shop_name,
            'plan'          => $client->plan?->name ?? 'No Plan',
            'plan_active'   => $client->hasActivePlan(),
            'products'      => $client->products()->count(),
            'integration'   => 'custom_website',
            'endpoints'     => [
                'chat'         => config('app.url') . '/api/v1/chat/widget',
                'sync'         => config('app.url') . '/api/connector/sync-products',
                'widget_js'    => config('app.url') . '/js/chatbot-widget.js',
            ],
        ])->withHeaders(['Access-Control-Allow-Origin' => '*']);
    }

    // =========================================================
    // 2. SYNC PRODUCTS — Any website can POST here
    // =========================================================
    /**
     * POST /api/connector/sync-products
     * 
     * Accepts flexible payload format from any platform.
     * Supports: WooCommerce, Shopify, custom shop, plain HTML.
     * 
     * Body formats accepted:
     *   { "products": [...] }        ← bulk
     *   { "name": "...", ... }       ← single product
     */
    public function syncProducts(Request $request)
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Invalid API Key.'], 401);
        }

        if (!$client->hasActivePlan()) {
            return response()->json(['success' => false, 'message' => 'Your plan is expired. Please renew.'], 403);
        }

        // Normalize payload — accept both bulk and single product
        $rawProducts = [];
        if ($request->has('products') && is_array($request->input('products'))) {
            $rawProducts = $request->input('products');
        } else {
            $rawProducts[] = $request->all();
        }

        $synced = 0;
        $errors = [];

        foreach ($rawProducts as $item) {
            try {
                if (empty($item['name'])) continue;

                // Flexible SKU: accept id, sku, or generate one
                $sku = $item['sku'] ?? ($item['id'] ? 'EXT-' . $item['id'] : 'EXT-' . Str::random(8));

                // Normalize price
                $regularPrice = $this->normalizePrice($item['regular_price'] ?? $item['price'] ?? 0);
                $salePrice    = isset($item['sale_price']) ? $this->normalizePrice($item['sale_price']) : null;

                // Collect all image URLs
                $imageUrls = $this->extractImages($item);

                // Download images
                [$thumbnail, $gallery] = $this->downloadImages($client->id, $sku, $imageUrls);

                // Existing product check
                $existing  = Product::where('client_id', $client->id)->where('sku', $sku)->first();
                $thumbnail = $thumbnail ?? $existing?->thumbnail;
                $gallery   = !empty($gallery) ? $gallery : ($existing?->gallery ?? []);

                // Upsert product
                Product::updateOrCreate(
                    ['client_id' => $client->id, 'sku' => $sku],
                    [
                        'name'              => $item['name'],
                        'slug'              => Str::slug($item['name']) . '-' . strtolower(Str::random(5)),
                        'regular_price'     => $regularPrice,
                        'sale_price'        => $salePrice,
                        'short_description' => $item['short_description'] ?? $item['description'] ?? '',
                        'long_description'  => $item['long_description'] ?? '',
                        'stock_quantity'    => intval($item['stock'] ?? $item['stock_quantity'] ?? 100),
                        'stock_status'      => ($item['stock_status'] ?? 'in_stock') === 'outofstock' ? 'out_of_stock' : 'in_stock',
                        'thumbnail'         => $thumbnail,
                        'gallery'           => $gallery,
                        'video_url'         => $item['video_url'] ?? null,
                        'colors'            => $this->normalizeArray($item['colors'] ?? $item['available_colors'] ?? null),
                        'sizes'             => $this->normalizeArray($item['sizes'] ?? $item['available_sizes'] ?? null),
                        'brand'             => $item['brand'] ?? null,
                        'tags'              => $item['tags'] ?? null,
                        'meta_title'        => $item['name'],
                        'meta_description'  => strip_tags($item['short_description'] ?? $item['description'] ?? ''),
                        'currency'          => $item['currency'] ?? 'BDT',
                    ]
                );

                $synced++;
            } catch (\Exception $e) {
                Log::error("WebsiteConnector sync error: " . $e->getMessage());
                $errors[] = ($item['name'] ?? 'unknown') . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'synced'  => $synced,
            'failed'  => count($errors),
            'errors'  => $errors,
            'message' => "Synced {$synced} products successfully.",
        ]);
    }

    // =========================================================
    // 3. GET JS SNIPPET — Returns an auto-configuring chatbot snippet
    // =========================================================
    /**
     * GET /api/connector/js-snippet?api_key=YOUR_KEY
     * Returns the JS code any website can paste into their HTML.
     */
    public function getJsSnippet(Request $request)
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Invalid API Key.'], 401);
        }

        $appUrl = config('app.url');
        $apiKey = $request->query('api_key') ?? $request->bearerToken() ?? $request->header('X-Api-Key');

        $primaryColor = $client->primary_color ?? '#4f46e5';
        $snippet = <<<JS
<!-- AI Commerce Bot — Paste this before </body> -->
<script>
(function() {
  window.AICB_CONFIG = {
    apiKey: "{$apiKey}",
    shopName: "{$client->shop_name}",
    baseUrl: "{$appUrl}",
    position: "bottom-right",
    primaryColor: "{$primaryColor}",
    greeting: "আমি আপনাকে সাহায্য করতে পারি! 👋 কী খুঁজছেন?"
  };
  var s = document.createElement('script');
  s.src = "{$appUrl}/js/chatbot-widget.js";
  s.async = true;
  document.head.appendChild(s);
})();
</script>
<!-- End AI Commerce Bot -->
JS;

        return response()->json([
            'success'      => true,
            'snippet'      => $snippet,
            'chat_endpoint'=> $appUrl . '/api/v1/chat/widget',
            'instructions' => [
                '1. Copy the snippet above.',
                '2. Paste it just before the </body> tag on every page of your website.',
                '3. The AI chatbot will appear automatically with your brand color & product data.',
                '4. No other configuration needed.',
            ],
        ])->withHeaders(['Access-Control-Allow-Origin' => '*']);
    }

    // =========================================================
    // HELPERS
    // =========================================================
    private function normalizePrice($value): float
    {
        // Strip currency symbols, commas, "Tk", "৳" etc.
        return (float) preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));
    }

    private function normalizeArray($value): ?string
    {
        if (is_array($value)) return implode(',', array_filter($value));
        if (is_string($value)) return $value;
        return null;
    }

    private function extractImages(array $item): array
    {
        $urls = [];

        // WooCommerce-style
        if (!empty($item['images']) && is_array($item['images'])) {
            foreach ($item['images'] as $img) {
                if (is_array($img) && !empty($img['src'])) $urls[] = $img['src'];
                elseif (is_string($img)) $urls[] = $img;
            }
        }

        // Generic fields
        foreach (['image_url', 'thumbnail', 'image', 'photo'] as $field) {
            if (!empty($item[$field]) && is_string($item[$field])) $urls[] = $item[$field];
        }

        // Gallery arrays
        if (!empty($item['gallery']) && is_array($item['gallery'])) {
            foreach ($item['gallery'] as $g) {
                if (is_string($g)) $urls[] = $g;
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    private function downloadImages(int $clientId, string $sku, array $urls): array
    {
        $thumbnail = null;
        $gallery   = [];

        foreach ($urls as $i => $url) {
            try {
                $response = Http::withoutVerifying()->timeout(15)->get($url);
                if (!$response->successful()) continue;

                $ext  = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $slug = Str::slug($sku);

                if ($i === 0) {
                    $path = "products/thumbnails/client_{$clientId}_{$slug}_" . uniqid() . ".{$ext}";
                    Storage::disk('public')->put($path, $response->body());
                    $thumbnail = $path;
                } else {
                    if (count($gallery) < 5) {
                        $path = "products/gallery/client_{$clientId}_{$slug}_{$i}_" . uniqid() . ".{$ext}";
                        Storage::disk('public')->put($path, $response->body());
                        $gallery[] = $path;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Image download failed for SKU {$sku}: {$e->getMessage()}");
            }
        }

        return [$thumbnail, $gallery];
    }
}
