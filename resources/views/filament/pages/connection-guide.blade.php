<x-filament-panels::page>
<div class="space-y-6" x-data="{ activeTab: 'wordpress' }">

{{-- ========== HERO HEADER ========== --}}
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-8 text-white shadow-xl">
    <div class="flex items-start gap-5">
        <div class="text-5xl">🔌</div>
        <div>
            <h1 class="text-2xl font-bold mb-1">Integration & API Documentation</h1>
            <p class="text-indigo-200 text-sm">Connect any website, app, or platform to your AI Commerce Bot in minutes. Your chatbot will read your products, create orders, and reply to customers automatically.</p>
            @if($apiKey !== 'YOUR_API_KEY')
            <div class="mt-4 flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3">
                <span class="text-xs font-semibold text-indigo-200 uppercase tracking-wider">Your API Key</span>
                <code class="text-white font-mono text-sm flex-1 truncate" id="hero-key">{{ $apiKey }}</code>
                <button onclick="navigator.clipboard.writeText('{{ $apiKey }}'); this.textContent='✅ Copied!'; setTimeout(()=>this.textContent='📋 Copy', 2000)"
                        class="bg-white text-indigo-700 text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition">📋 Copy</button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ========== HOW IT WORKS ========== --}}
<div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
    <h2 class="text-lg font-bold text-gray-800 mb-4">⚡ How It Works — 3 Simple Steps</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
            <div class="text-3xl mb-2">1️⃣</div>
            <h3 class="font-bold text-indigo-800">Connect Your Store</h3>
            <p class="text-sm text-indigo-600 mt-1">Use our WordPress plugin, npm package, or REST API to push your product data to our system using your API Key.</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
            <div class="text-3xl mb-2">2️⃣</div>
            <h3 class="font-bold text-purple-800">Connect Your Channel</h3>
            <p class="text-sm text-purple-600 mt-1">Link your Facebook Page or WhatsApp number. The AI bot will now listen on your channels.</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
            <div class="text-3xl mb-2">3️⃣</div>
            <h3 class="font-bold text-green-800">It Works Automatically</h3>
            <p class="text-sm text-green-600 mt-1">Customers ask questions → AI reads your DB → Replies instantly. Orders are created automatically.</p>
        </div>
    </div>
</div>

