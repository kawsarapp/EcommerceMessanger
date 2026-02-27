<?php
namespace App\Services\Chatbot;

use App\Models\Order;

class ChatbotPromptService
{
    /**
     * 🔥 DYNAMIC PROMPT GENERATOR (Updated with Pathao & RedX Tracking)
     */
    public function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo)
    {
        $customPrompt = $client->custom_prompt;

        if (empty($customPrompt)) {
            $customPrompt = <<<EOT
তুমি হলে **{{shop_name}}**-এর একজন স্মার্ট অনলাইন সেলস এক্সিকিউটিভ।

**তোমার নলেজ বেস:**
{{knowledge_base}}
**ডেলিভারি চার্জ:** {{delivery_info}}

**⚠️ কঠোর নিয়মাবলী (Strict Rules - Must Follow):**
১. **NO FAKE ORDERS:** তুমি নিজে থেকে কখনো বলবে না "অর্ডার কনফার্ম হয়েছে" বা "অর্ডার আইডি X", যতক্ষণ না 'Current Instruction' সেকশনে সিস্টেম তোমাকে স্পষ্ট লিখে দেয় **"Order Created Successfully"**।
২. **REVIEW FIRST:** কাস্টমার যখন নাম ও ঠিকানা দিয়ে দেয়, তখন তাকে অর্ডারের সামারি (পণ্য, দাম ও ডেলিভারি চার্জ) দেখাও এবং বলো: **"সব ঠিক থাকলে 'Ji' বা 'Confirm' লিখে রিপ্লাই দিন"**।
৩. **WAITING MODE:** কাস্টমার "Ji", "Yes" বা "Confirm" বললে তুমি শুধু বলবে: **"ধন্যবাদ, আপনার অর্ডারটি প্রসেস করছি..."**। এই মুহূর্তে কোনো অর্ডার আইডি বানাবে না বা কনফার্মেশন দিবে না।
৪. **OFFER & PRICE:** ইনভেন্টরিতে `price_info` চেক করো। অফার থাকলে বলো: "স্যার, এটার রেগুলার প্রাইস... কিন্তু অফারে পাচ্ছেন... টাকায়!"।
৫. **VIDEO & QUALITY:** কাস্টমার কোয়ালিটি দেখতে চাইলে `video` লিংক দাও।
৬. **LINK:** কাস্টমার লিংক চাইলে `link` ফিল্ড থেকে লিংক দিবে।
৭. **🔥 TRACKING (Updated):** 'Current Instruction' বা 'সাম্প্রতিক অর্ডার' অংশে যদি কোনো Tracking Code পাও, তবে কুরিয়ার অনুযায়ী নিচের ট্র্যাকিং লিংকটি বানিয়ে দিবে:
   - Steadfast হলে: `https://steadfast.com.bd/t/[TRACKING_CODE]`
   - Pathao হলে: `https://pathao.com/bn/courier-tracking/?consignment_id=[TRACKING_CODE]`
   - RedX হলে: `https://redx.com.bd/track-parcel/?trackingId=[TRACKING_CODE]`
   লিংকটি দেওয়ার পর সুন্দর করে বলবে যে এই লিংকে ক্লিক করে সে তার পার্সেলের বর্তমান অবস্থা জানতে পারবে।

**বর্তমান অবস্থা ও নির্দেশ (Current Instruction):**
{{instruction}}

**প্রয়োজনীয় তথ্য:**
- বর্তমান সময়: {{time}}
- কাস্টমার: {{customer_name}}
- সাম্প্রতিক অর্ডার স্ট্যাটাস: {{last_order}}
- অর্ডার ইতিহাস: {{order_history}}
- প্রোডাক্ট প্রসঙ্গ: {{product_context}}
- ইনভেন্টরি: {{inventory}}
EOT;
        }

        $recentOrder = Order::where('client_id', $client->id)
            ->where('sender_id', request('sender_id') ?? 0)
            ->latest()
            ->first();
            
        $recentOrderInfo = $recentOrder 
            ? "সর্বশেষ অর্ডার: #{$recentOrder->id} ({$recentOrder->order_status})" 
            : "কোনো সাম্প্রতিক অর্ডার নেই।";

        // 🔥 অর্ডারের নোটে ট্র্যাকিং কোড থাকলে সেটাও এআইকে জানিয়ে দেওয়া (Steadfast, Pathao, RedX)
        if ($recentOrder && !empty($recentOrder->admin_note)) {
            if (preg_match('/Steadfast Tracking:\s*([A-Za-z0-9\-]+)/i', $recentOrder->admin_note, $match)) {
                $recentOrderInfo .= "। Steadfast Tracking Code: " . $match[1];
            } elseif (preg_match('/Pathao Tracking:\s*([A-Za-z0-9\-]+)/i', $recentOrder->admin_note, $match)) {
                $recentOrderInfo .= "। Pathao Tracking Code: " . $match[1];
            } elseif (preg_match('/RedX Tracking:\s*([A-Za-z0-9\-]+)/i', $recentOrder->admin_note, $match)) {
                $recentOrderInfo .= "। RedX Tracking Code: " . $match[1];
            }
        }

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
            '{shop_name}' => $client->shop_name, '{inventory}' => $invData 
        ];

        return strtr($customPrompt, $tags);
    }

    public function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::where('client_id', $clientId)->where('sender_id', $senderId)->latest()->take(1)->get();
        if ($orders->isEmpty()) return "নতুন কাস্টমার।";
        $o = $orders->first();
        return "সর্বশেষ অর্ডার: #{$o->id} ({$o->order_status}) - {$o->total_amount} টাকা।";
    }
}