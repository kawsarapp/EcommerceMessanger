<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;
use App\Models\Product;

class ConfirmStep implements OrderStepInterface
{
    public function process(OrderSession $session, string $userMessage): array
{
    $customerInfo = $session->customer_info ?? [];
    $productId = $customerInfo['product_id'] ?? null;

    if (!$productId) {
        return [
            'instruction' => "দুঃখিত, কোনো প্রোডাক্ট সিলেক্ট করা নেই। আবার শুরু করি।",
            'context' => "No product selected"
        ];
    }

    // ✅ Positive confirmation check
    if ($this->isPositiveConfirmation($userMessage)) {

        $phone   = $customerInfo['phone'] ?? '';
        $address = $customerInfo['address'] ?? '';
        $variant = $customerInfo['variant'] ?? [];
        $note    = $customerInfo['note'] ?? '';

        // 🔒 Final validation guard
        if (empty($phone) || empty($address)) {

            $customerInfo['step'] = 'collect_info';
            $session->update(['customer_info' => $customerInfo]);

            return [
                'instruction' => "অর্ডার কনফার্ম করার আগে ফোন এবং ঠিকানা নিশ্চিত করা প্রয়োজন। যা মিসিং আছে তা জিজ্ঞেস করো।",
                'context' => "Missing phone or address"
            ];
        }

        // ✅ SUCCESS → Tell ChatbotService to create order
        return [
            'action' => 'create_order', // 🔥 THIS WAS MISSING
            'instruction' => "অর্ডারটি সিস্টেমে প্রসেস করা হচ্ছে। ইউজারকে অভিনন্দন জানাও এবং বলো অর্ডার আইডি শীঘ্রই জানানো হবে।",
            'context' => json_encode([
                'product_id' => $productId,
                'phone'      => $phone,
                'address'    => $address,
                'variant'    => $variant,
                'note'       => $note
            ])
        ];
    }

    // ❌ Not confirmed yet
    return [
        'instruction' => "কাস্টমার এখনো কনফার্ম করেনি। প্রশ্ন থাকলে উত্তর দাও এবং আবার কনফার্ম করতে বলো। [CAROUSEL: {$productId}]",
        'context' => "Waiting for confirmation"
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