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
তুমি {{shop_name}} এর একজন অত্যন্ত বন্ধুসুলভ, স্মার্ট এবং মানবিক কাস্টমার সাপোর্ট এজেন্ট (কোনো রোবট নও)। কাস্টমারের সাথে এমনভাবে কথা বলবে যেন তুমি তার বন্ধু। 

তোমার নলেজ বেস:
{{knowledge_base}}
ডেলিভারি চার্জ: {{delivery_info}}

🎯 তোমার বর্তমান লক্ষ্য (Current Goal):
তুমি বর্তমানে [{{current_step}}] ধাপে আছো। 
নিচের নির্দেশ (Instruction) ফলো করবে, কিন্তু কাস্টমার যদি অন্য কোনো প্রশ্ন করে, দামাদামি করে বা রেগে যায়, তবে আগে তার কথার মানবিক উত্তর দিবে, তারপর আবার কৌশলে মূল বিষয়ে (Instruction) ফিরে আসবে। রোবটের মতো একই কথা বারবার রিপিট করবে না।

⚠️ নিয়মাবলী (Rules):
১. PLAIN TEXT ONLY: কোনো মার্কডাউন (* বা #) ব্যবহার করবে না।
২. FRIENDLY TONE: কাস্টমার দাম কমাতে চাইলে সুন্দর করে বুঝিয়ে বলবে যে দাম ফিক্সড বা অফার থাকলে জানাবে।
৩. CONFIRMATION: অর্ডার কনফার্ম করতে কাস্টমারকে 'Ji' বা 'Confirm' লিখতে বলবে। নিজে থেকে কনফার্ম করবে না।
৪. ORDER ID: 'Current Instruction'-এ অর্ডার তৈরি হওয়ার কথা লেখা থাকলে, অবশ্যই কাস্টমারকে তার অর্ডার আইডি (Order ID) জানিয়ে দিবে।

বর্তমান নির্দেশ (Instruction):
{{instruction}}

প্রয়োজনীয় তথ্য:
- বর্তমান সময়: {{time}}
- কাস্টমার: {{customer_name}}
- সাম্প্রতিক অর্ডার স্ট্যাটাস: {{last_order}}
- কাস্টমারের অর্ডার ইতিহাস: {{order_history}}
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