<div class="space-y-4">
    <div class="aspect-square bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 relative group cursor-zoom-in"
         @click="showZoomModal = true">
        
        <img :src="mainImage" class="w-full h-full object-contain p-2 md:p-6 group-hover:scale-105 transition-transform duration-500">
        
        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none bg-black/5">
            <div class="bg-white/90 text-gray-800 p-3 rounded-full shadow-lg backdrop-blur-sm">
                <i class="fas fa-search-plus text-xl"></i>
            </div>
        </div>

        @if($product->sale_price && $product->regular_price > $product->sale_price)
        <span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg animate-pulse">
            -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}% OFF
        </span>
        @endif
    </div>

    <div class="flex gap-3 overflow-x-auto scrollbar-hide py-2">
        <div @click="mainImage = '{{ asset('storage/' . $product->thumbnail) }}'" 
             class="w-20 h-20 flex-shrink-0 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1 transition-all"
             :class="mainImage === '{{ asset('storage/' . $product->thumbnail) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200 hover:border-gray-300'">
            <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-full h-full object-cover rounded-lg">
        </div>
        @if($product->gallery)
            @foreach($product->gallery as $img)
            <div @click="mainImage = '{{ asset('storage/' . $img) }}'" 
                 class="w-20 h-20 flex-shrink-0 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1 transition-all"
                 :class="mainImage === '{{ asset('storage/' . $img) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200 hover:border-gray-300'">
                <img src="{{ asset('storage/' . $img) }}" class="w-full h-full object-cover rounded-lg">
            </div>
            @endforeach
        @endif
    </div>

    @if($product->video_url)
    <button @click="playVideo('{{ $product->video_url }}')" 
            class="w-full bg-red-50 text-red-600 border border-red-100 py-3.5 rounded-xl font-bold flex items-center justify-center gap-3 hover:bg-red-100 hover:shadow-md transition">
        <i class="fas fa-play-circle text-2xl animate-pulse"></i>
        <span>Watch Product Video</span>
    </button>
    @endif
</div>