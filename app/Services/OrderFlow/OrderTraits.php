<?php

namespace App\Services\OrderFlow;

use App\Models\Product;
use App\Models\OrderSession;
use Illuminate\Support\Facades\Log;

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
     * ২. Strict Context Check (Avoid wrong products)
     * ৩. Keyword Mapping with Stop-word Logic
     */
    public function findProductSystematically($clientId, $message)
    {
        $message = trim((string) $message);
        if (empty($message)) return null;

        // ১. সরাসরি প্রোডাক্ট আইডি বা SKU (High Priority)
        // কাস্টমার যদি লিখে "123" বা "SKU-456"
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
            'আছে', 'নাই', 'কত', 'দাম', 'অর্ডার', 'চাই', 'নিতে', 'হবে'
        ];
        
        $keywords = array_filter(explode(' ', $message), function($word) use ($stopWords) {
            $word = strtolower(trim($word));
            return mb_strlen($word) >= 2 && !in_array($word, $stopWords);
        });

        if (empty($keywords)) return null;

        // ৩. মাল্টি-লেয়ার ডাটাবেস সার্চ
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        $query->where(function($q) use ($keywords, $message) {
            // A. সম্পূর্ণ বাক্যের সাথে আংশিক মিল (যেমন: "Black T-shirt")
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

        // কাস্টমার যেটা লেটেস্ট দেখেছে বা যেটা বেশি জনপ্রিয় সেটা আগে দেখানো (ঐচ্ছিক)
        $product = $query->latest()->first();
        
        if ($product) {
            Log::info("✅ Product Found by Smart Keywords: {$product->name}");
        }

        return $product;
    }

    /**
     * 🔥 INTELLIGENT VARIANT EXTRACTION
     * কাস্টমার কি কোনো কালার বা সাইজের কথা মেসেজেই বলেছে?
     * যেমন: "L size er red color hobe?"
     */
    public function extractVariantsFromMessage($message, $product)
    {
        $detected = ['color' => null, 'size' => null];
        $msg = strtolower((string)$message);

        // কালার ডিটেকশন
        $availableColors = $this->decodeVariants($product->colors);
        foreach ($availableColors as $color) {
            if (str_contains($msg, strtolower($color))) {
                $detected['color'] = $color;
                break;
            }
        }

        // সাইজ ডিটেকশন (Word boundary match for sizes like S, M, L)
        $availableSizes = $this->decodeVariants($product->sizes);
        foreach ($availableSizes as $size) {
            $s = strtolower($size);
            if (preg_match("/\b{$s}\b/", $msg)) {
                $detected['size'] = $size;
                break;
            }
        }

        return array_filter($detected);
    }

    /**
     * 🔥 SESSION RECOVERY SYSTEM
     * যদি ইউজার প্রোডাক্ট ছাড়া কথা বলে, তবে আগের মেসেজ থেকে প্রোডাক্ট রিকভার করবে
     */
    public function getProductFromSession($senderId, $clientId)
    {
        $session = OrderSession::where('sender_id', $senderId)
            ->where('client_id', $clientId)
            ->first();

        if ($session && !empty($session->customer_info['product_id'])) {
            $product = Product::find($session->customer_info['product_id']);
            if ($product && $product->stock_status === 'in_stock') {
                Log::info("🔄 Retrieved Product from Session Context: {$product->name}");
                return $product;
            }
        }
        return null;
    }
}