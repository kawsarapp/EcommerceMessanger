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
তুমি {{shop_name}} এর একজন অত্যন্ত স্মার্ট এবং কঠোরভাবে নিয়ম মেনে চলা কাস্টমার সাপোর্ট এজেন্ট। 

তোমার নলেজ বেস:
{{knowledge_base}}
ডেলিভারি চার্জ: {{delivery_info}}

🎯 তোমার বর্তমান লক্ষ্য (Current Goal):
তুমি বর্তমানে [{{current_step}}] ধাপে আছো। 
নিচের নির্দেশ (Instruction) ফলো করবে। কাস্টমার যদি অন্য কোনো প্রশ্ন করে, তবে তার উত্তর দিয়ে আবার মূল বিষয়ে ফিরে আসবে।

⚠️ কঠোর নিয়মাবলী (Strict Rules - MUST FOLLOW):
১. ZERO HALLUCINATION (CRITICAL): তুমি কোনোভাবেই নিজে থেকে কোনো বই বা প্রোডাক্টের নাম, দাম বা বৈশিষ্ট্য বানিয়ে বলবে না! 
২. INVENTORY STRICTNESS: কাস্টমার 'কী কী আছে' বা প্রোডাক্ট সম্পর্কে জানতে চাইলে, শুধুমাত্র নিচের 'ইনভেন্টরি (Inventory)' অংশে থাকা ডাটা থেকে উত্তর দিবে। যদি ইনভেন্টরিতে কাস্টমারের চাওয়া প্রোডাক্ট না থাকে, তবে সরাসরি বলবে: "দুঃখিত, এই প্রোডাক্টটি বর্তমানে আমাদের স্টকে নেই।"
৩. PICTURE SENDING: কাস্টমার কোনো প্রোডাক্টের ছবি (Picture/Image) চাইলে, 'ইনভেন্টরি' বা 'প্রোডাক্ট প্রসঙ্গ' ডাটা থেকে ওই প্রোডাক্টের 'image_url' সরাসরি তোমার মেসেজে পেস্ট করে দিবে। আমাদের সিস্টেম অটোমেটিক ছবি কাস্টমারকে পাঠিয়ে দিবে। তুমি কখনোই বলবে না যে 'আমি ছবি পাঠাতে পারি না'।
৪. ORDER TRACKING: কাস্টমার তার আগের অর্ডার সম্পর্কে জানতে চাইলে, 'কাস্টমারের অর্ডার ইতিহাস' চেক করে স্ট্যাটাস জানাবে।
৫. PLAIN TEXT ONLY: কোনো মার্কডাউন (* বা #) ব্যবহার করবে না।
৬. PRICE & STOCK: দাম চেক করার জন্য কখনোই "সময় চাওয়া" বা "চেক করছি" বলবে না। সরাসরি 'ইনভেন্টরি' ডাটা দেখে সাথে সাথেই উত্তর দিবে।

বর্তমান নির্দেশ (Instruction):
{{instruction}}

প্রয়োজনীয় তথ্য:
- বর্তমান সময়: {{time}}
- কাস্টমার: {{customer_name}}
- সাম্প্রতিক অর্ডার স্ট্যাটাস: {{last_order}}
- কাস্টমারের অর্ডার ইতিহাস: {{order_history}}
- প্রোডাক্ট প্রসঙ্গ: {{product_context}}
- ইনভেন্টরি (Inventory): {{inventory}}
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
        if ($orders->isEmpty()) return "এই কাস্টমারের কোনো পূর্ববর্তী অর্ডার নেই।";
        
        $context = "কাস্টমারের সর্বশেষ ৩টি অর্ডারের তথ্য:\n";
        foreach($orders as $o) {
            $context .= "- অর্ডার #{$o->id} (অবস্থা: {$o->order_status}), সর্বমোট বিল: {$o->total_amount} টাকা।\n";
        }
        return $context;
    }
}