<?php
namespace App\Services\OrderFlow;

use App\Models\OrderSession;

interface OrderStepInterface
{
    /**
     * স্টেপ প্রসেসিং ফাংশন
     * * @param OrderSession $session - কাস্টমারের সেশন ডাটা
     * @param string $userMessage - কাস্টমারের টেক্সট মেসেজ
     * @param string|null $imageUrl - (New) কাস্টমারের পাঠানো ছবির লিংক (যদি থাকে)
     * @return array
     */
    public function process(OrderSession $session, string $userMessage, ?string $imageUrl = null): array;
}