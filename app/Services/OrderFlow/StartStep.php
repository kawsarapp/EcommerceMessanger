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
        
        // ১. প্রোডাক্ট খোঁজা (Smart Search)
        // প্রথমে মেসেজ থেকে খোঁজার চেষ্টা করবে
        $product = $this->findProductSystematically($clientId, $userMessage);

        // যদি মেসেজে প্রোডাক্ট না পায়, তবে সেশন বা পূর্বের কথা (Context) থেকে চেক করবে
        if (!$product) {
            $product = $this->getProductFromSession($session->sender_id, $clientId);
            if ($product) {
                Log::info("🔄 Product recovered from session context: {$product->name}");
            }
        }

        if ($product) {
            // ২. স্টক চেক (Inventory Guard)
            $isOutOfStock = ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0);
            
            if ($isOutOfStock) {
                return [
                    'instruction' => "দুঃখিত, '{$product->name}' বর্তমানে স্টকে নেই। কাস্টমারকে অন্য কোনো সিমিলার পণ্য দেখতে বলো।",
                    'context' => json_encode(['id' => $product->id, 'name' => $product->name, 'stock' => 'Out of Stock'])
                ];
            }

            // ৩. ভেরিয়েন্ট চেক এবং অটো-ডিটেকশন (Auto-Detect Logic)
            $dbColors = $this->decodeVariants($product->colors);
            $dbSizes = $this->decodeVariants($product->sizes);
            
            // কাস্টমারের মেসেজ থেকেই ভেরিয়েন্ট বের করার চেষ্টা (যেমন: "Red T-shirt lagbe")
            $detectedVariant = $this->extractVariant($userMessage, $product);
            
            // কি কি মিসিং আছে তা বের করা
            $missingAttributes = [];
            if (!empty($dbColors) && empty($detectedVariant['color'])) $missingAttributes[] = 'কালার (Color)';
            if (!empty($dbSizes) && empty($detectedVariant['size'])) $missingAttributes[] = 'সাইজ (Size)';

            // ৪. সেশন আপডেট
            // যা ডিটেক্ট করা গেছে তা সেশনে সেভ করে রাখা
            if (!empty($detectedVariant)) {
                $customerInfo['variant'] = $detectedVariant;
            }
            
            $customerInfo['product_id'] = $product->id;
            
            // ডিসিশন: ভেরিয়েন্ট বাকি থাকলে সেখানে পাঠাও, নাহলে অ্যাড্রেস চাও
            $nextStep = !empty($missingAttributes) ? 'select_variant' : 'collect_info';
            $customerInfo['step'] = $nextStep;
            
            $session->update(['customer_info' => $customerInfo]);

            // ৫. প্রাইসিং এবং কন্টেক্সট তৈরি
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
                'detected_variant' => $detectedVariant // যা ইতিমধ্যে ধরা পড়েছে
            ];

            // ৬. রেসপন্স তৈরি (কন্ডিশনাল)
            if (!empty($missingAttributes)) {
                // কিছু ভেরিয়েন্ট মিসিং আছে
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
                // সব ভেরিয়েন্ট পাওয়া গেছে অথবা নেই (Direct Order)
                $variantConfirmText = !empty($detectedVariant) ? "ভেরিয়েন্ট সিলেক্টেড: " . implode(', ', $detectedVariant) : "";
                
                return [
                    'instruction' => "কাস্টমার '{$product->name}' পছন্দ করেছে। {$variantConfirmText}। দাম {$price} টাকা। এখন অর্ডারের জন্য নাম, ফোন নম্বর এবং ঠিকানা চাও। [CAROUSEL: {$product->id}]",
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

    /**
     * 🔥 Helper: Extract Variant from Start Message
     * (Duplicate logic from VariantStep but necessary for early detection)
     */
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
            // Exact word match for size to avoid conflicts (e.g., 's' in 'small')
            $s = strtolower($size);
            if (preg_match("/\b{$s}\b/", $msg) || $msg === $s) {
                $variant['size'] = $size;
                break;
            }
        }

        return $variant;
    }
}