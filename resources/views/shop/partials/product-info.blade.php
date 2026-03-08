<div class="flex flex-col h-full">
    <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 relative overflow-hidden flex-1">
        
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-40 h-40 bg-primary/5 rounded-full blur-3xl"></div>

        <div class="relative z-10">
            <span class="text-primary text-xs font-bold tracking-wider uppercase bg-primary/10 px-2.5 py-1 rounded-md">{{ $product->category->name ?? 'Product' }}</span>
            <h1 class="text-2xl md:text-4xl font-bold font-heading text-gray-900 mt-4 leading-tight">{{ $product->name }}</h1>
        </div>

        <div class="flex items-end gap-3 my-6 pb-6 border-b border-gray-100 relative z-10">
            <span class="text-3xl md:text-5xl font-extrabold text-gray-900 tracking-tight">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
            @if($product->sale_price && $product->regular_price > $product->sale_price)
            <div class="flex flex-col mb-1.5">
                <span class="text-sm text-gray-400 line-through font-medium">৳{{ number_format($product->regular_price) }}</span>
                <span class="text-xs text-green-600 font-bold bg-green-50 px-1.5 py-0.5 rounded">Save ৳{{ number_format($product->regular_price - $product->sale_price) }}</span>
            </div>
            @endif
        </div>

        <div class="space-y-6 mb-8 relative z-10">
            @if($product->colors)
            <div>
                <label class="text-sm font-bold text-gray-900 block mb-3">Select Color</label>
                <div class="flex flex-wrap gap-3">
                    @php $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors; @endphp
                    @foreach($colors as $color)
                    <button @click="selectedColor = '{{ $color }}'"
                            class="px-4 py-2.5 rounded-xl border text-sm font-bold transition-all flex items-center gap-2"
                            :class="selectedColor === '{{ $color }}' ? 'border-primary bg-primary text-white shadow-lg shadow-primary/30' : 'border-gray-200 hover:border-gray-300 text-gray-600 bg-white'">
                        
                        <span x-show="selectedColor !== '{{ $color }}'" class="w-3 h-3 rounded-full border border-gray-300" style="background-color: {{ strtolower($color) }}"></span>
                        <i x-show="selectedColor === '{{ $color }}'" class="fas fa-check text-xs"></i>
                        
                        {{ $color }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            @if($product->sizes)
            <div>
                <label class="text-sm font-bold text-gray-900 block mb-3">Select Size</label>
                <div class="flex flex-wrap gap-3">
                    @php $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes; @endphp
                    @foreach($sizes as $size)
                    <button @click="selectedSize = '{{ $size }}'"
                            class="min-w-[3.5rem] h-12 px-3 rounded-xl border text-sm font-bold flex items-center justify-center transition-all"
                            :class="selectedSize === '{{ $size }}' ? 'border-primary bg-primary text-white shadow-lg shadow-primary/30' : 'border-gray-200 hover:border-gray-300 text-gray-600 bg-white hover:bg-gray-50'">
                        {{ $size }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="prose prose-sm md:prose-base text-gray-600 max-w-none">
            <h3 class="font-bold text-gray-900 mb-3 text-lg">Product Details</h3>
            <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 leading-relaxed">
                {!! $product->description ?? $product->short_description !!}
            </div>
        </div>

        <div class="hidden md:flex gap-4 mt-8 pt-6 border-t border-gray-100">
            <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('Hi, I have a question about: {{ $product->name }}')" 
               target="_blank"
               class="flex-1 border-2 border-gray-200 hover:border-gray-300 text-gray-700 py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-2 transition hover:bg-gray-50">
                <i class="fas fa-comment-dots"></i> Chat
            </a>

            <a :href="'{{ $client->custom_domain ? route('shop.checkout.custom', $product->slug) : route('shop.checkout', [$client->slug, $product->slug]) }}' + '?qty=1' + (selectedColor ? '&color=' + selectedColor : '') + (selectedSize ? '&size=' + selectedSize : '')" 
               class="flex-[2] bg-primary hover:bg-primaryDark text-white py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-3 transition shadow-xl shadow-blue-500/20 transform hover:-translate-y-1">
                <i class="fas fa-shopping-cart text-2xl"></i> Order Now
            </a>


        </div>
    </div>
</div>