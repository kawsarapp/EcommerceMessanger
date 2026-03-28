@php $p = $product ?? $p; $baseUrl = $baseUrl ?? url('/'); @endphp

<a href="{{ $baseUrl.'/product/'.$p->slug }}" class="product-card block bg-white rounded-2xl border border-gray-100 overflow-hidden relative group">
    
    {{-- Badge --}}
    @if($p->sale_price)
        @php $discount = round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100); @endphp
        <span class="sale-badge absolute top-2 left-2 z-20 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow">
            -{{ $discount }}%
        </span>
    @endif

    {{-- Stock Overlay --}}
    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
        <div class="absolute inset-0 bg-white/90 backdrop-blur-[1px] z-30 flex items-center justify-center rounded-2xl">
            <span class="bg-gray-900 text-white font-bold text-[10px] uppercase tracking-wider px-3 py-1.5 rounded-lg">স্টক শেষ</span>
        </div>
    @endif

    {{-- Image --}}
    <div class="aspect-square bg-gray-50 relative p-3 flex items-center justify-center overflow-hidden">
        <img src="{{ asset('storage/'.$p->thumbnail) }}" alt="{{ $p->name }}" loading="lazy" class="p-img max-w-full max-h-full object-contain z-10">
        <div class="absolute inset-0 bg-primary/0 group-hover:bg-primary/5 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
            <span class="bg-primary text-white text-[10px] font-bold px-4 py-2 rounded-full transform translate-y-4 group-hover:translate-y-0 transition-all duration-300">বিস্তারিত</span>
        </div>
    </div>

    {{-- Info --}}
    <div class="p-3 md:p-4">
        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block truncate">{{ $p->category->name ?? 'General' }}</span>
        <h4 class="font-semibold text-dark leading-snug line-clamp-2 text-xs md:text-sm mt-0.5 mb-2 group-hover:text-primary transition">{{ $p->name }}</h4>
        
        {{-- Rating --}}
        @php $avgRating = $p->reviews()->where('is_visible', true)->avg('rating') ?? 0; $reviewCount = $p->reviews()->where('is_visible', true)->count(); @endphp
        @if($reviewCount > 0 && ($client->widget('show_reviews') ?? true))
        <div class="flex items-center gap-1 mb-2">
            <div class="flex text-amber-400 text-[10px]">
                @for($i = 1; $i <= 5; $i++)
                    <i class="{{ $i <= round($avgRating) ? 'fas' : 'far' }} fa-star"></i>
                @endfor
            </div>
            <span class="text-[10px] text-gray-400 font-medium">({{ $reviewCount }})</span>
        </div>
        @endif

        {{-- Price --}}
        <div class="flex items-end gap-2 flex-wrap">
            <span class="text-base md:text-lg font-bold text-dark">৳{{ number_format($p->sale_price ?? $p->regular_price) }}</span>
            @if($p->sale_price)
                <del class="text-[10px] md:text-xs text-gray-400 font-medium">৳{{ number_format($p->regular_price) }}</del>
            @endif
        </div>

        {{-- Buy Button --}}
        @if(!isset($p->stock_status) || $p->stock_status != 'out_of_stock')
        <div class="mt-3 md:hidden">
            <span class="block w-full text-center py-2 bg-primary/10 text-primary text-[10px] font-bold rounded-lg">বিস্তারিত দেখুন</span>
        </div>
        @endif
    </div>
</a>
