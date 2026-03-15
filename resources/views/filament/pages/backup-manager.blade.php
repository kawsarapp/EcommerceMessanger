<x-filament-panels::page>
    <div class="space-y-8">

        {{-- Page Header --}}
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-8 text-white">
            <div class="flex items-center gap-4 mb-2">
                <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-archive-box-arrow-down class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold">🛡️ Backup Manager</h2>
                    <p class="text-slate-400 text-sm">Super Admin Only — Download and manage system backups.</p>
                </div>
            </div>
        </div>

        {{-- Quick Action Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Full DB Backup --}}
            <a href="{{ route('filament.admin.pages.backup-manager.db') }}"
               class="group block bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-primary transition-all duration-300">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <x-heroicon-o-circle-stack class="w-7 h-7 text-blue-500" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-1">Full Database Backup</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">সম্পূর্ণ MySQL ডাটাবেস .sql ফাইল হিসেবে ডাউনলোড করুন।</p>
                        <div class="mt-4 flex items-center gap-2 text-blue-500 font-bold text-sm">
                            <x-heroicon-o-arrow-down-circle class="w-4 h-4" /> Download SQL
                        </div>
                    </div>
                </div>
            </a>

            {{-- Full Website Backup --}}
            <a href="{{ route('filament.admin.pages.backup-manager.zip') }}"
               class="group block bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-amber-400 transition-all duration-300">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <x-heroicon-o-archive-box class="w-7 h-7 text-amber-500" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-1">Full Website Backup</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">সমস্ত সোর্স কোড + ডাটাবেস + মিডিয়া ফাইল একটি .zip এ ডাউনলোড।</p>
                        <div class="mt-4 flex items-center gap-2 text-amber-500 font-bold text-sm">
                            <x-heroicon-o-archive-box class="w-4 h-4" /> Download ZIP
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Per-Client Backup --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
                <x-heroicon-o-user-group class="w-5 h-5 text-gray-400" />
                <h3 class="font-bold text-gray-900 dark:text-white">Individual Client Data Backup</h3>
                <span class="ml-auto text-xs font-bold bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300 px-3 py-1 rounded-full">
                    {{ \App\Models\Client::count() }} Clients
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse(\App\Models\Client::with('plan', 'user')->orderBy('shop_name')->get() as $client)
                <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    {{-- Avatar --}}
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center font-bold text-primary shrink-0">
                        {{ strtoupper(substr($client->shop_name, 0, 2)) }}
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white text-sm truncate">{{ $client->shop_name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                            <span>@php
                                $ordCount = \App\Models\Order::where('client_id',$client->id)->count();
                                $prodCount = \App\Models\Product::where('client_id',$client->id)->count();
                            @endphp
                            {{ $prodCount }} products · {{ $ordCount }} orders</span>
                            @if($client->plan)
                                <span class="bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded font-bold text-[10px]">{{ $client->plan->name }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="shrink-0">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold
                            {{ $client->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $client->status === 'active' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                            {{ ucfirst($client->status) }}
                        </span>
                    </div>

                    {{-- Download --}}
                    <a href="{{ route('filament.admin.pages.backup-manager.client', $client->id) }}"
                       class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-xs rounded-xl hover:opacity-90 transition">
                        <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" /> Backup
                    </a>
                </div>
                @empty
                <div class="px-6 py-12 text-center text-gray-400">No clients found.</div>
                @endforelse
            </div>
        </div>

        {{-- System Info --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $dbSize = collect(DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = DATABASE()"))->first()->size ?? '?';
                $storageSize = round(array_sum(array_map('filesize', File::allFiles(storage_path('app/public')))) / 1024 / 1024, 2);
                $tableCount = count(DB::select('SHOW TABLES'));
                $clientCount = \App\Models\Client::count();
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                <div class="text-3xl font-extrabold text-blue-500">{{ $dbSize }}MB</div>
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-1">Database Size</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                <div class="text-3xl font-extrabold text-amber-500">{{ $storageSize }}MB</div>
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-1">Uploaded Files</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                <div class="text-3xl font-extrabold text-emerald-500">{{ $tableCount }}</div>
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-1">DB Tables</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 text-center">
                <div class="text-3xl font-extrabold text-purple-500">{{ $clientCount }}</div>
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-1">Total Clients</div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
