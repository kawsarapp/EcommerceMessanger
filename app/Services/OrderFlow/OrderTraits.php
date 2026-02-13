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
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_filter($decoded, fn($item) => strtolower($item) !== 'n/a' && !empty($item));
        }
        if (is_string($data)) {
            if (strtolower($data) === 'n/a') return [];
            if (str_contains($data, ',')) return array_map('trim', explode(',', $data));
            return [$data];
        }
        return [];
    }

    /**
     * ✅ SQL FIX: Removed search on non-existent 'category' column
     */
    public function findProductSystematically($clientId, $message)
    {
        // Null Safety Check
        $message = (string) $message; 
        if (empty(trim($message))) return null;

        // 1. Stop words removal
        $stopWords = ['ami', 'kinbo', 'chai', 'korte', 'jonno', 'কিনবো', 'চাই', 'জন্য', 'দিবেন', 'ace', 'ase', 'আছে', 'নিব', 'nibo', 'product', 'koto', 'dam', 'price', 'hi', 'hello'];
        
        $keywords = array_filter(explode(' ', $message), function($word) use ($stopWords) {
            return is_string($word) && mb_strlen(trim($word)) >= 2 && !in_array(strtolower($word), $stopWords);
        });

        if (empty($keywords)) return null;

        Log::info("🔍 Searching for Client $clientId with Keywords: " . implode(', ', $keywords));

        $query = Product::where('client_id', $clientId)
            ->where('stock_status', 'in_stock');

        // 2. Fuzzy Search (Corrected for SQL Schema)
        $query->where(function($q) use ($keywords) {
            foreach($keywords as $word) {
                $word = trim($word);
                $q->orWhere('name', 'LIKE', "%{$word}%")
                  ->orWhere('sku', 'LIKE', "%{$word}%")
                  ->orWhere('tags', 'LIKE', "%{$word}%")
                  // SQL FIX: 'category' কলাম নেই, তাই এটি বাদ দিয়েছি। রিলেশনশিপ থাকলে এটি কাজ করবে:
                  ->orWhereHas('category', function($catQ) use ($word) { 
                      $catQ->where('name', 'LIKE', "%{$word}%");
                  });
            }
        });

        $product = $query->latest()->first();
        
        if ($product) {
            Log::info("✅ Product Found: {$product->name} (ID: {$product->id})");
        } else {
            Log::warning("❌ No Product Found.");
        }

        return $product;
    }
}