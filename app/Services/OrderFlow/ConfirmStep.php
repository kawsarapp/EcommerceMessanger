<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;
use App\Models\Order; // ✅ Order মডেল ইম্পোর্ট করা হলো
use Illuminate\Support\Facades\Log;

class ConfirmStep implements OrderStepInterface
{
    use OrderTraits; // For decodeVariants

    public function process(OrderSession $session, string $userMessage): array
    {
        $customerInfo = $session->customer_info ?? [];
        $productId = $customerInfo['product_id'] ?? null;

        if (!$productId) {
            return [
                'instruction' => "দুঃখিত, কোনো প্রোডাক্ট সিলেক্ট করা নেই। দয়া করে প্রথমে প্রোডাক্ট পছন্দ করুন।",
                'context' => "No product selected"
            ];
        }

        $product = Product::find($productId);
        if (!$product) {
            return [
                'instruction' => "দুঃখিত, এই প্রোডাক্টটি আর পাওয়া যাচ্ছে না। অন্য কিছু দেখুন।",
                'context' => "Product not found in DB"
            ];
        }

        // ✅ 1. VARIANT VALIDATION (Size/Color Check)
        $hasColors = !empty($this->decodeVariants($product->colors));
        $hasSizes = !empty($this->decodeVariants($product->sizes));
        
        $selectedVariant = $customerInfo['variant'] ?? null;

        if (($hasColors || $hasSizes) && empty($selectedVariant)) {
            $customerInfo['step'] = 'select_variant';
            $session->update(['customer_info' => $customerInfo]);

            return [
                'instruction' => "অর্ডার করার আগে কাস্টমারকে অবশ্যই প্রোডাক্টের কালার বা সাইজ সিলেক্ট করতে হবে। অপশনগুলো দেখাও।",
                'context' => json_encode([
                    'product' => $product->name,
                    'available_colors' => $this->decodeVariants($product->colors),
                    'available_sizes' => $this->decodeVariants($product->sizes)
                ])
            ];
        }

        // ✅ 2. CUSTOMER INFO VALIDATION (Name, Phone, Address)
        $name = $customerInfo['name'] ?? null;
        $phone = $customerInfo['phone'] ?? null;
        $address = $customerInfo['address'] ?? null;

        if (empty($phone) || empty($address)) {
            $customerInfo['step'] = 'collect_info';
            $session->update(['customer_info' => $customerInfo]);

            $missingFields = [];
            if (empty($phone)) $missingFields[] = "ফোন নম্বর";
            if (empty($address)) $missingFields[] = "ঠিকানা";
            
            return [
                'instruction' => "অর্ডার কনফার্ম করার জন্য কাস্টমারের " . implode(' এবং ', $missingFields) . " প্রয়োজন। বিনয়ের সাথে চাও।",
                'context' => "Missing Info: " . implode(',', $missingFields)
            ];
        }

        // ✅ 3. FINAL CONFIRMATION CHECK
        if ($this->isPositiveConfirmation($userMessage)) {
            
            // 🔥 DUPLICATE ORDER PROTECTION (নতুন লজিক)
            // চেক করা হচ্ছে গত ১ মিনিটের মধ্যে এই ইউজার কোনো অর্ডার করেছে কিনা
            $recentOrder = Order::where('sender_id', $session->sender_id)
                ->where('client_id', $session->client_id)
                ->where('created_at', '>=', now()->subMinutes(1)) // ১ মিনিটের বাফার
                ->first();

            if ($recentOrder) {
                Log::info("Duplicate Order Prevented for User: {$session->sender_id}");
                return [
                    // এখানে 'action' => 'create_order' পাঠানো হচ্ছে না, তাই ডুপ্লিকেট হবে না
                    'instruction' => "আপনার অর্ডারটি ইতিমধ্যেই গ্রহণ করা হয়েছে (অর্ডার #{$recentOrder->id})। নতুন করে কনফার্ম করার প্রয়োজন নেই। ধন্যবাদ!",
                    'context' => "Order already placed recently: #{$recentOrder->id}"
                ];
            }

            // যদি ডুপ্লিকেট না হয়, তবেই অর্ডার তৈরি করো
            return [
                'action' => 'create_order', 
                'instruction' => "অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে। কাস্টমারকে ধন্যবাদ জানাও এবং অর্ডার আইডি জানিয়ে দাও।",
                'context' => json_encode([
                    'product' => $product->name,
                    'variant' => $selectedVariant,
                    'phone' => $phone,
                    'address' => $address,
                    'price' => $product->sale_price ?? $product->regular_price
                ])
            ];
        }

        // ❌ 4. REVIEW SUMMARY (যদি কাস্টমার এখনো হ্যাঁ না বলে থাকে)
        $variantText = $selectedVariant ? " (Variant: $selectedVariant)" : "";
        $price = $product->sale_price ?? $product->regular_price;

        return [
            'instruction' => "অর্ডারটি কনফার্ম করার জন্য কাস্টমারের অনুমতি নাও। নিচের তথ্যগুলো সঠিক কিনা জিজ্ঞেস করো।",
            'context' => "Please Confirm Order Details:\nProduct: {$product->name}{$variantText}\nPrice: {$price} Tk\nPhone: {$phone}\nAddress: {$address}\n\nType 'Ji' or 'Yes' to confirm."
        ];
    }

    private function isPositiveConfirmation($msg)
    {
        $positiveWords = ['yes', 'ji', 'hmd', 'ok', 'confirm', 'thik ace', 'thik ase', 'koren', 'order koren', 'হ্যাঁ', 'জি', 'ঠিক আছে', 'কনফার্ম', 'করেন', 'done', 'humm', 'hum'];
        $msgLower = strtolower($msg);
        foreach ($positiveWords as $w) {
            if (trim($msgLower) === $w || str_contains($msgLower, $w)) return true;
        }
        return false;
    }
}