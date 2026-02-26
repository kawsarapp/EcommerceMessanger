<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderSession;
use App\Models\Client;
use App\Models\Product;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendCartReminders extends Command
{
    // কমান্ডের নাম
    protected $signature = 'cart:remind';
    protected $description = 'Send automated AI reminders for abandoned carts via Messenger';

    public function handle(NotificationService $notify)
    {
        // ১. যে সেলারদের রিমাইন্ডার অপশন অন করা আছে, তাদের খুঁজে বের করা
        $clients = Client::where('is_reminder_active', true)->get();

        $count = 0;

        foreach ($clients as $client) {
            $delayHours = $client->reminder_delay_hours ?? 2;
            $thresholdTime = Carbon::now()->subHours($delayHours);

            // ২. এই সেলারের অসম্পূর্ণ অর্ডারগুলো খোঁজা
            $abandonedSessions = OrderSession::where('client_id', $client->id)
                ->where('status', '!=', 'completed')
                ->where('reminder_status', 'pending')
                ->where('updated_at', '<=', $thresholdTime)
                ->get();

            foreach ($abandonedSessions as $session) {
                // ৩. প্রোডাক্টের নাম বের করা (কাস্টমাইজড মেসেজের জন্য)
                $productName = "আপনার পছন্দের প্রোডাক্টটি";
                $customerInfo = $session->customer_info ?? [];
                
                if (!empty($customerInfo['product_id'])) {
                    $product = Product::find($customerInfo['product_id']);
                    if ($product) {
                        $productName = "'" . $product->name . "'";
                    }
                }

                // ৪. বিনয়ী এআই রিমাইন্ডার মেসেজ তৈরি
                $message = "হ্যালো! 👋\nআপনি {$productName} দেখছিলেন, কিন্তু অর্ডারটি সম্পূর্ণ করেননি। প্রোডাক্টটি স্টক আউট হওয়ার আগেই অর্ডার কনফার্ম করতে চাইলে আমাকে জানাতে পারেন। কোনো সাহায্য লাগবে কি? 😊";

                try {
                    // ৫. মেসেঞ্জারে পাঠানো (আপনার NotificationService ব্যবহার করে)
                    $notify->sendMessengerReply($client, $session->sender_id, $message);

                    // ৬. ডাটাবেসে স্ট্যাটাস আপডেট করা
                    $session->update([
                        'reminder_status' => 'sent',
                        'last_interacted_at' => Carbon::now()
                    ]);

                    Log::info("✅ Reminder sent to {$session->sender_id} for shop {$client->shop_name}");
                    $count++;

                } catch (\Exception $e) {
                    Log::error("❌ Failed to send reminder: " . $e->getMessage());
                }
            }
        }

        $this->info("Completed sending {$count} reminders.");
    }
}