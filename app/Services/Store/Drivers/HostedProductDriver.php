<?php

namespace App\Services\Store\Drivers;

use App\Models\Product;
use App\Models\Order;
use App\Services\Store\Contracts\ProductDataDriverInterface;
use Illuminate\Support\Facades\Log;

/**
 * HostedProductDriver
 * Products আমাদের server এ host হলে এই driver use হবে।
 * বিদ্যমান Product/Order models ব্যবহার করে।
 */
class HostedProductDriver implements ProductDataDriverInterface
{
    public function __construct(private int $clientId) {}

    public function searchProducts(string $query, array $filters = []): array
    {
        $q = Product::where('client_id', $this->clientId)
            ->where(function ($sql) use ($query) {
                $sql->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('description', 'ILIKE', "%{$query}%")
                    ->orWhere('sku', 'ILIKE', "%{$query}%")
                    ->orWhere('id', 'ILIKE', "%{$query}%");
            })
            ->where('is_active', true)
            ->limit($filters['limit'] ?? 5)
            ->get();

        return $q->map(fn($p) => $this->mapProduct($p))->toArray();
    }

    public function getProduct(int|string $id): ?array
    {
        $p = Product::where('client_id', $this->clientId)->find($id);
        return $p ? $this->mapProduct($p) : null;
    }

    public function checkStock(int|string $productId): int
    {
        return (int) Product::where('client_id', $this->clientId)
            ->where('id', $productId)
            ->value('stock_quantity') ?? 0;
    }

    public function createOrder(array $orderData): array
    {
        try {
            $order = Order::create([
                'client_id'      => $this->clientId,
                'product_id'     => $orderData['product_id'],
                'customer_name'  => $orderData['customer_name'],
                'customer_phone' => $orderData['customer_phone'],
                'address'        => $orderData['address'],
                'quantity'       => $orderData['quantity'] ?? 1,
                'note'           => $orderData['note'] ?? null,
                'order_status'   => 'pending',
                'source'         => $orderData['source'] ?? 'chatbot',
                'sender_id'      => $orderData['sender_id'] ?? null,
            ]);
            return ['success' => true, 'order_id' => $order->id, 'order_number' => '#' . $order->id, 'message' => 'Order created'];
        } catch (\Throwable $e) {
            Log::error("HostedProductDriver::createOrder failed: " . $e->getMessage());
            return ['success' => false, 'order_id' => null, 'order_number' => null, 'message' => $e->getMessage()];
        }
    }

    public function getOrderStatus(int|string $orderId): ?array
    {
        $order = Order::where('client_id', $this->clientId)->find($orderId);
        if (!$order) return null;
        return ['status' => $order->order_status, 'message' => "Order #{$orderId} status: {$order->order_status}"];
    }

    public function testConnection(): array
    {
        try {
            $count = Product::where('client_id', $this->clientId)->count();
            return ['success' => true, 'message' => "✅ Hosted store connected. {$count} products found."];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => "❌ DB error: " . $e->getMessage()];
        }
    }

    private function mapProduct(Product $p): array
    {
        return [
            'id'          => $p->id,
            'title'       => $p->title,
            'price'       => (float) $p->price,
            'sale_price'  => $p->sale_price ? (float) $p->sale_price : null,
            'stock'       => (int) ($p->stock_quantity ?? 0),
            'sku'         => $p->sku ?? '',
            'image'       => $p->image ? asset('storage/' . $p->image) : null,
            'description' => $p->description,
            'in_stock'    => ($p->stock_quantity ?? 1) > 0,
        ];
    }
}
