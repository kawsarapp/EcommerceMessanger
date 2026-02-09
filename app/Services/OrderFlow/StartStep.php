<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
// use App\Services\ChatbotService; // REMOVED: Unused

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

            if ($hasVariants) {
                $nextStep = 'select_variant';
                $colorStr = !empty($colors) ? implode(', ', $colors) : 'N/A';
                $sizeStr = !empty($sizes) ? implode(', ', $sizes) : 'N/A';
                $instruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। কালার: [{$colorStr}] এবং সাইজ: [{$sizeStr}]। কাস্টমারকে বেছে নিতে বলো।";
                $context = json_encode([
                    'id' => $product->id, 
                    'name' => $product->name, 
                    'price' => $product->sale_price, 
                    'options' => ['colors' => $colors, 'sizes' => $sizes]
                ]);
            } else {
                $nextStep = 'collect_info';
                $instruction = "কাস্টমার '{$product->name}' পছন্দ করেছে। সরাসরি নাম, ফোন এবং ঠিকানা চাও।";
                $context = json_encode(['id' => $product->id, 'name' => $product->name, 'price' => $product->sale_price, 'stock' => 'Available']);
            }

            $customerInfo['step'] = $nextStep;
            $customerInfo['product_id'] = $product->id;
            $session->update(['customer_info' => $customerInfo]);

            return ['instruction' => $instruction, 'context' => $context];
        }

        return [
            'instruction' => "কাস্টমার কিছু কিনতে চাচ্ছে কিন্তু প্রোডাক্ট চিনতে পারছি না। নাম বা কোড জানতে চাও।",
            'context' => "Product Not Found"
        ];
    }
}