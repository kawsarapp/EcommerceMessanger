@php
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
@endphp

<div class="flex flex-col h-full">
    <div class="bg-white rounded-xl p-6 md:p-8 shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-slate-200 relative overflow-hidden flex-1">
        
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-40 h-40 bg-primary/5 rounded-full blur-3xl"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <span class="text-primary text-[10px] font-bold tracking-widest uppercase bg-blue-50 px-3 py-1 rounded border border-blue-100 font-mono">{{ $product->category->name ?? 'Tech' }}</span>
                
                @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                    <span class="text-red-500 text-[10px] font-bold bg-red-50 px-3 py-1 rounded border border-red-100 uppercase tracking-widest font-mono"><i class="fas fa-ban"></i> Out of Stock</span>
                @else
                    <span class="text-[#25D366] text-[10px] font-bold bg-green-50 px-3 py-1 rounded border border-green-100 uppercase tracking-widest font-mono"><i class="fas fa-check-circle"></i> In Stock</span>
                @endif
            </div>

            <h1 class="text-2xl md:text-3xl font-bold font-heading text-slate-900 mb-2 leading-tight">{{ $product->name }}</h1>
            
            @if($product->sku)
                <p class="text-xs text-slate-400 mb-6 font-mono font-medium">SKU: {{ $product->sku }}</p>
            @endif

            <div class="flex items-end gap-3 mb-6 pb-6 border-b border-slate-100">
                <span class="text-4xl font-extrabold text-slate-900 font-mono tracking-tighter">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                @if($product->sale_price)
                    <div class="flex flex-col mb-1.5">
                        <span class="text-sm text-slate-400 line-through font-medium font-mono">৳{{ number_format($product->regular_price) }}</span>
                        <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Save {{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}%</span>
                    </div>
                @endif
            </div>

            <div class="prose prose-sm text-slate-600 mb-8 max-w-none text-sm leading-relaxed">
                {!! clean($product->description) !!}
            </div>

            <form action="{{ $cleanDomain ? $baseUrl.'/checkout/'.$product->slug : route('shop.checkout', [$client->slug, $product->slug]) }}" method="GET" class="space-y-6">
                
                @if($product->colors && count($product->colors) > 0)
                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-widest mb-3">Color Options <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-3">
                        @foreach($product->colors as $color)
                        <label class="cursor-pointer relative">
                            <input type="radio" name="color" value="{{ $color }}" x-model="selectedColor" class="peer hidden" required>
                            <span class="px-4 py-2 border-2 border-slate-200 text-slate-600 rounded-lg text-xs font-bold uppercase tracking-wider peer-checked:border-primary peer-checked:bg-blue-50 peer-checked:text-primary transition-all block hover:border-slate-300">
                                {{ $color }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($product->sizes && count($product->sizes) > 0)
                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-widest mb-3">Variants / Storage <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-3">
                        @foreach($product->sizes as $size)
                        <label class="cursor-pointer relative">
                            <input type="radio" name="size" value="{{ $size }}" x-model="selectedSize" class="peer hidden" required>
                            <span class="min-w-[3rem] text-center px-4 py-2 border-2 border-slate-200 text-slate-600 rounded-lg text-xs font-bold uppercase tracking-wider peer-checked:border-primary peer-checked:bg-blue-50 peer-checked:text-primary transition-all block hover:border-slate-300">
                                {{ $size }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <input type="hidden" name="qty" value="1">

                <div class="pt-4 hidden md:block">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-3 transition shadow-[0_8px_30px_rgba(0,0,0,0.2)] transform hover:-translate-y-1 uppercase tracking-widest">
                        <i class="fas fa-bolt text-primary"></i> Buy Now
                    </button>
                </div>
            </form>
        </div>

        <div class="flex flex-col items-center mt-6 pt-6 border-t border-slate-100">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-3">Secure Tech Purchase</p>
            <div class="flex gap-4 text-slate-300 text-2xl">
                <i class="fas fa-shield-alt hover:text-primary transition tooltip" title="Buyer Protection"></i>
                <i class="fas fa-truck-fast hover:text-primary transition tooltip" title="Fast Delivery"></i>
                <i class="fas fa-box-open hover:text-primary transition tooltip" title="Original Product"></i>
            </div>
        </div>
    </div>
</div>