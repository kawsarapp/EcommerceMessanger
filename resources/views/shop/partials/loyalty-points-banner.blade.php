{{--
    Loyalty Points Banner Partial
    ==============================
    Include in tracking page AFTER order result:
    @include('shop.partials.loyalty-points-banner', ['client' => $client, 'customerPhone' => $customerPhone ?? null])

    Include in product page (earn estimate):
    @include('shop.partials.loyalty-points-banner', ['client' => $client, 'product' => $product ?? null, 'mode' => 'earn'])
--}}

@php
    $loyaltyActive = $client->widgets['loyalty']['active'] ?? false;
    $mode          = $mode ?? 'balance'; // 'balance' | 'earn'
@endphp

@if($loyaltyActive)

    @if($mode === 'earn' && isset($product))
        {{-- Product page: how many points will be earned --}}
        @php
            $rate     = (int) ($client->widgets['loyalty']['rate'] ?? 1); // 1 pt per 100tk
            $price    = (float) ($product->sale_price ?? $product->regular_price ?? 0);
            $estimate = (int) floor($price * $rate / 100);
        @endphp
        @if($estimate > 0)
        <div class="flex items-center gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2 rounded-lg">
            <span class="text-base">🏆</span>
            <span>এই পণ্য কিনলে <strong class="font-bold">{{ $estimate }} Loyalty Points</strong> পাবেন!</span>
        </div>
        @endif

    @elseif($mode === 'balance' && !empty($customerPhone))
        {{-- Tracking page: customer's points balance --}}
        @php
            $points = \App\Models\LoyaltyPoint::balanceFor($client->id, $customerPhone);
            $label  = $client->widgets['loyalty']['points_label'] ?? 'Loyalty Points';
        @endphp
        @if($points > 0)
        <div class="flex items-center justify-between bg-gradient-to-r from-amber-500 to-yellow-400 text-white rounded-xl p-4 shadow-sm mt-4">
            <div class="flex items-center gap-3">
                <span class="text-3xl">🏆</span>
                <div>
                    <p class="text-xs font-medium opacity-80">আপনার {{ $label }}</p>
                    <p class="text-2xl font-black">{{ number_format($points) }}</p>
                </div>
            </div>
            <div class="text-right text-xs opacity-80">
                <p>প্রতি ১০০ টাকা কেনাকাটায়</p>
                <p>{{ $client->widgets['loyalty']['rate'] ?? 1 }} পয়েন্ট</p>
            </div>
        </div>
        @endif

    @endif

@endif
