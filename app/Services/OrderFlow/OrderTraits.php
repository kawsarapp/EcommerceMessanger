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
        // যদি ডাটাবেসে সরাসরি না পাওয়া যায়, তবে বানানের ভুল চেক করবে
        return $this->findProductWithFuzzyLogic($clientId, $keywords);
    }

    /**
     * 🔥 NEW & IMPROVED: Fuzzy Logic Search (Levenshtein Distance)
     * কাস্টমার "Pnjabi" লিখলে "Panjabi" খুঁজে বের করবে। 
     * কিন্তু ছোট বা সাধারণ শব্দকে ভুল করে প্রোডাক্ট হিসেবে ধরবে না।
     */
    private function findProductWithFuzzyLogic($clientId, $keywords)
    {
        $allProducts = Product::where('client_id', $clientId)
            ->where('stock_status', 'in_stock')
            ->select('id', 'name')
            ->get();

        $bestMatch = null;
        $shortestDistance = -1;

        foreach ($allProducts as $product) {
            $productWords = explode(' ', strtolower($product->name));
            
            foreach ($keywords as $keyword) {
                // 🔥 STRICT FIX 1: কাস্টমারের মেসেজের শব্দ ৪ অক্ষরের চেয়ে ছোট হলে ইগনোর করো
                // (যাতে "to", "den", "ace" এগুলো কোনো প্রোডাক্টের সাথে ম্যাচ না করে)
                if (mb_strlen($keyword) <= 4) continue;

                foreach ($productWords as $pWord) {
                    // 🔥 STRICT FIX 2: প্রোডাক্টের নামও ৪ অক্ষরের চেয়ে ছোট হলে ইগনোর করো
                    if (mb_strlen($pWord) <= 4) continue;

                    $distance = levenshtein($keyword, $pWord);
                    
                    // 🔥 STRICT FIX 3: শব্দের সাইজের ওপর ভিত্তি করে ভুলের মাত্রা (Distance) নির্ধারণ
                    // যদি শব্দ অনেক বড় হয় (৬ অক্ষরের বেশি), তবে সর্বোচ্চ ২টা লেটার ভুল হতে পারবে।
                    // যদি শব্দ ছোট হয়, তবে শুধু ১টা লেটার ভুল হতে পারবে।
                    $maxAllowedDistance = (mb_strlen($pWord) > 6) ? 2 : 1; 

                    if ($distance <= $maxAllowedDistance) { 
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
            
            // স্টক চেক যুক্ত করা হয়েছে
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