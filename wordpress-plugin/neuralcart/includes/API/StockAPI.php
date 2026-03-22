<?php
class NeuralCartStockAPI {
    public static function handle(\WP_REST_Request $r): \WP_REST_Response {
        $p = wc_get_product((int)$r->get_param('id'));
        if (!$p) return new \WP_REST_Response(['stock'=>0,'in_stock'=>false],404);
        return new \WP_REST_Response(['success'=>true,'stock'=>(int)$p->get_stock_quantity(),'in_stock'=>$p->is_in_stock()],200);
    }
}
