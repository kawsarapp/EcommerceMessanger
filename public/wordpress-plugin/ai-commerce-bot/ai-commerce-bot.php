<?php
/**
 * Plugin Name:       AI Commerce Bot
 * Plugin URI:        https://aicommercebot.com
 * Description:       Connect your WooCommerce store to AI Commerce Bot SaaS. Syncs products automatically, adds a live AI chatbot to your site, and lets the AI create orders directly in your WooCommerce store.
 * Version:           2.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            AI Commerce Bot
 * License:           GPL v2 or later
 * Text Domain:       ai-commerce-bot
 */

if (!defined('ABSPATH')) exit; // No direct access

// ═══════════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════════
define('AICB_VERSION',      '2.0.0');
define('AICB_PLUGIN_DIR',   plugin_dir_path(__FILE__));
define('AICB_PLUGIN_URL',   plugin_dir_url(__FILE__));
define('AICB_OPTION_KEY',   'aicb_settings');

// ═══════════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════════
register_activation_hook(__FILE__,   'aicb_activate');
register_deactivation_hook(__FILE__, 'aicb_deactivate');

function aicb_activate() {
    // Create the orders table for live chat order creation
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'aicb_sessions';

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id   VARCHAR(120) NOT NULL UNIQUE,
        visitor_name VARCHAR(200),
        visitor_phone VARCHAR(30),
        visitor_email VARCHAR(200),
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY session_id (session_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Schedule product sync cron
    if (!wp_next_scheduled('aicb_sync_products_cron')) {
        wp_schedule_event(time(), 'hourly', 'aicb_sync_products_cron');
    }
}

function aicb_deactivate() {
    wp_clear_scheduled_hook('aicb_sync_products_cron');
}

// ═══════════════════════════════════════════════════════════════════════════════
// SETTINGS — Admin Menu
// ═══════════════════════════════════════════════════════════════════════════════
add_action('admin_menu', 'aicb_admin_menu');

function aicb_admin_menu() {
    add_menu_page(
        'AI Commerce Bot',
        'AI Commerce Bot',
        'manage_options',
        'ai-commerce-bot',
        'aicb_settings_page',
        'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 0 2h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1 0-2h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2zM9 9a5 5 0 0 0-5 5v3h16v-3a5 5 0 0 0-5-5H9zm3 2a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>'),
        58
    );
    add_submenu_page('ai-commerce-bot', 'Settings',       'Settings',       'manage_options', 'ai-commerce-bot',         'aicb_settings_page');
    add_submenu_page('ai-commerce-bot', 'Product Sync',   'Product Sync',   'manage_options', 'ai-commerce-bot-sync',    'aicb_sync_page');
    add_submenu_page('ai-commerce-bot', 'Live Chat Log',  'Live Chat Log',  'manage_options', 'ai-commerce-bot-chatlog', 'aicb_chatlog_page');
}

// ═══════════════════════════════════════════════════════════════════════════════
// SETTINGS PAGE
// ═══════════════════════════════════════════════════════════════════════════════
add_action('admin_init', 'aicb_register_settings');
function aicb_register_settings() {
    register_setting('aicb_settings_group', AICB_OPTION_KEY, 'aicb_sanitize_settings');
}

function aicb_sanitize_settings($input) {
    return [
        'api_key'          => sanitize_text_field($input['api_key'] ?? ''),
        'base_url'         => esc_url_raw(rtrim($input['base_url'] ?? '', '/')),
        'chat_enabled'     => !empty($input['chat_enabled']) ? 1 : 0,
        'chat_position'    => in_array($input['chat_position'] ?? '', ['bottom-right', 'bottom-left']) ? $input['chat_position'] : 'bottom-right',
        'primary_color'    => sanitize_hex_color($input['primary_color'] ?? '#4f46e5'),
        'greeting'         => sanitize_text_field($input['greeting'] ?? ''),
        'auto_sync'        => !empty($input['auto_sync']) ? 1 : 0,
        'order_webhook'    => !empty($input['order_webhook']) ? 1 : 0,
        'create_wc_order'  => !empty($input['create_wc_order']) ? 1 : 0,
        'live_chat'        => !empty($input['live_chat']) ? 1 : 0,
    ];
}

