{{--
    Universal Stock Alert Badge Partial
    ====================================
    Include in all theme product pages:
    @include('shop.partials.stock-alert-badge', ['product' => $product, 'client' => $client])
--}}

@php
    $stockQty       = (int) ($product->stock_quantity ?? 0);
    $showStock      = $client->show_stock ?? false;
    $isOutOfStock   = $product->stock_status === 'out_of_stock' || $stockQty <= 0;
    $threshold      = (int) ($client->widgets['stock_alert']['threshold'] ?? 5);
    $isLowStock     = !$isOutOfStock && $stockQty <= $threshold;
    $showNotifyMe   = $isOutOfStock && ($client->widgets['stock_alert']['notify_me'] ?? true);
@endphp

@if($isOutOfStock)
    {{-- Out of Stock Badge --}}
    <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-600 border border-red-200 text-xs font-semibold px-3 py-1.5 rounded-full">
        <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span>
        Out of Stock
    </span>

    @if($showNotifyMe)
    {{-- Notify Me Button --}}
    <div class="mt-4" x-data="{ showNotify: false, phone: '', sent: false }">
        <button @click="showNotify = !showNotify"
            class="w-full flex items-center justify-center gap-2 bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold py-3 px-6 rounded-lg transition">
            <i class="fas fa-bell text-yellow-400"></i>
            <span>স্টক আসলে জানান</span>
        </button>

        <div x-show="showNotify" x-transition class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-500 mb-3">পণ্যটি পুনরায় পাওয়া গেলে SMS-এ জানাব:</p>
            <template x-if="!sent">
                <form @submit.prevent="
                    if (!phone || phone.length < 11) return;
                    fetch('{{ route('shop.stock.notify', $client->slug) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ product_id: {{ $product->id }}, phone: phone })
                    }).then(r => r.json()).then(d => { if (d.success) sent = true; });
                " class="flex gap-2">
                    <input x-model="phone" type="tel" placeholder="01XXXXXXXXX" maxlength="11"
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gray-500">
                    <button type="submit" class="bg-gray-800 text-white text-sm px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </template>
            <template x-if="sent">
                <p class="text-green-600 text-sm font-semibold flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> আপনাকে SMS-এ জানানো হবে!
                </p>
            </template>
        </div>
    </div>
    @endif

@elseif($isLowStock)
    {{-- Low Stock Warning --}}
    <span class="inline-flex items-center gap-1.5 bg-orange-50 text-orange-600 border border-orange-200 text-xs font-semibold px-3 py-1.5 rounded-full">
        <span class="w-1.5 h-1.5 bg-orange-400 rounded-full animate-pulse"></span>
        @if($showStock) মাত্র {{ $stockQty }}টি বাকি! @else সীমিত স্টক! @endif
    </span>

@elseif($showStock && $stockQty > 0)
    {{-- In Stock --}}
    <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-600 border border-green-200 text-xs font-semibold px-3 py-1.5 rounded-full">
        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
        In Stock ({{ $stockQty }}টি)
    </span>
@endif
