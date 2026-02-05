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
    public function getAiResponse($userMessage, $clientId, $senderId, $imageUrl = null)
    {
        try {
            // ১. সেশন লোড
            $session = OrderSession::firstOrCreate(
                ['sender_id' => $senderId],
                ['client_id' => $clientId, 'customer_info' => ['history' => []]]
            );

            $client = Client::find($clientId);
            if (!$client) return "Shop currently unavailable.";

            // ২. ইনভেন্টরি ডাটা (JSON ফিক্স করা হয়েছে)
            $productsJson = $this->getInventoryData($clientId, $userMessage);
            
            // ৩. অর্ডার কন্টেক্সট
            $orderContext = $this->buildOrderContext($clientId, $senderId);

            // ৪. হিস্ট্রি (মেমোরি)
            $history = $session->customer_info['history'] ?? [];
            if (count($history) > 10) $history = array_slice($history, -10);

            // ৫. সিস্টেম প্রম্পট (ক্যান্সেলেশন ভেরিফিকেশন সহ)
            $systemPrompt = $this->buildSystemPrompt($client, $orderContext, $productsJson);

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $chat) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
                $messages[] = ['role' => 'assistant', 'content' => $chat['bot']];
            }

            // ইউজারের মেসেজ বা ছবি
            $userContent = $imageUrl ? [
                ['type' => 'text', 'text' => $userMessage ?: "Image details"],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
            ] : $userMessage;

            $messages[] = ['role' => 'user', 'content' => $userContent];

            // ৬. AI কল
            $aiResponse = $this->callLlmChain($messages, $imageUrl);

            // ৭. সেভ এবং রিটার্ন
            if ($aiResponse) {
                $logMsg = $imageUrl ? "[Photo] " . $userMessage : $userMessage;
                $history[] = ['user' => $logMsg, 'bot' => $aiResponse];
                
                $session->update(['customer_info' => array_merge($session->customer_info, ['history' => $history])]);
                return $aiResponse;
            }

            return "দুঃখিত, কানেকশন সমস্যা।";

        } catch (\Exception $e) {
            Log::error('ChatbotService Error: ' . $e->getMessage());
            return "সাময়িক ত্রুটি।";
        }
    }

    /**
     * [FIXED] Colors, Sizes, Description decoding
     */
    private function getInventoryData($clientId, $userMessage)
    {
        $query = Product::where('client_id', $clientId)->where('stock_status', 'in_stock');

        $isGeneric = Str::contains(strtolower($userMessage), ['pic', 'img', 'image', 'details', 'price', 'দাম', 'size', 'color', 'list', 'show', 'stock', 'koto']);
        $keywords = array_filter(explode(' ', $userMessage), fn($w) => mb_strlen($w) > 2);

        if ($isGeneric && count($keywords) < 2) {
            $products = $query->latest()->limit(8)->get();
        } else {
            if (!empty($keywords)) {
                $query->where(function($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('name', 'like', "%{$word}%")
                          ->orWhere('sku', 'like', "%{$word}%");
                    }
                });
            }
            $products = $query->limit(5)->get();
        }

        if ($products->isEmpty()) {
            return Product::where('client_id', $clientId)
                ->where('stock_status', 'in_stock')
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn($p) => ["Note" => "Searched item not found. Suggest available item:", "Name" => $p->name])
                ->toJson();
        }

        return $products->map(function ($p) {
            // [FIX 1] Handle Colors (JSON Array to String)
            $colors = $p->colors;
            if (is_string($colors)) {
                $decoded = json_decode($colors, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $colors = $decoded;
                }
            }
            $colorsStr = is_array($colors) ? implode(', ', $colors) : ((string)$colors ?: 'N/A');

            // [FIX 2] Handle Sizes (JSON Array to String)
            $sizes = $p->sizes;
            if (is_string($sizes)) {
                $decoded = json_decode($sizes, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $sizes = $decoded;
                }
            }
            $sizesStr = is_array($sizes) ? implode(', ', $sizes) : ((string)$sizes ?: 'N/A');

            // [FIX 3] Clean HTML Description
            $desc = strip_tags(str_replace(["<br>", "</p>"], " ", $p->description));
            $desc = preg_replace('/\s+/', ' ', $desc);

            return [
                'ID' => $p->id,
                'Name' => $p->name,
                'Sale_Price' => (int)$p->sale_price . ' Tk',
                'Regular_Price' => $p->regular_price ? (int)$p->regular_price . ' Tk' : 'Same',
                'Stock' => $p->stock_quantity . ' pcs',
                'Colors' => $colorsStr, 
                'Sizes' => $sizesStr,
                'Material' => $p->material ?? 'Standard',
                'Brand' => $p->brand ?? 'Generic',
                'Image_URL' => $p->thumbnail ? asset('storage/' . $p->thumbnail) : null,
                'Details' => Str::limit($desc, 200)
            ];
        })->toJson();
    }

    private function buildOrderContext($clientId, $senderId)
    {
        $order = Order::where('client_id', $clientId)->where('sender_id', $senderId)->latest()->first();
        if (!$order) return "New Customer";
        
        $status = strtoupper($order->order_status);
        $modifiable = in_array($order->order_status, ['pending', 'processing']) ? "YES" : "NO";
        
        return "Last Order ID: #{$order->id} ({$status}). Modifiable: {$modifiable}.";
    }

    private function buildSystemPrompt($client, $orderContext, $productsJson)
    {
        $delivery = "Inside Dhaka: " . ($client->delivery_charge_inside ?? 80) . " Tk, Outside: " . ($client->delivery_charge_outside ?? 150) . " Tk";
        $persona = $client->custom_prompt ?? "You are a friendly shop assistant.";

        return <<<EOT
{$persona}

[Delivery Charges]: {$delivery}

[INSTRUCTIONS - HUMAN LIKE]:
1. **Accurate Data:** Use [INVENTORY] strictly.
   - **Price:** Tell 'Sale_Price'. Mention 'Regular_Price' if discounted.
   - **Colors/Sizes:** Read fields properly. If 'N/A', say "Not specified".

2. **Image:** If asked, provide ONLY the 'Image_URL' link at the end. No markdown.

3. **No Markdown:** Speak naturally. Avoid bold (*) or bullets (-).

4. **Order Process:** - Confirm Name, Phone, Address first.
   - Then output: [ORDER_DATA: {"product_id":ID, "name":"...", "phone":"...", "address":"...", "is_dhaka":true/false, "note":"..."}]

5. **Cancellation (VERIFICATION REQUIRED):**
   - If user says "Cancel Order", **DO NOT** cancel immediately.
   - **ASK:** "অর্ডারটি কেন বাতিল করতে চাচ্ছেন?" (Why cancel?).
   - After user gives a reason, ONLY THEN output: [CANCEL_ORDER: {"reason": "User Reason"}]

6. **Actions:**
   - Note: [ADD_NOTE: {"note": "..."}]

[INVENTORY]: {$productsJson}
[CUSTOMER STATUS]: {$orderContext}
EOT;
    }

    private function callLlmChain($messages, $imageUrl)
    {
        try {
            $response = Http::withToken(config('services.openai.api_key') ?? env('OPENAI_API_KEY'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $imageUrl ? 'gpt-4o' : 'gpt-4o-mini',
                    'messages' => $messages,
                    'temperature' => 0.3, 
                ]);

            return $response->json()['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}