function aicb_get($key, $default = '') {
    $opts = get_option(AICB_OPTION_KEY, []);
    return $opts[$key] ?? $default;
}

function aicb_settings_page() {
    if (isset($_POST['aicb_test_connection'])) {
        aicb_test_connection_notice();
    }
    if (isset($_POST['aicb_sync_now'])) {
        $result = aicb_do_sync();
        echo '<div class="notice notice-' . ($result['success'] ? 'success' : 'error') . ' is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
    }
    settings_errors('aicb_messages');
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="font-size:28px">🤖</span> AI Commerce Bot
            <span style="font-size:12px;background:#4f46e5;color:white;padding:2px 10px;border-radius:20px;font-weight:normal;">v<?= AICB_VERSION ?></span>
        </h1>
        <p style="color:#64748b">Connect your WooCommerce store with AI-powered chatbot, auto product sync, and direct order creation.</p>
        <hr>

        <form method="post" action="options.php" id="aicb-settings-form">
            <?php settings_fields('aicb_settings_group'); ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:20px">

                <!-- ── Left Column ─────────────────────────────────────────── -->
                <div>
                    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px">
                        <h2 style="margin-top:0;color:#1e293b;font-size:16px">🔑 API Connection</h2>

                        <table class="form-table" style="margin-top:0">
                            <tr>
                                <th><label for="aicb_api_key">API Key</label></th>
                                <td>
                                    <input type="text" id="aicb_api_key" name="<?= AICB_OPTION_KEY ?>[api_key]"
                                        value="<?= esc_attr(aicb_get('api_key')) ?>"
                                        class="regular-text" placeholder="Your API Key from the dashboard" />
                                    <p class="description">Get this from your AI Commerce Bot seller dashboard → Integrations → API Key</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="aicb_base_url">Dashboard URL</label></th>
                                <td>
                                    <input type="url" id="aicb_base_url" name="<?= AICB_OPTION_KEY ?>[base_url]"
                                        value="<?= esc_attr(aicb_get('base_url')) ?>"
                                        class="regular-text" placeholder="https://your-saas-domain.com" />
                                </td>
                            </tr>
                        </table>

                        <div style="display:flex;gap:10px;margin-top:10px">
                            <?php submit_button('💾 Save Settings', 'primary', 'submit', false); ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="aicb_test_connection" value="1">
                                <button class="button" type="submit">🔗 Test Connection</button>
                            </form>
                        </div>
                    </div>

                    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px">
                        <h2 style="margin-top:0;color:#1e293b;font-size:16px">🛒 WooCommerce Integration</h2>
                        <table class="form-table" style="margin-top:0">
                            <tr>
                                <th>Auto Product Sync</th>
                                <td>
                                    <label><input type="checkbox" name="<?= AICB_OPTION_KEY ?>[auto_sync]" value="1" <?= checked(1, aicb_get('auto_sync'), false) ?>> Sync products every hour automatically</label>
                                    <p class="description">AI chatbot will use your live WooCommerce product data</p>
                                </td>
                            </tr>
                            <tr>
                                <th>Order Sync Webhook</th>
                                <td>
                                    <label><input type="checkbox" name="<?= AICB_OPTION_KEY ?>[order_webhook]" value="1" <?= checked(1, aicb_get('order_webhook'), false) ?>> Send new orders to AI dashboard</label>
                                </td>
                            </tr>
                            <tr>
                                <th>AI Creates WC Orders</th>
                                <td>
                                    <label><input type="checkbox" name="<?= AICB_OPTION_KEY ?>[create_wc_order]" value="1" <?= checked(1, aicb_get('create_wc_order'), false) ?>> Allow AI chatbot to create WooCommerce orders</label>
                                    <p class="description">When a customer confirms an order via chatbot, it will be created directly in WooCommerce</p>
                                </td>
                            </tr>
                        </table>
                        <form method="post">
                            <input type="hidden" name="aicb_sync_now" value="1">
                            <button class="button button-secondary" type="submit">🔄 Sync Products Now</button>
                        </form>
                    </div>
                </div>

                <!-- ── Right Column ────────────────────────────────────────── -->
                <div>
                    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:20px">
                        <h2 style="margin-top:0;color:#1e293b;font-size:16px">💬 Chatbot Widget</h2>
                        <table class="form-table" style="margin-top:0">
                            <tr>
                                <th>Enable Widget</th>
                                <td>
                                    <label><input type="checkbox" name="<?= AICB_OPTION_KEY ?>[chat_enabled]" value="1" <?= checked(1, aicb_get('chat_enabled'), false) ?>> Show AI chatbot on your website</label>
                                </td>
                            </tr>
                            <tr>
                                <th>Position</th>
                                <td>
                                    <select name="<?= AICB_OPTION_KEY ?>[chat_position]">
                                        <option value="bottom-right" <?= selected('bottom-right', aicb_get('chat_position', 'bottom-right'), false) ?>>Bottom Right</option>
                                        <option value="bottom-left"  <?= selected('bottom-left',  aicb_get('chat_position'), false) ?>>Bottom Left</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Primary Color</th>
                                <td>
                                    <input type="color" name="<?= AICB_OPTION_KEY ?>[primary_color]" value="<?= esc_attr(aicb_get('primary_color', '#4f46e5')) ?>">
                                </td>
                            </tr>
                            <tr>
                                <th>Greeting Message</th>
                                <td>
                                    <input type="text" name="<?= AICB_OPTION_KEY ?>[greeting]" class="regular-text"
                                        value="<?= esc_attr(aicb_get('greeting', 'আমি আপনাকে সাহায্য করতে পারি! 👋')) ?>">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px">
                        <h2 style="margin-top:0;color:#1e293b;font-size:16px">🟢 Live Chat</h2>
                        <table class="form-table" style="margin-top:0">
                            <tr>
                                <th>Enable Live Chat</th>
                                <td>
                                    <label><input type="checkbox" name="<?= AICB_OPTION_KEY ?>[live_chat]" value="1" <?= checked(1, aicb_get('live_chat'), false) ?>> Show "Talk to a human" button in chatbot</label>
                                    <p class="description">Customers can request a live agent. You'll get a notification in your dashboard.</p>
                                </td>
                            </tr>
                        </table>
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-top:12px">
                            <h4 style="margin:0 0 8px;font-size:13px">📋 Webhook URL for WooCommerce</h4>
                            <code style="font-size:11px;word-break:break-all"><?= esc_html(aicb_get('base_url')) ?>/api/v1/import-products</code>
                            <p style="font-size:11px;color:#64748b;margin:6px 0 0">Add this as a WooCommerce Webhook (Products > Created/Updated) for real-time sync.</p>
                        </div>
                    </div>
                </div>

            </div><!-- end grid -->

            <div style="margin-top:20px">
                <?php submit_button('💾 Save All Settings'); ?>
            </div>
        </form>
    </div>
    <?php
}

