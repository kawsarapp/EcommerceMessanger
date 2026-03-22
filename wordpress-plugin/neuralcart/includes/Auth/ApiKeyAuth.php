<?php
/**
 * NeuralCartApiKeyAuth
 * REST API request এ X-NeuralCart-Key header validate করে।
 */
class NeuralCartApiKeyAuth
{
    public static function verify(\WP_REST_Request $request): bool|\WP_Error
    {
        $providedKey = $request->get_header('X-NeuralCart-Key');
        $storedKey   = get_option('neuralcart_api_key', '');

        if (empty($providedKey) || empty($storedKey)) {
            return new \WP_Error('no_api_key', 'API key required', ['status' => 401]);
        }

        if (!hash_equals($storedKey, $providedKey)) {
            return new \WP_Error('invalid_api_key', 'Invalid API key', ['status' => 403]);
        }

        return true;
    }
}
