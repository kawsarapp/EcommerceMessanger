<?php
class NeuralCartPingAPI {
    public static function handle(\WP_REST_Request $r): \WP_REST_Response {
        return new \WP_REST_Response([
            'success'    => true,
            'store_name' => get_bloginfo('name'),
            'url'        => get_bloginfo('url'),
            'version'    => NEURALCART_VERSION,
            'wc_active'  => class_exists('WooCommerce'),
            'products'   => (int)(new \WP_Query(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']))->found_posts,
        ], 200);
    }
}
