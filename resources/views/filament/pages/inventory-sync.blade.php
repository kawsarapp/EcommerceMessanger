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

        <p class="text-xs text-gray-400 mt-4">Note: It may take up to a minute depending on the number of products on your website. Make sure you have entered your API keys in the Shop Configuration tab.</p>
    </div>
</x-filament-panels::page>