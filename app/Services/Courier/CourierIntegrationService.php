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
     * 🔥 SYNC STATUS FROM COURIER LIVE
     */
    public function syncStatus(Order $order)
    {
        if (!$order->tracking_code || !$order->courier_name) {
            return ['status' => 'error', 'message' => 'Tracking code or courier name is missing.'];
        }

        $courier = $order->courier_name;

        if ($courier === 'steadfast') {
            return $this->syncSteadfast($order);
        } elseif ($courier === 'pathao') {
            return $this->syncPathao($order);
        } elseif ($courier === 'redx') {
            return $this->syncRedx($order);
        }

        return ['status' => 'error', 'message' => 'Unsupported courier for syncing.'];
    }

    /**
     * 🚚 STEADFAST INTEGRATION
     */
    private function sendToSteadfast(Order $order, $client)
    {
        $apiKey = $client->steadfast_api_key;
        $secretKey = $client->steadfast_secret_key;

        if (empty($apiKey) || empty($secretKey)) return ['status' => 'error', 'message' => 'Steadfast credentials missing.'];

        if ($apiKey === 'test_api_key') {
            $tracking = 'TEST-STEADFAST-' . rand(10000, 99999);
            $order->update([
                'courier_name' => 'steadfast',
                'tracking_code' => $tracking,
                'admin_note' => "Steadfast Tracking: {$tracking}\n" . ($order->admin_note ?? '')
            ]);
            return ['status' => 'success', 'message' => 'TEST MODE: Sent to Steadfast.'];
        }

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
                    $tracking = $result['consignment']['tracking_code'];
                    // 🔥 FIX: tracking_code এবং courier_name ডাটাবেসে সেভ করা হলো
                    $order->update([
                        'courier_name' => 'steadfast',
                        'tracking_code' => $tracking,
                        'admin_note' => "Steadfast Tracking: {$tracking}\n" . ($order->admin_note ?? '')
                    ]);
                }
                return ['status' => 'success', 'message' => 'Parcel sent to Steadfast.'];
            }
            return ['status' => 'error', 'message' => $result['message'] ?? 'API Error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to Steadfast Server.'];
        }
    }

    private function syncSteadfast(Order $order)
    {
        $client = $order->client;
        $apiKey = $client->steadfast_api_key;
        $secretKey = $client->steadfast_secret_key;

        if (empty($apiKey) || empty($secretKey)) return ['status' => 'error', 'message' => 'Steadfast credentials missing.'];
        if ($apiKey === 'test_api_key') return ['status' => 'success', 'message' => 'Test Mode: Status is static.'];

        try {
            $response = Http::withHeaders(['Api-Key' => $apiKey, 'Secret-Key' => $secretKey])
                ->get("https://portal.steadfast.com.bd/api/v1/status_by_cid/{$order->tracking_code}");
            $result = $response->json();

            if ($response->successful() && isset($result['delivery_status'])) {
                $status = strtolower($result['delivery_status']);
                $mappedStatus = $order->order_status;
                
                if (in_array($status, ['delivered', 'partial_delivered'])) {
                    $mappedStatus = 'delivered';
                } elseif (in_array($status, ['cancelled', 'returned'])) {
                    $mappedStatus = 'cancelled';
                }

                if ($mappedStatus !== $order->order_status) {
                    $order->update(['order_status' => $mappedStatus, 'admin_note' => "Status auto-synced from Steadfast: {$status}\n" . ($order->admin_note ?? '')]);
                }
                return ['status' => 'success', 'message' => "Synced. Current Steadfast Status: {$status}"];
            }
            return ['status' => 'error', 'message' => 'Invalid API Response from Steadfast'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to reach Steadfast.'];
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

        if ($apiKey === 'test_pathao_key') {
            $tracking = 'TEST-PATHAO-' . rand(10000, 99999);
            $order->update([
                'courier_name' => 'pathao',
                'tracking_code' => $tracking,
                'admin_note' => "Pathao Tracking: {$tracking}\n" . ($order->admin_note ?? '')
            ]);
            return ['status' => 'success', 'message' => 'TEST MODE: Sent to Pathao.'];
        }

        try {
            $response = Http::withToken($apiKey)->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->post("https://api-hermes.pathao.com/aladdin/api/v1/orders", [
                    'store_id' => $storeId,
                    'merchant_order_id' => (string) $order->id,
                    'recipient_name' => $order->customer_name,
                    'recipient_phone' => $order->customer_phone,
                    'recipient_address' => $order->shipping_address,
                    'recipient_city' => 1, 
                    'recipient_zone' => 1, 
                    'amount_to_collect' => $order->total_amount,
                    'item_quantity' => $order->orderItems()->sum('quantity') ?? 1,
                    'item_weight' => 0.5,
                    'item_description' => "Products from " . $client->shop_name
                ]);
            $result = $response->json();

            if ($response->successful() && isset($result['data']['consignment_id'])) {
                $tracking = $result['data']['consignment_id'];
                // 🔥 FIX: tracking_code এবং courier_name ডাটাবেসে সেভ করা হলো
                $order->update([
                    'courier_name' => 'pathao',
                    'tracking_code' => $tracking,
                    'admin_note' => "Pathao Tracking: {$tracking}\n" . ($order->admin_note ?? '')
                ]);
                return ['status' => 'success', 'message' => 'Parcel sent to Pathao.'];
            }
            return ['status' => 'error', 'message' => $result['message'] ?? 'Pathao API Error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to Pathao Server.'];
        }
    }

    private function syncPathao(Order $order)
    {
        $client = $order->client;
        $apiKey = $client->pathao_api_key;

        if (empty($apiKey)) return ['status' => 'error', 'message' => 'Pathao credentials missing.'];
        if ($apiKey === 'test_pathao_key') return ['status' => 'success', 'message' => 'Test Mode: Status is static.'];

        try {
            $response = Http::withToken($apiKey)->get("https://api-hermes.pathao.com/aladdin/api/v1/orders/{$order->tracking_code}");
            $result = $response->json();

            if ($response->successful() && isset($result['data']['order_status'])) {
                $status = strtolower($result['data']['order_status']);
                $mappedStatus = $order->order_status;

                if (in_array($status, ['delivered', 'partial_delivery'])) {
                    $mappedStatus = 'delivered';
                } elseif (in_array($status, ['cancelled', 'returned', 'delivery_failed'])) {
                    $mappedStatus = 'cancelled';
                } elseif (str_contains($status, 'pickup') || str_contains($status, 'transit')) {
                    $mappedStatus = 'shipped';
                }

                if ($mappedStatus !== $order->order_status) {
                    $order->update(['order_status' => $mappedStatus, 'admin_note' => "Status auto-synced from Pathao: {$status}\n" . ($order->admin_note ?? '')]);
                }
                return ['status' => 'success', 'message' => "Synced. Current Pathao Status: {$status}"];
            }
            return ['status' => 'error', 'message' => 'Invalid API Response from Pathao'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to reach Pathao.'];
        }
    }

    /**
     * 🚚 REDX INTEGRATION
     */
    private function sendToRedx(Order $order, $client)
    {
        $token = $client->redx_api_token;

        if (empty($token)) return ['status' => 'error', 'message' => 'RedX token missing.'];

        if ($token === 'test_redx_key') {
            $tracking = 'TEST-REDX-' . rand(10000, 99999);
            $order->update([
                'courier_name' => 'redx',
                'tracking_code' => $tracking,
                'admin_note' => "RedX Tracking: {$tracking}\n" . ($order->admin_note ?? '')
            ]);
            return ['status' => 'success', 'message' => 'TEST MODE: Sent to RedX.'];
        }

        try {
            $response = Http::withToken($token)->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://openapi.redx.com.bd/v1.0.0-beta/parcel", [
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_address' => $order->shipping_address,
                    'merchant_invoice_id' => (string) $order->id,
                    'cash_collection_amount' => $order->total_amount,
                    'parcel_weight' => 500,
                    'instruction' => "Order from " . $client->shop_name,
                    'value' => $order->total_amount
                ]);
            $result = $response->json();

            if ($response->successful() && isset($result['tracking_id'])) {
                $tracking = $result['tracking_id'];
                // 🔥 FIX: tracking_code এবং courier_name ডাটাবেসে সেভ করা হলো
                $order->update([
                    'courier_name' => 'redx',
                    'tracking_code' => $tracking,
                    'admin_note' => "RedX Tracking: {$tracking}\n" . ($order->admin_note ?? '')
                ]);
                return ['status' => 'success', 'message' => 'Parcel sent to RedX.'];
            }
            return ['status' => 'error', 'message' => $result['message'] ?? 'RedX API Error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to connect to RedX Server.'];
        }
    }

    private function syncRedx(Order $order)
    {
        $client = $order->client;
        $token = $client->redx_api_token;

        if (empty($token)) return ['status' => 'error', 'message' => 'RedX token missing.'];
        if ($token === 'test_redx_key') return ['status' => 'success', 'message' => 'Test Mode: Status is static.'];

        try {
            $response = Http::withToken($token)->get("https://openapi.redx.com.bd/v1.0.0-beta/parcel/{$order->tracking_code}");
            $result = $response->json();

            if ($response->successful() && isset($result['parcel']['status'])) {
                $status = strtolower($result['parcel']['status']);
                $mappedStatus = $order->order_status;

                if (in_array($status, ['delivered', 'partial_delivered'])) {
                    $mappedStatus = 'delivered';
                } elseif (in_array($status, ['cancelled', 'returned'])) {
                    $mappedStatus = 'cancelled';
                } elseif (str_contains($status, 'transit') || str_contains($status, 'dispatch')) {
                    $mappedStatus = 'shipped';
                }

                if ($mappedStatus !== $order->order_status) {
                    $order->update(['order_status' => $mappedStatus, 'admin_note' => "Status auto-synced from RedX: {$status}\n" . ($order->admin_note ?? '')]);
                }
                return ['status' => 'success', 'message' => "Synced. Current RedX Status: {$status}"];
            }
            return ['status' => 'error', 'message' => 'Invalid API Response from RedX'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Failed to reach RedX.'];
        }
    }
}