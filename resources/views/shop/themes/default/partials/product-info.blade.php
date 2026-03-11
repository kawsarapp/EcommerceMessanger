@php
    // 🔥 Custom Domain Clean URL Logic
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
@endphp

<div class="flex flex-col h-full">
    <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 relative overflow-hidden flex-1">
        
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-40 h-40 bg-primary/5 rounded-full blur-3xl"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-2">
                <span class="text-primary text-xs font-bold tracking-wider uppercase bg-primary/10 px-2.5 py-1 rounded-md">{{ $product->category->name ?? 'Product' }}</span>
                
                {{-- 🔥 NEW: Stock Status Badge --}}
                @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                    <span class="text-red-500 text-xs font-bold bg-red-50 px-2.5 py-1 rounded-md"><i class="fas fa-times-circle"></i> Out of Stock</span>
                @else
                    <span class="text-green-500 text-xs font-bold bg-green-50 px-2.5 py-1 rounded-md"><i class="fas fa-check-circle"></i> In Stock</span>
                @endif
            </div>
            
            <h1 class="text-2xl md:text-4xl font-bold font-heading text-gray-900 mt-2 leading-tight">{{ $product->name }}</h1>
            
            {{-- 🔥 NEW: SKU Display --}}
            @if($product->sku)
            <p class="text-xs text-gray-400 mt-2 font-mono">SKU: {{ $product->sku }}</p>
            @endif
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
            @php 
                $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors; 
                $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
            @endphp

            @if(!empty($colors) && count($colors) > 0)
            <div>
                <label class="text-sm font-bold text-gray-900 block mb-3">Select Color</label>
                <div class="flex flex-wrap gap-3">
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

            @if(!empty($sizes) && count($sizes) > 0)
            <div>
                <label class="text-sm font-bold text-gray-900 block mb-3">Select Size</label>
                <div class="flex flex-wrap gap-3">
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

        <div class="hidden md:flex flex-col gap-4 mt-8 pt-6 border-t border-gray-100">

            <div class="flex gap-4">
                <button type="button" @click="showChatOptions = true"
                    class="flex-1 border-2 border-gray-200 hover:border-gray-300 text-gray-700 py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-2 transition hover:bg-gray-50">
                        <i class="fas fa-comment-dots"></i> Chat
                </button>

                {{-- 🔥 Custom Domain Clean URL Update --}}
                <a :href="'{{ $cleanDomain ? $baseUrl.'/checkout/'.$product->slug : route('shop.checkout', [$client->slug, $product->slug]) }}' + '?qty=1' + (selectedColor ? '&color=' + selectedColor : '') + (selectedSize ? '&size=' + selectedSize : '')" 
                class="flex-[2] bg-primary hover:bg-primaryDark text-white py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-3 transition shadow-xl shadow-blue-500/20 transform hover:-translate-y-1">
                    <i class="fas fa-shopping-cart text-2xl"></i> Order Now
                </a>
            </div>

            {{-- 🔥 NEW: Trust Badges & Secure Checkout --}}
            <div class="flex flex-col items-center mt-4 pt-4 border-t border-dashed border-gray-200">
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">100% Secure Checkout</p>
                <div class="flex gap-3 text-gray-400 text-2xl">
                    <i class="fas fa-money-bill-wave hover:text-green-600 transition tooltip" title="Cash on Delivery"></i>
                    <i class="fab fa-cc-visa hover:text-blue-600 transition tooltip"></i>
                    <i class="fab fa-cc-mastercard hover:text-orange-600 transition tooltip"></i>
                    <i class="fas fa-shield-alt hover:text-primary transition tooltip"></i>
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 font-medium">
                    <span class="flex items-center gap-1"><i class="fas fa-truck text-primary"></i> Fast Delivery</span>
                    <span class="flex items-center gap-1"><i class="fas fa-undo text-primary"></i> Easy Returns</span>
                </div>
            </div>

        </div>
    </div>
</div>