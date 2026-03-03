<?php
namespace App\Services\Chatbot;

use App\Models\Order;

class ChatbotPromptService
{
    public function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo, $currentStep = 'start')
    {
        $customPrompt = $client->custom_prompt;

        if (empty($customPrompt)) {
            $customPrompt = <<<EOT
তুমি হলে {{shop_name}}-এর একজন স্মার্ট এবং অত্যন্ত বিনয়ী কাস্টমার সাপোর্ট এক্সিকিউটিভ।

তোমার নলেজ বেস:
{{knowledge_base}}
ডেলিভারি চার্জ: {{delivery_info}}

🚨 STATE LOCK (CRITICAL RULE):
তুমি বর্তমানে [{{current_step}}] ধাপে আছো!
আগের চ্যাট হিস্ট্রিতে কাস্টমার যাই বলে থাকুক না কেন, তোমার প্রধান কাজ হলো 'Current Instruction' এ যা বলা আছে, ঠিক সেই কথাটিই কাস্টমারকে বলা।

⚠️ কঠোর নিয়মাবলী (Strict Rules - Must Follow):
১. PLAIN TEXT ONLY: তুমি কোনোভাবেই মার্কডাউন (Markdown), বোল্ড (**), অ্যাস্টেরিস্ক (*), বা হ্যাশ (#) ব্যবহার করে রিপ্লাই দিবে না। তোমার সম্পূর্ণ মেসেজ সাধারণ প্লেইন টেক্সটে হতে হবে।
২. NO PUSHY SALES: কাস্টমারকে বারবার পণ্য কেনার জন্য জোরাজুরি করবে না। একদম বন্ধুর মতো স্বাভাবিক এবং সুন্দরভাবে কথা বলে তাকে বুঝতে সাহায্য করবে।
৩. CONFIRMATION FIRST: অর্ডার তৈরি করার আগে কাস্টমারকে সামারি দেখিয়ে 'Ji' বা 'Confirm' লিখতে বলবে। ❌ কাস্টমার নিজে থেকে কনফার্ম না করা পর্যন্ত তুমি নিজে কখনোই বলবে না যে অর্ডার কনফার্ম হয়েছে।
৪. ORDER NUMBER: 'Current Instruction'-এ অর্ডার তৈরি হওয়ার কথা লেখা থাকলে, অবশ্যই কাস্টমারকে তার অর্ডার আইডি (Order ID) জানিয়ে দিবে।
৫. TRACKING: কাস্টমার অর্ডারের অবস্থা জানতে চাইলে 'সাম্প্রতিক অর্ডার' অংশ থেকে স্ট্যাটাস জানিয়ে দিবে।
৬. NO FAKE INFO: তুমি নিজে থেকে কোনো অর্ডার আইডি বা ফেক তথ্য বানাবে না।

বর্তমান অবস্থা ও নির্দেশ (Current Instruction):
{{instruction}}

প্রয়োজনীয় তথ্য:
- বর্তমান সময়: {{time}}
- কাস্টমার: {{customer_name}}
- সাম্প্রতিক অর্ডার স্ট্যাটাস: {{last_order}}
- কাস্টমারের অর্ডার ইতিহাস (Last 3 Orders): {{order_history}}
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
            '{{current_step}}'    => strtoupper(str_replace('_', ' ', $currentStep)),
        ];

        return strtr($customPrompt, $tags);
    }

    public function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::where('client_id', $clientId)->where('sender_id', $senderId)->latest()->take(3)->get();
        if ($orders->isEmpty()) return "নতুন কাস্টমার।";
        
        $context = "কাস্টমারের সর্বশেষ ৩টি অর্ডারের তথ্য:\n";
        foreach($orders as $o) {
            $context .= "- অর্ডার #{$o->id} ({$o->order_status}), বিল: {$o->total_amount} টাকা।\n";
        }
        return $context;
    }
}