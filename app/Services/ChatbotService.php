<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderSession;

class ChatbotService
{
    /**
     * মেইন ফাংশন: কন্ট্রোলার থেকে রিকোয়েস্ট রিসিভ করে এবং প্রসেস করে
     */
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        try {
            // ১. সেশন লোড বা তৈরি
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['history' => []]]
            );

            // [SECURITY 1] হিউম্যান এজেন্ট মোড চেক
            if ($session->is_human_agent_active) {
                return null; // হিউম্যান মোডে থাকলে বট চুপ থাকবে
            }

            // [SECURITY 2] হেট স্পিচ ডিটেকশন
            if ($this->detectHateSpeech($userMessage)) {
                $session->update(['is_human_agent_active' => true]); // বট অফ
                $this->sendTelegramAlert($clientId, $senderId, "Hate Speech Detected: " . $userMessage); // টেলিগ্রাম অ্যালার্ট
                return "দুঃখিত, আপনার শব্দচয়ন আমাদের কমিউনিটি গাইডলাইনের বিরোধী। আমাদের একজন সিনিয়র প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।";
            }
            //$this->sendTelegramAlert($clientId, $senderId, "💬 **মেসেজ পাঠিয়েছে:**\n$userMessage");

            $client = Client::find($clientId);
            if (!$client) return "দুঃখিত, শপের কনফিগারেশনে সমস্যা হচ্ছে।";

            // ২. ইনপুট প্রসেসিং (বাংলা নম্বর ইংরেজি করা)
            $processedMessage = $this->convertToEnglishNumbers($userMessage);

            // ৩. হিস্ট্রি ম্যানেজমেন্ট
            $history = $session->customer_info['history'] ?? [];
            
            // ৪. স্মার্ট ইনভেন্টরি সার্চ (কনটেক্সট সহ)
            $productsJson = $this->getInventoryData($clientId, $processedMessage, $history);
            
            // ৫. অর্ডার কন্টেক্সট (লাস্ট ৩টি অর্ডার)
            $orderContext = $this->buildOrderContext($clientId, $senderId);

            // [FEATURE] ফোন নম্বর দিয়ে অর্ডার ট্র্যাকিং (মেসেজে নম্বর থাকলে)
            $phoneLookupInfo = $this->lookupOrderByPhone($clientId, $processedMessage);

            // মেমোরি অপ্টিমাইজেশন (লাস্ট ১৫ মেসেজ রাখা নিরাপদ)
            if (count($history) > 15) $history = array_slice($history, -15);

            // ৬. সিস্টেম প্রম্পট তৈরি (আপডেট করা হয়েছে)
            $systemPrompt = $this->buildSystemPrompt($client, $orderContext, $productsJson, $phoneLookupInfo);

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $chat) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
                $messages[] = ['role' => 'assistant', 'content' => $chat['bot']];
            }

            $userContent = $imageUrl ? [
                ['type' => 'text', 'text' => $processedMessage ?: "এই ছবিটির ব্যাপারে বলুন"],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
            ] : $processedMessage;

            $messages[] = ['role' => 'user', 'content' => $userContent];

            // ৭. AI কল (GPT-4o Mini)
            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            // ৮. সেভ এবং রিটার্ন
            if ($aiResponse) {
                $logMsg = $imageUrl ? "[Photo] " . $processedMessage : $processedMessage;
                $history[] = ['user' => $logMsg, 'bot' => $aiResponse];
                
                // [FIX] ডাটাবেসে customer_info যদি null থাকে, তবে array_merge ক্রাশ রোধ করা
                $currentInfo = is_array($session->customer_info) ? $session->customer_info : [];

                $session->update([
                    'customer_info' => array_merge($currentInfo, ['history' => $history])
                ]);
                
                return $aiResponse;
            }

            return "দুঃখিত, বর্তমানে সংযোগে সমস্যা হচ্ছে। কিছুক্ষণ পর আবার চেষ্টা করুন।";

        } catch (\Exception $e) {
            Log::error('ChatbotService Error: ' . $e->getMessage());
            $this->sendTelegramAlert($clientId, $senderId, "⚠️ **সিস্টেম এরর:** চ্যাটবট লুপে পড়েছে বা এপিআই কানেকশনে সমস্যা হচ্ছে। এরর: " . $e->getMessage());
            return "আমি দু:খিত, আমার সিনিয়র আপনার সাথে কথা বলবে";
        }
    }

    /**
     * [LOGIC] মেসেজে ফোন নম্বর থাকলে অর্ডার স্ট্যাটাস বের করা
     */
    private function lookupOrderByPhone($clientId, $message)
    {
        // ১১ ডিজিটের বিডি নম্বর প্যাটার্ন (01xxxxxxxxx)
        if (preg_match('/01[3-9]\d{8}/', $message, $matches)) {
            $phone = $matches[0];
            $order = Order::where('client_id', $clientId)
                          ->where('customer_phone', $phone)
                          ->latest()
                          ->first();

            if ($order) {
                $status = strtoupper($order->order_status);
                $note = $order->admin_note ?? $order->notes ?? ''; 
                $noteInfo = $note ? " (Admin Note: {$note})" : "";
                
                return "FOUND_ORDER: Phone {$phone} matched Order ID #{$order->id}. Status: {$status} {$noteInfo}. Total: {$order->total_amount} Tk.";
            } else {
                return "NO_ORDER_FOUND: Phone {$phone} provided but no order exists.";
            }
        }
        return null;
    }

    /**
     * [LOGIC] স্মার্ট ইনভেন্টরি সার্চ (কনটেক্সট মেমোরি সহ)
     */
    private function getInventoryData($clientId, $userMessage, $history)
    {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2);
        
        // কনটেক্সট চেক
        $genericWords = ['price', 'details', 'dam', 'koto', 'eta', 'atar', 'size', 'color', 'picture', 'img', 'kemon', 'product', 'available', 'stock', 'kinbo', 'order', 'chai', 'lagbe', 'nibo'];
        $isFollowUp = Str::contains(strtolower($userMessage), $genericWords) || count($keywords) < 2;

        if ($isFollowUp && !empty($history)) {
            $lastUserMsg = end($history)['user'] ?? '';
            $lastKeywords = array_filter(explode(' ', $lastUserMsg), fn($w) => mb_strlen($w) > 3);
            $keywords = array_unique(array_merge($keywords, $lastKeywords));
        }

        if (!empty($keywords)) {
            $query->where(function($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('name', 'like', "%{$word}%")
                      ->orWhere('colors', 'like', "%{$word}%")
                      ->orWhere('sku', 'like', "%{$word}%");
                }
            });
        }

        $products = $query->latest()->limit(5)->get();

        if ($products->isEmpty()) {
            $products = Product::where('client_id', $clientId)
                ->where('stock_status', 'in_stock')
                ->latest()->limit(5)->get();
        }

        return $products->map(function ($p) {
            $colors = is_string($p->colors) ? (json_decode($p->colors, true) ?: $p->colors) : $p->colors;
            $colorsStr = is_array($colors) ? implode(', ', $colors) : ((string)$colors ?: 'N/A');

            $sizes = is_string($p->sizes) ? (json_decode($p->sizes, true) ?: $p->sizes) : $p->sizes;
            $sizesStr = is_array($sizes) ? implode(', ', $sizes) : ((string)$sizes ?: 'N/A');

            $desc = strip_tags(str_replace(["<br>", "</p>", "&nbsp;", "\n"], " ", $p->description));

            return [
                'ID' => $p->id,
                'Name' => $p->name,
                'Sale_Price' => (int)$p->sale_price . ' Tk',
                'Regular_Price' => $p->regular_price ? (int)$p->regular_price . ' Tk' : null,
                'Stock' => $p->stock_quantity > 0 ? 'Available' : 'Out of Stock',
                'Colors' => $colorsStr, 
                'Sizes' => $sizesStr,
                'Details' => Str::limit($desc, 200),
                'Image_URL' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null,
            ];
        })->toJson();
    }

    /**
     * [LOGIC] অর্ডার কনটেক্সট বিল্ডার
     */
    private function buildOrderContext($clientId, $senderId)
    {
        $orders = Order::where('client_id', $clientId)
                        ->where('sender_id', $senderId)
                        ->latest()
                        ->take(3)
                        ->get();

        if ($orders->isEmpty()) return "NO_ORDER_HISTORY (New User).";
        
        $context = "MESSENGER HISTORY:\n";
        foreach ($orders as $order) {
            $status = strtoupper($order->order_status);
            $note = $order->admin_note ?? $order->notes ?? '';
            $noteInfo = $note ? " | Note: {$note}" : "";
            $context .= "- Order #{$order->id}: Status [{$status}], Phone: {$order->customer_phone}{$noteInfo}\n";
        }
        
        return $context;
    }

    /**
     * [CORE] সিস্টেম প্রম্পট (WebhookController এর সাথে হ্যান্ডশেক)
     */
 
