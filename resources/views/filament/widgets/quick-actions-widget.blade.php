<div class="fi-widget p-0 overflow-hidden">
    <div class="px-6 py-5 rounded-xl text-white" style="background: linear-gradient(135deg, rgb(var(--primary-600)), rgb(var(--primary-800)));">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <p class="text-primary-200 text-sm font-medium">{{ $greeting }}, {{ auth()->user()->name }}! 👋</p>
                <h2 class="text-white text-xl font-bold mt-0.5">আপনার Dashboard-এ স্বাগতম</h2>
                @if($shopName)
                <p class="text-primary-200 text-sm mt-1 flex items-center gap-1.5">
                    <x-heroicon-s-building-storefront class="w-3.5 h-3.5" />
                    {{ $shopName }}
                    @if($shopUrl)
                    <a href="{{ $shopUrl }}" target="_blank" class="text-white hover:text-primary-100 font-bold text-xs underline underline-offset-2 transition ml-1">
                        (Visit Store →)
                    </a>
                    @endif
                </p>
                @endif
            </div>

            {{-- Quick Action Buttons --}}
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('filament.admin.resources.products.create') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/20 hover:bg-white/30 text-white text-xs font-semibold transition backdrop-blur-sm border border-white/20">
                    <x-heroicon-s-plus class="w-4 h-4" />
                    Add Product
                </a>
                <a href="{{ route('filament.admin.resources.orders.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/20 hover:bg-white/30 text-white text-xs font-semibold transition backdrop-blur-sm border border-white/20">
                    <x-heroicon-s-shopping-cart class="w-4 h-4" />
                    View Orders
                </a>
                <a href="{{ route('filament.admin.pages.inbox') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white text-primary-700 hover:bg-primary-50 text-xs font-bold transition shadow-md">
                    <x-heroicon-s-inbox class="w-4 h-4" />
                    Open Inbox
                </a>
            </div>
        </div>
    </div>
</div>
