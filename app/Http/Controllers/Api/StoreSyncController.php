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

class StoreSyncController extends Controller
{
    public function pushProducts(Request $request)
    {
        // Debugging er jonno log e save kore rakha (pore dorkar na hole muche dite paren)
        Log::info('WP_INCOMING_DATA:', $request->all());

        // Header theke API Key neoar jonno
        $apiKey = $request->header('x-api-key') ?? $request->bearerToken();

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'API Key missing'], 401);
        }

        $client = Client::where('api_token', $apiKey)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Invalid API Key'], 401);
        }

        $products = $request->input('products', []);
        $count = 0;

        foreach ($products as $item) {
            
            // ==========================================
            // 🔥 BULLETPROOF IMAGE EXTRACTOR LOGIC
            // ==========================================
            $allImageUrls = [];

            // ১. WooCommerce Default Format ("images" array)
            if (!empty($item['images']) && is_array($item['images'])) {
                foreach ($item['images'] as $img) {
                    if (is_array($img) && !empty($img['src'])) {
                        $allImageUrls[] = $img['src'];
                    } elseif (is_string($img)) {
                        $allImageUrls[] = $img;
                    }
                }
            }

            // ২. Custom Single Image Format
            if (!empty($item['image_url']) && is_string($item['image_url'])) {
                $allImageUrls[] = $item['image_url'];
            }
            if (!empty($item['thumbnail']) && is_string($item['thumbnail'])) {
                $allImageUrls[] = $item['thumbnail'];
            }

            // ৩. Custom Gallery Format
            if (!empty($item['gallery_urls']) && is_array($item['gallery_urls'])) {
                foreach ($item['gallery_urls'] as $gUrl) {
                    if (is_string($gUrl)) $allImageUrls[] = $gUrl;
                }
            }
            if (!empty($item['gallery']) && is_array($item['gallery'])) {
                foreach ($item['gallery'] as $gUrl) {
                    if (is_string($gUrl)) $allImageUrls[] = $gUrl;
                }
            }

            // ৪. Duplicate URLs remove kora ebang array index thik kora
            $allImageUrls = array_values(array_unique(array_filter($allImageUrls)));

            // ==========================================
            // 🔥 IMAGE DOWNLOAD & SAVE LOGIC
            // ==========================================
            $thumbnailPath = null;
            $galleryPaths = [];

            if (!empty($allImageUrls)) {
                foreach ($allImageUrls as $index => $imageUrl) {
                    try {
                        $imageResponse = Http::timeout(20)->get($imageUrl);
                        
                        if ($imageResponse->successful()) {
                            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                            
                            if ($index === 0) {
                                // Prothom chobiti Main Thumbnail hobe
                                $filename = 'products/thumbnails/sync_' . time() . '_' . uniqid() . '.' . $extension;
                                Storage::disk('public')->put($filename, $imageResponse->body());
                                $thumbnailPath = $filename;
                            } else {
                                // Baki gulo Gallery te jabe (Max 4 ta)
                                if (count($galleryPaths) < 4) {
                                    $filename = 'products/gallery/sync_' . time() . '_' . uniqid() . '.' . $extension;
                                    Storage::disk('public')->put($filename, $imageResponse->body());
                                    $galleryPaths[] = $filename;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Image Sync Error for SKU {$item['sku']} at URL {$imageUrl}: " . $e->getMessage());
                    }
                }
            }

            // ==========================================
            // 🔥 DATABASE SAVE LOGIC
            // ==========================================
            Product::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'sku' => $item['sku'] ?? 'SKU-' . uniqid()
                ],
                [
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']) . '-' . Str::random(4),
                    'regular_price' => $item['regular_price'] ?? 0,
                    'sale_price' => $item['sale_price'] ?? null,
                    'description' => $item['description'] ?? '',
                    'short_description' => $item['short_description'] ?? '',
                    'stock_quantity' => $item['stock_quantity'] ?? 100,
                    'stock_status' => ($item['stock_quantity'] ?? 1) > 0 ? 'in_stock' : 'out_of_stock',
                    'thumbnail' => $thumbnailPath, // Download kora local image path
                    'gallery' => !empty($galleryPaths) ? $galleryPaths : null, // Download kora local gallery paths
                ]
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$count} products with robust image processing.",
        ]);
    }
}