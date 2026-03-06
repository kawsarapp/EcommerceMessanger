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
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?string $navigationLabel = 'Inventory Sync';
    protected static ?string $title = 'Sync Website Products';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.inventory-sync';

    public $client;
    public $isLoading = false;

    public function mount()
    {
        $this->client = auth()->id() === 1 ? \App\Models\Client::first() : auth()->user()->client;
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
}