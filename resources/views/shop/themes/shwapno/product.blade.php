@extends('shop.themes.shwapno.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
    $clean  = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')); 
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug); 
    $deliveryText = $client->widgets['delivery_time']['text'] ?? ($client->widgets['delivery']['text'] ?? '');
@endphp

<style>
    .sw-breadcrumb { font-size: 11px; color: #4b5563; padding: 16px 0; font-weight: 500; display: flex; gap: 8px; align-items: center; }
    .sw-breadcrumb a { color: #1f2937; transition: color 0.1s; }
    .sw-breadcrumb a:hover { color: #e31e24; text-decoration: underline; }
    
    .sw-btn-pill { border-radius: 9999px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer; }
    .sw-btn-red { background-color: #e31e24; color: #fff; border: 1px solid #e31e24; }
    .sw-btn-red:hover { background-color: #c8161c; }
    
    .sw-qty-input { width: 44px; height: 36px; text-align: center; border: 1px solid #d1d5db; border-radius: 4px; font-weight: bold; color: #333; outline: none; margin: 0 10px; }
    .sw-qty-btn { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; background: #f3f4f6; color: #4b5563; font-weight: bold; border-radius: 4px; transition: background 0.2s; cursor: pointer; }
    .sw-qty-btn:hover { background: #e5e7eb; }
    
    .sw-info-box { border: 1px solid #e5e7eb; padding: 20px; border-radius: 4px; background: white; margin-bottom: 24px; }
    
    .sw-card { background: white; border: 1px solid #f3f4f6; transition: all 0.2s; position: relative; display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; height: 100%; border-radius: 2px; }
    .sw-card:hover { border-color: #fee2e2; box-shadow: 0 4px 12px rgba(227,30,36,0.06); transform: translateY(-2px); }
    .sw-section-title { font-size: 18px; font-weight: 800; color: #333; text-transform: uppercase; text-align: center; margin-bottom: 24px; letter-spacing: 0.5px; }

    /* Carousel */
    .sw-scroll-track { display: flex; overflow-x: auto; gap: 12px; padding-bottom: 8px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; }
    .sw-scroll-track::-webkit-scrollbar { display: none; }
    .sw-scroll-track > * { scroll-snap-align: start; flex-shrink: 0; }
    .sw-carousel-arrow { width: 36px; height: 36px; background: #ffd100; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 50%; transform: translateY(-50%); z-index: 10; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12); border: none; opacity: 0.85; transition: opacity 0.2s; }
    .sw-carousel-arrow:hover { opacity: 1; }
    .sw-carousel-arrow.left { left: -18px; }
    .sw-carousel-arrow.right { right: -18px; }
    @media(max-width:1024px){ .sw-carousel-arrow { display: none; } }
</style>

<div class="max-w-[1340px] mx-auto px-4 lg:px-6" x-data="{ mainImg: '{{ asset('storage/'.$product->thumbnail) }}' }">
    
    {{-- Breadcrumb --}}
    <div class="sw-breadcrumb">
        <a href="{{ $baseUrl }}">Home</a>
        <i class="fas fa-chevron-right text-[8px] text-gray-400"></i>
        @if($product->category)
        <a href="{{ $baseUrl }}?category={{ $product->category->slug }}">{{ $product->category->name }}</a>
        <i class="fas fa-chevron-right text-[8px] text-gray-400"></i>
        @endif
        <span class="font-bold text-gray-800 line-clamp-1">{{ $product->name }}</span>
    </div>

    {{-- Main Product Layout --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10 pb-16 border-b border-gray-200 bg-white p-6 rounded shadow-sm">
        
        {{-- Product Image --}}
        <div class="lg:col-span-5 flex flex-col items-center relative">
            @if($product->sale_price)<span class="absolute top-0 left-4 bg-swred text-white text-[11px] font-bold px-2 py-0.5 z-10 rounded-sm shadow-sm">SALE</span>@endif
            
            <div class="w-full aspect-square border border-gray-100 p-4 mb-4 flex items-center justify-center relative bg-white">
                <img :src="mainImg" class="max-w-full max-h-full object-contain" loading="lazy" alt="{{ $product->name }}">
                <div class="absolute bottom-4 right-4 text-gray-400 opacity-50"><i class="fas fa-search-plus text-sm"></i></div>
            </div>

            {{-- Thumbnails --}}
            @if($product->gallery && count($product->gallery) > 0)
            <div class="flex gap-3 justify-center w-full flex-wrap">
                <div @click="mainImg = '{{ asset('storage/'.$product->thumbnail) }}'" 
                     :class="mainImg === '{{ asset('storage/'.$product->thumbnail) }}' ? 'border-swred' : 'border-gray-200'"
                     class="w-16 h-16 bg-white border cursor-pointer p-1.5 rounded-sm hover:border-gray-400 transition">
                    <img src="{{ asset('storage/'.$product->thumbnail) }}" loading="lazy" class="w-full h-full object-contain" alt="{{ $product->name }}">
                </div>
                @foreach($product->gallery as $img)
                <div @click="mainImg = '{{ asset('storage/'.$img) }}'" 
                     :class="mainImg === '{{ asset('storage/'.$img) }}' ? 'border-swred' : 'border-gray-200'"
                     class="w-16 h-16 bg-white border cursor-pointer p-1.5 rounded-sm hover:border-gray-400 transition">
                    <img src="{{ asset('storage/'.$img) }}" class="w-full h-full object-contain" loading="lazy" alt="{{ $product->name }}">
                </div>
                @endforeach
            </div>
            @endif

            @if($product->video_url)
            <div class="mt-4 w-full px-4 mb-2">
                <a href="{{ $product->video_url }}" target="_blank" class="flex items-center justify-center gap-2 w-full py-2.5 bg-red-50 text-swred border border-red-200 rounded text-sm font-bold hover:bg-swred hover:text-white transition">
                    <i class="fab fa-youtube text-lg"></i> Watch Product Video
                </a>
            </div>
            @endif
        </div>

        {{-- Product Center Info --}}
        <div class="lg:col-span-4 flex flex-col mt-2">
            @if($product->brand)
                <span class="text-[10px] font-bold text-gray-400 tracking-wider uppercase mb-0.5">{{ $product->brand }}</span>
            @endif
            <h1 class="text-[17px] text-gray-800 font-bold leading-snug mb-3">{{ $product->name }}</h1>
            
            <div class="flex items-center gap-2 mb-5">
                <span class="text-3xl font-black text-swred">৳{{ number_format($product->sale_price ?? $product->regular_price, 0) }}</span>
                @if($product->sale_price)
                    <del class="text-[15px] text-gray-400 font-medium ml-1">৳{{ number_format($product->regular_price, 0) }}</del>
                    <span class="text-[11px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">
                        {{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}% OFF
                    </span>
                @endif
            </div>

            @include('shop.partials.product-variations')

            @if($product->material)
            <div class="mt-5 text-[12px] text-gray-600"><strong>Material:</strong> {{ $product->material }}</div>
            @endif

            {{-- Description --}}
            <div class="mt-7 pt-5 border-t border-gray-100">
                <h3 class="text-[13px] font-bold text-gray-800 mb-2">Product Description</h3>
                <div class="text-[11px] text-gray-500 leading-relaxed text-justify">
                    {!! $product->description ?? $product->long_description !!}
                </div>
            </div>
        </div>

        {{-- Right Info Panel --}}
        <div class="lg:col-span-3">
            <div class="sw-info-box">
                {{-- Stock Status --}}
                <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                    <span class="text-[11px] font-bold text-gray-700">SKU: <span class="font-normal text-gray-500">{{ $product->id }}</span></span>
                    @if($client->show_stock ?? true)
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <span class="text-[11px] font-bold text-red-500 flex items-center gap-1"><i class="fas fa-times-circle"></i> Out of stock</span>
                        @else
                            <span class="text-[11px] font-bold text-green-600 flex items-center gap-1"><i class="fas fa-check-circle"></i> In-stock</span>
                        @endif
                    @endif
                </div>



                {{-- Social Share --}}
                @php
                    $shareUrl = urlencode(url()->current());
                    $fbShare  = 'https://www.facebook.com/sharer/sharer.php?u='.$shareUrl;
                    $waShare  = 'https://wa.me/?text='.$shareUrl;
                @endphp
                <div class="flex justify-between items-center mb-5">
                    <span class="text-[11px] text-gray-500 font-medium">Share:</span>
                    <div class="flex gap-2">
                        <a href="{{ $fbShare }}" target="_blank" class="text-blue-600 hover:opacity-80 transition"><i class="fab fa-facebook bg-blue-50 p-1.5 rounded-full text-sm"></i></a>
                        <a href="{{ $waShare }}" target="_blank" class="text-green-500 hover:opacity-80 transition"><i class="fab fa-whatsapp bg-green-50 p-1.5 rounded-full text-sm"></i></a>
                    </div>
                </div>

                {{-- Warranty / Return --}}
                @if(($client->show_return_warranty ?? true) && ($product->warranty || $product->return_policy))
                <div class="bg-gray-50 border border-gray-100 rounded p-3 mb-5">
                    @if($product->warranty)
                    <div class="flex gap-2 text-[11px] text-gray-600 mb-2 font-medium">
                         <i class="fas fa-shield-alt text-green-500 mt-0.5"></i>
                         <span><strong class="text-gray-800">Warranty:</strong> {{ $product->warranty }}</span>
                    </div>
                    @endif
                    @if($product->return_policy)
                    <div class="flex gap-2 text-[11px] text-gray-600 font-medium">
                         <i class="fas fa-undo-alt text-blue-500 mt-0.5"></i>
                         <span><strong class="text-gray-800">Return Policy:</strong> {{ $product->return_policy }}</span>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Delivery info (dynamic) --}}
                @if($deliveryText)
                <div class="text-[11px] text-gray-600 mb-3 font-medium flex gap-2">
                    <i class="fas fa-truck text-gray-400 mt-1"></i>
                    <span><strong class="text-gray-800">Delivery:</strong> {{ $deliveryText }}</span>
                </div>
                @endif

                {{-- Active Payment Methods (Dynamic) --}}
                @php $activeMethods = $client->getActivePaymentMethods(); @endphp
                @if(count($activeMethods) > 0)
                <div class="rounded border border-gray-200 overflow-hidden mt-3">
                    <div class="bg-gray-50 px-3 py-2 text-[10px] font-bold text-gray-700 border-b border-gray-200">
                        Payment Methods
                    </div>
                    <div class="p-3 flex flex-wrap gap-2">
                        @foreach($activeMethods as $key => $label)
                        <span class="text-[10px] font-bold px-2 py-1 rounded-sm
                            @if($key === 'cod') bg-green-50 text-green-700 border border-green-200
                            @elseif(str_contains($key, 'bkash')) bg-pink-50 text-pink-700 border border-pink-200
                            @elseif($key === 'sslcommerz') bg-blue-50 text-blue-700 border border-blue-200
                            @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                            {{ $label }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Reviews --}}
    <div class="mt-8 mb-12">
        <div class="bg-swyellow text-swdark font-bold text-xs px-6 py-2 rounded-full inline-block shadow-sm mb-4">Customer Reviews</div>
        <div class="border border-gray-200 rounded-sm bg-white p-8 flex flex-col items-center justify-center">
            <div class="w-full max-w-2xl">
                @include('shop.partials.related-products', ['client' => $client, 'product' => $product, 'relatedProducts' => App\Models\Product::where('client_id', $client->id)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->limit(8)->get()])

@include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
            </div>
        </div>
    </div>

    {{-- Similar Products --}}
    @if($client->show_related_products ?? true)
    <div class="mb-12">
        <h3 class="sw-section-title">SIMILAR PRODUCTS</h3>
        @php $similar = App\Models\Product::where('client_id', $client->id)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->inRandomOrder()->limit(6)->get(); @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 lg:gap-4">
            @forelse($similar as $p)
            <div class="sw-card group/card">
                <a href="{{ $baseUrl.'/product/'.$p->slug }}" class="flex items-center justify-center h-32 mb-2 mt-4">
                    <img src="{{ asset('storage/'.$p->thumbnail) }}" loading="lazy" class="max-w-full max-h-full object-contain group-hover/card:scale-105 transition duration-300" alt="{{ $p->name }}">
                </a>
                <div class="text-center mt-auto flex flex-col items-center">
                    <a href="{{ $baseUrl.'/product/'.$p->slug }}" class="w-full">
                        <h4 class="text-[11px] font-bold text-gray-800 line-clamp-2 h-8 leading-snug mb-1 hover:text-swred transition">{{ $p->name }}</h4>
                    </a>
                    <div class="flex items-center justify-center gap-1.5 mb-2">
                        @if($p->sale_price)<del class="text-[9px] text-gray-400">৳{{ number_format((float)$p->regular_price, 0) }}</del>@endif
                        <span class="font-bold text-swred text-xs">৳{{ number_format((float)($p->sale_price ?? $p->regular_price ?? 0), 0) }}</span>
                    </div>
                    @if($client->show_order_button ?? true)
                    <form action="{{ $baseUrl.'/checkout/'.$p->slug }}" method="GET" class="w-full">
                        <button class="sw-btn-pill sw-btn-red py-1.5 text-[10px] w-full hover:shadow-md"><i class="fas fa-plus mr-1 text-[8px]"></i> Add to Bag</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
                <div class="col-span-full py-8 text-center text-gray-400 text-sm">No similar items in this category.</div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- Related Products (from global pool) --}}
    @php $related = App\Models\Product::where('id', '!=', $product->id)->where('client_id', $client->id)->inRandomOrder()->limit(5)->get(); @endphp
    @if($related->count() > 0)
    <div class="mb-16">
        <h3 class="sw-section-title">YOU MAY ALSO LIKE</h3>
        <div style="position:relative;">
            <div class="sw-scroll-track" x-data="{}" id="relatedSlider">
                @foreach($related as $p)
                <div class="sw-card group/card min-w-[160px] md:min-w-[200px] lg:min-w-[215px]">
                    @if($p->sale_price)<span class="absolute top-0 left-0 bg-swred text-white text-[10px] font-bold px-1.5 py-1 z-10 flex flex-col items-center leading-none rounded-br-sm shadow-sm"><span class="text-[8px]">৳{{ $p->regular_price - $p->sale_price }}</span><span>OFF</span></span>@endif
                    <a href="{{ $baseUrl.'/product/'.$p->slug }}" class="flex items-center justify-center h-40 mb-2 mt-4">
                        <img src="{{ asset('storage/'.$p->thumbnail) }}" loading="lazy" class="max-w-full max-h-full object-contain group-hover/card:scale-105 transition duration-300" alt="{{ $p->name }}">
                    </a>
                    <div class="text-center mt-auto flex flex-col items-center">
                        <a href="{{ $baseUrl.'/product/'.$p->slug }}" class="w-full">
                            <h4 class="text-xs font-bold text-gray-800 line-clamp-2 h-8 leading-snug mb-2 hover:text-swred transition">{{ $p->name }}</h4>
                        </a>
                        <div class="flex items-center justify-center gap-1.5 mb-2">
                            @if($p->sale_price)<del class="text-[11px] text-gray-400">৳{{ number_format((float)$p->regular_price, 0) }}</del>@endif
                            <span class="font-bold text-swred text-sm">৳{{ number_format((float)($p->sale_price ?? $p->regular_price ?? 0), 0) }}</span>
                        </div>
                        @if($client->show_order_button ?? true)
                        <form action="{{ $baseUrl.'/checkout/'.$p->slug }}" method="GET" class="w-full mt-1">
                            <button class="sw-btn-pill sw-btn-red py-2 text-xs w-[85%] hover:shadow-md"><i class="fas fa-plus mr-1"></i> Add to Bag</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@include('shop.partials.product-sticky-bar')
@endsection

