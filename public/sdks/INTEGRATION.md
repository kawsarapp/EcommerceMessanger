# 🤖 AI Commerce Bot — Integration Guide

## যেকোনো website থেকে কিভাবে connect করবেন

---

## ✅ Step 1 — API Key নিন

1. আপনার **AI Commerce Bot dashboard**-এ login করুন
2. **Settings → Integrations** -এ যান
3. **API Key** copy করুন
4. **Dashboard URL** হবে: `https://your-saas-domain.com`

---

## 🔌 SDK Download

| Framework | ফাইল |
|-----------|------|
| Laravel (PHP) | `GET /api/v1/wordpress-plugin/download` (WordPress plugin) বা নিচের SDK |
| Node.js / Express | SDK ফাইল নিচে |
| Next.js (JS) | SDK ফাইল নিচে |
| Next.js (TypeScript) | TypeScript SDK নিচে |
| Vanilla HTML | শুধু JS snippet paste করুন |

---

## 🟡 Option A — যেকোনো Website-এ (Vanilla HTML / Static Site)

**সবচেয়ে সহজ পদ্ধতি।** শুধু এই snippet আপনার সাইটের `</body>` এর আগে paste করুন:

```html
<!-- AI Commerce Bot Widget -->
<script>
(function() {
  window.AICB_CONFIG = {
    apiKey:       "YOUR_API_KEY",
    shopName:     "আমার শপ",
    baseUrl:      "https://your-saas-domain.com",
    position:     "bottom-right",          // বা "bottom-left"
    primaryColor: "#4f46e5",               // আপনার brand color
    greeting:     "আমি আপনাকে সাহায্য করতে পারি! 👋"
  };
  var s = document.createElement('script');
  s.src = "https://your-saas-domain.com/js/chatbot-widget.js";
  s.async = true;
  document.head.appendChild(s);
})();
</script>
```

> 💡 **Tip:** Dashboard থেকে এই snippet auto-generate করে copy করতে পারবেন।

---

## 🟢 Option B — Laravel Project

### 1. SDK ফাইল কপি করুন

```bash
# SDK ফাইল আপনার Laravel project-এ রাখুন:
cp AiCommerceBot.php app/Services/AiCommerceBot.php
```

### 2. `.env` এ যোগ করুন

```env
AICB_API_KEY=your_api_key_here
AICB_BASE_URL=https://your-saas-domain.com
```

### 3. `config/services.php` এ যোগ করুন (optional)

```php
'aicb' => [
    'api_key'  => env('AICB_API_KEY'),
    'base_url' => env('AICB_BASE_URL'),
],
```

### 4. ব্যবহার

```php
use App\Services\AiCommerceBot;

// ── Connection test
$bot  = new AiCommerceBot();
$info = $bot->verify();
// ['success' => true, 'shop' => 'My Shop', 'products' => 45]

// ── Product sync (আপনার DB থেকে)
$products = Product::all()->map(fn($p) => [
    'sku'         => $p->id,
    'name'        => $p->name,
    'price'       => $p->price,
    'stock'       => $p->stock,
    'category'    => $p->category->name,
    'image_url'   => $p->image,
    'description' => $p->description,
])->toArray();

$result = $bot->syncProducts($products);
// ['success' => true, 'synced' => 45]

// ── Server-side AI chat
$reply = $bot->chat($request->message, 'user_' . auth()->id());
return response()->json(['reply' => $reply['reply']]);
```

### 5. Blade layout-এ chatbot widget যোগ করুন

```blade
{{-- resources/views/layouts/app.blade.php --}}
{{-- </body> এর আগে: --}}
{!! App\Services\AiCommerceBot::embedScript() !!}
```

### 6. Laravel Artisan command দিয়ে auto-sync setup

```php
// app/Console/Commands/SyncProductsAicb.php
class SyncProductsAicb extends Command {
    protected $signature = 'aicb:sync';
    public function handle() {
        $result = (new AiCommerceBot())->syncProducts(...);
        $this->info("Synced: " . $result['synced']);
    }
}

// Cron এ যোগ করুন (app/Console/Kernel.php):
$schedule->command('aicb:sync')->hourly();
```

---

## 🔵 Option C — Node.js / Express

### 1. SDK ফাইল রাখুন

```bash
cp aiCommerceBot.js lib/aiCommerceBot.js
```

### 2. `.env`

```env
AICB_API_KEY=your_api_key_here
AICB_BASE_URL=https://your-saas-domain.com
```

### 3. ব্যবহার

```js
const AiCommerceBot = require('./lib/aiCommerceBot');

// ── Server start-এ connection test
app.listen(3000, async () => {
    const info = await AiCommerceBot.verify();
    console.log('✅ AI Commerce Bot connected:', info.shop);
});

// ── Chat API endpoint (Frontend আপনার এই route call করবে)
app.post('/api/chat', async (req, res) => {
    const { message } = req.body;
    const sessionId = req.session.id || req.ip;
    const { reply } = await AiCommerceBot.chat(message, sessionId);
    res.json({ reply });
});

// ── Product sync (DB থেকে)
app.post('/admin/sync', async (req, res) => {
    const products = await db.select('SELECT * FROM products WHERE active = 1');
    const result   = await AiCommerceBot.syncProducts(
        products.map(p => ({ sku: p.id, name: p.name, price: p.price, stock: p.qty }))
    );
    res.json(result);
});
```

---

## 🔵 Option D — Next.js

### JS (App Router)

```jsx
// app/api/chat/route.js
import { chat } from '@/lib/aiCommerceBot';

export async function POST(req) {
    const { message, sessionId } = await req.json();
    const data = await chat(message, sessionId);
    return Response.json(data);
}
```

