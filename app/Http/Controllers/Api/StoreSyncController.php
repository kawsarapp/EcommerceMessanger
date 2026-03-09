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
        // Debugging (লগ চেক করার জন্য)
        Log::info('WP_WEBHOOK_RECEIVED:', $request->all());

        // 🔥 FIX 1: Header এর পাশাপাশি URL parameter থেকেও API Key নেওয়ার ব্যবস্থা
        $apiKey = $request->header('x-api-key') ?? $request->bearerToken() ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'API Key missing'], 401);
        }

        $client = Client::where('api_token', $apiKey)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Invalid API Key'], 401);
        }

        // 🔥 FIX 2: Bulk ইম্পোর্ট (Custom Plugin) এবং Single Webhook (WooCommerce Default) দুইটার জন্যই সাপোর্ট
        $products = [];
        if ($request->has('products') && is_array($request->input('products'))) {
            $products = $request->input('products'); // Bulk Import format
        } else {
            $products[] = $request->all(); // Default WooCommerce Single Webhook format
        }

        $count = 0;

        foreach ($products as $item) {
            
            // যদি কোনো নাম না থাকে, তবে সেটি ভ্যালিড প্রোডাক্ট ডাটা নয়
            if (empty($item['name'])) continue;

            // ==========================================
            // 🔥 BULLETPROOF IMAGE EXTRACTOR LOGIC
            // ==========================================
            $allImageUrls = [];

            if (!empty($item['images']) && is_array($item['images'])) {
                foreach ($item['images'] as $img) {
                    if (is_array($img) && !empty($img['src'])) {
                        $allImageUrls[] = $img['src'];
                    } elseif (is_string($img)) {
                        $allImageUrls[] = $img;
                    }
                }
            }

            if (!empty($item['image_url']) && is_string($item['image_url'])) $allImageUrls[] = $item['image_url'];
            if (!empty($item['thumbnail']) && is_string($item['thumbnail'])) $allImageUrls[] = $item['thumbnail'];

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

            $allImageUrls = array_values(array_unique(array_filter($allImageUrls)));

            // ==========================================
            // 🔥 IMAGE DOWNLOAD LOGIC
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
                                $filename = 'products/thumbnails/sync_' . time() . '_' . uniqid() . '.' . $extension;
                                Storage::disk('public')->put($filename, $imageResponse->body());
                                $thumbnailPath = $filename;
                            } else {
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
                    'sku' => (!empty($item['sku'])) ? $item['sku'] : 'SKU-' . Str::random(6)
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
                    'thumbnail' => $thumbnailPath, 
                    'gallery' => !empty($galleryPaths) ? $galleryPaths : null, 
                ]
            );
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$count} products.",
        ]);
    }
}