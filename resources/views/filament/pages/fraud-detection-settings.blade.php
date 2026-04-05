<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ══════════════════ Page Header ══════════════════ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-red-900 via-rose-800 to-orange-700 rounded-2xl p-8 text-white shadow-xl">
            <div class="absolute inset-0 opacity-10"
                 style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
            </div>
            <div class="relative flex items-center gap-5">
                <div class="w-16 h-16 bg-white/15 backdrop-blur rounded-2xl flex items-center justify-center shadow-lg">
                    <x-heroicon-o-shield-exclamation class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight">🛡️ Fraud Detection API</h2>
                    <p class="text-rose-200 text-sm mt-1">BDCourier.com integration — manage API key, test connection & view plan usage.</p>
                </div>
                {{-- Connection status badge --}}
                @if($tested)
                    <div class="ml-auto">
                        @if($connOk)
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/20 border border-emerald-400/40 rounded-full text-emerald-200 font-bold text-sm">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                                Connected
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/20 border border-red-400/40 rounded-full text-red-200 font-bold text-sm">
                                <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                                Disconnected
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════ API Key Manager ══════════════════ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
                <div class="w-9 h-9 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center">
                    <x-heroicon-o-key class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">API Key Configuration</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Your BDCourier API key — saved to .env file</p>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        BDCourier API Key
                    </label>
                    <div class="flex gap-3">
                        <input
                            type="text"
                            wire:model="apiKey"
                            placeholder="Enter your BDCourier API Key..."
                            class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition"
                        >
                        <button
                            wire:click="saveApiKey"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm rounded-xl transition-all duration-200 shadow hover:shadow-md disabled:opacity-60"
                        >
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                            <span wire:loading.remove wire:target="saveApiKey">Save Key</span>
                            <span wire:loading wire:target="saveApiKey">Saving...</span>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                        🔒 Key is stored in your <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded font-mono">.env</code> file as <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded font-mono">BDCOURIER_API_KEY</code>
                    </p>
                </div>

                {{-- Current key preview --}}
                @if(config('services.bdcourier.api_key'))
                    <div class="flex items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-500 shrink-0" />
                        <div>
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Active Key</p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-500 font-mono">
                                {{ substr(config('services.bdcourier.api_key'), 0, 12) }}•••••••••••{{ substr(config('services.bdcourier.api_key'), -8) }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-500 shrink-0" />
                        <p class="text-xs text-amber-700 dark:text-amber-400 font-semibold">No API key configured. Add one above.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════ Quick Action Cards ══════════════════ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Test Connection Card --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform">
                        <x-heroicon-o-wifi class="w-7 h-7 text-blue-500" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white">Test Connection</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">API connection এবং authentication verify করুন।</p>
                        <button
                            wire:click="testConnection"
                            wire:loading.attr="disabled"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl transition disabled:opacity-60"
                        >
                            <x-heroicon-o-signal class="w-4 h-4" />
                            <span wire:loading.remove wire:target="testConnection">Test Now</span>
                            <span wire:loading wire:target="testConnection">Testing...</span>
                        </button>
                    </div>
                </div>

                {{-- Connection Result --}}
                @if($tested && $connData)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Status</span>
                            <span class="font-bold text-emerald-600">✅ Connected</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Authenticated</span>
                            <span class="font-bold {{ ($connData['authenticated'] ?? false) ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ ($connData['authenticated'] ?? false) ? '✅ Yes' : '❌ No' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">User ID</span>
                            <span class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ $connData['user_id'] ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Server Time</span>
                            <span class="font-mono text-gray-600 dark:text-gray-300 text-xs">{{ $connData['server_time'] ?? '-' }}</span>
                        </div>
                    </div>
                @elseif($tested && !$connOk)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2 text-red-500 text-sm font-bold">
                            <x-heroicon-o-x-circle class="w-5 h-5" />
                            Connection failed — check your API key.
                        </div>
                    </div>
                @endif
            </div>

            {{-- My Plan Card --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform">
                        <x-heroicon-o-credit-card class="w-7 h-7 text-emerald-500" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white">My Plan</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Subscription details ও API call usage দেখুন।</p>
                        <button
                            wire:click="fetchPlan"
                            wire:loading.attr="disabled"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition disabled:opacity-60"
                        >
                            <x-heroicon-o-arrow-path class="w-4 h-4" />
                            <span wire:loading.remove wire:target="fetchPlan">Fetch Plan</span>
                            <span wire:loading wire:target="fetchPlan">Loading...</span>
                        </button>
                    </div>
                </div>

                {{-- Plan Result --}}
                @if($planData)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700 space-y-2">

                        {{-- Plan Name Badge --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Plan</span>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold
                                {{ ($planData['is_free'] ?? true) ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                {{ ($planData['is_free'] ?? true) ? '🆓 Free' : '⭐ ' . ($planData['plan_name'] ?? 'Paid') }}
                            </span>
                        </div>

                        @if(!($planData['is_free'] ?? true))
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Status</span>
                                <span class="font-bold {{ ($planData['status'] ?? '') === 'active' ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ ucfirst($planData['status'] ?? '-') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Expires</span>
                                <span class="font-mono text-xs text-gray-600 dark:text-gray-300">
                                    {{ $planData['next_due_date'] ?? '-' }} ({{ $planData['days_remaining'] ?? '?' }} days left)
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Price</span>
                                <span class="font-bold text-gray-800 dark:text-white">৳{{ $planData['price'] ?? '-' }}/{{ $planData['frequency'] ?? 'month' }}</span>
                            </div>
                        @endif

                        {{-- Usage Bars --}}
                        <div class="pt-2 space-y-3">
                            {{-- Free Calls --}}
                            @php
                                $freeUsed  = ($planData['api_calls'] ?? 0) - ($planData['remaining_free_calls'] ?? 0);
                                $freeLimit = $planData['call_limit'] ?? 5;
                                $freePct   = $freeLimit > 0 ? min(100, round($freeUsed / $freeLimit * 100)) : 0;
                            @endphp
                            <div>
                                <div class="flex justify-between text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    <span>Free API Calls</span>
                                    <span>{{ $planData['remaining_free_calls'] ?? 0 }} remaining / {{ $freeLimit }}</span>
                                </div>
                                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-400 rounded-full transition-all duration-500"
                                         style="width: {{ $freePct }}%"></div>
                                </div>
                            </div>

                            {{-- Paid Calls --}}
                            @if(!($planData['is_free'] ?? true))
                                @php
                                    $paidUsed  = ($planData['paid_calls'] ?? 0);
                                    $paidLimit = $planData['paid_limit'] ?? 100;
                                    $paidPct   = $paidLimit > 0 ? min(100, round($paidUsed / $paidLimit * 100)) : 0;
                                @endphp
                                <div>
                                    <div class="flex justify-between text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                        <span>Paid API Calls Used</span>
                                        <span>{{ $planData['remaining_paid_calls'] ?? 0 }} remaining / {{ $paidLimit }}</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-400 rounded-full transition-all duration-500"
                                             style="width: {{ $paidPct }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════ How It Works ══════════════════ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
                <x-heroicon-o-information-circle class="w-5 h-5 text-gray-400" />
                <h3 class="font-bold text-gray-900 dark:text-white">How Fraud Check Works</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="flex flex-col items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center text-white font-black text-lg">1</div>
                        <h4 class="font-bold text-gray-900 dark:text-white">Order-level button</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Orders table-এ প্রতিটি অর্ডারে "Fraud Check" বাটন আছে।</p>
                    </div>
                    <div class="flex flex-col items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                        <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-white font-black text-lg">2</div>
                        <h4 class="font-bold text-gray-900 dark:text-white">BDCourier API Call</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Customer phone পাঠানো হয় — সব major courier-এ check হয়।</p>
                    </div>
                    <div class="flex flex-col items-start gap-3 p-4 bg-rose-50 dark:bg-rose-900/20 rounded-xl">
                        <div class="w-10 h-10 bg-rose-500 rounded-xl flex items-center justify-center text-white font-black text-lg">3</div>
                        <h4 class="font-bold text-gray-900 dark:text-white">Risk Assessment</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Success ratio, cancelled parcels ও fraud reports দেখে 🔴🟡🟢 risk দেখায়।</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div class="flex items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                        <span class="text-2xl">🟢</span>
                        <div>
                            <p class="font-bold text-emerald-700 dark:text-emerald-400">LOW RISK</p>
                            <p class="text-xs text-gray-500">Success rate ≥80%, কোনো fraud report নেই</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <span class="text-2xl">🟡</span>
                        <div>
                            <p class="font-bold text-amber-700 dark:text-amber-400">MODERATE RISK</p>
                            <p class="text-xs text-gray-500">Success rate 60–80%, বা ১০+ cancelled</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
                        <span class="text-2xl">🔴</span>
                        <div>
                            <p class="font-bold text-red-700 dark:text-red-400">HIGH RISK</p>
                            <p class="text-xs text-gray-500">Fraud report আছে, বা success rate &lt;60%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════ Supported Couriers ══════════════════ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-truck class="w-5 h-5 text-gray-400" />
                Supported Couriers (BDCourier tracks these)
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach(['Pathao', 'Steadfast', 'Redx', 'PaperFly', 'ParcelDex', 'CarryBee'] as $courier)
                    <div class="flex flex-col items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 text-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-rose-400 to-orange-400 rounded-full flex items-center justify-center text-white font-black text-xs">
                            {{ substr($courier, 0, 2) }}
                        </div>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $courier }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-panels::page>
