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
        $clientId = $session->client_id;

        // ১. প্রোডাক্ট ভ্যালিডেশন
        if (!$productId) {
            return ['instruction' => "দুঃখিত, কোনো প্রোডাক্ট সিলেক্ট করা নেই। দয়া করে প্রথমে প্রোডাক্ট পছন্দ করুন।", 'context' => "No product selected"];
        }

        $product = Product::find($productId);
        if (!$product) {
            return ['instruction' => "দুঃখিত, এই প্রোডাক্টটি আর পাওয়া যাচ্ছে না। অন্য কিছু দেখুন।", 'context' => "Product not found in DB"];
        }

        // 🔥 ২. রিয়েল-টাইম স্টক চেক
        if ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0) {
            return ['instruction' => "দুঃখিত! এইমাত্র প্রোডাক্টটি স্টক আউট হয়ে গেছে। আপনি কি অন্য কোনো প্রোডাক্ট দেখতে চান?", 'context' => "Stock finished during flow"];
        }

        // ৩. ভেরিয়েন্ট ভ্যালিডেশন
        $hasColors = !empty($this->decodeVariants($product->colors));
        $hasSizes = !empty($this->decodeVariants($product->sizes));
        $selectedVariant = $customerInfo['variant'] ?? null;

        if (($hasColors || $hasSizes) && empty($selectedVariant)) {
            $customerInfo['step'] = 'select_variant';
            $session->update(['customer_info' => $customerInfo]);
            return ['instruction' => "অর্ডার করার আগে কাস্টমারকে অবশ্যই প্রোডাক্টের কালার বা সাইজ সিলেক্ট করতে হবে।", 'context' => "Variant missing"];
        }

        // ✅ ৪. ইনফরমেশন চেক (STRICT)
        $name = $customerInfo['name'] ?? null;
        $phone = $customerInfo['phone'] ?? null;
        $address = $customerInfo['address'] ?? null;

        if (empty($name) || empty($phone) || empty($address)) {
            $customerInfo['step'] = 'collect_info';
            $session->update(['customer_info' => $customerInfo]);

            $missingFields = [];
            if (empty($name)) $missingFields[] = "আপনার নাম";
            if (empty($phone)) $missingFields[] = "ফোন নম্বর";
            if (empty($address)) $missingFields[] = "পূর্ণ ঠিকানা";
            
            return [
                'instruction' => "অর্ডার কনফার্ম করার জন্য কাস্টমারের " . implode(' এবং ', $missingFields) . " প্রয়োজন। বিনয়ের সাথে চাও।",
                'context' => "Missing Info: " . implode(',', $missingFields)
            ];
        }

        // 🔥 ৫. তথ্য পরিবর্তনের রিকোয়েস্ট হ্যান্ডলিং
        if ($this->isModificationIntent($userMessage)) {
            $customerInfo['step'] = 'collect_info';
            $session->update(['customer_info' => $customerInfo]);
            
            return [
                'instruction' => "ঠিক আছে, আপনি আপনার সঠিক তথ্য (নাম, ফোন বা ঠিকানা) আবার দিন। আমি আপডেট করে নিচ্ছি।",
                'context' => "User wants to modify info"
            ];
        }

        // ৬. নেগেটিভ ইন্টেন্ট চেক
        if ($this->isNegativeConfirmation($userMessage)) {
            return [
                'instruction' => "কাস্টমার অর্ডারটি কনফার্ম করতে চাচ্ছে না। জিজ্ঞেস করো তারা কি অর্ডার বাতিল করতে চায় নাকি কোনো প্রশ্ন আছে?",
                'context' => "User declined confirmation"
            ];
        }

        // ✅ ৭. ফাইনাল কনফার্মেশন চেক & নোট সেভিং
        if ($this->isPositiveConfirmation($userMessage)) {
            
            // 🔥 কাস্টমার নোট এক্সট্রাকশন (New Feature)
            // কাস্টমার যদি বলে "হ্যাঁ, কিন্তু বিকেলে কল দিবেন", তবে শেষের অংশ নোট হিসেবে সেভ হবে
            $note = $this->extractNoteFromConfirmation($userMessage);
            if ($note) {
                $customerInfo['user_note'] = $note;
                $session->update(['customer_info' => $customerInfo]);
            }

            // ডুপ্লিকেট অর্ডার প্রোটেকশন
            $recentOrder = Order::where('sender_id', $session->sender_id)
                ->where('client_id', $session->client_id)
                ->where('created_at', '>=', now()->subMinutes(2)) 
                ->latest()
                ->first();

            if ($recentOrder) {
                return [
                    'instruction' => "আপনার অর্ডারটি ইতিমধ্যেই গ্রহণ করা হয়েছে (অর্ডার #{$recentOrder->id})। ধন্যবাদ!",
                    'context' => "Duplicate Order Attempt"
                ];
            }

            // অর্ডার তৈরির সিগন্যাল
            return [
                'action' => 'create_order', 
                'instruction' => "অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে। কাস্টমারকে অভিনন্দন জানাও এবং অর্ডার আইডি (Order ID) জানিয়ে দাও। ডেলিভারি টাইম সম্পর্কে Shop Policy বা FAQ দেখে উত্তর দাও।",
                'context' => json_encode([
                    'product' => $product->name,
                    'variant' => $selectedVariant,
                    'price' => $product->sale_price ?? $product->regular_price,
                    'note' => $note ?? 'N/A'
                ])
            ];
        }

        // ❌ ৮. রিভিউ সামারি (Detailed Review before Order)
        
        $client = Client::find($clientId);
        $unitPrice = $product->sale_price ?? $product->regular_price;
        
        // A. ভেরিয়েন্ট ডিসপ্লে
        $variantText = "";
        if ($selectedVariant) {
            $vDetails = is_array($selectedVariant) ? implode(', ', array_filter($selectedVariant)) : $selectedVariant;
            $variantText = " (সাইজ/কালার: $vDetails)";
        }

        // B. ডেলিভারি চার্জ ক্যালকুলেশন
        $deliveryCharge = 120; 
        $deliveryNote = "ডেলিভারি চার্জ";

        if ($client) {
            $locationType = $customerInfo['location_type'] ?? 'unknown';
            
            if ($locationType === 'inside_dhaka') {
                $deliveryCharge = $client->delivery_charge_inside ?? 80;
                $deliveryNote .= " (ঢাকা)";
            } elseif ($locationType === 'outside_dhaka') {
                $deliveryCharge = $client->delivery_charge_outside ?? 150;
                $deliveryNote .= " (ঢাকার বাইরে)";
            } else {
                // ডিফল্ট লজিক
                $isDhaka = str_contains(strtolower($address), 'dhaka') || str_contains($address, 'ঢাকা');
                $deliveryCharge = $isDhaka ? ($client->delivery_charge_inside ?? 80) : ($client->delivery_charge_outside ?? 150);
            }
        }

        $totalAmount = $unitPrice + $deliveryCharge;

        // 🔥 C. ফিক্সড পেমেন্ট মেথড (COD Only)
        $paymentMethod = "ক্যাশ অন ডেলিভারি (COD)";

        return [
            'instruction' => "অর্ডার করার আগে কাস্টমারকে নিচের সমস্ত তথ্য ভালো করে চেক করতে বলো। যদি সব ঠিক থাকে তবে 'Ji' বা 'Confirm' লিখতে বলো।\n\n" .
                             "📝 **অর্ডার রিভিউ:**\n" .
                             "- পণ্য: {$product->name}{$variantText}\n" .
                             "- পণ্যের দাম: {$unitPrice} টাকা\n" .
                             "- {$deliveryNote}: {$deliveryCharge} টাকা\n" .
                             "- **সর্বমোট বিল: {$totalAmount} টাকা**\n\n" .
                             "📦 **শিপিং তথ্য:**\n" .
                             "- নাম: {$name}\n" . 
                             "- ফোন: {$phone}\n" .
                             "- ঠিকানা: {$address}\n" .
                             "- পেমেন্ট: {$paymentMethod}\n\n" .
                             "👉 *ডেলিভারি সময় এবং বিস্তারিত জানতে আমাদের পলিসি চেক করা হচ্ছে।* \n" .
                             "আপনি কি কনফার্ম করছেন? বিশেষ কোনো নোট থাকলে তাও লিখতে পারেন।",
            'context' => "Waiting for Confirmation. Total: {$totalAmount}. Check KB for delivery time."
        ];
    }

    /**
     * পজিটিভ কিওয়ার্ড চেক
     */
    private function isPositiveConfirmation($msg)
    {
        $words = [
            'yes', 'ji', 'hmd', 'ok', 'confirm', 'thik ace', 'thik ase', 'done', 
            'order koren', 'create', 'nibo', 'pathan', 'place order', 'right',
            'হ্যাঁ', 'জি', 'ঠিক আছে', 'কনফার্ম', 'করেন', 'অর্ডার করেন', 'পাঠান', 'নিব'
        ];
        $msg = strtolower(trim($msg));
        foreach ($words as $w) if (str_contains($msg, $w)) return true;
        return false;
    }

    /**
     * নেগেটিভ কিওয়ার্ড চেক
     */
    private function isNegativeConfirmation($msg)
    {
        $words = [
            'no', 'na', 'cancel', 'bad', 'thak', 'pore', 'later', 'not now',
            'না', 'বাদ', 'ক্যানসেল', 'থাক', 'পরে', 'নিব না'
        ];
        $msg = strtolower(trim($msg));
        foreach ($words as $w) if (str_contains($msg, $w)) return true;
        return false;
    }

    /**
     * তথ্য পরিবর্তনের ইচ্ছা ডিটেকশন
     */
    private function isModificationIntent($msg)
    {
        $words = [
            'change', 'wrong', 'vul', 'thik nai', 'edit', 'poriborton', 
            'address change', 'name change', 'number change',
            'ভুল', 'চেঞ্জ', 'পরিবর্তন', 'ঠিকানা ভুল', 'নাম ভুল', 'নম্বর ভুল', 'এডিট'
        ];
        $msg = strtolower(trim($msg));
        foreach ($words as $w) if (str_contains($msg, $w)) return true;
        return false;
    }

    /**
     * 🔥 নোট এক্সট্রাকশন (New Feature)
     * "Ji, kintu bikele diben" -> Note: "bikele diben"
     */
    private function extractNoteFromConfirmation($msg)
    {
        $confirmationKeywords = ['ji', 'yes', 'ok', 'confirm', 'thik ace', 'হ্যাঁ', 'জি', 'ঠিক আছে'];
        $cleanMsg = str_ireplace($confirmationKeywords, '', $msg);
        $cleanMsg = trim(preg_replace('/[[:punct:]]+/', ' ', $cleanMsg)); // Remove punctuation

        // যদি কনফার্মেশনের পরেও ৪ অক্ষরের বেশি কিছু থাকে, সেটা নোট
        if (mb_strlen($cleanMsg) > 4) {
            return $cleanMsg;
        }
        return null;
    }
}