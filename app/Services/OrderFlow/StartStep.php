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
        
        $product = $this->findProductSystematically($clientId, $userMessage);

        if ($product) {
            $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
            
            if ($isOutOfStock) {
                return [
                    'instruction' => "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে অন্য কিছু দেখতে বলো।",
                    'context' => json_encode(['id' => $product->id, 'name' => $product->name, 'stock' => 'Out of Stock'])
                ];
            }

            $colors = $this->decodeVariants($product->colors);
            $sizes = $this->decodeVariants($product->sizes);
            $hasVariants = !empty($colors) || !empty($sizes);

            $nextStep = $hasVariants ? 'select_variant' : 'collect_info';
            
            $customerInfo['step'] = $nextStep;
            $customerInfo['product_id'] = $product->id;
            $session->update(['customer_info' => $customerInfo]);

            if ($hasVariants) {
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। কালার বা সাইজ বেছে নিতে বলো। [CAROUSEL: {$product->id}]",
                    'context' => json_encode(['options' => ['colors' => $colors, 'sizes' => $sizes]])
                ];
            } else {
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। এখন কনফার্মেশনের জন্য তার ফোন নম্বর এবং ঠিকানা চাও। [CAROUSEL: {$product->id}]",
                    'context' => json_encode(['product' => $product->name])
                ];
            }
        }

        // 🔥 FIX: যদি প্রোডাক্ট না পাওয়া যায়, তবে স্টেপ পরিবর্তন হবে না।
        return [
            'instruction' => "কাস্টমার যা খুঁজছে তা পাওয়া যায়নি। ইনভেন্টরি চেক করে আমাদের কাছে যা আছে তা অফার করো।",
            'context' => "Product Not Found"
        ];
    }
}