{{-- ========== TABS ========== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    {{-- Tab Bar --}}
    <div class="flex overflow-x-auto border-b border-gray-200 bg-gray-50">
        @foreach([
            ['id' => 'wordpress',    'icon' => '🔷', 'label' => 'WordPress'],
            ['id' => 'laravel',      'icon' => '🔴', 'label' => 'Laravel / PHP'],
            ['id' => 'nodejs',       'icon' => '🟢', 'label' => 'Node.js'],
            ['id' => 'python',       'icon' => '🐍', 'label' => 'Python'],
            ['id' => 'anysite',      'icon' => '🌐', 'label' => 'Any HTML Site'],
            ['id' => 'facebook',     'icon' => '📘', 'label' => 'Facebook Page'],
            ['id' => 'whatsapp',     'icon' => '💬', 'label' => 'WhatsApp'],
            ['id' => 'api',          'icon' => '📡', 'label' => 'REST API Ref'],
        ] as $tab)
        <button
            @click="activeTab = '{{ $tab['id'] }}'"
            :class="activeTab === '{{ $tab['id'] }}' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-white font-semibold' : 'text-gray-500 hover:text-gray-700'"
            class="px-5 py-3.5 text-sm whitespace-nowrap transition flex items-center gap-1.5">
            {{ $tab['icon'] }} {{ $tab['label'] }}
        </button>
        @endforeach
    </div>

    <div class="p-6">

    {{-- ====== WORDPRESS TAB ====== --}}
    <div x-show="activeTab === 'wordpress'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">🔷</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">WordPress / WooCommerce</h2>
                <p class="text-gray-500 text-sm">Install our plugin — products sync automatically to the AI bot.</p>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <p class="font-semibold text-blue-800 mb-2">📦 Step 1 — Install the Plugin</p>
            <ol class="list-decimal ml-4 text-sm text-blue-700 space-y-1">
                <li>Download the plugin: <a href="{{ asset('plugins/ecommerce-messenger-ai.zip') }}" download class="font-bold underline">ecommerce-messenger-ai.zip</a></li>
                <li>Go to your WordPress Admin → <strong>Plugins → Add New → Upload Plugin</strong></li>
                <li>Upload the zip file and click <strong>Install Now</strong> then <strong>Activate</strong></li>
            </ol>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">⚙️ Step 2 — Configure in WordPress</p>
            <p class="text-sm text-gray-600 mb-2">In your WP Admin, click <strong>"AI Commerce Bot"</strong> in the sidebar and enter your API Key:</p>
            <div class="bg-gray-900 rounded-lg p-3">
                <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap">Seller API Key: {{ $apiKey }}</pre>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="font-semibold text-green-800 mb-2">🔗 Step 3 — Add Your Store URL to Dashboard</p>
            <p class="text-sm text-green-700 mb-2">After activating the plugin, copy the endpoint URL shown in WP admin and paste it in:<br>
            <strong>This Dashboard → Clients → Edit → External Product API URL</strong></p>
            <div class="bg-gray-900 rounded-lg p-3">
                <pre class="text-green-400 text-sm font-mono">https://your-wordpress-site.com/wp-json/ai-commerce-bot/v1/products</pre>
            </div>
        </div>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-r-xl text-sm text-yellow-800">
            ✅ Done! The AI bot will now pull live product data, stock, and prices from your WooCommerce store in real-time.
        </div>
    </div>

    {{-- ====== LARAVEL TAB ====== --}}
    <div x-show="activeTab === 'laravel'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">🔴</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Laravel / PHP Integration</h2>
                <p class="text-gray-500 text-sm">Push your products using our REST API with a simple HTTP call.</p>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">📦 Install via Composer (optional helper)</p>
            <div class="bg-gray-900 rounded-lg p-4">
                <pre class="text-green-400 font-mono text-sm"># No package needed — just use Laravel Http facade</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">🔄 Sync Products (sync on product save/update)</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
<pre class="text-green-400 font-mono text-sm">use Illuminate\Support\Facades\Http;

// In your ProductObserver or product save controller:
Http::withHeaders([
    'X-Api-Key' => '{{ $apiKey }}',
])->post('{{ $appUrl }}/api/connector/sync-products', [
    'products' => [
        [
            'name'          => $product->name,
            'sku'           => $product->sku,
            'price'         => $product->price,
            'sale_price'    => $product->sale_price,
            'description'   => $product->description,
            'stock'         => $product->stock,
            'image_url'     => $product->thumbnail_url,
            'gallery'       => $product->gallery_urls,
            'video_url'     => $product->video_url,
            'colors'        => $product->colors,
            'sizes'         => $product->sizes,
        ]
    ]
]);
</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">✅ Test the Connection</p>
            <div class="bg-gray-900 rounded-lg p-4">
<pre class="text-green-400 font-mono text-sm">$response = Http::withHeaders(['X-Api-Key' => '{{ $apiKey }}'])
    ->get('{{ $appUrl }}/api/connector/verify');

// Returns: { "success": true, "shop": "{{ $shopName }}", ... }
dd($response->json());</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">🤖 Get the JS Chatbot Snippet for Your Site</p>
            <div class="bg-gray-900 rounded-lg p-4">
<pre class="text-green-400 font-mono text-sm">$snippet = Http::withHeaders(['X-Api-Key' => '{{ $apiKey }}'])
    ->get('{{ $appUrl }}/api/connector/js-snippet')
    ->json('snippet');

// Paste $snippet in your layout Blade file before &lt;/body&gt;</pre>
            </div>
        </div>
    </div>

    {{-- ====== NODE.JS TAB ====== --}}
    <div x-show="activeTab === 'nodejs'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">🟢</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Node.js Integration</h2>
                <p class="text-gray-500 text-sm">Works with Express, NestJS, Next.js, or any Node framework.</p>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">📦 Install (terminal)</p>
            <div class="bg-gray-900 rounded-lg p-4">
                <pre class="text-green-400 font-mono text-sm">npm install axios   # or use built-in fetch</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">🔄 Sync Products</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
<pre class="text-green-400 font-mono text-sm">const axios = require('axios');

const API_KEY = '{{ $apiKey }}';
const BASE_URL = '{{ $appUrl }}';

// Sync products
async function syncProducts(products) {
  const res = await axios.post(`${BASE_URL}/api/connector/sync-products`, {
    products: products
  }, {
    headers: { 'X-Api-Key': API_KEY }
  });
  console.log(res.data); // { success: true, synced: 5 }
}

// Usage
syncProducts([
  {
    name: 'Blue T-Shirt',
    sku: 'TS-001',
    price: 450,
    stock: 100,
    image_url: 'https://yoursite.com/images/tshirt.jpg',
    colors: ['Blue', 'Red'],
    sizes: ['S', 'M', 'L'],
  }
]);
</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">✅ Verify Connection (2 lines)</p>
            <div class="bg-gray-900 rounded-lg p-4">
<pre class="text-green-400 font-mono text-sm">const res = await axios.get(`{{ $appUrl }}/api/connector/verify`, { headers: { 'X-Api-Key': '{{ $apiKey }}' } });
console.log(res.data); // { success: true, shop: '{{ $shopName }}', products: 42 }</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">🔁 Auto-Sync on Product Save (Express example)</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
<pre class="text-green-400 font-mono text-sm">app.post('/admin/products', async (req, res) => {
  // Save to your own DB...
  await db.products.create(req.body);

  // Auto-sync to AI Commerce Bot (fire & forget)
  axios.post('{{ $appUrl }}/api/connector/sync-products',
    { products: [req.body] },
    { headers: { 'X-Api-Key': '{{ $apiKey }}' } }
  ).catch(console.error);

  res.json({ success: true });
});</pre>
            </div>
        </div>
    </div>

    {{-- ====== PYTHON TAB ====== --}}
    <div x-show="activeTab === 'python'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">🐍</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Python Integration</h2>
                <p class="text-gray-500 text-sm">Works with Django, Flask, FastAPI, or any Python backend.</p>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">📦 Install (terminal)</p>
            <div class="bg-gray-900 rounded-lg p-4">
                <pre class="text-green-400 font-mono text-sm">pip install requests</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">🔄 Sync Products (2–4 lines)</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
<pre class="text-green-400 font-mono text-sm">import requests

API_KEY = '{{ $apiKey }}'
BASE_URL = '{{ $appUrl }}'
HEADERS = {'X-Api-Key': API_KEY}

def sync_products(products: list):
    r = requests.post(f'{BASE_URL}/api/connector/sync-products',
                      json={'products': products}, headers=HEADERS)
    print(r.json())  # {'success': True, 'synced': 3}

sync_products([
    {'name': 'Jamdani Saree', 'sku': 'SAR-001', 'price': 3500, 'stock': 15,
     'image_url': 'https://yourshop.com/saree.jpg'}
])
</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">✅ Verify Connection</p>
            <div class="bg-gray-900 rounded-lg p-4">
<pre class="text-green-400 font-mono text-sm">r = requests.get(f'{BASE_URL}/api/connector/verify', headers=HEADERS)
print(r.json())  # {'success': True, 'shop': '{{ $shopName }}'}</pre>
            </div>
        </div>
    </div>

    {{-- ====== ANY HTML SITE TAB ====== --}}
    <div x-show="activeTab === 'anysite'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">🌐</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Any Website (HTML / Static / Custom)</h2>
                <p class="text-gray-500 text-sm">No framework needed. Just paste 2 lines of code.</p>
            </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
            <p class="font-semibold text-indigo-800 mb-3">🤖 Embed AI Chatbot Widget (paste before &lt;/body&gt;)</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto relative">
<pre class="text-green-400 font-mono text-sm" id="html-snippet">&lt;!-- AI Commerce Bot — Paste before &lt;/body&gt; --&gt;
&lt;script&gt;
window.AICB_KEY = '{{ $apiKey }}';
window.AICB_URL = '{{ $appUrl }}';
&lt;/script&gt;
&lt;script src="{{ $appUrl }}/js/chatbot-widget.js" async&gt;&lt;/script&gt;</pre>
            </div>
            <button onclick="navigator.clipboard.writeText(document.getElementById('html-snippet').textContent); this.textContent='✅ Copied!'; setTimeout(()=>this.textContent='📋 Copy Code', 2000)"
                    class="mt-3 bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                📋 Copy Code
            </button>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">🔄 Push Products via plain JavaScript (fetch)</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
<pre class="text-green-400 font-mono text-sm">fetch('{{ $appUrl }}/api/connector/sync-products', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Api-Key': '{{ $apiKey }}'
  },
  body: JSON.stringify({
    products: [
      { name: 'Cotton Panjabi', sku: 'PJ-01', price: 890, stock: 200,
        image_url: 'https://yourshop.com/pj.jpg' }
    ]
  })
})
.then(r => r.json())
.then(data => console.log('Synced:', data.synced));</pre>
            </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-r-xl text-sm text-yellow-800">
            💡 <strong>Tip:</strong> Call the sync endpoint every time a product is saved in your custom admin panel. Products will appear in the AI bot's knowledge base within seconds.
        </div>
    </div>

    {{-- ====== FACEBOOK TAB ====== --}}
    <div x-show="activeTab === 'facebook'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">📘</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Facebook Page Connection</h2>
                <p class="text-gray-500 text-sm">Connect your Facebook Business Page to the AI bot. Customers send messages → AI replies automatically.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h3 class="font-bold text-blue-800 mb-3">📋 What You Need</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>✅ A Facebook Business Page (not personal)</li>
                    <li>✅ Facebook Developer App (facebook.com/developers)</li>
                    <li>✅ Messenger permission approved</li>
                    <li>✅ Page Access Token</li>
                    <li>✅ Webhook Verify Token (any random string)</li>
                </ul>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                <h3 class="font-bold text-purple-800 mb-3">🔗 Integration URLs</h3>
                <div class="space-y-2 text-sm font-mono">
                    <div class="bg-white rounded p-2">
                        <span class="text-gray-500 text-xs">Webhook URL:</span><br>
                        <span class="text-purple-700">{{ $appUrl }}/api/webhook</span>
                    </div>
                    <div class="bg-white rounded p-2">
                        <span class="text-gray-500 text-xs">Verify Token:</span><br>
                        <span class="text-purple-700">Set in your Clients → Edit page</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                <p class="font-semibold text-gray-700 mb-2">📖 Step-by-Step Setup</p>
                <ol class="list-decimal ml-4 text-sm text-gray-600 space-y-2">
                    <li>Go to <a href="https://developers.facebook.com" target="_blank" class="text-blue-600 underline">developers.facebook.com</a> → Create App → Select "Business"</li>
                    <li>Add the <strong>Messenger</strong> product to your app</li>
                    <li>Go to <strong>Messenger → Settings → Webhooks</strong></li>
                    <li>Set <strong>Callback URL</strong>: <code class="bg-gray-100 px-1 rounded">{{ $appUrl }}/api/webhook</code></li>
                    <li>Set <strong>Verify Token</strong> to any string (e.g. <code class="bg-gray-100 px-1 rounded">myshopbot2024</code>)</li>
                    <li>Subscribe to events: <code class="bg-gray-100 px-1 rounded">messages, messaging_postbacks, messaging_referrals</code></li>
                    <li>Generate a <strong>Page Access Token</strong></li>
                    <li>In this Dashboard → <strong>Clients → Edit your profile</strong> → paste:
                        <ul class="ml-4 mt-1 space-y-0.5 text-gray-600">
                            <li>• <strong>Facebook Page ID</strong></li>
                            <li>• <strong>Page Access Token</strong></li>
                            <li>• <strong>Verify Token</strong></li>
                        </ul>
                    </li>
                    <li>Save and test by sending a message to your Facebook page!</li>
                </ol>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                <p class="font-semibold text-green-800 mb-2">✅ What the AI Bot Does Automatically</p>
                <ul class="text-sm text-green-700 space-y-1">
                    <li>✅ Answers product inquiries with real-time prices and stock</li>
                    <li>✅ Shows product images when asked</li>
                    <li>✅ Creates orders and asks for delivery info</li>
                    <li>✅ Sends WhatsApp order confirmation (if connected)</li>
                    <li>✅ Handles follow-up questions, complaints, and returns</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ====== WHATSAPP TAB ====== --}}
    <div x-show="activeTab === 'whatsapp'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">💬</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">WhatsApp Connection</h2>
                <p class="text-gray-500 text-sm">Connect via QR scan (personal/business number) or WhatsApp Business API (for high volume).</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                <h3 class="font-bold text-green-800 mb-1">🔵 Method 1 — QR Code (Quick)</h3>
                <p class="text-xs text-green-700 mb-3">For any WhatsApp number. No business verification needed.</p>
                <ol class="list-decimal ml-4 text-sm text-green-700 space-y-1">
                    <li>Go to <strong>Clients → Edit</strong> → WhatsApp tab</li>
                    <li>Click <strong>"Generate QR Code"</strong></li>
                    <li>Open WhatsApp → Settings → <strong>Linked Devices</strong></li>
                    <li>Scan the QR code</li>
                    <li>Done! AI bot is now active on that number ✅</li>
                </ol>
                <div class="mt-3 p-2 bg-yellow-50 rounded text-xs text-yellow-700">
                    ⚠️ Session may expire after ~2 weeks. Re-scan to reconnect.
                </div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h3 class="font-bold text-blue-800 mb-1">🟣 Method 2 — WhatsApp Business API</h3>
                <p class="text-xs text-blue-700 mb-3">For official business accounts. More reliable + green tick.</p>
                <ol class="list-decimal ml-4 text-sm text-blue-700 space-y-1">
                    <li>Register at <a href="https://developers.facebook.com/docs/whatsapp" target="_blank" class="underline">Meta Business API</a></li>
                    <li>Get your <strong>Phone Number ID</strong> and <strong>Access Token</strong></li>
                    <li>Set Webhook URL: <code class="bg-white px-1 rounded">{{ $appUrl }}/api/v1/whatsapp/receive</code></li>
                    <li>In Dashboard → <strong>Clients → Edit</strong> → WhatsApp API tab → paste credentials</li>
                    <li>Save → Test with a message!</li>
                </ol>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">📊 WhatsApp API Endpoints</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="text-left p-2 rounded-tl">Method</th>
                            <th class="text-left p-2">URL</th>
                            <th class="text-left p-2 rounded-tr">Purpose</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr class="bg-white">
                            <td class="p-2"><span class="bg-green-100 text-green-700 px-2 py-0.5 rounded font-mono text-xs">POST</span></td>
                            <td class="p-2 font-mono text-xs">/api/v1/whatsapp/receive</td>
                            <td class="p-2 text-gray-600">Receive inbound messages</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td class="p-2"><span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-mono text-xs">POST</span></td>
                            <td class="p-2 font-mono text-xs">/api/v1/whatsapp/status</td>
                            <td class="p-2 text-gray-600">Message delivery status updates</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ====== API REFERENCE TAB ====== --}}
    <div x-show="activeTab === 'api'" class="space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-4xl">📡</span>
            <div>
                <h2 class="text-xl font-bold text-gray-800">REST API Reference</h2>
                <p class="text-gray-500 text-sm">Full list of endpoints. Use any HTTP client — curl, Postman, Axios, requests, etc.</p>
            </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
            <p class="font-semibold text-indigo-800 mb-2">🔑 Authentication</p>
            <p class="text-sm text-indigo-700 mb-2">All connector endpoints require your API key. Send it in one of these ways:</p>
            <div class="bg-gray-900 rounded-lg p-3 space-y-1">
                <pre class="text-green-400 font-mono text-xs">Header:  X-Api-Key: {{ $apiKey }}</pre>
                <pre class="text-green-400 font-mono text-xs">Header:  Authorization: Bearer {{ $apiKey }}</pre>
                <pre class="text-green-400 font-mono text-xs">Param:   ?api_key={{ $apiKey }}</pre>
            </div>
        </div>

        @foreach([
            [
                'method' => 'GET', 'color' => 'blue',
                'path' => '/api/connector/verify',
                'desc' => 'Test your API key and get shop info',
                'response' => '{ "success": true, "shop": "' . $shopName . '", "plan_active": true, "products": 42 }',
            ],
            [
                'method' => 'POST', 'color' => 'green',
                'path' => '/api/connector/sync-products',
                'desc' => 'Push products from any platform. Supports bulk or single. Images auto-downloaded.',
                'response' => '{ "success": true, "synced": 5, "failed": 0 }',
            ],
            [
                'method' => 'GET', 'color' => 'blue',
                'path' => '/api/connector/js-snippet',
                'desc' => 'Get the ready-to-paste JS chatbot widget code for your website',
                'response' => '{ "success": true, "snippet": "<script>...</script>", "instructions": [...] }',
            ],
            [
                'method' => 'GET', 'color' => 'blue',
                'path' => '/api/webhook',
                'desc' => 'Facebook Webhook verification (GET) and message handling (POST)',
                'response' => 'hub.challenge (GET) / 200 OK (POST)',
            ],
            [
                'method' => 'POST', 'color' => 'green',
                'path' => '/api/v1/import-products',
                'desc' => 'Legacy product import endpoint (same as sync-products)',
                'response' => '{ "success": true, "message": "Synced X products" }',
            ],
        ] as $ep)
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="bg-{{ $ep['color'] }}-100 text-{{ $ep['color'] }}-700 font-bold font-mono text-xs px-2 py-1 rounded">{{ $ep['method'] }}</span>
                <code class="text-gray-800 font-mono text-sm font-semibold">{{ $appUrl }}{{ $ep['path'] }}</code>
            </div>
            <p class="text-sm text-gray-600 mb-2">{{ $ep['desc'] }}</p>
            <div class="bg-gray-900 rounded-lg p-2">
                <pre class="text-green-400 font-mono text-xs">Response: {{ $ep['response'] }}</pre>
            </div>
        </div>
        @endforeach

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">📦 Product Object Schema (for sync-products)</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
<pre class="text-green-400 font-mono text-xs">{
  "name":            "Product Name",        // required
  "sku":             "SKU-001",             // optional (auto-generated if missing)
  "price":           450,                   // required
  "regular_price":   500,                   // optional
  "sale_price":      450,                   // optional
  "description":     "Short description",   // optional
  "short_description": "...",              // optional
  "stock":           100,                   // optional (default: 100)
  "stock_status":    "in_stock",            // optional: in_stock / out_of_stock
  "image_url":       "https://...",        // optional (thumbnail)
  "gallery":         ["https://...", ...]  // optional (extra images)
  "video_url":       "https://...",        // optional
  "colors":          ["Red", "Blue"],      // optional
  "sizes":           ["S", "M", "L"],      // optional
  "brand":           "Nike",               // optional
  "currency":        "BDT",               // optional (default: BDT)
}</pre>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="font-semibold text-gray-700 mb-3">🧪 Test with curl (terminal)</p>
            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
<pre class="text-green-400 font-mono text-xs"># Verify connection
curl -H "X-Api-Key: {{ $apiKey }}" {{ $appUrl }}/api/connector/verify

# Sync one product
curl -X POST {{ $appUrl }}/api/connector/sync-products \
  -H "X-Api-Key: {{ $apiKey }}" \
  -H "Content-Type: application/json" \
  -d '{"products":[{"name":"Test Product","sku":"TEST-01","price":500,"stock":50}]}'
</pre>
            </div>
        </div>
    </div>

    </div>{{-- end tab content --}}
</div>{{-- end tab container --}}

</div>{{-- end main space-y-6 --}}
</x-filament-panels::page>
