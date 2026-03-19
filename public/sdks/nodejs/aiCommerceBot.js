/**
 * AI Commerce Bot — Node.js / Next.js / Express SDK
 * 
 * Works with: Node.js, Express, Next.js (API routes), NestJS, Fastify
 *
 * Installation:
 *   No npm package needed — just copy this file into your project.
 *   Place at: lib/aiCommerceBot.js  (or .ts for TypeScript — see bottom)
 *
 * Setup .env:
 *   AICB_API_KEY=your_api_key_here
 *   AICB_BASE_URL=https://your-saas-domain.com
 */

// ─────────────────────────────────────────────────────────────────────────────
// CONFIG
// ─────────────────────────────────────────────────────────────────────────────
const AICB_API_KEY  = process.env.AICB_API_KEY  || '';
const AICB_BASE_URL = (process.env.AICB_BASE_URL || '').replace(/\/$/, '');

// ─────────────────────────────────────────────────────────────────────────────
// CORE REQUEST HELPER
// ─────────────────────────────────────────────────────────────────────────────
async function aicbRequest(method, path, body = null) {
    const url = `${AICB_BASE_URL}${path}`;
    const options = {
        method: method.toUpperCase(),
        headers: {
            'X-Api-Key':    AICB_API_KEY,
            'Content-Type': 'application/json',
            'Accept':       'application/json',
        },
    };
    if (body) options.body = JSON.stringify(body);

    const res = await fetch(url, options);
    return res.json();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. VERIFY CONNECTION
// ─────────────────────────────────────────────────────────────────────────────
/**
 * Test if API key is valid and get shop info.
 *
 * @returns {Promise<{success:boolean, shop:string, plan:string, products:number}>}
 *
 * @example
 * const info = await AiCommerceBot.verify();
 * console.log(info.shop); // "My Shop"
 */
async function verify() {
    return aicbRequest('GET', '/api/connector/verify');
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. SYNC PRODUCTS
// ─────────────────────────────────────────────────────────────────────────────
/**
 * Push products to the AI system.
 *
 * @param {Array<{
 *   sku?: string,
 *   name: string,
 *   price: number,
 *   stock?: number,
 *   category?: string,
 *   image_url?: string,
 *   description?: string,
 *   colors?: string[],
 *   sizes?: string[],
 * }>} products
 *
 * @returns {Promise<{success:boolean, synced:number, failed:number}>}
 *
 * @example
 * const result = await AiCommerceBot.syncProducts([
 *   { sku: 'SKU-001', name: 'T-Shirt', price: 599, stock: 20, category: 'Clothing' }
 * ]);
 */
async function syncProducts(products) {
    return aicbRequest('POST', '/api/connector/sync-products', { products });
}

/**
 * Sync a single product.
 */
async function syncProduct(product) {
    return syncProducts([product]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. AI CHAT (server-side)
// ─────────────────────────────────────────────────────────────────────────────
/**
 * Send a message to the AI and receive a reply.
 *
 * @param {string} message
 * @param {string} sessionId  — unique per visitor (e.g. socket ID, cookie, user ID)
 * @returns {Promise<{reply: string}>}
 *
 * @example
 * const { reply } = await AiCommerceBot.chat('What is your price?', 'user_abc123');
 * res.json({ message: reply });
 */
async function chat(message, sessionId) {
    return aicbRequest('POST', '/api/v1/chat/widget', { message, session_id: sessionId });
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. GET EMBED SNIPPET (server-rendered pages)
// ─────────────────────────────────────────────────────────────────────────────
/**
 * Generate the HTML snippet to embed the chatbot on any page.
 * Use this in SSR pages (Next.js, Nuxt, etc.)
 *
 * @param {Object} options
 * @param {string} [options.position='bottom-right']   'bottom-right' | 'bottom-left'
 * @param {string} [options.primaryColor='#4f46e5']
 * @param {string} [options.shopName='My Shop']
 * @param {string} [options.greeting]
 * @returns {string}  HTML snippet — inject with dangerouslySetInnerHTML or similar
 *
 * @example (Next.js pages/_document.jsx)
 * import { getEmbedSnippet } from '@/lib/aiCommerceBot';
 * // In getServerSideProps:
 * const snippet = getEmbedSnippet({ shopName: 'My Store' });
 * // In JSX:
 * <div dangerouslySetInnerHTML={{ __html: snippet }} />
 */
function getEmbedSnippet({ position = 'bottom-right', primaryColor = '#4f46e5', shopName = 'AI Shop', greeting = '' } = {}) {
    return `<!-- AI Commerce Bot Widget -->
<script>
(function() {
  window.AICB_CONFIG = {
    apiKey:       "${AICB_API_KEY}",
    shopName:     "${shopName.replace(/"/g, '\\"')}",
    baseUrl:      "${AICB_BASE_URL}",
    position:     "${position}",
    primaryColor: "${primaryColor}",
    greeting:     "${(greeting || 'আমি আপনাকে সাহায্য করতে পারি! 👋').replace(/"/g, '\\"')}"
  };
  var s = document.createElement('script');
  s.src = "${AICB_BASE_URL}/js/chatbot-widget.js";
  s.async = true;
  document.head.appendChild(s);
})();
</script>`;
}

// ─────────────────────────────────────────────────────────────────────────────
// EXPORT
// ─────────────────────────────────────────────────────────────────────────────
const AiCommerceBot = { verify, syncProducts, syncProduct, chat, getEmbedSnippet };

// CommonJS
if (typeof module !== 'undefined') module.exports = AiCommerceBot;

// ESM
export { verify, syncProducts, syncProduct, chat, getEmbedSnippet };
export default AiCommerceBot;


/*
|─────────────────────────────────────────────────────────────────────────────
| USAGE EXAMPLES
|─────────────────────────────────────────────────────────────────────────────
|
| ── Express.js ──────────────────────────────────────────────────────────────
|
| const AiCommerceBot = require('./lib/aiCommerceBot');
|
| // 1. Test connection (run once on startup)
| app.listen(3000, async () => {
|   const info = await AiCommerceBot.verify();
|   console.log('✅ AICB Connected:', info.shop);
| });
|
| // 2. Chat endpoint (your frontend calls this)
| app.post('/api/chat', async (req, res) => {
|   const { reply } = await AiCommerceBot.chat(req.body.message, req.sessionID);
|   res.json({ reply });
| });
|
| // 3. Sync all products from your DB
| app.post('/admin/sync-products', async (req, res) => {
|   const products = await db.query('SELECT * FROM products WHERE active = 1');
|   const mapped = products.map(p => ({
|     sku: String(p.id), name: p.name, price: p.price,
|     stock: p.quantity, image_url: p.image, description: p.description,
|   }));
|   const result = await AiCommerceBot.syncProducts(mapped);
|   res.json(result);
| });
|
|
| ── Next.js (App Router) ─────────────────────────────────────────────────────
|
| // app/api/chat/route.js
| import { chat } from '@/lib/aiCommerceBot';
| export async function POST(req) {
|   const { message, sessionId } = await req.json();
|   const data = await chat(message, sessionId);
|   return Response.json(data);
| }
|
| // app/layout.jsx — Embed the chatbot widget
| import { getEmbedSnippet } from '@/lib/aiCommerceBot';
| export default function RootLayout({ children }) {
|   const snippet = getEmbedSnippet({ shopName: 'My Store', primaryColor: '#e11d48' });
|   return (
|     <html>
|       <body>
|         {children}
|         <div dangerouslySetInnerHTML={{ __html: snippet }} />
|       </body>
|     </html>
|   );
| }
|
|
| ── Next.js (Pages Router) ────────────────────────────────────────────────────
|
| // pages/api/chat.js
| import { chat } from '../../lib/aiCommerceBot';
| export default async function handler(req, res) {
|   const { reply } = await chat(req.body.message, req.body.sessionId);
|   res.json({ reply });
| }
|
| // pages/_app.js
| import { getEmbedSnippet } from '../lib/aiCommerceBot';
| export default function App({ Component, pageProps }) {
|   return (
|     <>
|       <Component {...pageProps} />
|       <div dangerouslySetInnerHTML={{ __html: getEmbedSnippet() }} />
|     </>
|   );
| }
|
*/
