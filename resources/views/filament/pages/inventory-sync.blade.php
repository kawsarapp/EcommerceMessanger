<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-900 p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 text-center max-w-3xl mx-auto mt-10">
        
        <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <x-heroicon-o-arrow-path class="w-10 h-10" />
        </div>

        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">Sync Your Store Inventory</h2>
        <p class="text-gray-500 mb-8">Click the button below to fetch all your latest products, prices, and stock directly from your WooCommerce website.</p>

        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl text-left mb-8 flex justify-between items-center border border-gray-100 dark:border-gray-700">
            <div>
                <p class="text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">WooCommerce (WordPress)</p>
                <p class="text-xs text-gray-400">
                    Last Synced: 
                    <span class="font-bold text-indigo-500">
                        {{ $client->last_inventory_sync_at ? $client->last_inventory_sync_at->diffForHumans() : 'Never Synced' }}
                    </span>
                </p>
            </div>
            <x-filament::button wire:click="syncWooCommerce" color="info" size="lg" icon="heroicon-o-cloud-arrow-down" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="syncWooCommerce">Start Sync Now</span>
                <span wire:loading wire:target="syncWooCommerce">Syncing Products... Please wait</span>
            </x-filament::button>
        </div>

            <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl text-left mb-8 flex justify-between items-center border border-gray-100 dark:border-gray-700 mt-4">
            <div>
                <p class="text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Shopify Store</p>
                <p class="text-xs text-gray-400">
                    Sync products instantly using Shopify Admin API.
                </p>
            </div>
            
            <x-filament::button wire:click="syncShopify" color="success" size="lg" icon="heroicon-o-cloud-arrow-down" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="syncShopify">Start Sync Now</span>
                <span wire:loading wire:target="syncShopify">Syncing Products... Please wait</span>
            </x-filament::button>
        </div>
            


        


        <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl text-left mb-8 border border-gray-100 dark:border-gray-700 mt-4">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <p class="text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-1">Developer API (Custom Push)</p>
                    <p class="text-xs text-gray-400">
                        Use this API to push products from any Custom Laravel or Node.js website.
                    </p>
                </div>
                <x-filament::button wire:click="generateApiKey" color="warning" size="sm" icon="heroicon-o-key">
                    Generate API Key
                </x-filament::button>
            </div>

            @if($client->api_token)
            <div class="bg-white dark:bg-gray-900 p-4 rounded border border-gray-200 dark:border-gray-700 mb-4">
                <p class="text-xs text-gray-500 mb-1">Your API Endpoint (POST Request):</p>
                <code class="text-sm text-green-600 bg-green-50 px-2 py-1 rounded block mb-3">{{ url('/api/v1/import-products') }}</code>
                
                <p class="text-xs text-gray-500 mb-1">Your Secret API Key (Send in Header as <strong>x-api-key</strong>):</p>
                <code class="text-sm text-red-600 bg-red-50 px-2 py-1 rounded block break-all">{{ $client->api_token }}</code>
            </div>
            
            <details class="text-xs text-gray-500">
                <summary class="cursor-pointer text-indigo-500 font-bold hover:underline">View JSON Payload Format</summary>
                <pre class="mt-2 bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto">
{
  "products": [
    {
      "name": "Custom T-Shirt",
      "sku": "TS-001",
      "regular_price": 500,
      "sale_price": 450,
      "stock_quantity": 50,
      "image_url": "https://example.com/image.jpg",
      "description": "Awesome cotton t-shirt"
    }
  ]
}
                </pre>
            </details>
            @else
            <p class="text-sm text-red-500 mt-2"><i class="fas fa-info-circle"></i> Click the button above to generate your secret API key.</p>
            @endif
        </div>




        <p class="text-xs text-gray-400 mt-4">Note: It may take up to a minute depending on the number of products on your website. Make sure you have entered your API keys in the Shop Configuration tab.</p>
    </div>
</x-filament-panels::page>