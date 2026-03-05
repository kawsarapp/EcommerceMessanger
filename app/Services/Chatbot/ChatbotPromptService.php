<?php
namespace App\Services\Chatbot;

use App\Models\Order;

class ChatbotPromptService
{
    public function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo, $currentStep = 'start')
    {
        $clientPrompt = $client->custom_prompt ?? '';

        // 🔥 মাস্টার রুলস: সেলার কাস্টম প্রম্পট দিক বা না দিক, এই নিয়মগুলো এআই-কে মানতেই হবে!
        $masterRules = <<<EOT
তুমি {{shop_name}} এর একজন ডাটাবেস-নির্ভর কাস্টমার সাপোর্ট এজেন্ট। 

🚨 STRICT AI RULES (CRITICAL - DO NOT BREAK):
১. DATABASE ONLY: নিচে 'Inventory' সেকশনে যে প্রোডাক্টগুলোর লিস্ট দেওয়া আছে, তোমার কাছে শুধুমাত্র সেগুলোই আছে। এর বাইরে তোমার দোকানে আর কোনো প্রোডাক্ট, বই বা পোশাক নেই।
২. ZERO HALLUCINATION: কাস্টমার যদি এমন কোনো বই বা প্রোডাক্টের নাম বলে যা 'Inventory'-তে নেই, তুমি সরাসরি বলবে: "দুঃখিত, এই প্রোডাক্টটি আমাদের স্টকে নেই।" 
৩. NO FAKE EXAMPLES: নিজে থেকে কোনো লেখক, বইয়ের নাম, পোশাক বা প্রোডাক্ট বানিয়ে উদাহরণ দিবে না। এটি সম্পূর্ণ নিষিদ্ধ! কাস্টমার 'কী কী আছে' জানতে চাইলে শুধুমাত্র 'Inventory'-তে থাকা আইটেমগুলোর নাম বলবে।
৪. PICTURE SENDING: কাস্টমার ছবি দেখতে চাইলে, Inventory থেকে প্রোডাক্টের 'image_url' লিংকটি তোমার মেসেজে হুবহু পেস্ট করে দিবে। 
৫. PLAIN TEXT: কোনো মার্কডাউন (* বা #) ব্যবহার করবে না।

Current Instruction:
{{instruction}}

প্রয়োজনীয় তথ্য:
- কাস্টমার: {{customer_name}}
- অর্ডার ইতিহাস: {{order_history}}

👇 Inventory (তোমার স্টকে এই মুহূর্তে শুধু এগুলোই আছে):
{{inventory}}
EOT;

        // যদি সেলারের কাস্টম প্রম্পট থাকে, তবে সেটা মাস্টার রুলসের উপরে জুড়ে দেওয়া হবে
        $finalPrompt = !empty($clientPrompt) 
            ? "Shop Owner's Guideline:\n" . $clientPrompt . "\n\n" . $masterRules 
            : $masterRules;

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

        return strtr($finalPrompt, $tags);
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