private function buildSystemPrompt($client, $orderContext, $productsJson, $phoneLookupInfo)
    {
        $delivery = "ঢাকার ভিতরে " . ($client->delivery_charge_inside ?? 80) . " টাকা, বাইরে " . ($client->delivery_charge_outside ?? 150) . " টাকা।";
        $persona = $client->custom_prompt ?? "তুমি একজন স্মার্ট শপ অ্যাসিস্ট্যান্ট।";

        return <<<EOT
{$persona}
তোমার প্রধান কাজ হলো কাস্টমারের অর্ডার কনফার্ম করা। নিচের লজিকগুলো **রোবটের মতো** মেনে চলো:

[১. ইনভেন্টরি চেক (সবচেয়ে গুরুত্বপূর্ণ)]:
- কাস্টমার কোনো প্রোডাক্ট চাইলে সাথে সাথে নিচের [INVENTORY] ডাটা চেক করো।
- **RULE 1:** যদি প্রোডাক্টের 'Colors' বা 'Sizes' এর ভ্যালু "N/A", "null", বা ফাঁকা থাকে, তবে **খবরদার! কাস্টমারকে কালার বা সাইজ নিয়ে কোনো প্রশ্ন করবে না।** - **RULE 2:** "N/A" থাকলে ভাববে এই প্রোডাক্টের একটাই ভেরিয়েশন। সরাসরি কাস্টমারের নাম, ফোন নম্বর এবং ঠিকানা জিজ্ঞেস করো।
- **RULE 3:** শুধুমাত্র যদি নির্দিষ্ট অপশন (যেমন: Red, Blue, L, XL) লেখা থাকে, কেবল তখনই কাস্টমারকে অপশনগুলো থেকে বেছে নিতে বলো।

[২. কাস্টমার ডাটা সংগ্রহ]:
- কাস্টমারের **নিজস্ব নাম** জিজ্ঞেস করো। (সতর্কতা: প্রোডাক্টের নামকে কাস্টমারের নাম হিসেবে ব্যবহার করবে না)।
- ফোন নম্বর এবং পূর্ণ ঠিকানা নাও।
- সব তথ্য পেলে অর্ডার কনফার্ম করো।

[৩. আউটপুট ফরম্যাট (JSON Tags)]:
- অর্ডার কনফার্ম হলে: [ORDER_DATA: {"product_id":ID, "name":"Customer Name", "phone":"01xxxxxxxxx", "address":"...", "is_dhaka":true/false, "note":"..."}]
- অর্ডারের পরে কোনো অনুরোধ আসলে: [ADD_NOTE: {"note":"..."}]
- অর্ডার বাতিল হলে: [CANCEL_ORDER: {"reason":"..."}]

[কঠোর বিধিনিষেধ]:
- ইনভেন্টরিতে কালার না থাকলে নিজে থেকে কালার বানাবে না বা জিজ্ঞেস করবে না।
- Markdown (স্টার/ড্যাশ) ব্যবহার করবে না।

[DATA SOURCES]:
[DELIVERY]: {$delivery}
[INVENTORY]: {$productsJson}
[CUSTOMER HISTORY]: {$orderContext}
[PHONE_LOOKUP_DATA]: {$phoneLookupInfo}
EOT;
    }

    private function detectHateSpeech($message)
    {
        if (!$message) return false;
        $badWords = ['fucker', 'idiot', 'stupid', 'bastard', 'scam', 'cheat', 'shala', 'kutta', 'harami', 'shuor', 'magi', 'khananki', 'chuda', 'bal', 'boka', 'faltu', 'butpar', 'chor', 'sala', 'khankir', 'madarchod', 'tor mare', 'fraud'];
        $lowerMsg = strtolower($message);
        foreach ($badWords as $word) {
            if (str_contains($lowerMsg, $word)) return true;
        }
        return false;
    }

 
    // ChatbotService.php এর ভেতর