```jsx
// app/layout.jsx — Chatbot widget সব পেজে
import { getEmbedSnippet } from '@/lib/aiCommerceBot';

export default function RootLayout({ children }) {
    const snippet = getEmbedSnippet({ shopName: 'My Store', primaryColor: '#e11d48' });
    return (
        <html>
            <body>
                {children}
                <div dangerouslySetInnerHTML={{ __html: snippet }} />
            </body>
        </html>
    );
}
```

### TypeScript (App Router)

```tsx
// app/api/chat/route.ts
import { chat, AicbChatResponse } from '@/lib/aiCommerceBot';

export async function POST(req: Request): Promise<Response> {
    const { message, sessionId } = await req.json();
    const data: AicbChatResponse = await chat(message, sessionId);
    return Response.json(data);
}
```

```tsx
// app/layout.tsx
import { getEmbedSnippet } from '@/lib/aiCommerceBot';

export default function RootLayout({ children }: { children: React.ReactNode }) {
    return (
        <html lang="bn">
            <body>
                {children}
                <div dangerouslySetInnerHTML={{
                    __html: getEmbedSnippet({
                        shopName:     process.env.NEXT_PUBLIC_SHOP_NAME ?? 'My Shop',
                        primaryColor: '#4f46e5',
                        liveChat:     true,
                    })
                }} />
            </body>
        </html>
    );
}
```

---

## ⚡ Option E — React / Vue / Any SPA (Client-side only)

**Frontend-এ সরাসরি JS snippet যোগ করুন:**

```jsx
// React: src/index.jsx বা main.tsx
useEffect(() => {
    window.AICB_CONFIG = {
        apiKey:       import.meta.env.VITE_AICB_API_KEY,
        shopName:     'My Shop',
        baseUrl:      import.meta.env.VITE_AICB_BASE_URL,
        primaryColor: '#4f46e5',
        position:     'bottom-right',
    };
    const s = document.createElement('script');
    s.src = `${import.meta.env.VITE_AICB_BASE_URL}/js/chatbot-widget.js`;
    s.async = true;
    document.head.appendChild(s);
}, []);
```

```js
// Vue: src/main.js
import { createApp } from 'vue';

window.AICB_CONFIG = {
    apiKey:    import.meta.env.VITE_AICB_API_KEY,
    baseUrl:   import.meta.env.VITE_AICB_BASE_URL,
    shopName:  'My Shop',
};
const s = document.createElement('script');
s.src = `${import.meta.env.VITE_AICB_BASE_URL}/js/chatbot-widget.js`;
document.head.appendChild(s);
```

---

## 📡 REST API Reference (সব framework-এ সরাসরি HTTP call করতে পারবেন)

Base URL: `https://your-saas-domain.com`
Auth Header: `X-Api-Key: YOUR_API_KEY`

| Method | Endpoint | কাজ | Body |
|--------|----------|-----|------|
| `GET`  | `/api/connector/verify` | Connection test | — |
| `POST` | `/api/connector/sync-products` | Products sync করুন | `{ products: [...] }` |
| `GET`  | `/api/connector/js-snippet?api_key=KEY` | JS snippet নিন | — |
| `POST` | `/api/v1/chat/widget` | AI-কে message পাঠান | `{ message, session_id }` |

### Product object format

```json
{
    "sku":         "SKU-001",
    "name":        "Cotton T-Shirt",
    "price":       599,
    "sale_price":  499,
    "stock":       50,
    "category":    "Clothing",
    "description": "100% pure cotton...",
    "image_url":   "https://example.com/img.jpg",
    "gallery":     ["https://...", "https://..."],
    "colors":      ["Red", "Blue", "Green"],
    "sizes":       ["S", "M", "L", "XL"],
    "video_url":   "https://youtube.com/watch?v=...",
    "tags":        "cotton, summer, casual"
}
```

### Chat request/response

```json
// POST /api/v1/chat/widget
// Request:
{ "message": "এই শার্টটার দাম কত?", "session_id": "visitor_abc123" }

// Response:
{ "reply": "এই শার্টটার দাম ৫৯৯ টাকা। সেল চলছে, এখন মাত্র ৪৯৯ টাকায় পাবেন! 🎉" }
```

---

## 🎁 Connect করার পর কী কী পাবেন

| Feature | Details |
|---------|---------|
| 🤖 **AI Chatbot** | আপনার website-এ floating AI chatbot — Bangla, English, Banglish সব ভাষায় কাজ করে |
| 📦 **Product AI Search** | Customer যেকোনো product খুঁজলে AI আপনার real database থেকে উত্তর দেবে |
| 🛒 **Order Assistant** | AI customer-এর order নিয়ে আপনাকে জানাবে, confirm করে সব কিছু |
| 📸 **Image Search** | Customer product-এর ছবি পাঠালে AI SKU match করবে |
| 🎙️ **Voice Note** | Customer voice note পাঠালে AI শুনে reply করবে |
| 🟢 **Live Chat** | Customer চাইলে human agent-এর সাথে connect হতে পারবে |
| 📊 **Analytics** | Dashboard থেকে সব conversation ও order দেখতে পারবেন |
| 🔔 **Notifications** | নতুন order হলে আপনাকে Telegram/Email-এ alert যাবে |

---

## 🚨 CORS Note

**সব API endpoints-এ CORS allow করা আছে** (`Access-Control-Allow-Origin: *`), তাই যেকোনো domain থেকে call করা যাবে।

---

*আরো সাহায্যের জন্য dashboard-এ support ticket open করুন।*
