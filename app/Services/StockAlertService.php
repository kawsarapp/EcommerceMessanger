<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * Stock Alert Service
 * ===================
 * 1. Seller alert — stock quantity below threshold → Telegram + SMS
 * 2. Customer notify request — "Notify me when back in stock"
 */
class StockAlertService
{
    public function __construct(protected SmsService $sms) {}

    /**
     * Check product stock after order and alert seller if low
     */
    public function checkAfterOrder(Client $client, Product $product): void
    {
        $threshold = (int) ($client->widgets['stock_alert']['threshold'] ?? 5);

        if ($product->stock_quantity <= 0) {
            $this->alertSeller($client, $product, 'out_of_stock');
        } elseif ($product->stock_quantity <= $threshold) {
            $this->alertSeller($client, $product, 'low_stock');
        }
    }

    /**
     * Send seller alert via Telegram + SMS
     */
    private function alertSeller(Client $client, Product $product, string $type): void
    {
        $isOutOfStock = $type === 'out_of_stock';
        $emoji        = $isOutOfStock ? '🚫' : '⚠️';
        $label        = $isOutOfStock ? 'স্টক শেষ' : 'স্টক কম';
        $qty          = $product->stock_quantity;

        $message = "{$emoji} {$label}! '{$product->name}'\n"
            . ($isOutOfStock
                ? "পণ্যটি এখন **Out of Stock**। দ্রুত রিস্টক করুন!"
                : "মাত্র {$qty}টি বাকি আছে। রিস্টক করুন।");

        // Telegram alert
        $this->sendTelegramAlert($client, $message);

        // SMS alert to seller/admin
        if ($client->widgets['stock_alert']['sms_alert'] ?? false) {
            $this->sms->sendLowStockAlert($client, $product);
        }
    }

    /**
     * Send Telegram alert to shop admin
     */
    private function sendTelegramAlert(Client $client, string $message): void
    {
        if (empty($client->telegram_bot_token) || empty($client->telegram_chat_id)) return;

        try {
            \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$client->telegram_bot_token}/sendMessage",
                [
                    'chat_id'    => $client->telegram_chat_id,
                    'text'       => "📦 *{$client->shop_name}* — Stock Alert\n\n{$message}",
                    'parse_mode' => 'Markdown',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Stock Alert Telegram failed: ' . $e->getMessage());
        }
    }

    /**
     * Save a customer "Notify Me" request when product is out of stock
     */
    public function saveNotifyRequest(Client $client, Product $product, string $phone, ?string $name = null): bool
    {
        try {
            \App\Models\StockNotifyRequest::updateOrCreate(
                [
                    'client_id'  => $client->id,
                    'product_id' => $product->id,
                    'phone'      => $phone,
                ],
                [
                    'customer_name' => $name,
                    'notified'      => false,
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error('StockNotifyRequest save failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify all waiting customers when a product is back in stock
     * Called from admin when stock is updated
     */
    public function notifyWaitingCustomers(Client $client, Product $product): int
    {
        $requests = \App\Models\StockNotifyRequest::where('client_id', $client->id)
            ->where('product_id', $product->id)
            ->where('notified', false)
            ->get();

        $count = 0;
        foreach ($requests as $req) {
            $msg = "🎉 Good news! '{$product->name}' এখন আবার পাওয়া যাচ্ছে! "
                . "দ্রুত অর্ডার করুন: " . route('shop.show', $client->slug);

            if ($this->sms->send($client, $req->phone, $msg)) {
                $req->update(['notified' => true, 'notified_at' => now()]);
                $count++;
            }
        }

        return $count;
    }
}
