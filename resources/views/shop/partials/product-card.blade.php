{{-- 
    Reusable Product Card - Used in all theme index pages
    Required: $product (or aliased as $p), $baseUrl, $client
--}}
@php
    $p = $product ?? $p;
    $baseUrl = $baseUrl ?? url('/');
@endphp

<a href="{{$baseUrl.'/product/'.$p->slug}}" class="product-card group flex flex-col h-full bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-xl transition-all duration-300 relative">
    
    @if($p->sale_price)
        @php $discount = round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100); @endphp
        <div class="absolute top-2.5 left-2.5 z-20 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-sm">
            -{{ $discount }}%
        </div>
    @endif
    
    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
        <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-30 flex items-center justify-center">
            <span class="bg-slate-900/80 text-white font-bold text-[10px] uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-md">Out of Stock</span>
        </div>
    @endif

    {{-- Strict Square Image Container --}}
    <div class="relative w-full pt-[100%] bg-white shrink-0">
        <div class="absolute inset-0 flex items-center justify-center overflow-hidden">
            <img src="{{asset('storage/'.$p->thumbnail)}}" alt="{{$p->name}}" loading="lazy"
                class="w-full h-full object-contain z-10 transform group-hover:scale-[1.03] transition-transform duration-500">
        </div>
    </div>
    
    {{-- Info --}}
    <div class="p-3.5 sm:p-4 flex flex-col flex-1 bg-white relative z-20">
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider truncate mb-1">{{$p->category->name ?? 'General'}}</p>
        
        <h4 class="font-semibold text-slate-800 leading-snug mb-3 line-clamp-2 group-hover:text-primary transition text-xs sm:text-sm">{{$p->name}}</h4>
        
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
            <span class="text-[10px] text-slate-400 font-medium">({{$reviewCount}})</span>
        </div>
        @endif
        
        <div class="flex items-center gap-2 mt-auto">
            <span class="font-extrabold text-base sm:text-lg text-slate-900 tracking-tight">&#2547;{{number_format($p->sale_price ?? $p->regular_price)}}</span>
            @if($p->sale_price)
                <del class="text-[11px] text-slate-400 font-semibold">&#2547;{{number_format($p->regular_price)}}</del>
            @endif
        </div>
    </div>
</a>
