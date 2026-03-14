<?php
/**
 * Plugin Name: AI Agent Store Sync
 * Plugin URI: https://bangladeshmail24.com/
 * Description: Connects your WooCommerce store to the AI Employee/SaaS platform via a Secure Real-time API.
 * Version: 1.1.0
 * Author: Ecommerce Messanger AI
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AI_Store_Sync {
    
    public function __construct() {
        // Register REST API
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
        
        // Add Admin Menu Page
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_plugin_settings'));
    }

    // ==========================================
    // ADMIN SETTINGS PAGE
    // ==========================================
    public function add_plugin_admin_menu() {
        add_menu_page(
            'AI Agent Sync', 
            'AI Agent Sync', 
            'manage_options', 
            'ai-agent-store-sync', 
            array($this, 'display_plugin_settings_page'), 
            'dashicons-rest-api', 
            55
        );
    }

    public function register_plugin_settings() {
        register_setting('ai_agent_sync_options', 'ai_sync_is_active');
        register_setting('ai_agent_sync_options', 'ai_sync_secret_key');
    }

    public function display_plugin_settings_page() {
        $api_url = rest_url('ai-bot/v1/search');
        ?>
        <div class="wrap" style="max-width: 800px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
            <h1 style="border-bottom: 2px solid #ccc; padding-bottom: 10px;">🤖 AI Agent Store Sync Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('ai_agent_sync_options'); ?>
                <?php do_settings_sections('ai_agent_sync_options'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Status</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_sync_is_active" value="1" <?php checked(1, get_option('ai_sync_is_active', 1), true); ?> />
                                ✔️ <strong>Enable API Endpoint</strong> (Allow AI to perform real-time search)
                            </label>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Secret API Key (Optional)</th>
                        <td>
                            <input type="text" name="ai_sync_secret_key" value="<?php echo esc_attr(get_option('ai_sync_secret_key')); ?>" style="width: 100%; max-width: 400px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Djsfkh2834njNJd" />
                            <p class="description">If you want to secure your connection, enter a random secret key here and put the same key in your Agent SaaS Dashboard. Leave empty for open access.</p>
                        </td>
                    </tr>
                </table>
                <br>
                <?php submit_button('Save Settings', 'primary'); ?>
            </form>

            <div style="background: #f1f5f9; padding: 20px; border-left: 4px solid #3b82f6; margin-top: 30px; border-radius: 0 8px 8px 0;">
                <h2 style="margin-top: 0; color: #1e293b;">🔗 How to Connect With Your SaaS Panel?</h2>
                <p>Follow these steps to link your store with your AI Agent:</p>
                <ol style="line-height: 1.8; font-size: 15px;">
                    <li>Log into your <b>Ecommerce Messenger Dashboard</b>.</li>
                    <li>Go to the <b>Store Sync</b> Tab for your business profile.</li>
                    <li>Find the <b>"Real-time AI Product Lookup"</b> section.</li>
                    <li>Copy the exact URL below and paste it in the <code>External API URL</code> field:
                        <br>
                        <input type="text" readonly value="<?php echo esc_url($api_url); ?>" style="width: 100%; max-width: 500px; font-family: monospace; padding: 8px; margin: 10px 0; background: #fff; cursor: copy;" onclick="this.select()" />
                    </li>
                    <li>If you entered a Secret API Key above, copy that key and paste it into the <code>Secret API Key</code> field on the SaaS dashboard. Then click <b>Save</b>.</li>
                    <li>🎉 <b>Done!</b> The AI Bot will now automatically pull live products, stock, and prices from your WooCommerce store whenever customers ask for them!</li>
                </ol>
            </div>
        </div>
        <?php
    }

    // ==========================================
    // REST API ENDPOINT
    // ==========================================
    public function register_api_endpoints() {
        register_rest_route('ai-bot/v1', '/search', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_products'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    public function check_permission(WP_REST_Request $request) {
        $is_active = get_option('ai_sync_is_active', 1);
        if (!$is_active) {
            return new WP_Error('rest_forbidden', 'AI Sync is currently disabled by store admin.', array('status' => 403));
        }

        $saved_key = get_option('ai_sync_secret_key');
        if (!empty($saved_key)) {
            $auth_header = $request->get_header('Authorization');
            $request_key = str_replace('Bearer ', '', $auth_header);
            if ($request_key !== $saved_key) {
                return new WP_Error('rest_forbidden', 'Invalid API key. Access Denied.', array('status' => 401));
            }
        }
        
        return true; 
    }

    public function get_products(WP_REST_Request $request) {
        $keyword = sanitize_text_field($request->get_param('q'));
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 8,
            'post_status' => 'publish'
        );

        if (!empty($keyword)) {
            $args['s'] = $keyword;
        } else {
            $args['orderby'] = 'rand';
        }

        $query = new WP_Query($args);
        $products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                global $product;
                
                if (!$product) continue;
                
                $prices = $product->get_price();
                $finalPrice = !empty($prices) ? $prices : 0;
                
                $colors = 'N/A';
                $sizes = 'N/A';
                if ($product->is_type('variable')) {
                    $attributes = $product->get_attributes();
                    foreach ($attributes as $attr) {
                        $name = strtolower($attr->get_name());
                        if (strpos($name, 'color') !== false) {
                            $colors = implode(', ', $attr->get_options());
                        }
                        if (strpos($name, 'size') !== false) {
                            $sizes = implode(', ', $attr->get_options());
                        }
                    }
                }

                $mainImage = wp_get_attachment_image_url($product->get_image_id(), 'full');

                $products[] = array(
                    'id'               => $product->get_id(),
                    'sku'              => $product->get_sku() ? $product->get_sku() : 'WP-' . $product->get_id(),
                    'name'             => $product->get_name(),
                    'available_colors' => $colors,
                    'available_sizes'  => $sizes,
                    'price'            => $finalPrice . " Tk",
                    'stock'            => $product->get_stock_quantity() ? $product->get_stock_quantity() : 100,
                    'desc'             => wp_trim_words(strip_tags($product->get_short_description() ?: $product->get_description()), 20),
                    'link'             => get_permalink($product->get_id()),
                    'image_url'        => $mainImage
                );
            }
        }
        
        wp_reset_postdata();

        return rest_ensure_response($products);
    }
}

new AI_Store_Sync();
