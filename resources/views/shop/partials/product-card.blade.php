{--
    Reusable Product Card - Used in all theme index pages
    Required: $product (or aliased as $p), $baseUrl, $client
    Fully Responsive: Mobile, Tablet, Desktop
--}}
@php
    $p = $product ?? $p;
    $baseUrl = $baseUrl ?? url('/');
@endphp

<a href="{{ $baseUrl . '/product/' . $p->slug }}" class="product-card group flex flex-col bg-white rounded-xl border border-slate-100 overflow-hidden hover:shadow-xl transition-all duration-300 relative">

    {{-- Discount Badge --}}
    @if($p->sale_price)
        @php $discount = round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100); @endphp
        <div class="absolute top-2 left-2 z-20 bg-gradient-to-r from-red-500 to-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-md">
            -{{ $discount }}%
        </div>
    @endif

    {{-- Out of Stock Overlay --}}
    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
        <div class="absolute inset-0 bg-white/80 backdrop-blur-[2px] z-30 flex items-center justify-center">
            <span class="bg-slate-900/90 text-white font-bold text-[10px] uppercase tracking-widest px-3 py-1.5 rounded-lg">স্টক শেষ</span>
        </div>
    @endif

    {{-- Image Container --}}
    <div class="aspect-square bg-slate-50 relative p-3 sm:p-4 flex items-center justify-center overflow-hidden">
        <img src="{{ asset('storage/' . $p->thumbnail) }}" alt="{{ $p->name }}" loading="lazy"
            class="max-w-full max-h-full object-contain z-10 transform group-hover:scale-110 transition-transform duration-500">

        {{-- Quick View Button (Desktop) --}}
        <div class="absolute bottom-2 left-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 hidden sm:block">
            <span class="block w-full text-center py-2 bg-primary text-white text-xs font-bold rounded-lg">
                বিস্তারিত দেখুন
            </span>
        </div>
    </div>

    {{-- Info Section --}}
    <div class="p-3 sm:p-4 flex flex-col flex-1 bg-white relative z-20">
        {{-- Category --}}
        <p class="text-[9px] sm:text-[10px] text-slate-400 font-bold uppercase tracking-wider truncate mb-1">
            {{ $p->category->name ?? 'জেনারেল' }}
        </p>

        {{-- Product Name --}}
        <h4 class="font-semibold text-slate-800 leading-snug mb-2 line-clamp-2 group-hover:text-primary transition text-xs sm:text-sm">
            {{ $p->name }}
        </h4>

        {{-- Rating --}}
        @php
            $avgRating = $p->reviews()->where('is_visible', true)->avg('rating') ?? 0;
            $reviewCount = $p->reviews()->where('is_visible', true)->count();
        @endphp
        @if($reviewCount > 0 && ($client->widget('show_reviews') ?? true))
        <div class="flex items-center gap-1 mb-2">
            <div class="flex text-amber-400 text-[10px]">
                @for($i = 1; $i <= 5; $i++)
                    <i class="{{ $i <= round($avgRating) ? 'fas' : 'far' }} fa-star"></i>
                @endfor
            </div>
            <span class="text-[10px] text-slate-400 font-medium">({{ $reviewCount }})</span>
        </div>
        @endif

        {{-- Price Section --}}
        <div class="flex items-center gap-2 mt-auto">
            <span class="font-bold text-base sm:text-lg text-slate-900 tracking-tight">
                ৳{{ number_format($p->sale_price ?? $p->regular_price) }}
            </span>
            @if($p->sale_price)
                <del class="text-[10px] sm:text-[11px] text-slate-400 font-semibold">৳{{ number_format($p->regular_price) }}</del>
            @endif
        </div>

        {{-- Mobile: Quick Add Button --}}
        <div class="mt-2 sm:hidden">
            <span class="block w-full text-center py-2 bg-primary/10 text-primary text-xs font-bold rounded-lg">
                বিস্তারিত
            </span>
        </div>
    </div>
</a>
