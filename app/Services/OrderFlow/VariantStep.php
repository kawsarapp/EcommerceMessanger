<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class VariantStep implements OrderStepInterface
{
    use OrderTraits;

    public function process(OrderSession $session, string $userMessage, ?string $imageUrl = null): array
    {
        $customerInfo = $session->customer_info ?? [];
        $productId = $customerInfo['product_id'] ?? null;
        $product = Product::find($productId);

        // 🛑 ১. প্রোডাক্ট ভ্যালিডেশন
        if (!$product) {
            $customerInfo['step'] = 'start';
            $session->update(['customer_info' => $customerInfo]);
            return ['instruction' => 'দুঃখিত, প্রোডাক্টটি ডাটাবেসে পাওয়া যাচ্ছে না। নতুন করে শুরু করো।', 'context' => 'Error: Product Not Found'];
        }

        // 🔥 ২. ভেরিয়েন্ট চেক (অটো স্কিপ ফিচার)
        // যদি প্রোডাক্টের কোনো কালার বা সাইজ না থাকে, তবে সরাসরি অ্যাড্রেস স্টেপে পাঠিয়ে দাও
        $dbColors = $this->decodeVariants($product->colors);
        $dbSizes = $this->decodeVariants($product->sizes);
        $hasColors = !empty($dbColors);
        $hasSizes = !empty($dbSizes);

        if (!$hasColors && !$hasSizes) {
            Log::info("⏭️ No variants found for product {$product->name}. Auto-skipping to Address Step.");
            $customerInfo['step'] = 'collect_info';
            $customerInfo['variant'] = 'Default'; // ডিফল্ট ভ্যালু
            $session->update(['customer_info' => $customerInfo]);
            
            return [
                'instruction' => "এই প্রোডাক্টের কোনো কালার বা সাইজ নেই। সরাসরি কাস্টমারের নাম, ফোন নম্বর এবং ঠিকানা চাও।",
                'context' => json_encode(['product' => $product->name, 'variant' => 'N/A'])
            ];
        }

        // 🔥 ৩. অ্যাডভান্সড ভেরিয়েন্ট এক্সট্রাকশন
        // কাস্টমার মেসেজ থেকে কালার এবং সাইজ বের করা
        $extracted = $this->extractVariant($userMessage, $product);
        
        // আগের কোনো ভেরিয়েন্ট সিলেক্ট করা থাকলে সেগুলোর সাথে মার্জ করা
        $currentVariant = $customerInfo['variant'] ?? [];
        if (!is_array($currentVariant)) $currentVariant = []; // সেফটি চেক
        
        $finalVariant = array_merge($currentVariant, $extracted);

        // ভ্যালিডেশন লজিক
        $missing = [];
        if ($hasColors && empty($finalVariant['color'])) $missing[] = "কালার (Color)";
        if ($hasSizes && empty($finalVariant['size'])) $missing[] = "সাইজ (Size)";

        // ✅ ৪. ডিসিশন লজিক (সব তথ্য আছে কিনা)
        if (empty($missing)) {
            // সব তথ্য পাওয়া গেছে
            $customerInfo['variant'] = $finalVariant;
            $customerInfo['step'] = 'collect_info'; // পরের স্টেপে পাঠাও
            $session->update(['customer_info' => $customerInfo]);
            
            $variantStr = implode(', ', array_filter($finalVariant));
            return [
                'instruction' => "ভেরিয়েশন কনফার্ম হয়েছে: [{$variantStr}]। এখন অর্ডারের জন্য কাস্টমারের নাম, ফোন নম্বর এবং পূর্ণ ঠিকানা চাও।",
                'context' => json_encode(['selected_variant' => $finalVariant])
            ];
        } 
        
        // ⚠️ ৫. যদি কিছু মিসিং থাকে (Partial Input Handling)
        elseif (!empty($extracted)) {
            // ইউজার কিছু একটা দিয়েছে, কিন্তু সব দেয়নি (যেমন: শুধু কালার দিয়েছে, সাইজ দেয়নি)
            $customerInfo['variant'] = $finalVariant; // যা পেয়েছে তা সেভ রাখো
            $session->update(['customer_info' => $customerInfo]);

            $missingStr = implode(' এবং ', $missing);
            return [
                'instruction' => "কাস্টমার ভেরিয়েশন দিয়েছে কিন্তু {$missingStr} সিলেক্ট করেনি। কাস্টমারকে বলো {$missingStr} জানাতে।",
                'context' => json_encode([
                    'received' => $finalVariant,
                    'missing' => $missing,
                    'available_options' => [
                        'colors' => $hasColors ? $dbColors : [],
                        'sizes' => $hasSizes ? $dbSizes : []
                    ]
                ])
            ];
        }

        // ❌ ৬. যদি কিছুই ম্যাচ না করে (Invalid Input)
        $optionsStr = "";
        if ($hasColors) $optionsStr .= "Colors: " . implode(', ', $dbColors) . ". ";
        if ($hasSizes) $optionsStr .= "Sizes: " . implode(', ', $dbSizes) . ".";

        return [
            'instruction' => "কাস্টমার এখনো সঠিক ভেরিয়েশন সিলেক্ট করেনি। তাকে নিচের অপশনগুলো থেকে বেছে নিতে বলো।\n{$optionsStr}",
            'context' => json_encode([
                'id' => $product->id, 
                'name' => $product->name, 
                'available_options' => ['colors' => $dbColors, 'sizes' => $dbSizes]
            ])
        ];
    }

    // ==========================================
    // HELPER METHODS (Enhanced)
    // ==========================================

    private function hasVariantInMessage($msg, $product)
    {
        // এই ফাংশনটি এখন extractVariant এর মাধ্যমে হ্যান্ডেল হচ্ছে, 
        // তবে backward compatibility এর জন্য রাখা হলো।
        $extracted = $this->extractVariant($msg, $product);
        return !empty($extracted);
    }

    /**
     * 🔥 Advanced Extraction: একসাথে Color এবং Size ডিটেক্ট করতে পারে
     * যেমন: "Red XL", "Blue shirt large size"
     */
    private function extractVariant($msg, $product)
    {
        $msg = strtolower(trim($msg));
        $variant = [];

        // 1. Color Extraction
        $dbColors = $this->decodeVariants($product->colors);
        foreach ($dbColors as $color) {
            // Exact match or contains logic
            if (str_contains($msg, strtolower($color))) {
                $variant['color'] = $color;
                break; // একটা কালার পেলেই হবে
            }
        }

        // 2. Size Extraction
        $dbSizes = $this->decodeVariants($product->sizes);
        foreach ($dbSizes as $size) {
            // সাইজের ক্ষেত্রে Exact word match জরুরি (নাহলে 'small' এর 's' ম্যাচ করে ফেলবে)
            // তাই আমরা স্পেস দিয়ে চেক করব অথবা Exact Match
            $s = strtolower($size);
            if (preg_match("/\b{$s}\b/", $msg) || $msg === $s) {
                $variant['size'] = $size;
                break;
            }
        }

        // 3. Fallback Synonyms (Optional Feature)
        // যদি কাস্টমার 'Large' লেখে কিন্তু ডাটাবেসে 'L' থাকে
        if (empty($variant['size']) && !empty($dbSizes)) {
            $synonyms = ['large' => 'L', 'medium' => 'M', 'small' => 'S', 'extra large' => 'XL'];
            foreach ($synonyms as $key => $val) {
                if (str_contains($msg, $key) && in_array($val, $dbSizes)) {
                    $variant['size'] = $val;
                }
            }
        }

        return $variant;
    }
}