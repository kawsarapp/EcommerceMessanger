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
        $keywords = array_filter(explode(' ', $message), function($word) {
            return is_string($word) && mb_strlen(trim($word)) >= 3 && !in_array(strtolower($word), ['ami', 'kinbo', 'chai', 'korte', 'jonno', 'কিনবো', 'চাই', 'জন্য', 'দিবেন']);
        });

        if (empty($keywords)) return null;

        foreach($keywords as $word) {
            $product = Product::where('client_id', $clientId)
                ->where('sku', 'LIKE', "%".strtoupper(trim($word))."%")
                ->first();
            if($product) return $product;
        }

        return Product::where('client_id', $clientId)
            ->where(function($q) use ($keywords) {
                foreach($keywords as $word) {
                    $q->orWhere('name', 'LIKE', "%".trim($word)."%");
                }
            })
            ->first();
    }
}