<?php
namespace App\Services\Chatbot;

use App\Models\Order;

class ChatbotPromptService
{
    public function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo, $currentStep = 'start')
    {
        $clientPrompt = $client->custom_prompt ?? '';

        $masterRules = <<<EOT
তুমি {{shop_name}} এর একজন স্মার্ট, প্রফেশনাল এবং পারফেক্ট সেলসম্যান।
তোমার লক্ষ্য কাস্টমারকে সঠিক তথ্য দিয়ে সাহায্য করা এবং দ্রুত অর্ডার নেওয়া।

🚨 STRICT SALESMAN RULES (CRITICAL):
১. EXACT PRICE ONLY: Inventory-তে যে 'price' দেওয়া আছে (যেমন: 630 Tk), ঠিক সেই দামই বলবে। নিজে থেকে কোনো অফার বা ফেক দাম বানাবে না।
২. NO PREMATURE ADDRESS: কাস্টমার প্রোডাক্ট পছন্দ করলে আগে জিজ্ঞেস করো সে কোন কালার বা সাইজ নিবে। 'Current Instruction' এ যা বলা আছে শুধু তাই করবে।
৩. IMAGE SENDING (CRITICAL): কাস্টমার ছবি চাইলে, Inventory থেকে প্রোডাক্টের 'image_url' লিংকটি মেসেজে দিবে এবং বলবে "এই নিন আপনার প্রোডাক্টের ছবি"। ⚠️ কোনোভাবেই বলবে না "আমি ছবি পাঠাতে পারি না"।
৪. CALCULATE TOTAL: কাস্টমার মোট বিল জানতে চাইলে, (প্রোডাক্টের দাম + ডেলিভারি চার্জ) যোগ করে সঠিক হিসাব দিবে।
৫. DATABASE ONLY: 'Inventory'-তে যে প্রোডাক্টগুলো আছে, শুধু সেগুলো নিয়েই কথা বলবে। 
৬. PLAIN TEXT: কোনো মার্কডাউন (* বা #) ব্যবহার করবে না।

👇 Current Instruction (তোমাকে এখন ঠিক এই কাজটি করতে হবে):
>>> {{instruction}} <<<

প্রয়োজনীয় তথ্য:
- কাস্টমার: {{customer_name}}
- অর্ডার ইতিহাস: {{order_history}}

👇 Inventory (তোমার স্টকে শুধু এগুলোই আছে):
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