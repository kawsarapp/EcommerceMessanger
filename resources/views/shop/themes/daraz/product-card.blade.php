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
            <span class="bg-gray-900 text-white font-bold text-[10px] uppercase tracking-wider px-3 py-1.5 rounded-lg">{{ ->widgets['trans_out_of_stock'] ?? 'Out of Stock' }}</span>
        </div>
    @endif

    {{-- Image --}}
    <div class="aspect-square bg-gray-50 relative p-3 flex items-center justify-center overflow-hidden">
        <img src="{{ asset('storage/'.$p->thumbnail) }}" loading="lazy" alt="{{ $p->name }}"
             class="p-img max-w-full max-h-full object-contain z-10 transition-transform duration-500 ease-out group-hover:scale-110">
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
            <span class="bg-white text-primary text-[10px] font-bold px-4 py-2 rounded-full transform translate-y-4 group-hover:translate-y-0 transition-all duration-300 shadow-lg flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                বিস্তারিত
            </span>
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
            <span class="text-base md:text-lg font-bold text-dark">&#2547;{{ number_format($p->sale_price ?? $p->regular_price) }}</span>
            @if($p->sale_price)
                <del class="text-[10px] md:text-xs text-gray-400 font-medium">&#2547;{{ number_format($p->regular_price) }}</del>
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
