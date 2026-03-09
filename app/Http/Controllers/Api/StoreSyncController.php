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
            
            // 🔥 নতুন ফিচার: ছবি ডাউনলোড করে লোকাল স্টোরেজে সেভ করার লজিক
            $thumbnailPath = null;
            if (!empty($item['image_url'])) {
                try {
                    // ওয়ার্ডপ্রেস থেকে ছবি ডাউনলোড করা হচ্ছে
                    $imageResponse = Http::timeout(20)->get($item['image_url']);
                    
                    if ($imageResponse->successful()) {
                        $imageContents = $imageResponse->body();
                        // ছবির অরিজিনাল এক্সটেনশন বের করা (না পেলে ডিফল্ট jpg)
                        $extension = pathinfo(parse_url($item['image_url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                        // ইউনিক নাম তৈরি করা
                        $filename = 'products/sync_' . time() . '_' . uniqid() . '.' . $extension;
                        
                        // সার্ভারে ছবি সেভ করা
                        Storage::disk('public')->put($filename, $imageContents);
                        $thumbnailPath = $filename;
                    }
                } catch (\Exception $e) {
                    Log::error("Image Sync Error for SKU {$item['sku']}: " . $e->getMessage());
                }
            }

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
                    // 🔥 এখন আর ডিরেক্ট লিংক নয়, সার্ভারে সেভ করা লোকাল ছবির পাথ ডাটাবেসে যাবে
                    'thumbnail' => $thumbnailPath ?? ($item['image_url'] ?? null), 
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