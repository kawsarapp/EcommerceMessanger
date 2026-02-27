<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class CourierWebhookController extends Controller
{
    /**
     * কুরিয়ার থেকে আসা অটোমেটিক স্ট্যাটাস আপডেট রিসিভ করা
     */
    public function handle(Request $request, $client_id, $courier_name)
    {
        // ১. সিকিউরিটি চেক: ক্লায়েন্ট আইডি ডাটাবেসে আছে কিনা
        $client = Client::where('id', $client_id)->where('status', 'active')->first();
        if (!$client) {
            Log::error("❌ Courier Webhook Failed: Client ID {$client_id} not found.");
            return response()->json(['status' => 'error', 'message' => 'Invalid Client ID'], 404);
        }

        $data = $request->all();
        Log::info("🚚 Webhook Received from {$courier_name} for Client {$client->shop_name}", $data);

        // ২. Steadfast Courier এর লজিক
        if ($courier_name === 'steadfast') {
            
            // Steadfast সাধারণত tracking_code বা consignment_id পাঠায়
            $trackingCode = $data['consignment_id'] ?? $data['tracking_code'] ?? null;
            $status = strtolower($data['status'] ?? ''); // delivered, returned, in_review ইত্যাদি

            if ($trackingCode && $status) {
                // ক্লায়েন্টের নিজস্ব অর্ডারটি ট্র্যাকিং কোড দিয়ে খোঁজা হচ্ছে
                $order = Order::where('client_id', $client->id)
                    ->where('tracking_code', $trackingCode)
                    ->first();

                if ($order) {
                    // স্ট্যাটাস ম্যাপিং (Steadfast এর স্ট্যাটাস অনুযায়ী আপনার ডাটাবেসের স্ট্যাটাস আপডেট)
                    if (in_array($status, ['delivered', 'partial_delivered'])) {
                        $order->update([
                            'order_status' => 'delivered',
                            'payment_status' => 'paid', // ডেলিভারি হলে পেমেন্ট পেইড হয়ে যাবে
                            'admin_note' => "অটোমেটিক আপডেট: কুরিয়ার পার্সেলটি ডেলিভারি করেছে।\n" . $order->admin_note
                        ]);
                        Log::info("✅ Order {$order->id} marked as Delivered.");

                    } elseif (in_array($status, ['returned', 'cancelled', 'lost'])) {
                        $order->update([
                            'order_status' => 'cancelled',
                            'admin_note' => "অটোমেটিক আপডেট: কুরিয়ার পার্সেলটি রিটার্ন করেছে।\n" . $order->admin_note
                        ]);
                        Log::info("❌ Order {$order->id} marked as Returned/Cancelled.");
                    }

                    return response()->json(['status' => 'success', 'message' => 'Order updated'], 200);
                }
            }
        }

        // পরবর্তীতে Pathao/RedX এর লজিক এখানে যুক্ত করা যাবে

        return response()->json(['status' => 'ignored', 'message' => 'No action taken'], 200);
    }
}