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
        // Header এবং URL parameter থেকে API Key রিসিভ করা
        $apiKey = $request->header('x-api-key') ?? $request->bearerToken() ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'API Key missing'], 401);
        }

        $client = Client::where('api_token', $apiKey)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Invalid API Key'], 401);
        }

        $products = [];
        if ($request->has('products') && is_array($request->input('products'))) {
            $products = $request->input('products'); // Bulk Import format
        } else {
            $products[] = $request->all(); // Webhook format
        }

        $count = 0;

        foreach ($products as $item) {
            
            if (empty($item['name'])) continue;

            // 🔥 FIX 1: SKU null থাকলে ওয়ার্ডপ্রেসের অরিজিনাল ID (যেমন: WP-361) ব্যবহার করা হবে, যাতে ডুপ্লিকেট না হয়!
            $wpId = $item['id'] ?? uniqid();
            $sku = (!empty($item['sku'])) ? $item['sku'] : 'WP-' . $wpId;

            // ==========================================
            // 🔥 BULLETPROOF IMAGE EXTRACTOR
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

            $allImageUrls = array_values(array_unique(array_filter($allImageUrls)));

            // ==========================================
            // 🔥 IMAGE DOWNLOAD LOGIC (SSL BYPASS)
            // ==========================================
            $thumbnailPath = null;
            $galleryPaths = [];

            if (!empty($allImageUrls)) {
                foreach ($allImageUrls as $index => $imageUrl) {
                    try {
                        // 🔥 FIX 2: withoutVerifying() যোগ করা হয়েছে যেন Test ডোমেইন থেকেও ছবি ডাউনলোড হয়
                        $imageResponse = Http::withoutVerifying()->timeout(30)->get($imageUrl);
                        
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
                        Log::error("Image Sync Error for SKU {$sku} at URL {$imageUrl}: " . $e->getMessage());
                    }
                }
            }

            // ==========================================
            // 🔥 DATABASE SAVE LOGIC
            // ==========================================
            $existingProduct = Product::where('client_id', $client->id)->where('sku', $sku)->first();
            
            // নতুন ছবি না আসলে আগের ছবিটাই ডাটাবেসে রেখে দিব
            $finalThumbnail = $thumbnailPath ?? ($existingProduct->thumbnail ?? null);
            $finalGallery = !empty($galleryPaths) ? $galleryPaths : ($existingProduct->gallery ?? null);

            Product::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'sku' => $sku
                ],
                [
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']) . '-' . Str::random(4),
                    'regular_price' => $item['regular_price'] ?? ($item['price'] ?? 0),
                    'sale_price' => $item['sale_price'] ?? null,
                    'description' => $item['description'] ?? '',
                    'short_description' => $item['short_description'] ?? '',
                    'stock_quantity' => $item['stock_quantity'] ?? 100,
                    'stock_status' => ($item['stock_quantity'] ?? 1) > 0 ? 'in_stock' : 'out_of_stock',
                    'thumbnail' => $finalThumbnail, 
                    'gallery' => $finalGallery, 
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