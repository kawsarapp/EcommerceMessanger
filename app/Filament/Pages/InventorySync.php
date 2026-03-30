<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class InventorySync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = '🔌 Integrations';
    protected static ?string $navigationLabel = 'Inventory Sync';
    protected static ?string $title = 'Sync Website Products';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.inventory-sync';

    public $client;
    public $isLoading = false;

    public function mount()
    {
        $this->client = auth()->user()?->isSuperAdmin() ? \App\Models\Client::first() : auth()->user()->client;
    }

    public function syncWooCommerce()
    {
        $this->isLoading = true;

        if (!$this->client || !$this->client->wc_store_url || !$this->client->wc_consumer_key) {
            Notification::make()->title('WooCommerce API Credentials Missing!')->warning()->send();
            $this->isLoading = false;
            return;
        }

        try {
            $url = rtrim($this->client->wc_store_url, '/') . '/wp-json/wc/v3/products';
            
            $response = Http::withBasicAuth($this->client->wc_consumer_key, $this->client->wc_consumer_secret)
                            ->withoutVerifying()
                            ->get($url, ['per_page' => 50, 'status' => 'publish']);

            if ($response->failed()) {
                Notification::make()->title('Failed to connect with WooCommerce.')->danger()->send();
                $this->isLoading = false;
                return;
            }

            $products = $response->json();
            $count = 0;

            foreach ($products as $wcProduct) {
                // প্রোডাক্টের ছবি নেওয়া
                $imageUrl = !empty($wcProduct['images']) ? $wcProduct['images'][0]['src'] : null;

                // ডাটাবেসে সেভ করা বা আপডেট করা (SKU বা Name দিয়ে চেক করবে)
                Product::updateOrCreate(
                    [
                        'client_id' => $this->client->id,
                        'sku' => $wcProduct['sku'] ?: 'WC-' . $wcProduct['id']
                    ],
                    [
                        'name' => $wcProduct['name'],
                        'slug' => Str::slug($wcProduct['name']) . '-' . Str::random(4),
                        'regular_price' => $wcProduct['regular_price'] ?: 0,
                        'sale_price' => $wcProduct['sale_price'] ?: null,
                        'description' => strip_tags($wcProduct['description']),
                        'short_description' => strip_tags($wcProduct['short_description']),
                        'stock_quantity' => $wcProduct['stock_quantity'] ?? 100,
                        'stock_status' => $wcProduct['stock_status'] === 'instock' ? 'in_stock' : 'out_of_stock',
                        // যদি URL থাকে তবে সরাসরি সেভ করা হচ্ছে (ওয়েবসাইটের ইমেজ লিংক ব্যবহার করবে)
                        'thumbnail' => $imageUrl,
                        'is_featured' => $wcProduct['featured'] ?? false,
                    ]
                );
                $count++;
            }

            $this->client->update(['last_inventory_sync_at' => now()]);

            Notification::make()
                ->title("Success!")
                ->body("Successfully imported/updated {$count} products from WooCommerce.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()->title('Error Syncing: ' . $e->getMessage())->danger()->send();
        }

        $this->isLoading = false;
    }


    public function syncShopify()
    {
        $this->isLoading = true;

        if (!$this->client || !$this->client->shopify_store_url || !$this->client->shopify_access_token) {
            Notification::make()->title('Shopify API Credentials Missing!')->warning()->send();
            $this->isLoading = false;
            return;
        }

        try {
            // URL ঠিক করা (যাতে শেষে / না থাকে)
            $storeUrl = rtrim($this->client->shopify_store_url, '/');
            $storeUrl = preg_replace('#^https?://#', '', $storeUrl); // http/https সরিয়ে ফেলা
            $url = "https://" . $storeUrl . "/admin/api/2024-01/products.json";

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->client->shopify_access_token,
                'Content-Type' => 'application/json',
            ])->get($url, ['limit' => 50, 'status' => 'active']);

            if ($response->failed()) {
                Notification::make()->title('Failed to connect with Shopify. Check Domain and Token.')->danger()->send();
                $this->isLoading = false;
                return;
            }

            $products = $response->json()['products'] ?? [];
            $count = 0;

            foreach ($products as $shProduct) {
                // শপিফাইয়ের ভেরিয়েন্ট থেকে দাম এবং স্টক বের করা
                $variant = $shProduct['variants'][0] ?? null;
                if (!$variant) continue;

                $imageUrl = !empty($shProduct['images']) ? $shProduct['images'][0]['src'] : null;

                $price = $variant['price'] ?? 0;
                $compareAtPrice = $variant['compare_at_price'] ?? $price;

                $regularPrice = $compareAtPrice > $price ? $compareAtPrice : $price;
                $salePrice = $compareAtPrice > $price ? $price : null;

                // ডাটাবেসে সেভ করা বা আপডেট করা
                Product::updateOrCreate(
                    [
                        'client_id' => $this->client->id,
                        'sku' => $variant['sku'] ?: 'SH-' . $shProduct['id']
                    ],
                    [
                        'name' => $shProduct['title'],
                        'slug' => Str::slug($shProduct['title']) . '-' . Str::random(4),
                        'regular_price' => $regularPrice,
                        'sale_price' => $salePrice,
                        'description' => $shProduct['body_html'] ?? '',
                        'short_description' => Str::limit(strip_tags($shProduct['body_html'] ?? ''), 150),
                        'stock_quantity' => $variant['inventory_quantity'] ?? 100,
                        'stock_status' => ($variant['inventory_quantity'] ?? 1) > 0 ? 'in_stock' : 'out_of_stock',
                        'thumbnail' => $imageUrl,
                        'is_featured' => false,
                    ]
                );
                $count++;
            }

            $this->client->update(['last_inventory_sync_at' => now()]);

            Notification::make()
                ->title("Success!")
                ->body("Successfully imported/updated {$count} products from Shopify.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()->title('Error Syncing: ' . $e->getMessage())->danger()->send();
        }

        $this->isLoading = false;
    }


    public function generateApiKey()
    {
        if($this->client) {
            $this->client->update(['api_token' => \Illuminate\Support\Str::random(60)]);
            \Filament\Notifications\Notification::make()
                ->title('New API Key Generated!')
                ->success()
                ->send();
        }
    }



}