<?php

namespace App\Services\OrderFlow;

use App\Models\Product;
use App\Models\OrderSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait OrderTraits
{
    /**
     * ভেরিয়েন্ট ডাটা ডিকোড করার হেল্পার
     */
    public function decodeVariants($data)
    {
        if (empty($data)) return [];
        if (is_array($data)) return array_filter($data, fn($item) => !empty($item) && strtolower((string)$item) !== 'n/a');
        
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_filter($decoded, fn($item) => !empty($item));
        }
        
        if (is_string($data)) {
            return array_map('trim', explode(',', $data));
        }
        
        return [];
    }

    /**
     * 🔥 EXTREME PRODUCT SEARCH SYSTEM
     * ১. ID & SKU Priority Search
     * ২. Smart Keyword Search with Synonyms
     * ৩. Fuzzy Logic (Typo Correction)
     */
    public function findProductSystematically($clientId, $message)
    {
        $message = trim((string) $message);
        if (empty($message)) return null;

        // ১. সরাসরি প্রোডাক্ট আইডি বা SKU (High Priority)
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

        // ২. স্টপ ওয়ার্ডস ফিল্টারিং (স্মার্ট ফিল্টার)
        $stopWords = [
            'ami', 'kinbo', 'chai', 'korte', 'jonno', 'ace', 'ase', 'nibo', 
            'product', 'koto', 'dam', 'price', 'hi', 'hello', 'akta', 'ekta', 
            'ki', 'kivabe', 'order', 'please', 'details', 'pic', 'picture',
            'আছে', 'নাই', 'কত', 'দাম', 'অর্ডার', 'চাই', 'নিতে', 'হবে', 'দেখি', 'দেখান'
        ];
        
        // 🔥 NEW: Synonym Mapping (সমার্থক শব্দ)
        $synonyms = [
            'mobile' => 'phone',
            'pant' => 'trousers',
            'shirt' => 'top',
            'juta' => 'shoe',
            'ghori' => 'watch',
            'tup' => 'cap',
            'moila' => 'waste',
            'chob' => 'photo'
        ];

        $rawKeywords = explode(' ', strtolower($message));
        $keywords = [];

        foreach ($rawKeywords as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 2 || in_array($word, $stopWords)) continue;
            
            $keywords[] = $word;
            // সমার্থক শব্দ থাকলে সেটাও সার্চে যোগ করা হবে
            if (isset($synonyms[$word])) {
                $keywords[] = $synonyms[$word];
            }
        }

        if (empty($keywords)) return null;

        // ৩. মাল্টি-লেয়ার ডাটাবেস সার্চ
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        $query->where(function($q) use ($keywords, $message) {
            // A. সম্পূর্ণ বাক্যের সাথে আংশিক মিল
            $q->where('name', 'LIKE', "%{$message}%")
              ->orWhere('tags', 'LIKE', "%{$message}%");

            // B. প্রতিটি কিওয়ার্ড ধরে স্ক্যান
            foreach($keywords as $word) {
                $q->orWhere('name', 'LIKE', "%{$word}%")
                  ->orWhere('sku', 'LIKE', "%{$word}%")
                  ->orWhere('short_description', 'LIKE', "%{$word}%")
                  ->orWhereHas('category', function($cq) use ($word) {
                      $cq->where('name', 'LIKE', "%{$word}%");
                  });
            }
        });

        $product = $query->latest()->first();
        
        if ($product) {
            Log::info("✅ Product Found by Smart Keywords: {$product->name}");
            return $product;
        }

        // 🔥 ৪. FUZZY SEARCH (Typo Correction) - Fallback
        // যদি ডাটাবেসে সরাসরি না পাওয়া যায়, তবে বানানের ভুল চেক করবে
        return $this->findProductWithFuzzyLogic($clientId, $keywords);
    }

    /**
     * 🔥 NEW: Fuzzy Logic Search (Levenshtein Distance)
     * কাস্টমার "Pnjabi" লিখলে "Panjabi" খুঁজে বের করবে
     */
    private function findProductWithFuzzyLogic($clientId, $keywords)
    {
        // সব পণ্যের নাম এবং আইডি ক্যাশে থেকে বা ডিবি থেকে আনা (অপ্টিমাইজড)
        // ছোট ইনভেন্টরির জন্য এটি ঠিক আছে, বড় ইনভেন্টরির জন্য ElasticSearch বা Scout ভালো
        $allProducts = Product::where('client_id', $clientId)
            ->where('stock_status', 'in_stock')
            ->select('id', 'name')
            ->get();

        $bestMatch = null;
        $shortestDistance = -1;

        foreach ($allProducts as $product) {
            foreach ($keywords as $keyword) {
                // পণ্যের নামের প্রতিটি শব্দের সাথে কাস্টমারের কিওয়ার্ড তুলনা করা
                $productWords = explode(' ', strtolower($product->name));
                foreach ($productWords as $pWord) {
                    $distance = levenshtein($keyword, $pWord);
                    
                    // যদি পার্থক্য ৩ অক্ষরের কম হয় (অর্থাৎ বানান খুব কাছাকাছি)
                    if ($distance <= 2) { 
                        if ($shortestDistance < 0 || $distance < $shortestDistance) {
                            $shortestDistance = $distance;
                            $bestMatch = $product;
                        }
                    }
                }
            }
        }

        if ($bestMatch) {
            Log::info("✅ Product Found by Fuzzy Logic (Typo Fix): {$bestMatch->name}");
            return Product::find($bestMatch->id); // ফুল ডিটেইলস রিটার্ন
        }

        return null;
    }

    /**
     * 🔥 INTELLIGENT VARIANT EXTRACTION
     * কাস্টমার কি কোনো কালার বা সাইজের কথা মেসেজেই বলেছে?
     */
    public function extractVariantsFromMessage($message, $product)
    {
        $detected = ['color' => null, 'size' => null];
        $msg = strtolower((string)$message);

        // কালার ডিটেকশন
        $availableColors = $this->decodeVariants($product->colors);
        foreach ($availableColors as $color) {
            // বাংলিশ কালার সাপোর্ট ( লাল = Red, etc. if needed map)
            if (str_contains($msg, strtolower($color))) {
                $detected['color'] = $color;
                break;
            }
        }

        // সাইজ ডিটেকশন (Word boundary match for sizes like S, M, L, XL)
        $availableSizes = $this->decodeVariants($product->sizes);
        foreach ($availableSizes as $size) {
            $s = strtolower($size);
            // \b ensures "XL" doesn't match inside "ExtraLarge" incorrectly without context
            if (preg_match("/\b{$s}\b/", $msg)) {
                $detected['size'] = $size;
                break;
            }
        }

        return array_filter($detected);
    }

    /**
     * 🔥 SESSION RECOVERY SYSTEM
     */
    public function getProductFromSession($senderId, $clientId)
    {
        $session = OrderSession::where('sender_id', $senderId)
            ->where('client_id', $clientId)
            ->first();

        if ($session && !empty($session->customer_info['product_id'])) {
            $product = Product::find($session->customer_info['product_id']);
            
            // স্টক চেক যুক্ত করা হয়েছে
            if ($product && $product->stock_quantity > 0 && $product->stock_status === 'in_stock') {
                Log::info("🔄 Retrieved Product from Session Context: {$product->name}");
                return $product;
            }
        }
        return null;
    }

    /**
     * 🔥 NEW: Price Range Extraction
     * কাস্টমার যদি বলে "500 takar moddhe"
     */
    public function extractPriceConstraint($message)
    {
        if (preg_match('/(\d+)\s*(taka|tk)/i', $message, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }
}