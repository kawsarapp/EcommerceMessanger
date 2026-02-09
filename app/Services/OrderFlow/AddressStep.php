<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;
use Illuminate\Support\Str;

class AddressStep implements OrderStepInterface
{
    public function process(OrderSession $session, string $userMessage): array
    {
        $customerInfo = $session->customer_info;
        $product = Product::find($customerInfo['product_id']);

        // 1. Address Logic (Smart Guard)
        $existingAddress = $customerInfo['address'] ?? null;
        if (empty($existingAddress) && $this->isValidAddress($userMessage)) {
            $customerInfo['address'] = $userMessage;
        } elseif (!empty($existingAddress) && $this->isValidAddress($userMessage)) {
            $customerInfo['address'] = $userMessage;
        }

        // 2. Phone Logic
        $phone = $this->extractPhoneNumber($userMessage);
        if ($phone) {
            $customerInfo['phone'] = $phone;
        }

        // 3. Decision Logic (উভয় তথ্য আছে কিনা চেক)
        $hasPhone = !empty($customerInfo['phone']);
        $hasAddress = !empty($customerInfo['address']);

        if ($hasPhone && $hasAddress) {
            // সবকিছু পাওয়া গেছে -> কনফার্মেশন স্টেপে যাও
            $customerInfo['step'] = 'confirm_order';
            $session->update(['customer_info' => $customerInfo]);

            $capturedPhone = $customerInfo['phone'];
            $instruction = "ফোন নম্বর ({$capturedPhone}) এবং ঠিকানা পাওয়া গেছে। এখন [CAROUSEL: {$product->id}] দেখাও এবং অর্ডার কনফার্ম করতে বলো।";
        } elseif ($hasPhone && !$hasAddress) {
            // ফোন আছে, ঠিকানা নেই -> ঠিকানা চাও (স্টেপ চেইঞ্জ হবে না)
            $session->update(['customer_info' => $customerInfo]);
            $instruction = "ফোন নম্বর পাওয়া গেছে, কিন্তু ঠিকানা এখনো পাইনি। সুন্দর করে ঠিকানা জানতে চাও।";
        } elseif (!$hasPhone && $hasAddress) {
            // ঠিকানা আছে, ফোন নেই -> ফোন চাও
            $session->update(['customer_info' => $customerInfo]);
            $instruction = "ঠিকানা পাওয়া গেছে, কিন্তু ফোন নম্বর পাইনি। ফোন নম্বর জানতে চাও।";
        } else {
            // কিছুই নেই -> দুটোই চাও
            $session->update(['customer_info' => $customerInfo]);
            $instruction = "অর্ডার কনফার্ম করতে ফোন নম্বর এবং ঠিকানা আবশ্যক। ফোন নম্বর এবং ঠিকানা না পাওয়া পর্যন্ত [ORDER_DATA] জেনারেট করবে না।";
        }

        return [
            'instruction' => $instruction,
            'context' => json_encode([
                'id' => $product->id,
                'name' => $product->name,
                'captured_phone' => $customerInfo['phone'] ?? 'No',
                'captured_address' => $customerInfo['address'] ?? 'No'
            ])
        ];
    }

    private function isValidAddress($text) {
        if (strlen($text) < 10) return false;
        $invalidTriggers = ['price', 'dam', 'koto', 'picture', 'send', 'delivery charge', 'available', 'details'];
        foreach ($invalidTriggers as $trigger) {
            if (str_contains(strtolower($text), $trigger)) return false;
        }
        return true;
    }

    private function extractPhoneNumber($msg)
    {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $msg = str_replace($bn, $en, $msg);
        $msg = preg_replace('/[^0-9]/', '', $msg);

        if (str_starts_with($msg, '8801')) {
            $msg = substr($msg, 2);
        }

        if (preg_match('/^01[3-9]\d{8}$/', $msg)) {
            return $msg;
        }

        return null;
    }
}