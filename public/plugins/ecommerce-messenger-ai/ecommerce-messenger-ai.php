<?php
/**
 * Plugin Name: AI Commerce Bot - Store Connector
 * Plugin URI: https://asianhost.net/
 * Description: Connects your WooCommerce store to the AI Commerce Bot SaaS platform. Sync products in real-time using your Seller API Key.
 * Version: 2.0.0
 * Author: AI Commerce Bot
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

define('AICB_VERSION', '2.0.0');
define('AICB_PLUGIN_URL', plugin_dir_url(__FILE__));

class AI_Commerce_Bot_Connector {

    public function __construct() {
        add_action('rest_api_init',   [$this, 'register_api_endpoints']);
        add_action('admin_menu',      [$this, 'add_admin_menu']);
        add_action('admin_init',      [$this, 'register_settings']);
        add_action('admin_head',      [$this, 'admin_styles']);
    }

    // =========================================================
    // ADMIN STYLES
    // =========================================================
    public function admin_styles() {
        echo '<style>
        .aicb-wrap { max-width: 860px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .aicb-card { background: #fff; border-radius: 10px; padding: 28px 32px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .aicb-header { display:flex; align-items:center; gap:14px; padding-bottom:18px; border-bottom:2px solid #f0f0f0; margin-bottom:24px; }
        .aicb-header h1 { margin:0; font-size:22px; color:#1e1e1e; }
        .aicb-badge { background: linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; font-size:11px; border-radius:20px; padding:2px 10px; font-weight:600; }
        .aicb-status-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:6px; }
        .aicb-status-dot.connected { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2); }
        .aicb-status-dot.disconnected { background:#ef4444; }
        .aicb-field label { font-weight:600; display:block; margin-bottom:6px; color:#374151; }
        .aicb-field input[type=text], .aicb-field input[type=password] {
            width:100%; max-width:500px; padding:10px 14px; border:1.5px solid #d1d5db; border-radius:8px;
            font-size:14px; transition:border .2s;
        }
        .aicb-field input:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.15); }
        .aicb-copy-wrap { display:flex; gap:8px; align-items:center; max-width:600px; }
        .aicb-copy-wrap input { flex:1; background:#f9fafb; font-family:monospace; cursor:text; }
        .aicb-btn { background:#4f46e5; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; transition:background .2s; }
        .aicb-btn:hover { background:#3730a3; }
        .aicb-btn.secondary { background:#f3f4f6; color:#374151; }
        .aicb-btn.secondary:hover { background:#e5e7eb; }
        .aicb-step { background:#f8faff; border-left:4px solid #4f46e5; padding:14px 20px; border-radius:0 8px 8px 0; margin-bottom:10px; }
        .aicb-step ol { margin:10px 0 0 20px; line-height:1.9; font-size:14px; }
        .aicb-alert { padding:14px 18px; border-radius:8px; font-size:14px; margin-top:16px; }
        .aicb-alert.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
        .aicb-alert.error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }
        </style>';
    }

    // =========================================================
    // ADMIN MENU
    // =========================================================
    public function add_admin_menu() {
        add_menu_page(
            'AI Commerce Bot',
            'AI Commerce Bot',
            'manage_options',
            'ai-commerce-bot',
            [$this, 'settings_page'],
            'dashicons-superhero-alt',
            55
        );
    }

    public function register_settings() {
        register_setting('aicb_options', 'aicb_seller_api_key');
        register_setting('aicb_options', 'aicb_secret_key');
        register_setting('aicb_options', 'aicb_is_active');
    }

    // =========================================================
    // SETTINGS PAGE
    // =========================================================
    public function settings_page() {
        $api_url      = rest_url('ai-commerce-bot/v1/products');
        $seller_key   = get_option('aicb_seller_api_key', '');
        $secret_key   = get_option('aicb_secret_key', '');
        $is_active    = get_option('aicb_is_active', 1);
        $is_connected = !empty($seller_key);
        ?>
        <div class="aicb-wrap">

            <!-- Header -->
            <div class="aicb-card">
                <div class="aicb-header">
                    <div>
                        <h1>🤖 AI Commerce Bot <span class="aicb-badge">v<?php echo AICB_VERSION; ?></span></h1>
                        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
                            <span class="aicb-status-dot <?php echo $is_connected ? 'connected' : 'disconnected'; ?>"></span>
                            <?php echo $is_connected ? '<strong style="color:#15803d">Connected</strong> — Your store is synced with AI Commerce Bot.' : '<strong style="color:#dc2626">Not Connected</strong> — Enter your Seller API Key below.'; ?>
                        </p>
                    </div>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('aicb_options'); ?>

                    <!-- Seller API Key -->
                    <div class="aicb-field" style="margin-bottom:20px;">
                        <label>🔑 Seller API Key <span style="color:#ef4444">*</span></label>
                        <input type="text"
                               name="aicb_seller_api_key"
                               value="<?php echo esc_attr($seller_key); ?>"
                               placeholder="sk-seller-xxxxxxxxxxxxxxxx"
                               autocomplete="off" />
                        <p style="margin:6px 0 0; color:#6b7280; font-size:12px;">
                            Get this key from your <a href="https://asianhost.net/admin" target="_blank">AI Commerce Bot Dashboard</a> → Settings → API Keys.
                        </p>
                    </div>

                    <!-- Secret Key -->
                    <div class="aicb-field" style="margin-bottom:20px;">
                        <label>🔒 Secret Key (Optional)</label>
                        <input type="password"
                               name="aicb_secret_key"
                               value="<?php echo esc_attr($secret_key); ?>"
                               placeholder="Leave empty for open access" />
                        <p style="margin:6px 0 0; color:#6b7280; font-size:12px;">
                            If set, every request to your store must include this key as a Bearer token.
                        </p>
                    </div>

                    <!-- Enable/Disable -->
                    <div class="aicb-field" style="margin-bottom:24px;">
                        <label>
                            <input type="checkbox" name="aicb_is_active" value="1" <?php checked(1, $is_active); ?> />
                            ✅ <strong>Enable Product Sync API</strong>
                        </label>
                    </div>

                    <?php submit_button('💾 Save & Connect', 'primary aicb-btn', 'submit', false); ?>
                </form>
            </div>

            <!-- Your Store Endpoint -->
            <div class="aicb-card">
                <h2 style="margin-top:0; font-size:17px;">📡 Your Store API Endpoint</h2>
                <p style="color:#6b7280; font-size:13px; margin-bottom:14px;">
                    Paste this URL into the AI Commerce Bot Dashboard under <strong>External Product Source</strong>.
                </p>
                <div class="aicb-copy-wrap">
                    <input type="text" readonly id="aicb-endpoint" value="<?php echo esc_url($api_url); ?>" onclick="this.select()" />
                    <button class="aicb-btn" onclick="navigator.clipboard.writeText(document.getElementById('aicb-endpoint').value); this.textContent='✅ Copied!'; setTimeout(()=>this.textContent='📋 Copy', 2000); return false;">📋 Copy</button>
                </div>
            </div>

            <!-- How to Connect -->
            <div class="aicb-card">
                <h2 style="margin-top:0; font-size:17px;">🚀 How to Connect in 3 Steps</h2>
                <div class="aicb-step">
                    <ol>
                        <li>Log into your <strong>AI Commerce Bot Dashboard</strong> → go to <strong>Settings → Integrations</strong>.</li>
                        <li>In the <strong>WordPress / WooCommerce</strong> section, paste your <strong>Store API Endpoint URL</strong> (shown above) and your <strong>Seller API Key</strong>.</li>
                        <li>Click <strong>Verify & Save</strong>. Your AI bot will now pull live product data, stock levels, and prices from your WooCommerce store in real-time! 🎉</li>
                    </ol>
                </div>
                <?php if ($is_connected): ?>
                <div class="aicb-alert success">✅ <strong>Store is connected.</strong> The AI bot can now query your products, prices, and stock levels in real-time.</div>
                <?php else: ?>
                <div class="aicb-alert error">⚠️ <strong>Not connected yet.</strong> Please enter your Seller API Key above and save.</div>
                <?php endif; ?>
            </div>

        </div>
        <?php
    }

    // =========================================================
    // REST API ENDPOINT
    // =========================================================
    public function register_api_endpoints() {
        // Product search endpoint
        register_rest_route('ai-commerce-bot/v1', '/products', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_products'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Verify connection endpoint (for SaaS platform to ping)
        register_rest_route('ai-commerce-bot/v1', '/verify', [
            'methods'             => 'GET',
            'callback'            => [$this, 'verify_connection'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    public function check_permission(WP_REST_Request $request) {
        if (!get_option('aicb_is_active', 1)) {
            return new WP_Error('disabled', 'AI Commerce Bot sync is disabled.', ['status' => 403]);
        }

        $saved_key = get_option('aicb_secret_key', '');
        if (!empty($saved_key)) {
            $auth = $request->get_header('Authorization');
            $token = trim(str_replace('Bearer', '', $auth));
            if ($token !== $saved_key) {
                return new WP_Error('unauthorized', 'Invalid secret key.', ['status' => 401]);
            }
        }

        // Verify seller API key belongs to THIS store
        $seller_key = $request->get_param('api_key') ?: get_option('aicb_seller_api_key', '');
        if (empty($seller_key)) {
            return new WP_Error('no_key', 'Seller API key is required.', ['status' => 400]);
        }

        return true;
    }

    public function verify_connection(WP_REST_Request $request) {
        return rest_ensure_response([
            'status'     => 'connected',
            'site_name'  => get_bloginfo('name'),
            'site_url'   => get_site_url(),
            'woocommerce'=> class_exists('WooCommerce') ? 'active' : 'not_installed',
            'products'   => wp_count_posts('product')->publish ?? 0,
            'version'    => AICB_VERSION,
        ]);
    }

    public function get_products(WP_REST_Request $request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error('no_woocommerce', 'WooCommerce is not installed.', ['status' => 500]);
        }

        $keyword  = sanitize_text_field($request->get_param('q') ?? '');
        $limit    = min(intval($request->get_param('limit') ?? 10), 30);
        $category = sanitize_text_field($request->get_param('category') ?? '');

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
        ];

        if (!empty($keyword)) {
            $args['s'] = $keyword;
        } else {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        }

        if (!empty($category)) {
            $args['tax_query'] = [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category,
            ]];
        }

        $query    = new WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                global $product;
                if (!$product) continue;

                $colors = 'N/A';
                $sizes  = 'N/A';
                if ($product->is_type('variable')) {
                    foreach ($product->get_attributes() as $attr) {
                        $name = strtolower($attr->get_name());
                        if (strpos($name, 'color') !== false || strpos($name, 'colour') !== false) {
                            $colors = implode(', ', $attr->get_options());
                        }
                        if (strpos($name, 'size') !== false) {
                            $sizes = implode(', ', $attr->get_options());
                        }
                    }
                }

                $gallery = [];
                foreach ($product->get_gallery_image_ids() as $img_id) {
                    $url = wp_get_attachment_image_url($img_id, 'full');
                    if ($url) $gallery[] = $url;
                }

                $cats = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);

                $products[] = [
                    'id'               => $product->get_id(),
                    'sku'              => $product->get_sku() ?: 'WP-' . $product->get_id(),
                    'name'             => $product->get_name(),
                    'categories'       => $cats,
                    'available_colors' => $colors,
                    'available_sizes'  => $sizes,
                    'regular_price'    => $product->get_regular_price() . ' Tk',
                    'sale_price'       => $product->get_sale_price() ? $product->get_sale_price() . ' Tk' : null,
                    'price'            => $product->get_price() . ' Tk',
                    'stock'            => $product->get_stock_quantity() ?? 'In Stock',
                    'stock_status'     => $product->get_stock_status(),
                    'description'      => wp_trim_words(strip_tags($product->get_short_description() ?: $product->get_description()), 30),
                    'link'             => get_permalink($product->get_id()),
                    'thumbnail'        => wp_get_attachment_image_url($product->get_image_id(), 'full'),
                    'gallery'          => $gallery,
                    'video_url'        => get_post_meta($product->get_id(), '_product_video_url', true) ?: null,
                    'rating'           => $product->get_average_rating(),
                    'reviews_count'    => $product->get_review_count(),
                ];
            }
        }

        wp_reset_postdata();

        return rest_ensure_response([
            'source'   => 'woocommerce',
            'site'     => get_bloginfo('name'),
            'total'    => count($products),
            'products' => $products,
        ]);
    }
}

new AI_Commerce_Bot_Connector();
