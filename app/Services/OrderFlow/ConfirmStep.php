<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;

class ConfirmStep implements OrderStepInterface
{
    public function process(OrderSession $session, string $userMessage): array
    {
        $customerInfo = $session->customer_info;
        $productId = $customerInfo['product_id'] ?? null;
        
        // Positive check logic
        if ($this->isPositiveConfirmation($userMessage)) {
            $phone = $customerInfo['phone'] ?? '';
            $address = $customerInfo['address'] ?? '';
            $variant = $customerInfo['variant'] ?? [];
            $note = $customerInfo['note'] ?? '';

            // Final Guard: যদি কোনো কারণে ডাটা মিসিং থাকে
            if (empty($phone) || empty($address)) {
                $customerInfo['step'] = 'collect_info';
                $session->update(['customer_info' => $customerInfo]);
                $instruction = "অর্ডার কনফার্ম করার আগে ফোন এবং ঠিকানা নিশ্চিত করা প্রয়োজন। যা মিসিং তা চাও।";
            } else {
                // ✅ SUCCESS
                $instruction = "কাস্টমার অর্ডার কনফার্ম করেছে। শুধু এই স্কিমা দাও: [ORDER_DATA: {\"product_id\": {$productId}, \"phone\": \"{$phone}\", \"address\": \"{$address}\", \"variant\": " . json_encode($variant) . ", \"note\": \"{$note}\", \"status\": \"PROCESSING\"}]";
                
                $customerInfo['step'] = 'completed';
                $session->update(['customer_info' => $customerInfo]);
            }
        } else {
            // এখনো কনফার্ম করেনি
            $instruction = "কাস্টমার এখনো কনফার্ম করেনি। প্রশ্ন থাকলে উত্তর দাও এবং আবার কনফার্ম করতে বলো। [CAROUSEL: {$productId}]";
        }

        return [
            'instruction' => $instruction,
            'context' => "Waiting for confirmation..."
        ];
    }

    private function isPositiveConfirmation($msg)
    {
        $positiveWords = ['yes', 'ji', 'hmd', 'ok', 'confirm', 'thik ace', 'thik ase', 'koren', 'order koren', 'হ্যাঁ', 'জি', 'ঠিক আছে', 'কনফার্ম', 'করেন', 'done'];
        $msgLower = strtolower($msg);
        foreach ($positiveWords as $w) {
            if (str_contains($msgLower, $w)) return true;
        }
        return false;
    }
}