{{--
    Universal Product Features Bar
    ================================
    Inject in ALL theme product.blade.php right after the stock status block:
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client])

    Contains:
    1. Compare Button (Add to Compare)
    2. Loyalty Points Earn Estimate
--}}

@php
    $currency  = $client->currency ?? '&#2547;';
    $price     = (float) ($product->sale_price ?? $product->regular_price ?? 0);
    $thumbUrl  = $product->thumbnail ? asset('storage/'.$product->thumbnail) : ($product->image ? asset('storage/'.$product->image) : '');
    $compareUrl = $clean
        ? $baseUrl.'/compare'
        : route('shop.compare', $client->slug);
@endphp

<div class="flex flex-wrap items-center gap-3 my-4">
    {{-- ⚖️ Compare Button --}}
    <button
        data-compare-btn="{{ $product->id }}"
        onclick="addToCompare({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ $thumbUrl }}', '{{ $currency }}{{ number_format($price, 0) }}')"
        class="compare-btn inline-flex items-center gap-2 text-xs font-semibold text-gray-500 hover:text-gray-800 border border-gray-200 hover:border-gray-400 bg-white px-3 py-2 rounded-lg transition cursor-pointer select-none"
        title="{{ ->widgets['trans_compare'] ?? 'Add to Compare' }}">
        <i class="fas fa-balance-scale text-gray-400"></i>
        <span class="compare-label">{{ ->widgets['trans_compare'] ?? 'Compare' }}</span>
    </button>

    {{-- 🏆 Loyalty Earn Estimate --}}
    @if($client->widgets['loyalty']['active'] ?? false)
        @php
            $rate     = (int) ($client->widgets['loyalty']['rate'] ?? 1);
            $estimate = (int) floor($price * $rate / 100);
        @endphp
        @if($estimate > 0)
        <div class="inline-flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2 rounded-lg">
            <span>🏆</span>
            <span>কিনলে <strong>{{ number_format($estimate) }} points</strong> পাবেন</span>
        </div>
        @endif
    @endif
</div>

{{-- Update compare button style when active --}}
<style>
.compare-btn.compare-active {
    background-color: #1f2937;
    color: #fff;
    border-color: #1f2937;
}
.compare-btn.compare-active i,
.compare-btn.compare-active .compare-label {
    color: #fff;
}
</style>
