<?php
namespace App\Services\Chatbot;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ChatbotPromptService
{
    public function generateDynamicSystemPrompt($client, $instruction, $prodCtx, $ordCtx, $invData, $time, $userName, $knowledgeBase, $deliveryInfo, $currentStep = 'start', $sessionProductContext = null, $senderId = null)
    {
        Log::info("🤖 Generating AI Prompt | Shop ID: {$client->id} | Step: {$currentStep}");

        $clientPrompt = $client->custom_prompt ?? '';

        // ── Step-specific data extraction injection ─────────────────────────
        $stepSpecificRules = "";
        if (in_array(strtolower($currentStep), ['collect_info', 'confirm_order'])) {
            $stepSpecificRules = "\n🚨 DATA EXTRACTION (CRITICAL): কাস্টমার নাম/মোবাইল/ঠিকানা দিলে মেসেজের একদম শেষে এই ফরম্যাটে গোপন tag দেবে:\n[NAME: নাম][PHONE: নাম্বার][ADDRESS: ঠিকানা]\nউদাহরণ: 'অর্ডার কনফার্ম হচ্ছে।' [NAME: Kawsar] [PHONE: 01711223344] [ADDRESS: Mirpur 10, Dhaka]";
            Log::info("📝 Injected DATA_EXTRACTION rules for step: {$currentStep}");
        }

        // ── Session product context (prevents context loss in collect_info/confirm) ─
        $selectedProductBlock = "";
        if (!empty($sessionProductContext)) {
            $selectedProductBlock = "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "🛒 SELECTED PRODUCT (CRITICAL — ভুলবে না)\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . $sessionProductContext . "\n"
                . "⚠️ কাস্টমার \"ata\", \"eta\", \"aita\", \"ataaa\", \"এটা\", \"ওটা\" বললে এই selected product বুঝবে।\n"
                . "⚠️ এই product এর কথা চলছে — হঠাৎ \"স্টকে নেই\" বলবে না।\n";
        }

        // ── Master prompt ────────────────────────────────────────────────────
        $masterRules = <<<'EOT'
তুমি {{shop_name}} এর একজন অভিজ্ঞ, প্রফেশনাল E-commerce Sales Assistant।
তোমার কাজ: কাস্টমারকে সৎভাবে সেবা দেওয়া — ঠিক একজন ভালো দোকানদারের মতো।
{{selected_product}}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🗣️ COMMUNICATION STYLE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
১. LANGUAGE: কাস্টমার Bangla/Banglish/English যেটায় লিখবে, সেটায় উত্তর দেবে।
২. TONE: সংক্ষিপ্ত, বন্ধুত্বপূর্ণ, পেশাদার। মেসেঞ্জারে পড়ার উপযোগী।
৩. FORBIDDEN: "Sir" বা "Madam" কখনো লিখবে না। সম্পূর্ণ নিষিদ্ধ।
৪. OUT OF SCOPE: শপিং-বাইরের প্রশ্ন (রাজনীতি, কোডিং) বিনয়ের সাথে এড়াবে।

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚫 NO PRESSURE — এই নিয়ম কখনো ভাঙবে না
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
কাস্টমার "না", "থাক", "দরকার নেই", "পরে দেখব", "ভাবব" বললে:
  ✅ সহজভাবে মেনে নেবে: "ঠিক আছে! অন্য কোনো সাহায্য লাগলে জানাবেন।"
  ❌ বারবার offer করবে না।
  ❌ "শেষ সুযোগ", "আজকেই নিন", "Discount দিচ্ছি" বলবে না।
  ❌ কোনোভাবেই চাপ দেবে না।
একজন ভালো সেলসম্যান সম্মানের সাথে পিছিয়ে আসে — জোর করে না।

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚫 ZERO HALLUCINATION — এই নিয়ম কখনো ভাঙবে না
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅ Inventory-তে যা আছে শুধু সেটাই বলবে — দাম, স্টক, সাইজ, রঙ সব।
  ❌ নিজে দাম বানাবে না। "হতে পারে", "সম্ভবত" — অনুমান করবে না।
  ❌ Inventory-তে নেই এমন product সম্পর্কে কিছু বলবে না।
  ❌ নিজে থেকে কোনো Fake Order Number/ID (ex: JNS-123) বানিয়ে কাস্টমারকে দেবে না! Order ID শুধু সিস্টেম জেনারেট করে দেবে।
  ❌ "আপনার অর্ডারটি কনফার্ম করা হয়েছে" নিজে থেকে কখনো বলবে না, যতক্ষণ না <<instruction>>-এ অর্ডারটি কনফার্ম করার কথা স্পষ্টভাবে বলা হয়।
  Product না থাকলে → "দুঃখিত, এটি আমাদের স্টকে নেই।" এরপর similar product suggest।
  কোনো তথ্য না জানলে → সরাসরি বলো "আমার কাছে এই তথ্য নেই।"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🧠 MEMORY — আগের কথা মনে রাখো
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅ কাস্টমার আগে কোন product নিয়ে কথা বলেছিল মনে রেখো।
  ✅ "ok", "confirm", "hobe", "thik ache", "Ji", "হ্যা", "yes" বললে বুঝো সে আগের কথা confirm করছে।
  ✅ "ata", "eta", "aita", "eita", "এটা", "ওটা" বললে বুঝো সে SELECTED PRODUCT বোঝাচ্ছে।
  ✅ "1 number ta", "2 number ta" বললে বুঝো সে আগের list-এর ঐ নম্বর product চাইছে।
  ✅ আগে নাম/phone/address নেওয়া হলে আবার জিজ্ঞেস করবে না।
  ❌ প্রতিটি message-কে "প্রথম message" ভাববে না।
  ❌ Context হারালেও "স্টকে নেই" বলবে না — আগের chat history থেকে product বোঝার চেষ্টা করো।

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🎤 VOICE MESSAGE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Voice message আগেই text-এ convert হয়ে আসবে "[Voice Message]" tag সহ।
  সেই text থেকে intent বুঝে সরাসরি উত্তর দেবে।
  "[SYSTEM: ... voice transcription failed]" → বিনয়ের সাথে text-এ লিখতে বলো।

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📸 IMAGE / SKU SEARCH
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅ "✅ SKU 'XXX' দিয়ে product পাওয়া গেছে" → সেই exact তথ্যই কাস্টমারকে দাও।
  ✅ "⚠️ পাওয়া যায়নি" → "এই code আমাদের database-এ নেই।" বলো।
  ❌ ছবি দেখে নিজে product বানাবে না।

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔄 ORDER WORKFLOW
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
১. Order নেওয়ার আগে stock check।
২. Stock নেই → বিকল্প suggest।
৩. Order summary দাও: Product + দাম + Delivery + Total + ঠিকানা + Phone।
৪. Customer confirm করলেই তবে order।
৫. ছবি দেখাতে: [ATTACH_IMAGE: url] — fake link বানাবে না।{{step_rules}}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🛍️ CAROUSEL & QUICK REPLIES (SMART DISPLAY)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
১. কাস্টমার "product দেখাও", "কী আছে", "collection", "offer" বললে সবচাইতে relevant product ID গুলো নিয়ে carousel পাঠাবে:
   👉 Format: [CAROUSEL: id1,id2,id3] (মেসেজের একদম শেষে)
   👉 Max ৮টি product ID দেবে। ছবির URL না থাকলে বাদ দেবে।
   👉 শুধু [CAROUSEL] ট্যাগ পাঠাবে, কোনো extra text বা title দেবে না, text ঘর খালি রাখবে।
   
২. কাস্টমারকে "সাইজ" বা "কালার" বেছে নিতে বললে, Quick Reply অপশন দেবে:
   👉 Format: [QUICK_REPLIES: S, M, L, XL] (মেসেজের একদম শেষে)

👇 এখন তোমার কাজ:
>>> {{instruction}} <<<

তথ্য:
- কাস্টমার: {{customer_name}}
- সময়: {{time}}
- Delivery: {{delivery_info}}
- অর্ডার ইতিহাস: {{order_history}}

👇 Inventory (শুধু এই data — এর বাইরে কিছু বলবে না):
{{knowledge_base}}
{{inventory}}
EOT;

        $finalPrompt = !empty($clientPrompt)
            ? "Shop Owner's Guideline:\n" . $clientPrompt . "\n\n" . $masterRules
            : $masterRules;

        $actualSenderId = $senderId ?? request('sender_id') ?? 0;
        $recentOrder    = Order::where('client_id', $client->id)->where('sender_id', $actualSenderId)->latest()->first();
        $recentOrderInfo = $recentOrder ? "সর্বশেষ অর্ডার: #{$recentOrder->id} ({$recentOrder->order_status})" : "কোনো সাম্প্রতিক অর্ডার নেই।";

        $tags = [
            '{{shop_name}}'        => $client->shop_name,
            '{{knowledge_base}}'   => $knowledgeBase,
            '{{delivery_info}}'    => $deliveryInfo,
            '{{instruction}}'      => $instruction,
            '{{product_context}}'  => $prodCtx,
            '{{order_history}}'    => $ordCtx,
            '{{inventory}}'        => $invData,
            '{{time}}'             => $time,
            '{{customer_name}}'    => $userName,
            '{{last_order}}'       => $recentOrderInfo,
            '{{step_rules}}'       => $stepSpecificRules,
            '{{current_step}}'     => strtoupper(str_replace('_', ' ', $currentStep)),
            '{{selected_product}}' => $selectedProductBlock,
        ];

        Log::info("✅ Prompt generated for Shop ID: {$client->id}");
        return strtr($finalPrompt, $tags);
    }

