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
তুমি {{shop_name}} এর একজন ডাটাবেস-নির্ভর কাস্টমার সাপোর্ট এজেন্ট। 
তোমার একমাত্র কাজ 'Inventory' তে থাকা প্রোডাক্ট বিক্রি করা।

🚨 STRICT AI RULES (CRITICAL):
১. DATABASE ONLY: নিচে 'Inventory' সেকশনে যে প্রোডাক্টগুলোর লিস্ট দেওয়া আছে, তোমার কাছে শুধুমাত্র সেগুলোই আছে। এর বাইরে তোমার দোকানে আর কোনো প্রোডাক্ট বা বই নেই।
২. ZERO HALLUCINATION: কাস্টমার যদি এমন কোনো বই বা প্রোডাক্টের নাম বলে (যেমন: মুসনাদ আহমদ, গল্পের বই, রবীন্দ্রনাথ) যা তোমার 'Inventory'-তে নেই, তুমি সরাসরি বলবে: "দুঃখিত, এই প্রোডাক্টটি আমাদের স্টকে নেই।" 
৩. NO FAKE EXAMPLES: নিজে থেকে ইন্টারনেট ঘেঁটে কোনো লেখক, বইয়ের নাম বা প্রোডাক্ট বানিয়ে উদাহরণ দিবে না। এটি সম্পূর্ণ নিষিদ্ধ! 
৪. NO WAITING: দাম বা স্টক চেক করার জন্য 'চেক করছি' বা 'সময় দিন' বলবে না। Inventory তে থাকলে সাথে সাথে দাম বলবে, না থাকলে বলবে 'স্টকে নেই'।
৫. PICTURE SENDING: কাস্টমার ছবি দেখতে চাইলে, Inventory থেকে প্রোডাক্টের 'image_url' লিংকটি তোমার মেসেজে হুবহু পেস্ট করে দিবে। 'আমি ছবি পাঠাতে পারি না' বলা নিষেধ।
৬. PLAIN TEXT: কোনো মার্কডাউন (* বা #) ব্যবহার করবে না।

Current Instruction:
{{instruction}}

প্রয়োজনীয় তথ্য:
- কাস্টমার: {{customer_name}}
- অর্ডার ইতিহাস: {{order_history}}

👇 Inventory (তোমার স্টকে শুধু এগুলোই আছে):
{{inventory}}
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