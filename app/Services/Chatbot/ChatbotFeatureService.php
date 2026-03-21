<?php

namespace App\Services\Chatbot;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\FlashSale;
use App\Models\Referral;
use App\Models\LoyaltyPoint;
use App\Models\Client;
use App\Services\WebhookDispatchService;
use Illuminate\Support\Facades\Log;

/**
 * 🔥 ChatbotFeatureService
 * Coupon detection, Return/Refund flow, Flash Sale context,
 * Loyalty points, and Referral handling for the chatbot
 */
class ChatbotFeatureService
{
    // ──────────────────────────────────────────────────────────────────────────
    // COUPON DETECTION & APPLICATION
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Message এ coupon কোড আছে কিনা detect করো
     * Format: SAVE20, PROMO10, etc.
     */
    public function detectCouponCode(string $message): ?string
    {
        // Common coupon patterns
        if (preg_match('/\b(coupon|promo|code|ছাড়|কুপন)[:\s]*([A-Z0-9]{4,20})\b/iu', $message, $m)) {
            return strtoupper(trim($m[2]));
        }
        // Direct code pattern (all caps or digits)
        if (preg_match('/\b([A-Z]{2,10}[0-9]{1,5}|[A-Z0-9]{5,15})\b/', $message, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    /**
     * Coupon valid কিনা check করো এবং context দাও
     */
    public function validateCoupon(int $clientId, string $code, float $orderAmount = 0): array
    {
        $coupon = Coupon::where('client_id', $clientId)
            ->where('coupon_code', $code)
            ->orWhere('code', $code) // column name variation
            ->first();

        if (!$coupon) return ['valid' => false, 'message' => "কুপন কোড '{$code}' পাওয়া যায়নি।"];
        if (!$coupon->isValid()) return ['valid' => false, 'message' => "কুপন কোড '{$code}' মেয়াদ শেষ বা ব্যবহার limit শেষ।"];

        $discount = 0;
        if ($coupon->discount_type === 'percent' || !isset($coupon->discount_type)) {
            $discount = round($orderAmount * (($coupon->discount_percent ?? $coupon->value ?? 0) / 100), 0);
        } else {
            $discount = $coupon->discount_amount ?? $coupon->value ?? 0;
        }

        return [
            'valid'    => true,
            'coupon'   => $coupon,
            'discount' => $discount,
            'message'  => "✅ কুপন '{$code}' প্রযোজ্য! ৳{$discount} ছাড় পাবেন।",
        ];
    }

    /**
     * Flash Sale context message তৈরি করো
     */
    public function getFlashSaleContext(int $clientId): string
    {
        $sale = FlashSale::activeForClient($clientId);
        if (!$sale) return '';

        $diff    = now()->diffInMinutes($sale->ends_at);
        $hours   = intdiv($diff, 60);
        $minutes = $diff % 60;
        $timeLeft = $hours > 0 ? "{$hours} ঘণ্টা {$minutes} মিনিট" : "{$minutes} মিনিট";

        $discountText = $sale->discount_type === 'percent'
            ? "{$sale->discount_percent}% ছাড়"
            : "৳{$sale->discount_amount} ছাড়";

        return "\n\n🔥 FLASH SALE ACTIVE: '{$sale->title}' — এখন {$discountText}! মাত্র {$timeLeft} বাকি। যদি কাস্টমার price জিজ্ঞেস করে, flash sale price বলো।";
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RETURN / REFUND DETECTION
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Customer return/refund চাইছে কিনা detect করো
     */
    public function isReturnRequest(string $message): bool
    {
        $keywords = [
            'ফেরত', 'return', 'refund', 'রিফান্ড', 'ফেরৎ', 'বদলে', 'exchange',
            'ক্ষতিগ্রস্ত', 'ভাঙা', 'defective', 'wrong', 'ভুল', 'damaged',
            'পণ্য ফেরত', 'টাকা ফেরত', 'money back',
        ];
        $lower = mb_strtolower($message);
        foreach ($keywords as $kw) {
            if (str_contains($lower, mb_strtolower($kw))) return true;
        }
        return false;
    }

    /**
     * Latest completed order from this customer খুঁজে বের করো
     */
    public function getLastOrderForReturn(int $clientId, string $senderId): ?Order
    {
        return Order::where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->whereIn('order_status', ['completed', 'delivered', 'shipped'])
            ->latest()
            ->first();
    }

    /**
     * Return Request তৈরি করো
     */
    public function createReturnRequest(int $clientId, Order $order, string $senderId, string $reason): ReturnRequest
    {
        $rr = ReturnRequest::create([
            'client_id'     => $clientId,
            'order_id'      => $order->id,
            'sender_id'     => $senderId,
            'customer_name' => $order->customer_name,
            'customer_phone'=> $order->customer_phone,
            'reason'        => $reason,
            'reason_type'   => $this->guessReasonType($reason),
            'status'        => 'requested',
        ]);

        // Webhook dispatch
        app(WebhookDispatchService::class)->dispatch($clientId, 'return.requested', [
            'return_id'    => $rr->id,
            'order_id'     => $order->id,
            'customer'     => $order->customer_name,
            'reason'       => $reason,
        ]);

        Log::info("📦 Return Request created: #{$rr->id} for Order #{$order->id}");
        return $rr;
    }

    private function guessReasonType(string $reason): string
    {
        $lower = mb_strtolower($reason);
        if (str_contains($lower, 'ভাঙা') || str_contains($lower, 'defect') || str_contains($lower, 'ক্ষতি')) return 'defective';
        if (str_contains($lower, 'ভুল') || str_contains($lower, 'wrong')) return 'wrong_item';
        if (str_contains($lower, 'সাইজ') || str_contains($lower, 'size')) return 'size_issue';
        return 'other';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LOYALTY POINTS
    // ──────────────────────────────────────────────────────────────────────────

    public function isPointsQuery(string $message): bool
    {
        $keywords = ['points', 'পয়েন্ট', 'loyalty', 'ব্যালেন্স', 'balance', 'কত পয়েন্ট'];
        $lower = mb_strtolower($message);
        foreach ($keywords as $kw) {
            if (str_contains($lower, mb_strtolower($kw))) return true;
        }
        return false;
    }

    public function getPointsContext(int $clientId, string $senderId): string
    {
        $points = LoyaltyPoint::balanceFor($clientId, $senderId);
        return "\n[SYSTEM: Customer এর loyalty points balance = {$points} points. প্রতি ১০০ points = ১০৳ ছাড়।]";
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REFERRAL
    // ──────────────────────────────────────────────────────────────────────────

    public function isReferralQuery(string $message): bool
    {
        $keywords = ['referral', 'রেফারেল', 'refer', 'আমার কোড', 'my code', 'invite'];
        $lower = mb_strtolower($message);
        foreach ($keywords as $kw) {
            if (str_contains($lower, mb_strtolower($kw))) return true;
        }
        return false;
    }

    public function getReferralContext(int $clientId, string $senderId): string
    {
        $referral = Referral::getOrCreateForCustomer($clientId, $senderId);
        return "\n[SYSTEM: Customer এর referral code = '{$referral->referral_code}'. বন্ধুকে দিলে বন্ধু ১০% ছাড় পাবে, customer পাবে ৫০ loyalty points।]";
    }
}
