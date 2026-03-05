<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StartStep implements OrderStepInterface
{
    use OrderTraits;

    public function process(OrderSession $session, string $userMessage, ?string $imageUrl = null): array
    {
        $customerInfo = $session->customer_info ?? [];
        $clientId = $session->client_id;
        
        $product = $this->findProductSystematically($clientId, $userMessage);

        if (!$product) {
            $product = $this->getProductFromSession($session->sender_id, $clientId);
            if ($product) {
                Log::info("🔄 Product recovered from session context: {$product->name}");
            }
        }

        if ($product) {
            $price = $product->sale_price ?? $product->regular_price;
            $regularPrice = $product->regular_price;
            $discountText = ($product->sale_price && $regularPrice > $price) 
                ? "( রেগুলার প্রাইস: {$regularPrice} টাকা, আপনি পাচ্ছেন ডিসকাউন্টে! )" 
                : "";

            $contextData = [
                'product' => $product->name,
                'price' => $price . " Tk",
                'discount_info' => $discountText,
                'description' => Str::limit(strip_tags($product->description), 150),
                'stock' => $product->stock_quantity,
                'image' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
                'detected_variant' => [] 
            ];

            $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
            
            if ($isOutOfStock) {
                return [
                    'instruction' => "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে Inventory থেকে অন্য কোনো প্রোডাক্ট সাজেস্ট করো।",
                    'context' => json_encode($contextData)
                ];
            }

            $dbColors = $this->decodeVariants($product->colors);
            $dbSizes = $this->decodeVariants($product->sizes);
            
            $detectedVariant = $this->extractVariant($userMessage, $product);
            $contextData['detected_variant'] = $detectedVariant; 
            
            $missingAttributes = [];
            if (!empty($dbColors) && empty($detectedVariant['color'])) $missingAttributes[] = 'কালার (Color)';
            if (!empty($dbSizes) && empty($detectedVariant['size'])) $missingAttributes[] = 'সাইজ (Size)';

            if (!empty($detectedVariant)) {
                $customerInfo['variant'] = $detectedVariant;
            }
            
            $customerInfo['product_id'] = $product->id;
            
            $nextStep = !empty($missingAttributes) ? 'select_variant' : 'collect_info';
            $customerInfo['step'] = $nextStep;
            
            $session->update(['customer_info' => $customerInfo]);

            if (!empty($missingAttributes)) {
                $missingStr = implode(' এবং ', $missingAttributes);
                $contextData['options'] = [
                    'colors' => $dbColors, 
                    'sizes' => $dbSizes
                ];
                
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। দাম {$price} টাকা। অর্ডার কনফার্ম করতে কাস্টমারের কাছে অবশ্যই {$missingStr} জানতে চাও। [CAROUSEL: {$product->id}]",
                    'context' => json_encode($contextData)
                ];
            } else {
                $variantConfirmText = !empty($detectedVariant) ? "ভেরিয়েন্ট সিলেক্টেড: " . implode(', ', $detectedVariant) : "";
                
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। {$variantConfirmText}। দাম {$price} টাকা। এখন অর্ডারের জন্য নাম, ফোন নম্বর এবং ঠিকানা চাও। [CAROUSEL: {$product->id}]",
                    'context' => json_encode($contextData)
                ];
            }
        }

        // 🔥 FIX: জেনারেল প্রশ্ন বা প্রোডাক্ট না পাওয়ার ইনস্ট্রাকশন আপডেট (No Hallucination Zone)
        $isGeneralInquiry = preg_match('/(ki ki|ace|menu|list|offer|product|boi|dress|item|ache)/i', $userMessage);
        
        if ($isGeneralInquiry) {
            return [
                'instruction' => "কাস্টমার মেসেজে যা জানতে চেয়েছে (যেমন: '{$userMessage}'), সেটি 'Inventory' ডাটাতে আছে কিনা চেক করো। যদি Inventory-তে ওই রিলেটেড প্রোডাক্ট না থাকে, তবে পরিষ্কারভাবে বলো 'দুঃখিত, আমাদের কাছে এই মুহূর্তে এটি নেই।' এবং Inventory তে থাকা অন্য রিয়েল প্রোডাক্টগুলোর নাম ও দাম বলো। ❌ কোনো ফেক নাম বা উদাহরণ বানাবে না।",
                'context' => "General Query"
            ];
        }

        return [
            'instruction' => "কাস্টমার যা খুঁজছে তা Inventory-তে পাওয়া যায়নি। তাকে সরাসরি জানাও যে 'দুঃখিত, প্রোডাক্টটি আমাদের স্টকে নেই।' এবং Inventory থেকে অন্য প্রোডাক্টগুলো সাজেস্ট করো।",
            'context' => "Product Not Found"
        ];
    }

    private function extractVariant($msg, $product)
    {
        $msg = strtolower(trim($msg));
        $variant = [];

        $colors = $this->decodeVariants($product->colors);
        foreach ($colors as $color) {
            if (str_contains($msg, strtolower($color))) {
                $variant['color'] = $color;
                break;
            }
        }

        $sizes = $this->decodeVariants($product->sizes);
        foreach ($sizes as $size) {
            $s = strtolower($size);
            if (preg_match("/\b{$s}\b/", $msg) || $msg === $s) {
                $variant['size'] = $size;
                break;
            }
        }

        return $variant;
    }
}