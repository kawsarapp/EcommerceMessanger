<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;

class StartStep implements OrderStepInterface
{
    use OrderTraits;

    public function process(OrderSession $session, string $userMessage): array
    {
        $customerInfo = $session->customer_info;
        $clientId = $session->client_id;
        
        // ১. প্রোডাক্ট খোঁজার চেষ্টা
        $product = $this->findProductSystematically($clientId, $userMessage);

        if ($product) {
            // প্রোডাক্ট পাওয়া গেছে - স্টক চেক
            $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
            
            if ($isOutOfStock) {
                return [
                    'instruction' => "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে অন্য কিছু দেখতে বলো।",
                    'context' => json_encode(['id' => $product->id, 'name' => $product->name, 'stock' => 'Out of Stock'])
                ];
            }

            // ভেরিয়েন্ট চেক
            $colors = $this->decodeVariants($product->colors);
            $sizes = $this->decodeVariants($product->sizes);
            $hasVariants = !empty($colors) || !empty($sizes);

            $nextStep = $hasVariants ? 'select_variant' : 'collect_info';
            
            // সেশন আপডেট
            $customerInfo['step'] = $nextStep;
            $customerInfo['product_id'] = $product->id;
            $session->update(['customer_info' => $customerInfo]);

            if ($hasVariants) {
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। কালার/সাইজ বেছে নিতে বলো।",
                    'context' => json_encode(['options' => ['colors' => $colors, 'sizes' => $sizes]])
                ];
            } else {
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। এখন কনফার্মেশনের জন্য তার নাম, ফোন এবং ঠিকানা চাও।",
                    'context' => json_encode(['product' => $product->name])
                ];
            }
        }

        // ২. যদি প্রোডাক্ট না পাওয়া যায় (FIXED PART)
        // আমরা স্টেপ পরিবর্তন করব না, স্টার্টেই থাকব।
        
        return [
            'instruction' => "কাস্টমার কিছু কিনতে চাচ্ছে কিন্তু কোন প্রোডাক্ট তা বোঝা যাচ্ছে না। আমাদের ইনভেন্টরি চেক করে কাস্টমারকে প্রোডাক্টের নাম বলতে বলো বা নিচের প্রোডাক্টগুলো সাজেস্ট করো।",
            'context' => "Product Not Found. Ask user to specify product."
        ];
    }
}