// এটি PUBLIC যাতে WebhookController থেকেও কল করা যায়
public function sendTelegramAlert($clientId, $senderId, $message)
{
    try {

    // ChatbotService.php এর ভেতরে
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        //$token = env('TELEGRAM_BOT_TOKEN');
        //$chatId = env('TELEGRAM_CHAT_ID');

        if (!$token || !$chatId) {
            Log::warning("Telegram Credentials missing in .env");
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => "🔔 **নতুন আপডেট**\nUser: {$senderId}\n{$message}",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    ['text' => '⏸️ Stop AI', 'callback_data' => "pause_ai_{$senderId}"],
                    ['text' => '▶️ Resume AI', 'callback_data' => "resume_ai_{$senderId}"]
                ]]
            ])
        ];

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

        if (!$response->successful()) {
            Log::error("Telegram API Error: " . $response->body());
        }
    } catch (\Exception $e) {
        Log::error("Telegram Notification Error: " . $e->getMessage());
    }
}


    private function convertToEnglishNumbers($str) {
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        return str_replace($bn, $en, $str);
    }

   


    private function callLlmChain($messages, $imageUrl)
        {
            try {
                $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

                if (empty($apiKey)) {
                    Log::error("OpenAI API Key missing! .env ফাইলে API কী আছে কিনা এবং VPS-এ ক্যাশ ক্লিয়ার করা হয়েছে কিনা চেক করুন।");
                    return null;
                }

                $response = Http::withToken($apiKey)
                    ->timeout(30) // VPS-এর জন্য সময় বাড়িয়ে ৩০ সেকেন্ড করা হলো
                    ->retry(2, 500) // সাময়িক নেটওয়ার্ক সমস্যার জন্য ২ বার ট্রাই করবে
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
                        'messages' => $messages,
                        'temperature' => 0.3,
                    ]);

                if ($response->successful()) {
                    return $response->json()['choices'][0]['message']['content'] ?? null;
                }

                Log::error("OpenAI API Error: " . $response->status() . " - " . $response->body());
                return null;

            } catch (\Exception $e) {
                Log::error("LLM Call Exception: " . $e->getMessage());
                return null;
            }
        }


}