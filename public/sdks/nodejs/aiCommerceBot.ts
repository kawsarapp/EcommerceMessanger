/**
 * AI Commerce Bot — TypeScript SDK
 * Works with: Next.js (TypeScript), NestJS, Remix, SvelteKit, etc.
 *
 * Setup .env:
 *   AICB_API_KEY=your_api_key_here
 *   NEXT_PUBLIC_AICB_BASE_URL=https://your-saas-domain.com  (Next.js)
 *   AICB_BASE_URL=https://your-saas-domain.com             (Node.js)
 */

// ─────────────
// TYPES
// ─────────────
export interface AicbVerifyResponse {
    success:    boolean;
    message:    string;
    shop:       string;
    plan:       string;
    plan_active: boolean;
    products:   number;
    endpoints:  { chat: string; sync: string; widget_js: string };
}

export interface AicbProduct {
    sku?:         string;
    name:         string;
    price:        number;
    sale_price?:  number;
    stock?:       number;
    is_in_stock?: boolean;
    category?:    string;
    description?: string;
    image_url?:   string;
    gallery?:     string[];
    colors?:      string[];
    sizes?:       string[];
    tags?:        string;
    video_url?:   string;
    product_url?: string;
}

export interface AicbSyncResponse {
    success: boolean;
    synced:  number;
    failed:  number;
    errors:  string[];
    message: string;
}

export interface AicbChatResponse {
    reply:  string;
    error?: string;
}

export interface AicbSnippetOptions {
    position?:      'bottom-right' | 'bottom-left';
    primaryColor?:  string;
    shopName?:      string;
    greeting?:      string;
    liveChat?:      boolean;
}

// ─────────────
// CONFIG
// ─────────────
const API_KEY  = process.env.AICB_API_KEY  || '';
const BASE_URL = (
    process.env.AICB_BASE_URL ||
    process.env.NEXT_PUBLIC_AICB_BASE_URL ||
    ''
).replace(/\/$/, '');

// ─────────────
// CORE
// ─────────────
async function request<T>(method: string, path: string, body?: object): Promise<T> {
    const res = await fetch(`${BASE_URL}${path}`, {
        method,
        headers: {
            'X-Api-Key':    API_KEY,
            'Content-Type': 'application/json',
            'Accept':       'application/json',
        },
        body: body ? JSON.stringify(body) : undefined,
        // For Next.js server components — disable cache for chat
        ...(path.includes('chat') ? { cache: 'no-store' } : {}),
    });
    return res.json() as Promise<T>;
}

// ─────────────
// METHODS
// ─────────────
export const verify      = ()                               => request<AicbVerifyResponse>('GET', '/api/connector/verify');
export const syncProducts = (products: AicbProduct[])       => request<AicbSyncResponse>('POST', '/api/connector/sync-products', { products });
export const syncProduct  = (product: AicbProduct)          => syncProducts([product]);
export const chat         = (message: string, sessionId: string) => request<AicbChatResponse>('POST', '/api/v1/chat/widget', { message, session_id: sessionId });

export function getEmbedSnippet(opts: AicbSnippetOptions = {}): string {
    const {
        position     = 'bottom-right',
        primaryColor = '#4f46e5',
        shopName     = 'AI Shop',
        greeting     = 'আমি আপনাকে সাহায্য করতে পারি! 👋',
        liveChat     = false,
    } = opts;

    return `<!-- AI Commerce Bot Widget -->
<script>
(function() {
  window.AICB_CONFIG = {
    apiKey:       "${API_KEY}",
    shopName:     "${shopName.replace(/"/g, '\\"')}",
    baseUrl:      "${BASE_URL}",
    position:     "${position}",
    primaryColor: "${primaryColor}",
    greeting:     "${greeting.replace(/"/g, '\\"')}",
    liveChat:     ${liveChat}
  };
  var s = document.createElement('script');
  s.src = "${BASE_URL}/js/chatbot-widget.js";
  s.async = true;
  document.head.appendChild(s);
})();
</script>`;
}

const AiCommerceBot = { verify, syncProducts, syncProduct, chat, getEmbedSnippet };
export default AiCommerceBot;

/*
|─────────────────────────────────────────────────────────────────────────────
| NEXT.JS TYPESCRIPT EXAMPLES
|─────────────────────────────────────────────────────────────────────────────
|
| // app/api/chat/route.ts
| import { chat } from '@/lib/aiCommerceBot';
| export async function POST(req: Request) {
|   const { message, sessionId } = await req.json();
|   const data = await chat(message, sessionId);
|   return Response.json(data);
| }
|
| // app/layout.tsx
| import { getEmbedSnippet } from '@/lib/aiCommerceBot';
| export default function RootLayout({ children }: { children: React.ReactNode }) {
|   return (
|     <html lang="en">
|       <body>
|         {children}
|         <div dangerouslySetInnerHTML={{ __html: getEmbedSnippet({ shopName: process.env.NEXT_PUBLIC_SHOP_NAME }) }} />
|       </body>
|     </html>
|   );
| }
|
| // Sync products from Prisma/DB (server action or API route)
| import { syncProducts } from '@/lib/aiCommerceBot';
| import { prisma } from '@/lib/prisma';
| export async function POST() {
|   const products = await prisma.product.findMany({ where: { active: true } });
|   const mapped = products.map(p => ({
|     sku: String(p.id), name: p.name, price: p.price, stock: p.stock ?? 0,
|     image_url: p.imageUrl ?? '', description: p.description ?? '',
|   }));
|   const result = await syncProducts(mapped);
|   return Response.json(result);
| }
|
*/
