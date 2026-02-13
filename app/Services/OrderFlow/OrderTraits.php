<?php
namespace App\Services\OrderFlow;

use App\Models\Product;

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

   
    
    public function findProductSystematically($clientId, $message)
    {
        // 1. অপ্রয়োজনীয় শব্দ বাদ দেওয়া
        $stopWords = ['ami', 'kinbo', 'chai', 'korte', 'jonno', 'কিনবো', 'চাই', 'জন্য', 'দিবেন', 'ace', 'ase', 'আছে', 'নিব', 'nibo', 'product', 'koto', 'dam', 'price'];
        
        $keywords = array_filter(explode(' ', $message), function($word) use ($stopWords) {
            return is_string($word) && mb_strlen(trim($word)) >= 2 && !in_array(strtolower($word), $stopWords);
        });

        if (empty($keywords)) return null;

        $query = Product::where('client_id', $clientId)
            ->where('stock_status', 'in_stock');

        // 2. প্রতিটি কিওয়ার্ড দিয়ে সার্চ (Fuzzy Search)
        $query->where(function($q) use ($keywords) {
            foreach($keywords as $word) {
                $word = trim($word);
                $q->orWhere('name', 'LIKE', "%{$word}%")
                  ->orWhere('sku', 'LIKE', "%{$word}%")
                  ->orWhere('tags', 'LIKE', "%{$word}%")
                  ->orWhere('category', 'LIKE', "%{$word}%") // যদি category কলাম স্ট্রিং হয়
                  ->orWhereHas('category', function($catQ) use ($word) { // যদি রিলেশনশিপ থাকে
                      $catQ->where('name', 'LIKE', "%{$word}%");
                  });
            }
        });

        return $query->latest()->first();
    }

    
}