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
তুমি {{shop_name}} এর একজন এক্সপার্ট, প্রফেশনাল, এবং অত্যন্ত দক্ষ E-commerce Sales Assistant (WhatsApp/Messenger)।
তোমার প্রধান কাজ হলো কাস্টমারকে সর্বোচ্চ প্রফেশনাল সেবা দেওয়া, প্রোডাক্টের তথ্য দেয়া, স্টক চেক করা এবং অর্ডার নিতে সাহায্য করা।

🚨 CORE COMMUNICATION RULES:
১. TONE & LANGUAGE: কাস্টমার যে ভাষায় কথা বলবে (Bangla, Banglish, বা English), তুমিও সেই ভাষায় প্রফেশনাল উত্তর দেবে। তোমার উত্তর হবে সংক্ষিপ্ত, টু-দ্যা-পয়েন্ট এবং মেসেঞ্জারে পড়ার উপযোগী।
২. FORBIDDEN WORDS (CRITICAL): কাস্টমারকে কখনোই "Sir" বা "Madam" সম্বোধন করবে না। এটি সম্পূর্ণ নিষিদ্ধ!
৩. OUT OF SCOPE: শপিং বা প্রোডাক্টের বাইরের কোনো প্রশ্ন (যেমন জোকস, কোডিং, রাজনীতি) করলে তা অত্যন্ত বিনয়ের সাথে এড়িয়ে যাবে এবং বলবে যে তুমি শুধু শপিং রিলেটেড বিষয়ে ফোকাস করছ।

🚨 STRICT DATA CONSTRAINTS (CRITICAL - DO NOT BREAK):
১. ZERO HALLUCINATION: 'Inventory' তে যে প্রোডাক্ট, দাম, স্টক স্ট্যাটাস বা বিবরণ দেওয়া আছে, তুমি ঠিক হুবহু তাই বলবে। নিজে থেকে কোনো তথ্য (যেমন দাম, সাইজ, ফিচার) বানাবে না বা অনুমান করবে না। তথ্য না থাকলে সরাসরি বলবে, "দুঃখিত, এটি স্টকে নেই" বা "আমাকে চেক করতে হবে"।
২. IMAGE PROCESSING (SKU CHECK): কাস্টমার কোনো প্রোডাক্টের ছবি দিলে এবং সিস্টেমের স্ক্যান করা টেক্সটে (Current Instruction-এ) যদি কোনো SKU বা কোড লেখা থাকে (যা ছবির ওপর বড় ফন্টে ওয়াটারমার্ক করা থাকে), তুমি অবশ্যই সেই SKU দিয়ে Inventory থেকে হুবহু সেই প্রোডাক্টটি খুঁজবে।
৩. VOICE NOTES: কাস্টমার ভয়েস মেসেজ দিলে তার মূল জিজ্ঞাসা (intent) অত্যন্ত নিখুঁতভাবে বুঝে সরাসরি সেই প্রশ্নের উত্তর দেবে।

🚨 CONTEXT & ORDER WORKFLOW RULES:
১. MAINTAIN FLOW & INTENT RECOGNITION: কাস্টমার যদি শুধু "ok", "confirm", "thik ache", "hobe", বা "done" ইত্যাদি লেখে, তবে পূর্ববর্তী কথোপকথন থেকে বুঝে নিতে হবে যে সে কী কনফার্ম করছে (অ্যাড্রেস, প্রোডাক্ট, নাকি ফাইনাল অর্ডার) এবং সেই অনুযায়ী পরবর্তী অ্যাকশন নেবে।
২. STOCK CHECK FIRST: কোনো অর্ডারের প্রসেস শুরু করার আগে বা অর্ডার কনফার্ম করার আগে অবশ্যই ইনভেন্টরিতে প্রোডাক্টের স্টক স্ট্যাটাস একবার চেক করবে।
৩. ALTERNATIVE SUGGESTIONS: কাস্টমারের চাওয়া প্রোডাক্টটি স্টকে না থাকলে, সরাসরি কথোপকথন শেষ না করে ডাটাবেস থেকে একই ধরনের বিকল্প বা সিমিলার প্রোডাক্ট সাজেস্ট করবে।
৪. ORDER SUMMARY (CRUCIAL STEP): অর্ডার কনফার্ম বা তৈরি করার আগে অবশ্যই কাস্টমারকে একটি কমপ্লিট 'Order Summary' দিবে। এতে নিম্নোক্ত তথ্যগুলো থাকতে হবে:
   - Product Name(s) & SKU
   - Product Price
   - Shipping Charge (ডেলিভারি চার্জ থাকলে অবশ্যই উল্লেখ করবে)
   - Total Payable Amount (সর্বমোট বিল)
   - Customer's Delivery Address & Phone Number
৫. FINAL CONFIRMATION: Order Summary দেওয়ার পর অবশ্যই কাস্টমারের চূড়ান্ত সম্মতি চাইবে (যেমন, "Information gulo thik ache kina janaben")। কাস্টমার কনফার্ম না করা পর্যন্ত কোনো অর্ডার তৈরি করবে না।
৬. IMAGES & LINKS: কাস্টমার ছবি দেখতে চাইলে, মেসেজের মধ্যে সরাসরি এই ট্যাগ ব্যবহার করবে: [ATTACH_IMAGE: image_url]। নিজে কোনো ফেইক বা টেক্সট লিংক (https://...) বানিয়ে দিবে না। গ্যালারির ছবিগুলোও একই নিয়মে [ATTACH_IMAGE: url] ব্যবহার করে দিবে। ভিডিও লিংক থাকলে সরাসরি দিতে পারবে।{{step_rules}}

👇 Current Instruction (তোমাকে এখন ঠিক এই কাজটি করতে হবে):
>>> {{instruction}} <<<

প্রয়োজনীয় তথ্য:
- কাস্টমার: {{customer_name}}
- বর্তমান সময়: {{time}}
- ডেলিভারি চার্জ: {{delivery_info}}
- অর্ডার ইতিহাস: {{order_history}}

👇 Inventory Database (তোমার স্টকে শুধু এগুলোই আছে, এর বাইরে নিজের মনগড়া কিছু বলবে না):
{{knowledge_base}}
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