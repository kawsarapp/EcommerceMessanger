<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class ConfirmStep implements OrderStepInterface
{
    use OrderTraits; 

    public function process(OrderSession $session, string $userMessage, ?string $imageUrl = null): array
    {
        $customerInfo = $session->customer_info ?? [];
        $productId = $customerInfo['product_id'] ?? null;
        $clientId = $session->client_id; // ক্লায়েন্ট আইডি সেশন থেকে নেওয়া

        // ১. প্রোডাক্ট ভ্যালিডেশন (Product Validation)
        if (!$productId) {
            return [
                'instruction' => "দুঃখিত, কোনো প্রোডাক্ট সিলেক্ট করা নেই। দয়া করে প্রথমে প্রোডাক্ট পছন্দ করুন।",
                'context' => "No product selected"
            ];
        }

        $product = Product::find($productId);
        if (!$product) {
            return [
                'instruction' => "দুঃখিত, এই প্রোডাক্টটি আর পাওয়া যাচ্ছে না। অন্য কিছু দেখুন।",
                'context' => "Product not found in DB"
            ];
        }

        // ✅ ২. ভেরিয়েন্ট ভ্যালিডেশন (Variant Validation)
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

        // ✅ ৩. ইনফরমেশন চেক (Name, Phone, Address)
        $name = $customerInfo['name'] ?? null;
        $phone = $customerInfo['phone'] ?? null;
        $address = $customerInfo['address'] ?? null;

        if (empty($phone) || empty($address)) {
            $customerInfo['step'] = 'collect_info';
            $session->update(['customer_info' => $customerInfo]);

            $missingFields = [];
            if (empty($phone)) $missingFields[] = "ফোন নম্বর";
            if (empty($address)) $missingFields[] = "পূর্ণ ঠিকানা";
            
            return [
                'instruction' => "অর্ডার কনফার্ম করার জন্য কাস্টমারের " . implode(' এবং ', $missingFields) . " প্রয়োজন। বিনয়ের সাথে চাও।",
                'context' => "Missing Info: " . implode(',', $missingFields)
            ];
        }

        // 🔥 ৪. নেগেটিভ ইন্টেন্ট চেক (Cancellation Handling)
        if ($this->isNegativeConfirmation($userMessage)) {
            return [
                'instruction' => "কাস্টমার অর্ডারটি কনফার্ম করতে চাচ্ছে না। জিজ্ঞেস করো তারা কি কোনো তথ্য পরিবর্তন করতে চায় নাকি অর্ডার বাতিল করতে চায়?",
                'context' => "User declined confirmation. Intent: Cancel or Modify?"
            ];
        }

        // ✅ ৫. ফাইনাল কনফার্মেশন চেক (Final Confirmation)
        if ($this->isPositiveConfirmation($userMessage)) {
            
            // 🔥 ডুপ্লিকেট অর্ডার প্রোটেকশন (২ মিনিটের বাফার)
            $recentOrder = Order::where('sender_id', $session->sender_id)
                ->where('client_id', $session->client_id)
                ->where('created_at', '>=', now()->subMinutes(2)) 
                ->latest()
                ->first();

            if ($recentOrder) {
                Log::info("Duplicate Order Prevented for User: {$session->sender_id}");
                return [
                    'instruction' => "আপনার অর্ডারটি ইতিমধ্যেই গ্রহণ করা হয়েছে (অর্ডার #{$recentOrder->id})। নতুন করে কনফার্ম করার প্রয়োজন নেই। ধন্যবাদ!",
                    'context' => "Duplicate Order Attempt. Last Order: #{$recentOrder->id}"
                ];
            }

            // অর্ডার তৈরি করার সিগন্যাল
            return [
                'action' => 'create_order', 
                'instruction' => "অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে। কাস্টমারকে অভিনন্দন জানাও এবং অর্ডার আইডি (Order ID) জানিয়ে দাও।",
                'context' => json_encode([
                    'product' => $product->name,
                    'variant' => $selectedVariant,
                    'phone' => $phone,
                    'address' => $address,
                    'price' => $product->sale_price ?? $product->regular_price
                ])
            ];
        }

        // ❌ ৬. রিভিউ সামারি (Smart Review Summary - Advanced Pricing Logic)
        
        // A. ভেরিয়েন্ট টেক্সট তৈরি
        $variantText = "";
        if ($selectedVariant) {
            $vDetails = is_array($selectedVariant) ? implode(', ', array_filter($selectedVariant)) : $selectedVariant;
            $variantText = " (সাইজ/কালার: $vDetails)";
        }

        // B. প্রাইস ক্যালকুলেশন
        $unitPrice = $product->sale_price ?? $product->regular_price;
        $discountInfo = "";
        if ($product->sale_price && $product->regular_price > $product->sale_price) {
            $saved = $product->regular_price - $product->sale_price;
            $discountInfo = " (আপনি বাঁচিয়েছেন $saved টাকা!)";
        }

        // C. ডেলিভারি চার্জ ক্যালকুলেশন (Database Check)
        $client = Client::find($clientId);
        $deliveryCharge = 0;
        $deliveryNote = "ডেলিভারি চার্জ প্রযোজ্য";

        if ($client) {
            // AddressStep থেকে আসা লোকেশন টাইপ চেক করা
            $locationType = $customerInfo['location_type'] ?? 'unknown';
            
            if ($locationType === 'inside_dhaka') {
                $deliveryCharge = $client->delivery_charge_inside ?? 80;
                $deliveryNote = "ডেলিভারি চার্জ: {$deliveryCharge} টাকা (ঢাকার ভিতরে)";
            } elseif ($locationType === 'outside_dhaka') {
                $deliveryCharge = $client->delivery_charge_outside ?? 150;
                $deliveryNote = "ডেলিভারি চার্জ: {$deliveryCharge} টাকা (ঢাকার বাইরে)";
            } else {
                // ডিফল্ট লজিক: যদি লোকেশন ডিটেক্ট না হয়
                $deliveryCharge = 120; // আনুমানিক গড়
                $deliveryNote = "ডেলিভারি চার্জ: লোকেশন অনুযায়ী";
            }
        }

        $totalAmount = $unitPrice + $deliveryCharge;

        // D. ফাইনাল ইন্সট্রাকশন তৈরি
        return [
            'instruction' => "কাস্টমারকে অর্ডারের সম্পূর্ণ বিবরণ এবং মোট বিল দেখাও। কনফার্ম করার জন্য 'Ji' বা 'Confirm' লিখতে বলো।\n\n" .
                             "অর্ডার সারাংশ:\n" .
                             "- পণ্য: {$product->name}{$variantText}\n" .
                             "- দাম: {$unitPrice} টাকা {$discountInfo}\n" .
                             "- {$deliveryNote}\n" .
                             "- **সর্বমোট বিল: {$totalAmount} টাকা**\n" .
                             "- নাম: {$name}\n" . 
                             "- ফোন: {$phone}\n" .
                             "- ঠিকানা: {$address}",
            'context' => "Waiting for Explicit Confirmation. Total Bill: {$totalAmount}"
        ];
    }

    /**
     * পজিটিভ কিওয়ার্ড চেক (Expanded List for Future Proofing)
     */
    private function isPositiveConfirmation($msg)
    {
        $positiveWords = [
            // English / Banglish
            'yes', 'ji', 'hmd', 'ok', 'confirm', 'thik ace', 'thik ase', 'thik ache',
            'koren', 'order koren', 'create', 'koro', 'order create', 'order confirm',
            'nibo', 'ami nibo', 'pathan', 'bhej den', 'delivery den', 'confirm order',
            'done', 'humm', 'hum', 'hmm', 'okay', 'right', 'sothik', 'place order',
            'fuct', 'fuck', // Typo handling (Intent driven)
            'order dao', 'confirm koro', 'confirm kro', 'create koro', 'create kro',
            'thik', 'thikace', 'thikase',
            // Bengali
            'হ্যাঁ', 'জি', 'ঠিক আছে', 'কনফার্ম', 'করেন', 'অর্ডার করেন', 'পাঠান', 'নিব', 'নিবো', 'ঠিক'
        ];

        $msgLower = strtolower(trim($msg));

        foreach ($positiveWords as $w) {
            // Exact match or contains phrase
            if ($msgLower === $w || str_contains($msgLower, $w)) return true;
        }
        return false;
    }

    /**
     * নেগেটিভ কিওয়ার্ড চেক
     */
    private function isNegativeConfirmation($msg)
    {
        $negativeWords = [
            'no', 'na', 'cancel', 'bad', 'bad daw', 'thak', 'pore', 'change', 
            'vul', 'wrong', 'wait', 'na thak', 'not now', 'later',
            'না', 'বাদ', 'ক্যানসেল', 'থাক', 'পরে', 'ভুল', 'নিব না', 'নিবো না'
        ];

        $msgLower = strtolower(trim($msg));

        foreach ($negativeWords as $w) {
            if ($msgLower === $w || str_contains($msgLower, $w)) return true;
        }
        return false;
    }
}