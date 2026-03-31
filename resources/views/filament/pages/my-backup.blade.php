<x-filament-panels::page>
    <div class="space-y-8">
        @php
            $client = auth()->user()?->client;
            $orderCount   = $client ? \App\Models\Order::where('client_id', $client->id)->count() : 0;
            $productCount = $client ? \App\Models\Product::where('client_id', $client->id)->count() : 0;
            $catCount     = $client ? \App\Models\Category::where(fn($q) => $q->where('is_global',true)->orWhere('client_id',$client->id))->count() : 0;
            $totalRevenue = $client ? \App\Models\Order::where('client_id', $client->id)->where('status', '!=', 'cancelled')->sum('total_price') : 0;
        @endphp

        {{-- Hero Card --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 rounded-2xl p-8 text-white shadow-xl">
            <div class="absolute inset-0 opacity-10"
                 style="background-image: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"60\" height=\"60\"><circle cx=\"30\" cy=\"30\" r=\"20\" fill=\"none\" stroke=\"white\" stroke-width=\"1\"/></svg>'); background-size: 60px;">
            </div>
            <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <p class="text-indigo-200 text-sm font-semibold uppercase tracking-wider mb-1">Business Backup Center</p>
                    <h2 class="text-3xl font-extrabold">📦 {{ $client?->shop_name ?? 'Your Shop' }}</h2>
                    <p class="text-indigo-200 mt-2 text-sm max-w-md">
                        আপনার সম্পূর্ণ ব্যবসার তথ্য — orders, products, categories, media — সবকিছু 1 ক্লিকে download করুন।
                    </p>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div class="bg-white/15 backdrop-blur rounded-xl p-4 text-center min-w-[90px]">
                        <div class="text-2xl font-black">{{ number_format($orderCount) }}</div>
                        <div class="text-xs text-indigo-200 mt-0.5">Orders</div>
                    </div>
                    <div class="bg-white/15 backdrop-blur rounded-xl p-4 text-center min-w-[90px]">
                        <div class="text-2xl font-black">{{ number_format($productCount) }}</div>
                        <div class="text-xs text-indigo-200 mt-0.5">Products</div>
                    </div>
                    <div class="bg-white/15 backdrop-blur rounded-xl p-4 text-center min-w-[90px]">
                        <div class="text-2xl font-black">{{ number_format($totalRevenue, 0) }}</div>
                        <div class="text-xs text-indigo-200 mt-0.5">Total Sales ৳</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Download Options --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            {{-- Full ZIP --}}
            <x-filament::card class="group hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-2 border-transparent hover:border-indigo-300 dark:hover:border-indigo-600">
                <div class="flex flex-col items-center text-center gap-4 py-4">
                    <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <x-heroicon-o-archive-box-arrow-down class="w-8 h-8 text-indigo-500" />
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-1">Full Business Backup</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Orders + Products + Categories + Media + Shop Info — সব কিছু একটি ZIP এ</p>
                    </div>
                    <div class="w-full mt-2">
                        <x-filament::button wire:click="downloadMyZip" icon="heroicon-o-archive-box-arrow-down" color="primary" class="w-full">
                            ⬇️ Download Full ZIP
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>

            {{-- Orders CSV --}}
            <x-filament::card class="group hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-2 border-transparent hover:border-emerald-300 dark:hover:border-emerald-600">
                <div class="flex flex-col items-center text-center gap-4 py-4">
                    <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <x-heroicon-o-table-cells class="w-8 h-8 text-emerald-500" />
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-1">Orders CSV</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">সব অর্ডারের তালিকা Excel/Google Sheets এ খোলার জন্য</p>
                        <span class="mt-2 inline-block text-xs font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full">{{ number_format($orderCount) }} rows</span>
                    </div>
                    <div class="w-full mt-2">
                        <x-filament::button wire:click="downloadOrdersCsv" icon="heroicon-o-table-cells" color="success" class="w-full">
                            📊 Download Orders CSV
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>

            {{-- Products CSV --}}
            <x-filament::card class="group hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-2 border-transparent hover:border-blue-300 dark:hover:border-blue-600">
                <div class="flex flex-col items-center text-center gap-4 py-4">
                    <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <x-heroicon-o-shopping-bag class="w-8 h-8 text-blue-500" />
                    </div>
                    <div>
                        <h3 class="font-bold text-lg mb-1">Products CSV</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">সকল পণ্যের তালিকা SKU, দাম, স্টক সহ</p>
                        <span class="mt-2 inline-block text-xs font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full">{{ number_format($productCount) }} products</span>
                    </div>
                    <div class="w-full mt-2">
                        <x-filament::button wire:click="downloadProductsCsv" icon="heroicon-o-shopping-bag" color="info" class="w-full">
                            📦 Download Products CSV
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>
        </div>

        {{-- What's included --}}
        <x-filament::card>
            <div class="px-2">
                <h3 class="font-bold text-base mb-4 flex items-center gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500" />
                    Full ZIP Backup এ কী কী থাকবে?
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach([
                        ['icon' => '📋', 'title' => 'orders.csv', 'desc' => 'সব orders — customer info, address, total, status সহ'],
                        ['icon' => '📦', 'title' => 'products.csv', 'desc' => 'সব products — নাম, SKU, দাম, স্টক সহ'],
                        ['icon' => '🏷️', 'title' => 'categories.csv', 'desc' => 'সব categories এর তালিকা'],
                        ['icon' => '🏪', 'title' => 'shop-info.json', 'desc' => 'আপনার shop এর সকল settings'],
                        ['icon' => '🖼️', 'title' => 'media/ (folder)', 'desc' => 'Logo, banners, product images, category images'],
                    ] as $item)
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <span class="text-2xl shrink-0">{{ $item['icon'] }}</span>
                        <div>
                            <div class="font-mono text-sm font-bold text-gray-700 dark:text-gray-200">{{ $item['title'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $item['desc'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-gray-400 flex items-center gap-1.5">
                    <x-heroicon-o-shield-check class="w-4 h-4 text-green-500" />
                    আপনার ডেটা সম্পূর্ণ secure। শুধুমাত্র আপনার নিজের data download হবে।
                </p>
            </div>
        </x-filament::card>

    </div>
</x-filament-panels::page>
