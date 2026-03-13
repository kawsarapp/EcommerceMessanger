<?php
namespace App\Services\Chatbot;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ChatbotPromptService
{
    public function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo, $currentStep = 'start')
    {
        // 📝 প্রোডাকশন লগ: প্রম্পট জেনারেশন শুরু
        Log::info("🤖 Generating AI Prompt | Shop ID: {$client->id} | Current Step: {$currentStep}");

        $clientPrompt = $client->custom_prompt ?? '';

        // 🔥 PRODUCTION FIX: Address Collection Rule (AI যেন ডাটাবেসের জন্য সঠিক ফরম্যাটে ডাটা দেয়)
        $stepSpecificRules = "";
        if (in_array(strtolower($currentStep), ['collect_info', 'confirm_order'])) {
            $stepSpecificRules = "\n🚨 DATA EXTRACTION RULE (CRITICAL):\nকাস্টমার যখন তার নাম, মোবাইল নাম্বার এবং ঠিকানা দিবে, তখন তুমি সেই তথ্যগুলো বের করে মেসেজের একদম শেষে ঠিক এই ফরম্যাটে গোপন ট্যাগ দিবে:\n[NAME: কাস্টমারের নাম]\n[PHONE: কাস্টমারের ফোন নাম্বার]\n[ADDRESS: কাস্টমারের ঠিকানা]\nউদাহরণ: 'আপনার অর্ডারটি কনফার্ম করছি।' [NAME: Kawsar] [PHONE: 01711223344] [ADDRESS: Mirpur 10, Dhaka]";
            Log::info("📝 Injected Data Extraction Rules for step: {$currentStep}");
        }

        // 🔥 STRICT ZERO-HALLUCINATION & NO-LOOP MASTER RULES
        $masterRules = <<<EOT
তুমি {{shop_name}} এর একজন এক্সপার্ট সেলসম্যান।
তোমার একমাত্র কাজ হলো কাস্টমারের প্রশ্নের উত্তর দেওয়া এবং অর্ডার কনফার্ম করা।

🚨 STRICT AI RULES (CRITICAL - DO NOT BREAK):
১. ZERO HALLUCINATION: 'Inventory'-তে যে প্রোডাক্ট, দাম, কালার বা সাইজ দেওয়া আছে, তুমি ঠিক হুবহু তাই বলবে। স্টকে না থাকলে সরাসরি বলবে "দুঃখিত, এটি স্টকে নেই"। নিজে থেকে কোনো প্রোডাক্ট, অফার, দাম বা সাইজ বানাবে না।
২. NO LINKS FOR IMAGES: কাস্টমার যদি ছবি দেখতে চায়, তবে তুমি মেসেজের মধ্যে সরাসরি এই ফরম্যাটে ছবির লিংকটি বসিয়ে দিবে: [ATTACH_IMAGE: image_url]
যেমন: "জি, প্রোডাক্টের ছবি: [ATTACH_IMAGE: https://example.com/image.jpg]"। তুমি নিজে কোনো টেক্সট লিংক (https://...) দিবে না।
৩. IMAGE SEARCH (SKU): কাস্টমার ছবি দিলে, 'Current Instruction'-এ যদি ছবির গায়ে লেখা কোনো SKU বা কোড থাকে, তবে আগে সেই SKU দিয়ে Inventory তে খুঁজবে।
৪. NO LOOPING: কাস্টমার একই কথা বারবার বললে তুমিও একই উত্তর বারবার দিবে না। তুমি নতুন করে জিজ্ঞেস করবে "আমি কি আপনার অর্ডারটি কনফার্ম করবো?" অথবা "আপনি কি অন্য কোনো কালার দেখতে চান?"।
৫. CALCULATE TOTAL: মোট বিল জানতে চাইলে, (প্রোডাক্টের দাম + ডেলিভারি চার্জ) যোগ করে সঠিক হিসাব দিবে।{{step_rules}}

👇 Current Instruction (তোমাকে এখন ঠিক এই কাজটি করতে হবে):
>>> {{instruction}} <<<

প্রয়োজনীয় তথ্য:
- কাস্টমার: {{customer_name}}
- বর্তমান সময়: {{time}}
- ডেলিভারি চার্জ: {{delivery_info}}
- অর্ডার ইতিহাস: {{order_history}}

👇 Inventory Database (তোমার স্টকে শুধু এগুলোই আছে, এর বাইরে কিছু বলবে না):
{{inventory}}
EOT;

        $finalPrompt = !empty($clientPrompt) 
            ? "Shop Owner's Guideline:\n" . $clientPrompt . "\n\n" . $masterRules 
            : $masterRules;

        $recentOrder = Order::where('client_id', $client->id)->where('sender_id', request('sender_id') ?? 0)->latest()->first();
        $recentOrderInfo = $recentOrder ? "সর্বশেষ অর্ডার: #{$recentOrder->id} ({$recentOrder->order_status})" : "কোনো সাম্প্রতিক অর্ডার নেই।";

        $tags = [
            '{{shop_name}}'       => $client->shop_name,
            '{{knowledge_base}}'  => $knowledgeBase,
            '{{delivery_info}}'   => $deliveryInfo,
            '{{instruction}}'     => $instruction,
            '{{product_context}}' => $prodCtx,
            '{{order_history}}'   => $ordCtx,
            '{{inventory}}'       => $invData,
            '{{time}}'            => $time,
            '{{customer_name}}'   => $userName,
            '{{last_order}}'      => $recentOrderInfo,
            '{{step_rules}}'      => $stepSpecificRules, // ডাইনামিক রুলস
            '{{current_step}}'    => strtoupper(str_replace('_', ' ', $currentStep)),
        ];

        Log::info("✅ Prompt generated successfully for Shop ID: {$client->id}");
        return strtr($finalPrompt, $tags);
    }

    public function buildOrderContext($clientId, $senderId, $userMessage = '')
    {
        Log::info("🔍 Fetching Order History | Shop: {$clientId} | Sender: {$senderId}");
        
        $phone = null;
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $cleanMsg = str_replace($bn, $en, $userMessage);

        if (preg_match('/01[3-9]\d{8,9}/', $cleanMsg, $matches)) {
            $phone = substr($matches[0], 0, 11);
            Log::info("📞 Phone detected in message for history lookup: {$phone}");
        }

        $query = Order::where('client_id', $clientId);
        
        if ($phone) {
            $query->where(function($q) use ($senderId, $phone) {
                $q->where('sender_id', $senderId)->orWhere('customer_phone', $phone);
            });
        } else {
            $query->where('sender_id', $senderId);
        }

        $orders = $query->latest()->take(3)->get();
        if ($orders->isEmpty()) {
            Log::info("ℹ️ No previous order history found.");
            return "এই কাস্টমারের কোনো পূর্ববর্তী অর্ডার নেই।";
        }
        
        Log::info("📦 Found {$orders->count()} previous orders for context.");
        
        $context = "কাস্টমারের সর্বশেষ ৩টি অর্ডারের তথ্য:\n";
        foreach($orders as $o) {
            $trackingInfo = "";
            if (!empty($o->admin_note)) {
                if (preg_match('/Steadfast Tracking:\s*([A-Za-z0-9\-]+)/i', $o->admin_note, $match)) {
                    $trackingInfo = " (Steadfast Code: {$match[1]})";
                } elseif (preg_match('/Pathao Tracking:\s*([A-Za-z0-9\-]+)/i', $o->admin_note, $match)) {
                    $trackingInfo = " (Pathao Code: {$match[1]})";
                } elseif (preg_match('/RedX Tracking:\s*([A-Za-z0-9\-]+)/i', $o->admin_note, $match)) {
                    $trackingInfo = " (RedX Code: {$match[1]})";
                }
            }
            $context .= "- অর্ডার #{$o->id} (অবস্থা: {$o->order_status}), বিল: {$o->total_amount} টাকা{$trackingInfo}।\n";
        }
        return $context;
    }
}