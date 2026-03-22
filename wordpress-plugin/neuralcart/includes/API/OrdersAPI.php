<?php
/**
 * NeuralCartOrdersAPI
 * WooCommerce order create ও status check।
 */
class NeuralCartOrdersAPI
{
    public static function create(\WP_REST_Request $request): \WP_REST_Response
    {
        $data = $request->get_json_params();

        $productId = (int) ($data['product_id'] ?? 0);
        $qty       = max(1, (int) ($data['quantity'] ?? 1));
        $name      = sanitize_text_field($data['customer_name'] ?? '');
        $phone     = sanitize_text_field($data['customer_phone'] ?? '');
        $address   = sanitize_textarea_field($data['address'] ?? '');
        $note      = sanitize_textarea_field($data['note'] ?? '');
        $source    = sanitize_text_field($data['source'] ?? 'neuralcart_chatbot');

        if (!$productId || !$name || !$phone || !$address) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'product_id, customer_name, customer_phone, address are required',
            ], 422);
        }

        $product = wc_get_product($productId);
        if (!$product) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Create WooCommerce order
        $order = wc_create_order();

        // Add product
        $order->add_product($product, $qty);

        // Set billing info
        $order->set_billing_first_name($name);
        $order->set_billing_phone($phone);
        $order->set_billing_address_1($address);

        // Add order note (source tracking)
        $order->add_order_note("Order placed via NeuralCart Chatbot. Source: {$source}. Phone: {$phone}");
        if ($note) $order->add_order_note("Customer note: {$note}");

        // Set order meta
        $order->update_meta_data('_neuralcart_source', $source);
        $order->update_meta_data('_neuralcart_sender', $data['sender_id'] ?? '');

        // Calculate totals & save
        $order->calculate_totals();
        $order->set_status('pending');
        $order->save();

        return new \WP_REST_Response([
            'success'      => true,
            'order_id'     => $order->get_id(),
            'order_number' => '#' . $order->get_order_number(),
            'total'        => $order->get_total(),
            'message'      => 'Order created successfully',
        ], 201);
    }

    public static function status(\WP_REST_Request $request): \WP_REST_Response
    {
        $id    = (int) $request->get_param('id');
        $order = wc_get_order($id);

        if (!$order) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Order not found'], 404);
        }

        return new \WP_REST_Response([
            'success'      => true,
            'order_id'     => $order->get_id(),
            'order_number' => '#' . $order->get_order_number(),
            'status'       => $order->get_status(),
            'total'        => $order->get_total(),
            'message'      => 'Order #' . $order->get_order_number() . ' is ' . $order->get_status(),
        ], 200);
    }
}
