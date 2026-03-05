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

        if (!$productId) {
            return ['instruction' => "দুঃখিত, কোনো প্রোডাক্ট সিলেক্ট করা নেই। দয়া করে প্রথমে প্রোডাক্ট পছন্দ করুন।", 'context' => "No product selected"];
        }

        $product = Product::find($productId);
        if (!$product) {
            return ['instruction' => "দুঃখিত, এই প্রোডাক্টটি আর পাওয়া যাচ্ছে না। অন্য কিছু দেখুন।", 'context' => "Product not found in DB"];
        }

        if ($product->stock_status === 'out_of_stock' || $product->stock_quantity <= 0) {
            return ['instruction' => "দুঃখিত! এইমাত্র প্রোডাক্টটি স্টক আউট হয়ে গেছে। আপনি কি অন্য কোনো প্রোডাক্ট দেখতে চান?", 'context' => "Stock finished during flow"];
        }

        $hasColors = !empty($this->decodeVariants($product->colors));
        $hasSizes = !empty($this->decodeVariants($product->sizes));
        $selectedVariant = $customerInfo['variant'] ?? null;

        if (($hasColors || $hasSizes) && empty($selectedVariant)) {
            $customerInfo['step'] = 'select_variant';
            $session->update(['customer_info' => $customerInfo]);
            return ['instruction' => "অর্ডার করার আগে কাস্টমারকে অবশ্যই প্রোডাক্টের কালার বা সাইজ সিলেক্ট করতে হবে।", 'context' => "Variant missing"];
        }

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
                'context' => "Missing Info"
            ];
        }

        if ($this->isOrderInquiry($userMessage)) {
             return [
                'instruction' => "কাস্টমার জানতে চাইছে অর্ডার হয়েছে কিনা। তাকে স্পষ্টভাবে বলো: 'না স্যার, অর্ডারটি এখনো কনফার্ম হয়নি। অর্ডারটি সম্পন্ন করতে দয়া করে **Confirm** অথবা **Ji** লিখে রিপ্লাই দিন।'",
                'context' => "User asking about order status"
            ];
        }

        if ($this->isModificationIntent($userMessage)) {
            $customerInfo['step'] = 'collect_info';
            $session->update(['customer_info' => $customerInfo]);
            return ['instruction' => "ঠিক আছে, আপনি আপনার সঠিক তথ্য (নাম, ফোন বা ঠিকানা) আবার দিন।", 'context' => "User wants to modify info"];
        }

        if ($this->isNegativeConfirmation($userMessage)) {
            return ['instruction' => "কাস্টমার অর্ডারটি কনফার্ম করতে চাচ্ছে না। জিজ্ঞেস করো তারা কি অর্ডার বাতিল করতে চায়?", 'context' => "User declined"];
        }

        // 🔥 FIX: Price calculation robust logic
        $unitPrice = ($product->sale_price > 0 && $product->sale_price < $product->regular_price) 
            ? $product->sale_price 
            : $product->regular_price;

        if ($this->isPositiveConfirmation($userMessage)) {
            
            $note = $this->extractNoteFromConfirmation($userMessage);
            if ($note) {
                $customerInfo['user_note'] = $note;
                $session->update(['customer_info' => $customerInfo]);
            }

            $recentOrder = Order::where('sender_id', $session->sender_id)
                ->where('client_id', $session->client_id)
                ->where('created_at', '>=', now()->subMinutes(2)) 
                ->latest()
                ->first();

            if ($recentOrder) {
                return ['instruction' => "আপনার অর্ডারটি ইতিমধ্যেই গ্রহণ করা হয়েছে (অর্ডার #{$recentOrder->id})। ধন্যবাদ!", 'context' => "Duplicate"];
            }

            return [
                'action' => 'create_order', 
                'instruction' => "অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে। কাস্টমারকে অভিনন্দন জানাও এবং সিস্টেম জেনারেটেড অর্ডার আইডি (Order ID) জানিয়ে দাও।",
                'context' => json_encode(['product' => $product->name, 'variant' => $selectedVariant, 'price' => $unitPrice])
            ];
        }

        $client = Client::find($clientId);
        
        $variantText = "";
        if ($selectedVariant) {
            $vDetails = is_array($selectedVariant) ? implode(', ', array_filter($selectedVariant)) : $selectedVariant;
            $variantText = " (সাইজ/কালার: $vDetails)";
        }

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
                $isDhaka = str_contains(strtolower($address), 'dhaka') || str_contains($address, 'ঢাকা');
                $deliveryCharge = $isDhaka ? ($client->delivery_charge_inside ?? 80) : ($client->delivery_charge_outside ?? 150);
            }
        }

        $totalAmount = $unitPrice + $deliveryCharge;

        return [
            'instruction' => "অর্ডার কনফার্ম করার জন্য কাস্টমারকে নিচের তথ্যগুলো চেক করতে বলো। সব ঠিক থাকলে 'Ji' বা 'Confirm' লিখতে বলো।\n\n" .
                             "📝 **অর্ডার রিভিউ:**\n" .
                             "- পণ্য: {$product->name}{$variantText}\n" .
                             "- পণ্যের দাম: {$unitPrice} টাকা\n" .
                             "- {$deliveryNote}: {$deliveryCharge} টাকা\n" .
                             "- **সর্বমোট বিল: {$totalAmount} টাকা**\n\n" .
                             "📦 **শিপিং তথ্য:**\n" .
                             "- নাম: {$name}\n" . 
                             "- ফোন: {$phone}\n" .
                             "- ঠিকানা: {$address}\n\n" .
                             "আপনি কি কনফার্ম করছেন?",
            'context' => "Waiting for Confirmation. Total: {$totalAmount}."
        ];
    }

    private function isPositiveConfirmation($msg)
    {
        $words = ['yes', 'ji', 'hmd', 'ok', 'confirm', 'thik ace', 'thik ase', 'done', 'order koren', 'create', 'nibo', 'pathan', 'place order', 'right', 'হ্যাঁ', 'জি', 'ঠিক আছে', 'কনফার্ম', 'করেন', 'অর্ডার করেন', 'পাঠান', 'নিব'];
        $msg = strtolower(trim($msg));
        foreach ($words as $w) if (str_contains($msg, $w)) return true;
        return false;
    }

    private function isNegativeConfirmation($msg)
    {
        $words = ['no', 'na', 'cancel', 'bad', 'thak', 'pore', 'later', 'not now', 'না', 'বাদ', 'ক্যানসেল', 'থাক', 'পরে', 'নিব না'];
        $msg = strtolower(trim($msg));
        foreach ($words as $w) if (str_contains($msg, $w)) return true;
        return false;
    }

    private function isModificationIntent($msg)
    {
        $words = ['change', 'wrong', 'vul', 'thik nai', 'edit', 'poriborton', 'address change', 'name change', 'number change', 'ভুল', 'চেঞ্জ', 'পরিবর্তন', 'ঠিকানা ভুল', 'নাম ভুল', 'নম্বর ভুল', 'এডিট'];
        $msg = strtolower(trim($msg));
        foreach ($words as $w) if (str_contains($msg, $w)) return true;
        return false;
    }

    private function extractNoteFromConfirmation($msg)
    {
        $confirmationKeywords = ['ji', 'yes', 'ok', 'confirm', 'thik ace', 'হ্যাঁ', 'জি', 'ঠিক আছে'];
        $cleanMsg = str_ireplace($confirmationKeywords, '', $msg);
        $cleanMsg = trim(preg_replace('/[[:punct:]]+/', ' ', $cleanMsg));
        return (mb_strlen($cleanMsg) > 4) ? $cleanMsg : null;
    }

    private function isOrderInquiry($msg)
    {
        $words = ['order ki hoice', 'order hoise', 'confirm hoise', 'create hoice', 'create kora hoice', 'placed', 'hoice kina', 'hoy nai', 'অর্ডার হয়েছে', 'কনফার্ম হয়েছে', 'অর্ডার কি হয়েছে', 'অর্ডার কি কনফার্ম'];
        $msg = strtolower(trim($msg));
        foreach ($words as $w) if (str_contains($msg, $w)) return true;
        return false;
    }
}