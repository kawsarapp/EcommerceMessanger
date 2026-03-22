<?php
/**
 * Plugin Name: NeuralCart Connector
 * Plugin URI:  https://asianhost.net
 * Description: Connect your WooCommerce store to NeuralCart AI Chatbot SaaS platform.
 *              Enables AI-powered WhatsApp/Facebook/Instagram chatbot to query your products
 *              and create orders on your server — no product migration needed.
 * Version:     1.0.0
 * Author:      NeuralCart
 * Author URI:  https://asianhost.net
 * Text Domain: neuralcart
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

defined('ABSPATH') || exit;

define('NEURALCART_VERSION', '1.0.0');
define('NEURALCART_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEURALCART_PLUGIN_URL', plugin_dir_url(__FILE__));

// ─────────────────────────────────────────────────────────────────────────────
// AUTOLOAD includes
// ─────────────────────────────────────────────────────────────────────────────
require_once NEURALCART_PLUGIN_DIR . 'includes/Auth/ApiKeyAuth.php';
require_once NEURALCART_PLUGIN_DIR . 'includes/API/ProductsAPI.php';
require_once NEURALCART_PLUGIN_DIR . 'includes/API/OrdersAPI.php';
require_once NEURALCART_PLUGIN_DIR . 'includes/API/StockAPI.php';
require_once NEURALCART_PLUGIN_DIR . 'includes/API/PingAPI.php';
require_once NEURALCART_PLUGIN_DIR . 'includes/Admin/SettingsPage.php';

// ─────────────────────────────────────────────────────────────────────────────
// ACTIVATION HOOK — Generate API key on first install
// ─────────────────────────────────────────────────────────────────────────────
register_activation_hook(__FILE__, function () {
    if (!get_option('neuralcart_api_key')) {
        $key = 'nc_' . bin2hex(random_bytes(20));
        update_option('neuralcart_api_key', $key);
    }
    if (!get_option('neuralcart_saas_url')) {
        update_option('neuralcart_saas_url', 'https://asianhost.net');
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// REGISTER REST API ROUTES
// ─────────────────────────────────────────────────────────────────────────────
add_action('rest_api_init', function () {
    $namespace = 'neuralcart/v1';

    // Health check
    register_rest_route($namespace, '/ping', [
        'methods'             => 'GET',
        'callback'            => ['NeuralCartPingAPI', 'handle'],
        'permission_callback' => ['NeuralCartApiKeyAuth', 'verify'],
    ]);

    // Products search
    register_rest_route($namespace, '/products', [
        'methods'             => 'GET',
        'callback'            => ['NeuralCartProductsAPI', 'search'],
        'permission_callback' => ['NeuralCartApiKeyAuth', 'verify'],
    ]);

    // Single product
    register_rest_route($namespace, '/products/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => ['NeuralCartProductsAPI', 'single'],
        'permission_callback' => ['NeuralCartApiKeyAuth', 'verify'],
    ]);

    // Stock check
    register_rest_route($namespace, '/stock/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => ['NeuralCartStockAPI', 'handle'],
        'permission_callback' => ['NeuralCartApiKeyAuth', 'verify'],
    ]);

    // Create order
    register_rest_route($namespace, '/orders', [
        'methods'             => 'POST',
        'callback'            => ['NeuralCartOrdersAPI', 'create'],
        'permission_callback' => ['NeuralCartApiKeyAuth', 'verify'],
    ]);

    // Order status
    register_rest_route($namespace, '/orders/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => ['NeuralCartOrdersAPI', 'status'],
        'permission_callback' => ['NeuralCartApiKeyAuth', 'verify'],
    ]);
});

// Admin settings page
add_action('admin_menu', ['NeuralCartSettingsPage', 'register']);
