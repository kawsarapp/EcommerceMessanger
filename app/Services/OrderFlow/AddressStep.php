<?php

namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;

class AddressStep implements OrderStepInterface
{
    public function process(OrderSession $session, string $userMessage): array
    {
        $customerInfo = $session->customer_info ?? [];
        $productId = $customerInfo['product_id'] ?? null;
        $product = $productId ? Product::find($productId) : null;

        $userMessage = trim($userMessage);

        // =========================
        // 1️⃣ Strict Address Guard (Improved)
        // =========================
        if ($this->isValidAddress($userMessage)) {
            $customerInfo['address'] = $userMessage;
        }

        // =========================
        // 2️⃣ Phone Extraction
        // =========================
        $phone = $this->extractPhoneNumber($userMessage);
        if ($phone) {
            $customerInfo['phone'] = $phone;
        }

        $hasPhone = !empty($customerInfo['phone']);
        $hasAddress = !empty($customerInfo['address']);

        // =========================
        // 3️⃣ Decision Logic
        // =========================
        if ($hasPhone && $hasAddress) {

            $customerInfo['step'] = 'confirm_order';
            $session->update(['customer_info' => $customerInfo]);

            return [
                'instruction' =>
                    "ফোন ({$customerInfo['phone']}) এবং পূর্ণ ঠিকানা পেয়েছি। এখন অর্ডারটি কনফার্ম করতে বলো। [CAROUSEL: {$productId}]",
                'context' => json_encode([
                    'product_id' => $productId,
                    'phone' => $customerInfo['phone'],
                    'address' => $customerInfo['address']
                ])
            ];
        }

        // Update session once
        $session->update(['customer_info' => $customerInfo]);

        $missing = [];
        if (!$hasPhone) $missing[] = "ফোন নম্বর";
        if (!$hasAddress) $missing[] = "পূর্ণ ঠিকানা (জেলা ও থানা সহ)";

        return [
            'instruction' =>
                "অর্ডার প্রসেস করার জন্য " . implode(' এবং ', $missing) . " প্রয়োজন। বিনয়ের সাথে চাও।",
            'context' => json_encode([
                'product_id' => $productId,
                'captured_phone' => $customerInfo['phone'] ?? null,
                'captured_address' => $customerInfo['address'] ?? null
            ])
        ];
    }

    // =========================
    // Strict Address Validation (Improved)
    // =========================
    private function isValidAddress(string $text): bool
    {
        $text = trim($text);

        if (mb_strlen($text) < 15) {
            return false;
        }

        $invalidTriggers = [
            'price','dam','koto','picture','send',
            'delivery charge','available','details',
            'ace','ase','আছে','product','pic','chobi'
        ];

        $lower = mb_strtolower($text);

        foreach ($invalidTriggers as $trigger) {
            if (str_contains($lower, $trigger)) {
                return false;
            }
        }

        return true;
    }

    // =========================
    // BD Phone Extractor (Stable)
    // =========================
    private function extractPhoneNumber(string $msg): ?string
    {
        $bn = ["১","২","৩","৪","৫","৬","৭","৮","৯","০"];
        $en = ["1","2","3","4","5","6","7","8","9","0"];

        $msg = str_replace($bn, $en, $msg);
        $digits = preg_replace('/[^0-9]/', '', $msg);

        if (preg_match('/01[3-9]\d{8}/', $digits, $matches)) {
            return substr($matches[0], 0, 11);
        }

        return null;
    }
}
