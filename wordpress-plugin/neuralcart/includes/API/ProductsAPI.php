<?php
/**
 * NeuralCartProductsAPI
 * WooCommerce products search ও single product details।
 */
class NeuralCartProductsAPI
{
    public static function search(\WP_REST_Request $request): \WP_REST_Response
    {
        $search = sanitize_text_field($request->get_param('search') ?? '');
        $limit  = min((int) ($request->get_param('limit') ?? 5), 20);

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            's'              => $search,
        ];

        $query    = new \WP_Query($args);
        $products = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $products[] = self::mapProduct($product);
            }
        }

        return new \WP_REST_Response(['success' => true, 'products' => $products], 200);
    }

    public static function single(\WP_REST_Request $request): \WP_REST_Response
    {
        $id      = (int) $request->get_param('id');
        $product = wc_get_product($id);

        if (!$product || $product->get_status() !== 'publish') {
            return new \WP_REST_Response(['success' => false, 'message' => 'Product not found'], 404);
        }

        return new \WP_REST_Response(['success' => true, 'product' => self::mapProduct($product)], 200);
    }

    private static function mapProduct(\WC_Product $p): array
    {
        $imageId  = $p->get_image_id();
        $imageUrl = $imageId ? wp_get_attachment_url($imageId) : null;

        return [
            'id'          => $p->get_id(),
            'title'       => $p->get_name(),
            'price'       => (float) $p->get_regular_price(),
            'sale_price'  => $p->is_on_sale() ? (float) $p->get_sale_price() : null,
            'stock'       => (int) $p->get_stock_quantity(),
            'in_stock'    => $p->is_in_stock(),
            'sku'         => $p->get_sku(),
            'image'       => $imageUrl,
            'description' => wp_strip_all_tags($p->get_short_description() ?: $p->get_description()),
            'url'         => get_permalink($p->get_id()),
        ];
    }
}
