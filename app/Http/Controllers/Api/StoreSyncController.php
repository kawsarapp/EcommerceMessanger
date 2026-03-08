<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Str;

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
                    'thumbnail' => $item['image_url'] ?? null,
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