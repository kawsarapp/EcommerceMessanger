<?php

namespace App\Services\Store;

use App\Models\Client;
use App\Models\ExternalStoreConnection;
use App\Services\Store\Contracts\ProductDataDriverInterface;
use App\Services\Store\Drivers\HostedProductDriver;
use App\Services\Store\Drivers\ExternalApiDriver;

/**
 * StoreDriverFactory
 *
 * Client এর integration_type দেখে সঠিক Driver return করে।
 * ChatbotService এবং যেকোনো জায়গা থেকে এই factory use করবে।
 *
 * Usage:
 *   $driver = StoreDriverFactory::for($client);
 *   $products = $driver->searchProducts('জামা');
 */
class StoreDriverFactory
{
    /**
     * Client এর জন্য সঠিক ProductDataDriver return করো
     */
    public static function for(Client $client): ProductDataDriverInterface
    {
        if ($client->integration_type === 'external_api') {
            $connection = ExternalStoreConnection::where('client_id', $client->id)
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($connection) {
                return new ExternalApiDriver($client->id, $connection);
            }

            // Connection নেই? Fallback to hosted driver
            // (graceful degradation)
        }

        return new HostedProductDriver($client->id);
    }

    /**
     * Client ID দিয়ে সরাসরি
     */
    public static function forClientId(int $clientId): ProductDataDriverInterface
    {
        $client = Client::find($clientId);
        if (!$client) {
            return new HostedProductDriver($clientId);
        }
        return self::for($client);
    }

    /**
     * Available drivers list (for admin UI)
     */
    public static function availableDrivers(): array
    {
        return [
            'hosted'       => 'Hosted (Products on our server)',
            'external_api' => 'External API (WordPress Plugin / Custom)',
        ];
    }
}
