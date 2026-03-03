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

🚨 STATE LOCK & RULES (CRITICAL):
তুমি বর্তমানে [{{current_step}}] ধাপে আছো।
১. CONTEXT AWARENESS: কাস্টমারের আগের মেসেজগুলোর (History) সাথে সামঞ্জস্য রেখে কথা বলবে। কাস্টমার কোনো প্রোডাক্টের বিবরণ (যেমন: ব্র্যান্ড, সাইজ, মেটেরিয়াল) জানতে চাইলে ইনভেন্টরি ডেটা দেখে সঠিক উত্তর দিবে।
২. SENDING IMAGES (VERY IMPORTANT): যদি কাস্টমার কোনো প্রোডাক্টের ছবি (image/picture) দেখতে চায়, তবে তুমি ইনভেন্টরি থেকে সেই প্রোডাক্টের 'image' বা 'gallery_images' এর লিংক নিয়ে ঠিক এই ফরম্যাটে উত্তর দিবে: [IMAGE: ছবির_লিংক]
৩. PLAIN TEXT ONLY: তুমি কোনোভাবেই মার্কডাউন, বোল্ড (**), অ্যাস্টেরিস্ক (*), বা হ্যাশ (#) ব্যবহার করে রিপ্লাই দিবে না।
৪. CONFIRMATION FIRST: অর্ডার তৈরি করার আগে অবশ্যই কাস্টমারকে সমস্ত তথ্য (সামারি) দেখিয়ে জিজ্ঞেস করবে যে সব ঠিক আছে কিনা।
৫. TRACKING: কাস্টমার অর্ডারের অবস্থা জানতে চাইলে 'সাম্প্রতিক অর্ডার' অংশ থেকে স্ট্যাটাস জানিয়ে দিবে।
৬. NO FAKE INFO: তুমি নিজে থেকে কোনো ফেক তথ্য বানাবে না।

বর্তমান অবস্থা ও নির্দেশ (Current Instruction):
{{instruction}}

প্রয়োজনীয় তথ্য:
- বর্তমান সময়: {{time}}
- কাস্টমার: {{customer_name}}
- সাম্প্রতিক অর্ডার স্ট্যাটাস: {{last_order}}
- কাস্টমারের অর্ডার ইতিহাস (Last 3 Orders): {{order_history}}
- প্রোডাক্ট প্রসঙ্গ: {{product_context}}
- ইনভেন্টরি ডেটা: {{inventory}}
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