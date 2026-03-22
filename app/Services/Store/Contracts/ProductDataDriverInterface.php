<?php

namespace App\Services\Store\Contracts;

/**
 * ProductDataDriverInterface
 *
 * সব Store Driver এই interface implement করবে।
 * Chatbot শুধু এই interface এর সাথে কথা বলবে —
 * data hosted নাকি external সেটা জানার দরকার নেই।
 *
 * Future drivers: ShopifyDriver, MagentoDriver, CustomApiDriver ...
 */
interface ProductDataDriverInterface
{
    /**
     * পণ্য খোঁজো
     * @return array<array{id: int|string, title: string, price: float, sale_price: float|null, stock: int, sku: string, image: string|null, description: string|null, in_stock: bool}>
     */
    public function searchProducts(string $query, array $filters = []): array;

    /**
     * একটি পণ্যের details
     */
    public function getProduct(int|string $id): ?array;

    /**
     * Stock count
     */
    public function checkStock(int|string $productId): int;

    /**
     * Order তৈরি করো
     * @return array{success: bool, order_id: int|string|null, order_number: string|null, message: string}
     */
    public function createOrder(array $orderData): array;

    /**
     * Order status চেক করো
     * @return array{status: string, message: string}|null
     */
    public function getOrderStatus(int|string $orderId): ?array;

    /**
     * Driver টা সঠিকভাবে কাজ করছে কিনা test করো
     */
    public function testConnection(): array;
}
