=== AI Commerce Bot ===
Contributors: aicommercebot
Tags: chatbot, ai, woocommerce, live chat, order automation
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later

Connect your WooCommerce store to AI Commerce Bot — AI chatbot, auto product sync, live chat, and AI-powered order creation.

== Description ==

AI Commerce Bot is the easiest way to connect your WordPress/WooCommerce store to the AI Commerce Bot SaaS platform.

**Features:**

🤖 **AI Chatbot Widget**
* Floating AI chatbot on every page of your website
* Answers customer questions using your real WooCommerce product data
* Supports Bangla, English, and Banglish
* Customizable color, position, and greeting message

🛒 **WooCommerce Integration**
* Auto-syncs all products to the AI brain every hour
* Real-time sync when you save/update a product
* Supports variable products, sale prices, stock status

📦 **AI Creates WooCommerce Orders**
* When a customer confirms an order via chatbot, it's created directly in WooCommerce
* Includes customer name, phone, address, and product details
* Order status set to "Pending" for your review

🟢 **Live Chat**
* Customers can request a human agent from the chatbot
* Admin gets an email notification instantly
* Manage conversations from the AI Commerce Bot dashboard

📊 **Order Webhook**
* When new orders are placed in WooCommerce, your AI dashboard is notified
* Keep both systems in sync

== Installation ==

1. Upload the `ai-commerce-bot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **AI Commerce Bot → Settings** in your admin menu
4. Enter your API Key (get it from your AI Commerce Bot dashboard → Integrations)
5. Enter your Dashboard URL (e.g. `https://yoursaas.com`)
6. Enable the features you want and save
7. Click "Test Connection" to verify everything works
8. Click "Sync Products Now" to import your products into the AI

== REST API Endpoints (added by this plugin) ==

These endpoints are available on your WordPress site:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/wp-json/aicb/v1/create-order` | AI creates a WooCommerce order |
| GET  | `/wp-json/aicb/v1/products`      | Get all products in AI format |
| POST | `/wp-json/aicb/v1/live-chat/notify` | Notify admin of live chat request |

All endpoints require the `X-Api-Key` header matching your configured API key.

== Frequently Asked Questions ==

= Do I need WooCommerce? =
WooCommerce is required for product sync and order creation features. The chatbot widget works on any WordPress site.

= Is there a free plan? =
Please check your AI Commerce Bot subscription plan.

= What happens when AI creates an order? =
The order is created in WooCommerce as "Pending" status with the customer's info. You can then process it normally.

== Changelog ==

= 2.0.0 =
* Full rewrite with WooCommerce order creation
* Live chat notification system
* Real-time product sync on save
* Improved admin UI

= 1.0.0 =
* Initial release