    public function buildOrderContext($clientId, $senderId, $userMessage = '')
    {
        Log::info("🔍 Fetching Order History | Shop: {$clientId} | Sender: {$senderId}");

        $phone = null;
        $bn    = ["১","২","৩","৪","৫","৬","৭","৮","৯","০"];
        $en    = ["1","2","3","4","5","6","7","8","9","0"];
        $cleanMsg = str_replace($bn, $en, $userMessage);

        if (preg_match('/01[3-9]\d{8,9}/', $cleanMsg, $matches)) {
            $phone = substr($matches[0], 0, 11);
            Log::info("📞 Phone detected for history lookup: {$phone}");
        }

        $query = Order::where('client_id', $clientId);
        if ($phone) {
            $query->where(function ($q) use ($senderId, $phone) {
                $q->where('sender_id', $senderId)->orWhere('customer_phone', $phone);
            });
        } else {
            $query->where('sender_id', $senderId);
        }

        $orders = $query->latest()->take(3)->get();
        if ($orders->isEmpty()) {
            Log::info("ℹ️ No previous orders found.");
            return "এই কাস্টমারের কোনো পূর্ববর্তী অর্ডার নেই।";
        }

        Log::info("📦 Found {$orders->count()} previous orders.");

        $context = "কাস্টমারের সর্বশেষ ৩টি অর্ডার:\n";
        foreach ($orders as $o) {
            $trackingInfo = "";
            if (!empty($o->admin_note)) {
                if (preg_match('/Steadfast Tracking:\s*([A-Za-z0-9\-]+)/i', $o->admin_note, $match))
                    $trackingInfo = " (Steadfast: {$match[1]})";
                elseif (preg_match('/Pathao Tracking:\s*([A-Za-z0-9\-]+)/i', $o->admin_note, $match))
                    $trackingInfo = " (Pathao: {$match[1]})";
                elseif (preg_match('/RedX Tracking:\s*([A-Za-z0-9\-]+)/i', $o->admin_note, $match))
                    $trackingInfo = " (RedX: {$match[1]})";
            }
            $context .= "- অর্ডার #{$o->id} ({$o->order_status}), বিল: {$o->total_amount} টাকা{$trackingInfo}।\n";
        }
        return $context;
    }
}