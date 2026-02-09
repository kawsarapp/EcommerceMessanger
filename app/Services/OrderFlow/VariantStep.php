<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;

class VariantStep implements OrderStepInterface
{
    use OrderTraits;

    public function process(OrderSession $session, string $userMessage): array
    {
        $customerInfo = $session->customer_info;
        $productId = $customerInfo['product_id'];
        $product = Product::find($productId);

        if (!$product) {
            // প্রোডাক্ট না পাওয়া গেলে রিসেট
            $customerInfo['step'] = 'start';
            $session->update(['customer_info' => $customerInfo]);
            return ['instruction' => 'প্রোডাক্ট পাওয়া যায়নি, নতুন করে শুরু করো।', 'context' => 'Error'];
        }

        if ($this->hasVariantInMessage($userMessage, $product)) {
            $variant = $this->extractVariant($userMessage, $product);
            
            if (empty($variant)) {
                $instruction = "ভেরিয়েশন ম্যাচ করেনি। সঠিক কালার/সাইজ দিতে বলো।";
            } else {
                $customerInfo['variant'] = $variant;
                $customerInfo['step'] = 'collect_info';
                $session->update(['customer_info' => $customerInfo]);
                
                $instruction = "ভেরিয়েশন কনফার্ম (" . json_encode($variant) . ")। এখন নাম, ফোন নম্বর এবং ঠিকানা চাও।";
            }
        } else {
            $colors = $this->decodeVariants($product->colors);
            $instruction = "কাস্টমার এখনো ভেরিয়েশন সিলেক্ট করেনি। অপশনগুলো আবার বলো। Colors: " . implode(',', $colors);
        }

        return [
            'instruction' => $instruction,
            'context' => json_encode(['id' => $product->id, 'name' => $product->name, 'options' => ['colors' => $this->decodeVariants($product->colors), 'sizes' => $this->decodeVariants($product->sizes)]])
        ];
    }

     private function hasVariantInMessage($msg, $product)
    {
        $msgLower = strtolower($msg);
        $check = function($data) use ($msgLower) {
            $items = is_string($data) ? json_decode($data, true) : $data;
            if (!is_array($items)) {
                $items = is_string($data) ? [$data] : [];
            }
            foreach ($items as $item) {
                if (is_string($item) && stripos($msgLower, strtolower(trim($item))) !== false) {
                    return true;
                }
            }
            return false;
        };

        if ($check($product->colors) || $check($product->sizes)) return true;

        $variantKeywords = ['red', 'blue', 'black', 'white', 'green', 'yellow', 'xl', 'xxl', 'l', 'm', 's', 'লাল', 'কালো', 'সাদা', 'সবুজ', 'হলুদ', 'এক্সএল', 'এল', 'এম', 'এস', 'large', 'medium', 'small'];
        foreach ($variantKeywords as $kw) {
            if (stripos($msgLower, $kw) !== false) return true;
        }

        return false;
    }

    private function extractVariant($msg, $product)
    {
        $msg = strtolower($msg);
        $variant = [];

        $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors;
        if (is_array($colors)) {
            foreach ($colors as $color) {
                if (str_contains($msg, strtolower($color))) $variant['color'] = $color;
            }
        }

        $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                if (str_contains($msg, strtolower($size))) $variant['size'] = $size;
            }
        }

        return $variant;
    }
    



}