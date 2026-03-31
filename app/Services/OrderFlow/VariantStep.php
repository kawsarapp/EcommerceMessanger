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

        // 🔥 ২. ভেরিয়েন্ট রিকোয়ারমেন্ট এনালাইসিস
        // প্রোডাক্টে আদেও কালার বা সাইজ সেট করা আছে কিনা তা চেক করা হচ্ছে
        $dbColors = $this->decodeVariants($product->colors);
        $dbSizes = $this->decodeVariants($product->sizes);
        $hasColors = !empty($dbColors);
        $hasSizes = !empty($dbSizes);

        // যদি কোনো ভেরিয়েন্টই না থাকে, সরাসরি পরবর্তী স্টেপে (ঠিকানা চাওয়া) পাঠিয়ে দাও
        if (!$hasColors && !$hasSizes) {
            Log::info("⏭️ No variants required for product {$product->name}. Auto-skipping to info collection.");
            $customerInfo['step'] = 'collect_info';
            $customerInfo['variant'] = 'Default';
            $session->update(['customer_info' => $customerInfo]);
            
            return [
                'instruction' => "এই প্রোডাক্টের কোনো কালার বা সাইজ নেই। কাস্টমারের কাছে সরাসরি নাম, ফোন নম্বর এবং পূর্ণ ঠিকানা চাও।",
                'context' => json_encode(['product' => $product->name, 'variant' => 'None'])
            ];
        }

        // 🔥 ৩. ভেরিয়েন্ট এক্সট্রাকশন ও আপডেট
        // মেসেজ থেকে কালার এবং সাইজ বের করা
        $extracted = $this->extractVariant($userMessage, $product);
        
        $currentVariant = $customerInfo['variant'] ?? [];
        if (!is_array($currentVariant)) $currentVariant = []; 
        
        // আগে পাওয়া এবং বর্তমানে পাওয়া তথ্যগুলো একসাথে করা
        $finalVariant = array_merge($currentVariant, $extracted);

        // কি কি তথ্য এখনও মিসিং আছে তা চেক করা
        $missing = [];
        if ($hasColors && empty($finalVariant['color'])) $missing[] = "কালার (Color)";
        if ($hasSizes && empty($finalVariant['size'])) $missing[] = "সাইজ (Size)";

        // ✅ ৪. সব তথ্য পাওয়া গেলে পরবর্তী স্টেপে ট্রানজিশন
        if (empty($missing)) {
            $customerInfo['variant'] = $finalVariant;
            $customerInfo['step'] = 'collect_info'; // নাম-ঠিকানা চাওয়ার স্টেপে পাঠাও
            $session->update(['customer_info' => $customerInfo]);
            
            $variantStr = implode(', ', array_filter($finalVariant));
            return [
                'instruction' => "প্রোডাক্টের ভেরিয়েশন [{$variantStr}] কনফার্ম হয়েছে। এখন অর্ডারের জন্য কাস্টমারের নাম, মোবাইল নম্বর এবং ঠিকানা জানাও।",
                'context' => json_encode(['selected_variant' => $finalVariant])
            ];
        } 
        
        // ⚠️ ৫. আংশিক তথ্য পাওয়া গেলে (Partial Selection)
        elseif (!empty($extracted)) {
            $customerInfo['variant'] = $finalVariant; 
            $session->update(['customer_info' => $customerInfo]);

            $missingStr = implode(' এবং ', $missing);
            
            $availableStr = [];
            if ($hasColors && empty($finalVariant['color'])) $availableStr = array_merge($availableStr, $dbColors);
            if ($hasSizes && empty($finalVariant['size'])) $availableStr = array_merge($availableStr, $dbSizes);
            
            $qrTag = !empty($availableStr) ? " \n[QUICK_REPLIES: " . implode(',', $availableStr) . "]" : "";

            return [
                'instruction' => "কাস্টমার ভেরিয়েশন দিয়েছে কিন্তু এখনও {$missingStr} বাকি আছে। বিনয়ের সাথে তাকে {$missingStr} জানাতে বলো।{$qrTag}",
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

        // ❌ ৬. ভুল ইনপুট বা তথ্য না দিলে (Invalid/Missing Input)
        $optionsStr = "";
        $availableStr = [];
        if ($hasColors && empty($finalVariant['color'])) {
            $optionsStr .= "উপলব্ধ কালার: " . implode(', ', $dbColors) . ". ";
            $availableStr = array_merge($availableStr, $dbColors);
        }
        if ($hasSizes && empty($finalVariant['size'])) {
            $optionsStr .= "উপলব্ধ সাইজ: " . implode(', ', $dbSizes) . ".";
            $availableStr = array_merge($availableStr, $dbSizes);
        }

        $qrTag = !empty($availableStr) ? " \n[QUICK_REPLIES: " . implode(',', $availableStr) . "]" : "";

        return [
            'instruction' => "কাস্টমার এখনও সঠিক ভেরিয়েশন (কালার বা সাইজ) পছন্দ করেনি। তাকে নিচের অপশনগুলো থেকে বেছে নিতে সাহায্য করো।\n{$optionsStr}{$qrTag}",
            'context' => json_encode([
                'id' => $product->id, 
                'name' => $product->name, 
                'available_options' => ['colors' => $dbColors, 'sizes' => $dbSizes]
            ])
        ];
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function hasVariantInMessage($msg, $product)
    {
        $extracted = $this->extractVariant($msg, $product);
        return !empty($extracted);
    }

    /**
     * 🔥 Advanced Extraction: একসাথে Color এবং Size ডিটেক্ট করতে পারে
     */
    private function extractVariant($msg, $product)
    {
        $msg = strtolower(trim($msg));
        $variant = [];

        // 1. Color Extraction
        $dbColors = $this->decodeVariants($product->colors);
        foreach ($dbColors as $color) {
            if (str_contains($msg, strtolower($color))) {
                $variant['color'] = $color;
                break; 
            }
        }

        // 2. Size Extraction (Regex ব্যবহার করা হয়েছে নির্ভুলতার জন্য)
        $dbSizes = $this->decodeVariants($product->sizes);
        foreach ($dbSizes as $size) {
            $s = strtolower($size);
            // Word boundary (\b) নিশ্চিত করে যে "S" যেন "Small" এর ভেতরের "s" কে না ধরে
            if (preg_match("/\b{$s}\b/", $msg) || $msg === $s) {
                $variant['size'] = $size;
                break;
            }
        }

        // 3. Fallback Synonyms: যদি কাস্টমার কোড বা পূর্ণ নাম লেখে
        if (empty($variant['size']) && !empty($dbSizes)) {
            $synonyms = [
                'large' => 'L', 'medium' => 'M', 'small' => 'S', 
                'extra large' => 'XL', 'double excel' => 'XXL'
            ];
            foreach ($synonyms as $key => $val) {
                if (str_contains($msg, $key) && in_array($val, $dbSizes)) {
                    $variant['size'] = $val;
                    break;
                }
            }
        }

        return $variant;
    }
}