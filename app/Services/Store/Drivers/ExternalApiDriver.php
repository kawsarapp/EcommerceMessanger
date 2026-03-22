<?php

namespace App\Services\Store\Drivers;

use App\Models\ExternalStoreConnection;
use App\Services\Store\Contracts\ProductDataDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ExternalApiDriver
 * Products client এর নিজের server এ আছে (WordPress/WooCommerce/Custom)।
 * HTTP API call দিয়ে data আনে ও order তৈরি করে।
 */
class ExternalApiDriver implements ProductDataDriverInterface
{
    private string $endpoint;
    private string $apiKey;
    private int $timeout = 10;

    public function __construct(private int $clientId, private ExternalStoreConnection $connection)
    {
        $this->endpoint = rtrim($connection->endpoint_url, '/');
        $this->apiKey   = $connection->api_key;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC METHODS (interface)
    // ─────────────────────────────────────────────────────────────────────────

    public function searchProducts(string $query, array $filters = []): array
    {
        $response = $this->get('/products', [
            'search' => $query,
            'limit'  => $filters['limit'] ?? 5,
        ]);

        if (!$response || !isset($response['products'])) {
            Log::warning("ExternalApiDriver: products search failed for client {$this->clientId}");
            return [];
        }

        return $response['products'];
    }

    public function getProduct(int|string $id): ?array
    {
        $response = $this->get("/products/{$id}");
        return $response['product'] ?? null;
    }

    public function checkStock(int|string $productId): int
    {
        $response = $this->get("/stock/{$productId}");
        return (int) ($response['stock'] ?? 0);
    }

    public function createOrder(array $orderData): array
    {
        $response = $this->post('/orders', $orderData);

        if (!$response) {
            return ['success' => false, 'order_id' => null, 'order_number' => null, 'message' => 'Connection failed'];
        }

        return [
            'success'      => $response['success'] ?? false,
            'order_id'     => $response['order_id'] ?? null,
            'order_number' => $response['order_number'] ?? null,
            'message'      => $response['message'] ?? 'Order processed',
        ];
    }

    public function getOrderStatus(int|string $orderId): ?array
    {
        $response = $this->get("/orders/{$orderId}");
        if (!$response) return null;
        return [
            'status'  => $response['status'] ?? 'unknown',
            'message' => $response['message'] ?? '',
        ];
    }

    public function testConnection(): array
    {
        try {
            $response = $this->get('/ping');
            if ($response && ($response['success'] ?? false)) {
                return ['success' => true, 'message' => '✅ Plugin connected: ' . ($response['store_name'] ?? $this->endpoint)];
            }
            return ['success' => false, 'message' => '❌ Plugin responded but failed: ' . json_encode($response)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '❌ Connection error: ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HTTP HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function get(string $path, array $params = []): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeader('X-NeuralCart-Key', $this->apiKey)
                ->withHeader('Accept', 'application/json')
                ->get($this->endpoint . $path, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("ExternalApiDriver GET {$path} failed [{$response->status()}]: {$response->body()}");
            return null;
        } catch (\Throwable $e) {
            Log::error("ExternalApiDriver GET {$path} exception: " . $e->getMessage());
            return null;
        }
    }

    private function post(string $path, array $data = []): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeader('X-NeuralCart-Key', $this->apiKey)
                ->withHeader('Accept', 'application/json')
                ->post($this->endpoint . $path, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("ExternalApiDriver POST {$path} failed [{$response->status()}]: {$response->body()}");
            return null;
        } catch (\Throwable $e) {
            Log::error("ExternalApiDriver POST {$path} exception: " . $e->getMessage());
            return null;
        }
    }
}
