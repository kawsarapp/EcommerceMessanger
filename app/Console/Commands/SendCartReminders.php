<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderSession;
use App\Models\Client;
use App\Models\Product;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendCartReminders extends Command
{
    protected $signature = 'cart:remind';
    protected $description = 'Send automated AI reminders for abandoned carts (WhatsApp, Messenger, Telegram)';

    public function handle(NotificationService $notify)
    {
        $clients = Client::where('is_reminder_active', true)->get();
        $count = 0;

        foreach ($clients as $client) {
            $delayHours   = $client->reminder_delay_hours ?? 2;
            $thresholdTime = Carbon::now()->subHours($delayHours);

            $abandonedSessions = OrderSession::where('client_id', $client->id)
                ->where('status', '!=', 'completed')
                ->where('reminder_status', 'pending')
                ->where('updated_at', '<=', $thresholdTime)
                ->get();

            foreach ($abandonedSessions as $session) {
                // ১. Product নাম বের করা
                $productName = "আপনার পছন্দের প্রোডাক্টটি";
                $customerInfo = $session->customer_info ?? [];

                if (!empty($customerInfo['product_id'])) {
                    $product = Product::find($customerInfo['product_id']);
                    if ($product) $productName = "'{$product->name}'";
                }

                // ২. বিনয়ী এআই রিমাইন্ডার মেসেজ
                $message = "হ্যালো! 👋\nআপনি {$productName} দেখছিলেন, কিন্তু অর্ডারটি সম্পূর্ণ করেননি। প্রোডাক্টটি স্টক আউট হওয়ার আগেই অর্ডার কনফার্ম করতে চাইলে আমাকে জানাতে পারেন। কোনো সাহায্য লাগবে কি? 😊";

                // ৩. 🔥 Platform অনুযায়ী সঠিক channel এ পাঠানো
                $platform = $session->platform ?? 'messenger'; // default fallback

                try {
                    switch ($platform) {
                        case 'whatsapp':
                            // WhatsApp API দিয়ে পাঠানো
                            $waApiUrl = config('services.whatsapp.api_url');
                            if ($client->wa_instance_id && $waApiUrl) {
                                $response = Http::timeout(15)->post($waApiUrl . '/api/send-message', [
                                    'instance_id' => $client->wa_instance_id,
                                    'to'          => $session->sender_id,
                                    'message'     => $message,
                                ]);
                                if ($response->status() < 400) {
                                    Log::info("✅ Reminder sent via WhatsApp to {$session->sender_id} for shop {$client->shop_name}");
                                    $count++;
                                } else {
                                    Log::warning("⚠️ WhatsApp Reminder API failed for {$session->sender_id}: " . $response->body());
                                }
                            } else {
                                Log::warning("⚠️ WhatsApp reminder skipped: wa_instance_id not configured for {$client->shop_name}");
                            }
                            break;

                        case 'telegram':
                            // Telegram দিয়ে পাঠানো
                            $notify->sendTelegramCustomerReply($client->telegram_bot_token, $session->sender_id, $message);
                            Log::info("✅ Reminder sent via Telegram to {$session->sender_id} for shop {$client->shop_name}");
                            $count++;
                            break;

                        case 'instagram':
                            // Instagram = same as Messenger API
                            $notify->sendInstagramReply($client, $session->sender_id, $message);
                            Log::info("✅ Reminder sent via Instagram to {$session->sender_id} for shop {$client->shop_name}");
                            $count++;
                            break;

                        case 'messenger':
                        default:
                            // Facebook Messenger
                            $notify->sendMessengerReply($client, $session->sender_id, $message);
                            Log::info("✅ Reminder sent via Messenger to {$session->sender_id} for shop {$client->shop_name}");
                            $count++;
                            break;
                    }

                    // ৪. Status আপডেট
                    $session->update([
                        'reminder_status'    => 'sent',
                        'last_interacted_at' => Carbon::now(),
                    ]);

                } catch (\Exception $e) {
                    Log::error("❌ Failed to send reminder [{$platform}] to {$session->sender_id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Completed sending {$count} reminders.");
    }
}