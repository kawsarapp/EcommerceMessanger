<?php
/**
 * Plugin Name: AI Agent Store Sync
 * Plugin URI: https://bangladeshmail24.com/
 * Description: Connects your WooCommerce store to the AI Employee/SaaS platform via a Secure Real-time API.
 * Version: 1.0.0
 * Author: Ecommerce Messanger AI
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AI_Store_Sync {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
    }

    public function register_api_endpoints() {
        register_rest_route('ai-bot/v1', '/search', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_products'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    public function check_permission(WP_REST_Request $request) {
        // You can extend this to check API key if provided by merchant
        // For example:
        // $api_key = $request->get_header('Authorization');
        // if ($api_key !== 'Bearer YOUR_SECRET_KEY') return new WP_Error('rest_forbidden', 'Invalid API key', array('status' => 401));
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
                
                // Keep the exact same array structure format as your SaaS requires
                $prices = $product->get_price();
                $finalPrice = !empty($prices) ? $prices : 0;
                
                // Fetch colors/sizes from attributes if available
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