function aicb_test_connection_notice() {
    $apiKey  = aicb_get('api_key');
    $baseUrl = aicb_get('base_url');
    if (!$apiKey || !$baseUrl) {
        echo '<div class="notice notice-error"><p>❌ Please enter API Key and Dashboard URL first.</p></div>';
        return;
    }
    $resp = wp_remote_get("{$baseUrl}/api/connector/verify?api_key=" . urlencode($apiKey), ['timeout' => 10]);
    if (is_wp_error($resp)) {
        echo '<div class="notice notice-error"><p>❌ Connection failed: ' . esc_html($resp->get_error_message()) . '</p></div>';
        return;
    }
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    if (!empty($body['success'])) {
        $shop     = esc_html($body['shop'] ?? '');
        $plan     = esc_html($body['plan'] ?? '');
        $products = intval($body['products'] ?? 0);
        echo "<div class='notice notice-success'><p>✅ Connected to <strong>{$shop}</strong> | Plan: {$plan} | Products in AI: {$products}</p></div>";
    } else {
        echo '<div class="notice notice-error"><p>❌ Invalid API Key or Dashboard URL.</p></div>';
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// PRODUCT SYNC PAGE
// ═══════════════════════════════════════════════════════════════════════════════
function aicb_sync_page() {
    $last_sync = get_option('aicb_last_sync', 'Never');
    ?>
    <div class="wrap">
        <h1>🔄 Product Sync</h1>
        <p>Last sync: <strong><?= esc_html($last_sync) ?></strong></p>
        <form method="post">
            <input type="hidden" name="aicb_manual_sync" value="1">
            <?php wp_nonce_field('aicb_manual_sync', 'aicb_nonce'); ?>
            <button class="button button-primary" type="submit">🔄 Sync All Products Now</button>
        </form>
        <?php
        if (isset($_POST['aicb_manual_sync']) && wp_verify_nonce($_POST['aicb_nonce'], 'aicb_manual_sync')) {
            $result = aicb_do_sync();
            $cls = $result['success'] ? 'notice-success' : 'notice-error';
            echo "<div class='notice {$cls} is-dismissible' style='margin-top:16px'><p>" . esc_html($result['message']) . "</p></div>";
        }
        ?>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// CHAT LOG PAGE
// ═══════════════════════════════════════════════════════════════════════════════
function aicb_chatlog_page() {
    $baseUrl = aicb_get('base_url');
    ?>
    <div class="wrap">
        <h1>💬 Live Chat Log</h1>
        <p>View conversations on your AI Commerce Bot dashboard:</p>
        <?php if ($baseUrl): ?>
            <a href="<?= esc_url($baseUrl . '/admin/conversations') ?>" target="_blank" class="button button-primary">
                Open Dashboard Inbox ↗
            </a>
        <?php else: ?>
            <p>Please configure your Dashboard URL in the settings page first.</p>
        <?php endif; ?>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════════════════════════════
// CORE: PRODUCT SYNC FUNCTION
// ═══════════════════════════════════════════════════════════════════════════════
function aicb_do_sync(): array {
    $apiKey  = aicb_get('api_key');
    $baseUrl = aicb_get('base_url');

    if (!$apiKey || !$baseUrl) {
        return ['success' => false, 'message' => 'API Key or Dashboard URL not configured.'];
    }

    if (!function_exists('wc_get_products')) {
        return ['success' => false, 'message' => 'WooCommerce is not installed or active.'];
    }

    // Fetch all published WooCommerce products
    $wc_products = wc_get_products([
        'status' => 'publish',
        'limit'  => 500,
    ]);

    $products = [];
    foreach ($wc_products as $product) {
        /** @var WC_Product $product */

        // Get categories
        $cats = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
        $category = !empty($cats) ? implode(', ', $cats) : 'Uncategorized';

        // Image
        $imageId  = $product->get_image_id();
        $imageUrl = $imageId ? wp_get_attachment_url($imageId) : '';

        // Variations for variable products
        $variants = [];
        if ($product->is_type('variable')) {
            foreach ($product->get_available_variations() as $v) {
                $variants[] = [
                    'sku'   => $v['sku'] ?? '',
                    'price' => $v['display_price'] ?? 0,
                    'attrs' => $v['attributes'] ?? [],
                ];
            }
        }

        $products[] = [
            'sku'             => $product->get_sku() ?: 'wc-' . $product->get_id(),
            'name'            => $product->get_name(),
            'description'     => wp_strip_all_tags($product->get_description() ?: $product->get_short_description()),
            'price'           => (float) $product->get_price(),
            'sale_price'      => (float) ($product->get_sale_price() ?: $product->get_price()),
            'stock'           => $product->get_stock_quantity() ?? 999,
            'category'        => $category,
            'image_url'       => $imageUrl,
            'product_url'     => get_permalink($product->get_id()),
            'wc_product_id'   => $product->get_id(),
            'variations'      => $variants,
            'is_in_stock'     => $product->is_in_stock(),
            'tags'            => implode(', ', wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names'])),
        ];
    }

    if (empty($products)) {
        return ['success' => false, 'message' => 'No published products found in WooCommerce.'];
    }

    // Send to AI system
    $response = wp_remote_post("{$baseUrl}/api/v1/import-products", [
        'timeout' => 60,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Api-Key'    => $apiKey,
        ],
        'body'    => wp_json_encode(['products' => $products]),
    ]);

    if (is_wp_error($response)) {
        return ['success' => false, 'message' => 'Sync failed: ' . $response->get_error_message()];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);

    if ($code === 200 && !empty($body['success'])) {
        $synced    = $body['synced'] ?? count($products);
        $timestamp = current_time('Y-m-d H:i:s');
        update_option('aicb_last_sync', $timestamp);
        return ['success' => true, 'message' => "✅ {$synced} products synced successfully at {$timestamp}"];
    }

    return ['success' => false, 'message' => 'Sync failed: ' . ($body['message'] ?? 'Unknown error')];
}

// ── Cron: Auto sync hourly ──────────────────────────────────────────────────
add_action('aicb_sync_products_cron', function() {
    if (aicb_get('auto_sync')) {
        aicb_do_sync();
    }
});

// ── Hook: Sync when product is saved/updated ────────────────────────────────
add_action('woocommerce_update_product', 'aicb_sync_single_product', 10, 1);
add_action('woocommerce_new_product',    'aicb_sync_single_product', 10, 1);

function aicb_sync_single_product($product_id) {
    if (!aicb_get('auto_sync') || !aicb_get('api_key') || !aicb_get('base_url')) return;

    $product = wc_get_product($product_id);
    if (!$product || $product->get_status() !== 'publish') return;

    $cats     = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
    $imageId  = $product->get_image_id();

    wp_remote_post(aicb_get('base_url') . '/api/v1/import-products', [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/json', 'X-Api-Key' => aicb_get('api_key')],
        'body'    => wp_json_encode(['products' => [[
            'sku'           => $product->get_sku() ?: 'wc-' . $product_id,
            'name'          => $product->get_name(),
            'description'   => wp_strip_all_tags($product->get_description() ?: $product->get_short_description()),
            'price'         => (float) $product->get_price(),
            'sale_price'    => (float) ($product->get_sale_price() ?: $product->get_price()),
            'stock'         => $product->get_stock_quantity() ?? 999,
            'category'      => implode(', ', $cats),
            'image_url'     => $imageId ? wp_get_attachment_url($imageId) : '',
            'product_url'   => get_permalink($product_id),
            'wc_product_id' => $product_id,
            'is_in_stock'   => $product->is_in_stock(),
        ]]]),
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// CORE: AI CREATES WOOCOMMERCE ORDER (called via REST API from SaaS)
// ═══════════════════════════════════════════════════════════════════════════════
add_action('rest_api_init', function() {
    register_rest_route('aicb/v1', '/create-order', [
        'methods'             => 'POST',
        'callback'            => 'aicb_create_wc_order',
        'permission_callback' => 'aicb_verify_rest_request',
    ]);

    register_rest_route('aicb/v1', '/products', [
        'methods'             => 'GET',
        'callback'            => 'aicb_get_products_rest',
        'permission_callback' => 'aicb_verify_rest_request',
    ]);

    register_rest_route('aicb/v1', '/live-chat/notify', [
        'methods'             => 'POST',
        'callback'            => 'aicb_live_chat_notify',
        'permission_callback' => 'aicb_verify_rest_request',
    ]);
});

function aicb_verify_rest_request(WP_REST_Request $request): bool {
    $key = $request->get_header('X-Api-Key') ?? $request->get_param('api_key');
    return $key === aicb_get('api_key');
}

/**
 * POST /wp-json/aicb/v1/create-order
 * Called by AI SaaS when customer confirms an order via chatbot.
 *
 * Required body: { customer_name, customer_phone, customer_address, items: [{wc_product_id, qty}] }
 */
function aicb_create_wc_order(WP_REST_Request $request): WP_REST_Response {
    if (!aicb_get('create_wc_order')) {
        return new WP_REST_Response(['success' => false, 'message' => 'WC Order creation is disabled in plugin settings.'], 403);
    }

    if (!function_exists('wc_create_order')) {
        return new WP_REST_Response(['success' => false, 'message' => 'WooCommerce not active.'], 500);
    }

    $params = $request->get_json_params();

    $customerName    = sanitize_text_field($params['customer_name']   ?? 'Customer');
    $customerPhone   = sanitize_text_field($params['customer_phone']  ?? '');
    $customerAddress = sanitize_text_field($params['customer_address'] ?? '');
    $customerEmail   = sanitize_email($params['customer_email']       ?? '');
    $items           = $params['items']                                ?? [];
    $note            = sanitize_text_field($params['note']            ?? 'Order via AI Chatbot');
    $platform        = sanitize_text_field($params['platform']        ?? 'chatbot');

    if (empty($items)) {
        return new WP_REST_Response(['success' => false, 'message' => 'No items provided.'], 400);
    }

    // Create WC Order
    $order = wc_create_order();

    // Add products
    $addedItems = 0;
    foreach ($items as $item) {
        $wcId = intval($item['wc_product_id'] ?? $item['product_id'] ?? 0);
        $qty  = max(1, intval($item['qty'] ?? $item['quantity'] ?? 1));

        if (!$wcId) continue;

        $product = wc_get_product($wcId);
        if (!$product) continue;

        $order->add_product($product, $qty);
        $addedItems++;
    }

    if ($addedItems === 0) {
        return new WP_REST_Response(['success' => false, 'message' => 'No valid products found.'], 400);
    }

    // Set billing info
    $nameParts = explode(' ', $customerName, 2);
    $order->set_billing_first_name($nameParts[0]);
    $order->set_billing_last_name($nameParts[1] ?? '');
    $order->set_billing_phone($customerPhone);
    $order->set_billing_email($customerEmail ?: $customerPhone . '@chatbot.local');
    $order->set_billing_address_1($customerAddress);

    // Set shipping same as billing
    $order->set_shipping_first_name($nameParts[0]);
    $order->set_shipping_last_name($nameParts[1] ?? '');
    $order->set_shipping_address_1($customerAddress);

    // Payment method
    $order->set_payment_method('cod');
    $order->set_payment_method_title('Cash on Delivery');

    // Custom note
    $order->add_order_note("📱 Created via AI Chatbot ({$platform}). Customer phone: {$customerPhone}");
    if ($note) $order->add_order_note($note);

    // Recalculate totals and save
    $order->calculate_totals();
    $order->set_status('pending', "Pending — placed via AI Chatbot", true);
    $order->save();

    return new WP_REST_Response([
        'success'  => true,
        'order_id' => $order->get_id(),
        'total'    => $order->get_total(),
        'currency' => get_woocommerce_currency(),
        'message'  => "Order #{$order->get_id()} created successfully in WooCommerce.",
    ], 200);
}

/**
 * GET /wp-json/aicb/v1/products
 * Returns all published WooCommerce products in AI-friendly format.
 */
function aicb_get_products_rest(WP_REST_Request $request): WP_REST_Response {
    $wc_products = wc_get_products(['status' => 'publish', 'limit' => 500]);
    $products = [];
    foreach ($wc_products as $p) {
        $products[] = [
            'wc_product_id' => $p->get_id(),
            'name'          => $p->get_name(),
            'price'         => (float) $p->get_price(),
            'sale_price'    => (float) ($p->get_sale_price() ?: $p->get_price()),
            'stock'         => $p->get_stock_quantity(),
            'is_in_stock'   => $p->is_in_stock(),
            'image_url'     => $p->get_image_id() ? wp_get_attachment_url($p->get_image_id()) : '',
            'product_url'   => get_permalink($p->get_id()),
        ];
    }
    return new WP_REST_Response(['success' => true, 'products' => $products], 200);
}

/**
 * POST /wp-json/aicb/v1/live-chat/notify
 * AI SaaS notifies WordPress when a customer requests a live agent.
 */
function aicb_live_chat_notify(WP_REST_Request $request): WP_REST_Response {
    $params      = $request->get_json_params();
    $senderName  = sanitize_text_field($params['sender_name']  ?? 'Customer');
    $platform    = sanitize_text_field($params['platform']     ?? 'chatbot');
    $lastMessage = sanitize_text_field($params['last_message'] ?? '');

    // Email the admin
    $to      = get_option('admin_email');
    $subject = "🟢 [{$platform}] {$senderName} wants to chat live!";
    $body    = "Customer <strong>{$senderName}</strong> on <strong>{$platform}</strong> has requested a live agent.\n\n"
             . "Last message: {$lastMessage}\n\n"
             . "Login to your dashboard to respond.";

    wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);

    return new WP_REST_Response(['success' => true, 'message' => 'Admin notified.'], 200);
}

// ═══════════════════════════════════════════════════════════════════════════════
// FRONTEND: Load Chatbot Widget Script
// ═══════════════════════════════════════════════════════════════════════════════
add_action('wp_footer', 'aicb_inject_widget');

function aicb_inject_widget() {
    if (!aicb_get('chat_enabled') || !aicb_get('api_key') || !aicb_get('base_url')) return;

    $apiKey       = esc_js(aicb_get('api_key'));
    $baseUrl      = esc_js(rtrim(aicb_get('base_url'), '/'));
    $shopName     = esc_js(get_bloginfo('name'));
    $primaryColor = esc_js(aicb_get('primary_color', '#4f46e5'));
    $position     = esc_js(aicb_get('chat_position', 'bottom-right'));
    $greeting     = esc_js(aicb_get('greeting', 'আমি আপনাকে সাহায্য করতে পারি! 👋'));
    $liveChat     = aicb_get('live_chat') ? 'true' : 'false';
    $wcOrderUrl   = aicb_get('create_wc_order') ? esc_js(rest_url('aicb/v1/create-order')) : '';

    echo <<<HTML
<!-- AI Commerce Bot Widget -->
<script>
(function() {
    window.AICB_CONFIG = {
        apiKey:        "{$apiKey}",
        shopName:      "{$shopName}",
        baseUrl:       "{$baseUrl}",
        position:      "{$position}",
        primaryColor:  "{$primaryColor}",
        greeting:      "{$greeting}",
        liveChat:      {$liveChat},
        wcOrderUrl:    "{$wcOrderUrl}",
        wcApiKey:      "{$apiKey}",
        source:        "wordpress"
    };
    var s = document.createElement('script');
    s.src = "{$baseUrl}/js/chatbot-widget.js";
    s.async = true;
    document.head.appendChild(s);
})();
</script>
<!-- End AI Commerce Bot Widget -->
HTML;
}

// ═══════════════════════════════════════════════════════════════════════════════
// ORDER SYNC: When WooCommerce order is placed, notify SaaS dashboard
// ═══════════════════════════════════════════════════════════════════════════════
add_action('woocommerce_new_order', 'aicb_notify_new_order', 10, 1);

function aicb_notify_new_order($order_id) {
    if (!aicb_get('order_webhook') || !aicb_get('api_key') || !aicb_get('base_url')) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $items = [];
    foreach ($order->get_items() as $item) {
        $items[] = [
            'name'     => $item->get_name(),
            'qty'      => $item->get_quantity(),
            'price'    => $item->get_total(),
        ];
    }

    // Async fire-and-forget (non-blocking)
    wp_remote_post(aicb_get('base_url') . '/api/v1/wc-order-notify', [
        'timeout'   => 5,
        'blocking'  => false,
        'headers'   => ['Content-Type' => 'application/json', 'X-Api-Key' => aicb_get('api_key')],
        'body'      => wp_json_encode([
            'wc_order_id'      => $order_id,
            'customer_name'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer_phone'   => $order->get_billing_phone(),
            'customer_address' => $order->get_billing_address_1(),
            'total'            => $order->get_total(),
            'status'           => $order->get_status(),
            'items'            => $items,
        ]),
    ]);
}
