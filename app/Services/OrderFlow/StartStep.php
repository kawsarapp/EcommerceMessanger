<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use Illuminate\Support\Str;

class StartStep implements OrderStepInterface
{
    use OrderTraits;

    public function process(OrderSession $session, string $userMessage): array
    {
        $customerInfo = $session->customer_info;
        $clientId = $session->client_id;
        
        $product = $this->findProductSystematically($clientId, $userMessage);

        if ($product) {
            // ১. স্টক চেক
            $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
            
            if ($isOutOfStock) {
                return [
                    'instruction' => "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে অন্য কোনো পণ্য দেখতে বলো।",
                    'context' => json_encode(['id' => $product->id, 'name' => $product->name, 'stock' => 'Out of Stock'])
                ];
            }

            // ২. ভেরিয়েন্ট চেক
            $colors = $this->decodeVariants($product->colors);
            $sizes = $this->decodeVariants($product->sizes);
            $hasVariants = !empty($colors) || !empty($sizes);

            $nextStep = $hasVariants ? 'select_variant' : 'collect_info';
            
            // সেশন আপডেট
            $customerInfo['step'] = $nextStep;
            $customerInfo['product_id'] = $product->id;
            $session->update(['customer_info' => $customerInfo]);

            // ৩. সেলসম্যান কনটেক্সট তৈরি (দাম ও বিবরণ সহ)
            // এটি এআইকে প্রোডাক্ট সম্পর্কে বিস্তারিত বলতে সাহায্য করবে
            $price = $product->sale_price ?? $product->regular_price;
            $desc = Str::limit(strip_tags($product->description), 150);
            
            $contextData = [
                'product' => $product->name,
                'price' => $price . " Tk",
                'regular_price' => $product->regular_price . " Tk",
                'description' => $desc,
                'stock' => $product->stock_quantity,
                'image' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null
            ];

            if ($hasVariants) {
                $contextData['options'] = ['colors' => $colors, 'sizes' => $sizes];
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। এর দাম {$price} টাকা। কাস্টমারকে কালার বা সাইজ বেছে নিতে বলো।",
                    'context' => json_encode($contextData)
                ];
            } else {
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। এর দাম {$price} টাকা। এখন কনফার্মেশনের জন্য তার নাম, ফোন এবং ঠিকানা চাও।",
                    'context' => json_encode($contextData)
                ];
            }
        }

        // প্রোডাক্ট না পাওয়া গেলে
        return [
            'instruction' => "কাস্টমার যা খুঁজছে তা সরাসরি পাওয়া যায়নি। ইনভেন্টরি লিস্ট চেক করে অফার বা বেস্ট সেলিং প্রোডাক্ট সাজেস্ট করো।",
            'context' => "Product Not Found"
        ];
    }
}