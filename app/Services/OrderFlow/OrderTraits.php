<?php
namespace App\Services\OrderFlow;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

trait OrderTraits
{
    public function decodeVariants($data)
    {
        if (empty($data)) return [];
        if (is_array($data)) return array_filter($data, fn($item) => strtolower($item) !== 'n/a' && !empty($item));
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
        if (is_string($data)) return array_map('trim', explode(',', $data));
        return [];
    }

    public function findProductSystematically($clientId, $message)
    {
        $message = (string) $message;
        if (empty(trim($message))) return null;

        // কমন শব্দ বাদ দেওয়া
        $stopWords = ['ami', 'kinbo', 'chai', 'korte', 'jonno', 'ace', 'ase', 'nibo', 'product', 'koto', 'dam', 'price', 'hi', 'hello', 'akta'];
        
        $keywords = array_filter(explode(' ', $message), function($word) use ($stopWords) {
            return mb_strlen(trim($word)) >= 3 && !in_array(strtolower($word), $stopWords);
        });

        if (empty($keywords)) return null;

        Log::info("🔍 Searching keywords: " . implode(', ', $keywords));

        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        $query->where(function($q) use ($keywords) {
            foreach($keywords as $word) {
                $word = trim($word);
                // Broad Search using LIKE
                $q->orWhere('name', 'LIKE', "%{$word}%")
                  ->orWhere('tags', 'LIKE', "%{$word}%")
                  ->orWhereHas('category', function($cq) use ($word) {
                      $cq->where('name', 'LIKE', "%{$word}%");
                  });
            }
        });

        $product = $query->latest()->first();
        
        if ($product) {
            Log::info("✅ Product Found: {$product->name}");
        } else {
            Log::warning("❌ No Product Found");
        }

        return $product;
    }
}