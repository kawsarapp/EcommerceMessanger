<?php

namespace App\Services\Courier;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourierIntegrationService
{
    public function sendParcel(Order $order)
    {
        $client = $order->client;
        $courier = $client->default_courier;

        if (!$courier) {
            return ['status' => 'error', 'message' => 'Please select a default courier in settings.'];
        }

        if ($courier === 'steadfast') {
            return $this->sendToSteadfast($order, $client);
        } elseif ($courier === 'pathao') {
            return $this->sendToPathao($order, $client);
        } elseif ($courier === 'redx') {
            return $this->sendToRedx($order, $client);
        }

        return ['status' => 'error', 'message' => 'Invalid courier selected.'];
    }

    /**
     * 🚚 STEADFAST INTEGRATION
     */
    private function sendToSteadfast(Order $order, $client)
    {
        $apiKey = $client->steadfast_api_key;
        $secretKey = $client->steadfast_secret_key;

        if (empty($apiKey) || empty($secretKey)) return ['status' => 'error', 'message' => 'Steadfast credentials missing.'];

        // 🛠️ MOCK MODE
        if ($apiKey === 'test_api_key') {
            $tracking = 'TEST-STEADFAST-' . rand(10000, 99999);
            $order->update([
                'courier_name' => 'steadfast',
                'tracking_code' => $tracking,
                'admin_note' => "Steadfast Tracking: {$tracking}\n" . ($order->admin_note ?? '')
            ]);
            return ['status' => 'success', 'message' => 'TEST MODE: Sent to Steadfast.'];
        }

        // 🌐 LIVE MODE
        try {
            $response = Http::withHeaders(['Api-Key' => $apiKey, 'Secret-Key' => $secretKey, 'Content-Type' => 'application/json'])
                ->post("https://portal.steadfast.com.bd/api/v1/create_order", [
                    'invoice' => (string) $order->id,
                    'recipient_name' => $order->customer_name,
                    'recipient_phone' => $order->customer_phone,
                    'recipient_address' => $order->shipping_address,
                    'cod_amount' => $order->total_amount,
                    'note' => "Order from " . $client->shop_name
                ]);
            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] == 200) {
                if (isset($result['consignment']['tracking_code'])) {
                    $order->update(['admin_note' => "Steadfast Tracking: " . $result['consignment']['tracking_code'] . "\n" . ($order->admin_note ?? '')]);
                }
                return ['status' => 'success', 'message' => 'Parcel sent to Steadfast.'];
            }
            return ['status' => 'error', 'message' => $result['message'] ?? 'API Error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to Steadfast Server.'];
        }
    }

    /**
     * 🚚 PATHAO INTEGRATION
     */
    private function sendToPathao(Order $order, $client)
    {
        $apiKey = $client->pathao_api_key;
        $storeId = $client->pathao_store_id;

        if (empty($apiKey) || empty($storeId)) return ['status' => 'error', 'message' => 'Pathao credentials missing.'];

        // 🛠️ MOCK MODE
        if ($apiKey === 'test_pathao_key') {
            $tracking = 'TEST-PATHAO-' . rand(10000, 99999);
            $order->update([
                'courier_name' => 'pathao',
                'tracking_code' => $tracking,
                'admin_note' => "Pathao Tracking: {$tracking}\n" . ($order->admin_note ?? '')
            ]);
            return ['status' => 'success', 'message' => 'TEST MODE: Sent to Pathao.'];
        }

        // 🌐 LIVE MODE
        try {
            $response = Http::withToken($apiKey)->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->post("https://api-hermes.pathao.com/aladdin/api/v1/orders", [
                    'store_id' => $storeId,
                    'merchant_order_id' => (string) $order->id,
                    'recipient_name' => $order->customer_name,
                    'recipient_phone' => $order->customer_phone,
                    'recipient_address' => $order->shipping_address,
                    'recipient_city' => 1, // Default Dhaka (Production e Dynamic hobe)
                    'recipient_zone' => 1, // Default (Production e Dynamic hobe)
                    'amount_to_collect' => $order->total_amount,
                    'item_quantity' => $order->orderItems()->sum('quantity') ?? 1,
                    'item_weight' => 0.5,
                    'item_description' => "Products from " . $client->shop_name
                ]);
            $result = $response->json();

            if ($response->successful() && isset($result['data']['consignment_id'])) {
                $order->update(['admin_note' => "Pathao Tracking: " . $result['data']['consignment_id'] . "\n" . ($order->admin_note ?? '')]);
                return ['status' => 'success', 'message' => 'Parcel sent to Pathao.'];
            }
            return ['status' => 'error', 'message' => $result['message'] ?? 'Pathao API Error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to Pathao Server.'];
        }
    }

    /**
     * 🚚 REDX INTEGRATION
     */
    private function sendToRedx(Order $order, $client)
    {
        $token = $client->redx_api_token;

        if (empty($token)) return ['status' => 'error', 'message' => 'RedX token missing.'];

        // 🛠️ MOCK MODE
        if ($token === 'test_redx_key') {
            $tracking = 'TEST-REDX-' . rand(10000, 99999);
            $order->update([
                'courier_name' => 'redx',
                'tracking_code' => $tracking,
                'admin_note' => "RedX Tracking: {$tracking}\n" . ($order->admin_note ?? '')
            ]);
            return ['status' => 'success', 'message' => 'TEST MODE: Sent to RedX.'];
        }

        // 🌐 LIVE MODE
        try {
            $response = Http::withToken($token)->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://openapi.redx.com.bd/v1.0.0-beta/parcel", [
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_address' => $order->shipping_address,
                    'merchant_invoice_id' => (string) $order->id,
                    'cash_collection_amount' => $order->total_amount,
                    'parcel_weight' => 500, // in grams
                    'instruction' => "Order from " . $client->shop_name,
                    'value' => $order->total_amount
                ]);
            $result = $response->json();

            if ($response->successful() && isset($result['tracking_id'])) {
                $order->update(['admin_note' => "RedX Tracking: " . $result['tracking_id'] . "\n" . ($order->admin_note ?? '')]);
                return ['status' => 'success', 'message' => 'Parcel sent to RedX.'];
            }
            return ['status' => 'error', 'message' => $result['message'] ?? 'RedX API Error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to RedX Server.'];
        }
    }
}