<?php

namespace App\Services\OrderFlow;

use App\Models\Product;
use App\Models\OrderSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait OrderTraits
{
    public function decodeVariants($data)
    {
        if (empty($data)) return [];

        if (is_array($data)) {
            return array_values(array_filter($data, fn($item) =>
                !empty($item) && strtolower((string)$item) !== 'n/a'
            ));
        }

        $decoded = json_decode($data, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded, fn($item) => !empty($item)));
        }

        if (is_string($data)) {
            return array_values(array_filter(
                array_map('trim', explode(',', $data))
            ));
        }

        return [];
    }

    public function findProductSystematically($clientId, $message)
    {
        $message = trim((string) $message);
        if (empty($message)) return null;

        // 🔥 Fast Match (ID / SKU Exact)
        $fastMatch = Product::where('client_id', $clientId)
            ->where(function($q) use ($message) {
                $q->where('id', $message)
                  ->orWhere('sku', $message);
            })
            ->where('stock_status', 'in_stock')
            ->first();

        if ($fastMatch) {
            Log::info("✅ Product Found by Fast-Match: {$fastMatch->name}");
            return $fastMatch;
        }

        // 🔥 Stop Words
        $stopWords = [
            'ami','kinbo','chai','korte','jonno','ace','ase','nibo',
            'product','koto','dam','price','hi','hello','akta','ekta',
            'ki','kivabe','order','please','details','pic','picture',
            'আছে','নাই','কত','দাম','অর্ডার','চাই','নিতে','হবে','দেখি','দেখান'
        ];

        // 🔥 Synonyms
        $synonyms = [
            'mobile' => 'phone',
            'pant'   => 'trousers',
            'shirt'  => 'top',
            'juta'   => 'shoe',
            'ghori'  => 'watch',
            'tup'    => 'cap',
            'moila'  => 'waste',
            'chob'   => 'photo'
        ];

        $rawKeywords = explode(' ', strtolower($message));
        $keywords = [];

        foreach ($rawKeywords as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 2 || in_array($word, $stopWords)) continue;

            $keywords[] = $word;

            if (isset($synonyms[$word])) {
                $keywords[] = $synonyms[$word];
            }
        }

        if (empty($keywords)) return null;

        $query = Product::where('client_id', $clientId)
            ->where('stock_status', 'in_stock');

        $query->where(function($q) use ($keywords, $message) {

            $safeMessage = addcslashes($message, '%_');

            $q->where('name', 'LIKE', "%{$safeMessage}%")
              ->orWhere('tags', 'LIKE', "%{$safeMessage}%");

            foreach($keywords as $word) {

                $safeWord = addcslashes($word, '%_');

                $q->orWhere('name', 'LIKE', "%{$safeWord}%")
                  ->orWhere('sku', 'LIKE', "%{$safeWord}%")
                  ->orWhere('tags', 'LIKE', "%{$safeWord}%")
                  ->orWhere('short_description', 'LIKE', "%{$safeWord}%")
                  ->orWhereHas('category', function($cq) use ($safeWord) {
                      $cq->where('name', 'LIKE', "%{$safeWord}%");
                  });
            }
        });

        $product = $query->latest()->first();

        if ($product) {
            Log::info("✅ Product Found by Smart Keywords: {$product->name}");
            return $product;
        }

        return $this->findProductWithFuzzyLogic($clientId, $keywords);
    }

    private function findProductWithFuzzyLogic($clientId, $keywords)
    {
        $allProducts = Product::where('client_id', $clientId)
            ->where('stock_status', 'in_stock')
            ->select('id', 'name')
            ->get();

        $bestMatch = null;
        $shortestDistance = PHP_INT_MAX;

        foreach ($allProducts as $product) {

            $productWords = explode(' ', strtolower($product->name));

            foreach ($keywords as $keyword) {

                if (mb_strlen($keyword) <= 4) continue;

                foreach ($productWords as $pWord) {

                    if (mb_strlen($pWord) <= 4) continue;

                    $distance = levenshtein($keyword, $pWord);
                    $maxAllowedDistance = mb_strlen($pWord) > 6 ? 2 : 1;

                    if ($distance <= $maxAllowedDistance && $distance < $shortestDistance) {
                        $shortestDistance = $distance;
                        $bestMatch = $product;
                    }
                }
            }
        }

        if ($bestMatch) {
            Log::info("✅ Product Found by Fuzzy Logic (Typo Fix): {$bestMatch->name}");
            return Product::find($bestMatch->id);
        }

        return null;
    }

    public function extractVariantsFromMessage($message, $product)
    {
        $detected = ['color' => null, 'size' => null];
        $msg = strtolower((string)$message);

        $availableColors = $this->decodeVariants($product->colors);

        foreach ($availableColors as $color) {
            if (str_contains($msg, strtolower($color))) {
                $detected['color'] = $color;
                break;
            }
        }

        $availableSizes = $this->decodeVariants($product->sizes);

        foreach ($availableSizes as $size) {
            $s = preg_quote(strtolower($size), '/');

            if (preg_match("/\b{$s}\b/u", $msg)) {
                $detected['size'] = $size;
                break;
            }
        }

        return array_filter($detected);
    }

    public function getProductFromSession($senderId, $clientId)
    {
        $session = OrderSession::where('sender_id', $senderId)
            ->where('client_id', $clientId)
            ->first();

        if ($session && !empty($session->customer_info['product_id'])) {

            $product = Product::find($session->customer_info['product_id']);

            if ($product &&
                $product->stock_quantity > 0 &&
                $product->stock_status === 'in_stock'
            ) {
                Log::info("🔄 Retrieved Product from Session Context: {$product->name}");
                return $product;
            }
        }

        return null;
    }

    public function extractPriceConstraint($message)
    {
        if (preg_match('/(\d+)\s*(taka|tk)/i', $message, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}