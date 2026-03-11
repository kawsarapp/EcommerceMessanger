<div class="space-y-4 lg:sticky lg:top-28">
    <div class="aspect-square bg-slate-50 rounded-xl overflow-hidden shadow-sm border border-slate-200 relative group cursor-zoom-in"
         @click="showZoomModal = true">
        
        <img :src="mainImage" class="w-full h-full object-contain p-4 md:p-8 mix-blend-multiply group-hover:scale-105 transition-transform duration-500">
        
        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none bg-slate-900/5">
            <div class="bg-slate-900/90 text-white p-4 rounded-full shadow-2xl backdrop-blur-sm">
                <i class="fas fa-expand text-xl"></i>
            </div>
        </div>

        @if($product->sale_price && $product->regular_price > $product->sale_price)
        <span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded shadow-lg animate-pulse uppercase tracking-wider font-mono">
            -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}%
        </span>
        @endif
    </div>

    <div class="flex gap-3 overflow-x-auto scrollbar-hide py-2">
        <div @click="mainImage = '{{ asset('storage/' . $product->thumbnail) }}'" 
             class="w-20 h-20 flex-shrink-0 rounded-lg border-2 cursor-pointer overflow-hidden bg-slate-50 p-1.5 transition-all"
             :class="mainImage === '{{ asset('storage/' . $product->thumbnail) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-slate-200 hover:border-slate-300'">
            <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-full h-full object-contain mix-blend-multiply">
        </div>
        @if($product->gallery)
            @foreach($product->gallery as $img)
            <div @click="mainImage = '{{ asset('storage/' . $img) }}'" 
                 class="w-20 h-20 flex-shrink-0 rounded-lg border-2 cursor-pointer overflow-hidden bg-slate-50 p-1.5 transition-all"
                 :class="mainImage === '{{ asset('storage/' . $img) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-slate-200 hover:border-slate-300'">
                <img src="{{ asset('storage/' . $img) }}" class="w-full h-full object-contain mix-blend-multiply">
            </div>
            @endforeach
        @endif
    </div>

    @if($product->video_url)
    <button @click="playVideo('{{ $product->video_url }}')" 
            class="w-full bg-slate-900 text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-black transition shadow-lg mt-4 group uppercase tracking-widest text-sm">
        <i class="fas fa-play-circle text-red-500 group-hover:scale-110 transition-transform text-lg"></i> Watch Review
    </button>
    @endif
</div>