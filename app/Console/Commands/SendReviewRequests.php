<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Client;
use App\Services\Messenger\MessengerResponseService;
use Carbon\Carbon;

class SendReviewRequests extends Command
{
    protected $signature = 'review:request';
    protected $description = 'Send auto review requests to customers via Messenger';

    public function handle(MessengerResponseService $messenger)
    {
        $clients = Client::where('is_review_collection_active', true)->get();

        foreach ($clients as $client) {
            $delayDays = $client->review_delay_days ?? 3;
            $thresholdDate = Carbon::now()->subDays($delayDays);

            // যেসব অর্ডার ডেলিভারি হয়েছে কিন্তু রিভিউ চাওয়া হয়নি
            $orders = Order::where('client_id', $client->id)
                ->where('order_status', 'delivered')
                ->where('is_review_requested', false)
                ->where('updated_at', '<=', $thresholdDate)
                ->get();

            foreach ($orders as $order) {
                $product = $order->orderItems->first()?->product;
                if (!$product) continue;

                $message = "হ্যালো {$order->customer_name}! 🎉\nআপনার অর্ডার করা '{$product->name}' প্রোডাক্টটি সফলভাবে ডেলিভারি হয়েছে। প্রোডাক্টটি আপনার কেমন লেগেছে? দয়া করে নিচের বাটন থেকে রেটিং দিন 👇";

                // কুইক রিপ্লাই বাটন (রেটিং দেওয়ার জন্য)
                $quickReplies = [
                    ['content_type' => 'text', 'title' => '⭐⭐⭐⭐⭐ (৫)', 'payload' => "RATE_{$product->id}_{$order->id}_5"],
                    ['content_type' => 'text', 'title' => '⭐⭐⭐⭐ (৪)', 'payload' => "RATE_{$product->id}_{$order->id}_4"],
                    ['content_type' => 'text', 'title' => '⭐⭐⭐ (৩)', 'payload' => "RATE_{$product->id}_{$order->id}_3"],
                ];

                try {
                    $messenger->sendMessengerMessage($order->sender_id, $message, $client->fb_page_token, null, $quickReplies);
                    $order->update(['is_review_requested' => true]);
                } catch (\Exception $e) {}
            }
        }
    }
}