<?php

/**
 * AI Commerce Bot — Laravel SDK
 * 
 * Installation:
 *   1. Copy this file to your Laravel project: app/Services/AiCommerceBot.php
 *   2. Add to .env:
 *        AICB_API_KEY=your_api_key_here
 *        AICB_BASE_URL=https://your-saas-domain.com
 * 
 * Usage: See examples at the bottom of this file.
 */

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AiCommerceBot
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->apiKey  = $apiKey  ?? config('services.aicb.api_key',  env('AICB_API_KEY', ''));
        $this->baseUrl = rtrim($baseUrl ?? config('services.aicb.base_url', env('AICB_BASE_URL', '')), '/');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. TEST CONNECTION
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Test if the API key is valid.
     * Returns shop info on success.
     *
     * @return array{success: bool, shop: string, plan: string, products: int}
     */
    public function verify(): array
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/api/connector/verify");

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. SYNC PRODUCTS (Bulk)
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Push an array of products to the AI system.
     * Products must have at least 'name' and 'price'.
     *
     * Example product:
     * [
     *   'sku'         => 'SKU-001',
     *   'name'        => 'Cotton T-Shirt',
     *   'price'       => 599,
     *   'stock'       => 50,
     *   'category'    => 'Clothing',
     *   'image_url'   => 'https://...',
     *   'description' => '...',
     *   'colors'      => ['Red', 'Blue'],
     *   'sizes'       => ['S', 'M', 'L'],
     * ]
     *
     * @param  array $products  Array of product arrays
     * @return array{success: bool, synced: int, failed: int}
     */
    public function syncProducts(array $products): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(60)
            ->post("{$this->baseUrl}/api/connector/sync-products", [
                'products' => $products,
            ]);

        return $response->json();
    }

    /**
     * Sync a single product.
     */
    public function syncProduct(array $product): array
    {
        return $this->syncProducts([$product]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. AI CHAT (for server-side / API usage)
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Send a message to the AI and get a reply.
     * sessionId should be unique per visitor/user.
     *
     * @return array{reply: string}
     */
    public function chat(string $message, string $sessionId): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/api/v1/chat/widget", [
                'message'    => $message,
                'session_id' => $sessionId,
            ]);

        return $response->json();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. GET JS SNIPPET (for blade templates)
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Get the JS snippet string to embed in your HTML.
     * Cache it for 1 hour since it rarely changes.
     *
     * @return string  HTML snippet to paste before </body>
     */
    public function getEmbedSnippet(): string
    {
        return Cache::remember('aicb_snippet_' . md5($this->apiKey), 3600, function () {
            $r = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/connector/js-snippet");
            return $r->json('snippet', '');
        });
    }

    /**
     * Get the embeddable script tag — ready to use in Blade.
     *
     * Usage in Blade:
     *   {!! AiCommerceBot::embedScript() !!}
     *
     * @return \Illuminate\Support\HtmlString
     */
    public static function embedScript(?string $position = 'bottom-right'): string
    {
        $bot     = new static();
        $apiKey  = $bot->apiKey;
        $baseUrl = $bot->baseUrl;
        $shopName = config('app.name');

        return <<<HTML
<!-- AI Commerce Bot Widget -->
<script>
(function() {
  window.AICB_CONFIG = {
    apiKey:       "{$apiKey}",
    shopName:     "{$shopName}",
    baseUrl:      "{$baseUrl}",
    position:     "{$position}",
    primaryColor: "#4f46e5"
  };
  var s = document.createElement('script');
  s.src = "{$baseUrl}/js/chatbot-widget.js";
  s.async = true;
  document.head.appendChild(s);
})();
</script>
HTML;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────
    private function headers(): array
    {
        return [
            'X-Api-Key'    => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }
}

/*
|─────────────────────────────────────────────────────────
| USAGE EXAMPLES
|─────────────────────────────────────────────────────────
|
| // 1. Test connection
| $bot = new AiCommerceBot();
| $info = $bot->verify();
| // ['success' => true, 'shop' => 'My Shop', 'plan' => 'Pro', 'products' => 45]
|
| // 2. Sync all products from your DB
| use App\Models\Product;
| $products = Product::all()->map(fn($p) => [
|     'sku'       => $p->id,
|     'name'      => $p->name,
|     'price'     => $p->price,
|     'stock'     => $p->stock,
|     'category'  => $p->category->name ?? '',
|     'image_url' => $p->image,
|     'description' => $p->description,
| ])->toArray();
| $result = $bot->syncProducts($products);
|
| // 3. In your Blade layout (app.blade.php):
| // Add before </body>:
| {!! App\Services\AiCommerceBot::embedScript() !!}
|
| // 4. Server-side chat (e.g. in an API controller):
| $reply = $bot->chat($request->message, 'user_' . auth()->id());
| return response()->json(['reply' => $reply['reply']]);
|
*/
