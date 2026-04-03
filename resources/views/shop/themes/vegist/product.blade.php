@extends('shop.themes.vegist.layout')

@section('title', $product->name . ' - ' . $client->shop_name)

@section('content')
@php 
    $clean    = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
    $baseUrl  = $clean ? 'https://'.$clean : route('shop.show', $client->slug);
    $cartUrl  = $clean ? $baseUrl.'/cart/add' : route('shop.cart.add', $client->slug);
    $checkoutUrl = $clean ? $baseUrl.'/checkout' : route('shop.checkout', $client->slug);
    $initPrice = (float)($product->sale_price ?? $product->regular_price ?? 0);
@endphp

{{-- Breadcrumb --}}
<div class="bg-[#fcfdfa] py-4 mb-6 border-b border-gray-100">
    <div class="max-w-[1400px] mx-auto px-4 xl:px-8 text-[12px] text-gray-500 font-medium tracking-wide flex items-center gap-2">
        <a href="{{$baseUrl}}" class="hover:text-primary transition">Home</a>
        @if($product->category)
        <span>/</span>
        <a href="{{$clean?$baseUrl.'/?category='.$product->category->slug:route('shop.show',['shop'=>$client->slug,'category'=>$product->category->slug])}}" class="hover:text-primary transition">{{$product->category->name}}</a>
        @endif
        <span>/</span>
        <span class="text-dark line-clamp-1">{{$product->name}}</span>
    </div>
</div>

<div x-data="productData()" class="max-w-[1400px] mx-auto px-4 xl:px-8 pb-24 md:pb-16">

    {{-- === MAIN PRODUCT GRID === --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">

        {{-- ─── Left: Image Gallery ─── --}}
        <div class="lg:col-span-4" x-data="{ zoomPos: '50% 50%', showZoom: false }">

            {{-- Main Image --}}
            <div class="relative bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center overflow-hidden cursor-crosshair"
                 style="aspect-ratio:1/1;"
                 @mousemove="zoomPos=(($event.offsetX/$el.offsetWidth)*100)+'% '+(($event.offsetY/$el.offsetHeight)*100)+'%'"
                 @mouseenter="showZoom = window.innerWidth > 768"
                 @mouseleave="showZoom = false">

                @if($product->sale_price && $product->regular_price > 0)
                @php $pct = round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100); @endphp
                <div class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded z-20 shadow">-{{$pct}}%</div>
                @endif

                {{-- Normal image --}}
                <img :src="activeImage" alt="{{$product->name}}"
                     class="w-full h-full object-contain p-6 transition-opacity duration-200"
                     :class="showZoom?'opacity-0':'opacity-100'">

                {{-- Zoom overlay --}}
                <div x-show="showZoom"
                     class="absolute inset-0 pointer-events-none bg-no-repeat z-10"
                     :style="'background-image:url(\''+activeImage+'\'); background-position:'+zoomPos+'; background-size:260%;'">
                </div>

                {{-- Zoom hint --}}
                <div class="absolute bottom-3 right-3 bg-white/80 text-gray-500 text-[10px] px-2 py-1 rounded-full hidden lg:flex items-center gap-1">
                    <i class="fas fa-search text-[10px]"></i> Hover to zoom
                </div>
            </div>

            {{-- Thumbnails --}}
            @php
                $images = [];
                if($product->images) {
                    $images = is_string($product->images) ? (json_decode($product->images, true) ?? []) : (array)$product->images;
                }
            @endphp
            @if($product->thumbnail || count($images) > 0)
            <div class="grid grid-cols-5 gap-2 mt-3">
                @if($product->thumbnail)
                <div @click="activeImage='{{asset('storage/'.$product->thumbnail)}}'"
                     class="border-2 rounded-lg cursor-pointer p-1.5 bg-white transition"
                     :class="activeImage==='{{asset('storage/'.$product->thumbnail)}}'?'border-primary shadow':'border-gray-100 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full aspect-square object-contain">
                </div>
                @endif
                @foreach($images as $img)
                <div @click="activeImage='{{asset('storage/'.$img)}}'"
                     class="border-2 rounded-lg cursor-pointer p-1.5 bg-white transition"
                     :class="activeImage==='{{asset('storage/'.$img)}}'?'border-primary shadow':'border-gray-100 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full aspect-square object-contain">
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ─── Center: Product Details ─── --}}
        <div class="lg:col-span-5 flex flex-col">

            {{-- Name --}}
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-3 leading-snug">{{$product->name}}</h1>

            {{-- Ratings & Availability --}}
            <div class="flex flex-wrap items-center gap-4 mb-4 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-1">
                    @php $rating = (float)($product->average_rating ?? 5); @endphp
                    @for($i=1;$i<=5;$i++)
                        @if($i<=$rating)<i class="fas fa-star text-[#ffb522] text-xs"></i>
                        @elseif($i-0.5<=$rating)<i class="fas fa-star-half-alt text-[#ffb522] text-xs"></i>
                        @else<i class="far fa-star text-gray-300 text-xs"></i>
                        @endif
                    @endfor
                    <span class="text-gray-400 text-xs ml-1">({{$product->reviews_count ?? 0}})</span>
                </div>
                <div class="text-[12px]">
                    @if(($product->stock ?? 0) > 0)
                        <span class="text-green-600 font-medium"><i class="fas fa-circle text-[7px] mr-1"></i>In Stock</span>
                    @else
                        <span class="text-red-500 font-medium"><i class="fas fa-circle text-[7px] mr-1"></i>Out of Stock</span>
                    @endif
                </div>
                @if($product->sku)
                <div class="text-[12px] text-gray-400">SKU: <span class="text-gray-600">{{$product->sku}}</span></div>
                @endif
            </div>

            {{-- Price --}}
            <div class="flex items-baseline gap-3 mb-5">
                <span class="text-3xl font-black text-dark">৳<span x-text="Number(currentPrice).toLocaleString('en-BD')"></span></span>
                @if($product->sale_price && $product->regular_price > 0)
                <span class="text-lg text-gray-400 line-through">৳{{number_format((float)$product->regular_price)}}</span>
                <span class="text-xs bg-red-50 text-red-500 border border-red-100 px-2 py-0.5 rounded font-semibold">Save {{$pct ?? 0}}%</span>
                @endif
            </div>

            {{-- Short Description --}}
            @if($product->short_description)
            <p class="text-[13px] text-gray-500 mb-6 leading-relaxed border-l-2 border-primary/30 pl-3">
                {{$product->short_description}}
            </p>
            @endif

            {{-- Variants --}}
            @if(isset($product->attributes) && is_array($product->attributes) && count($product->attributes) > 0)
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm font-bold text-dark">Variant:</span>
                    <span class="text-sm text-primary font-semibold" x-text="selectedVariantName"></span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($product->attributes as $attr)
                        @if(!empty($attr['name']) && !empty($attr['price']))
                        <label class="cursor-pointer">
                            <input type="radio" name="variant" value="{{$attr['price']}}" data-name="{{$attr['name']}}"
                                   class="peer sr-only"
                                   @change="updateVariant($event)"
                                   @if($loop->first) checked x-init="$nextTick(()=>{ updateVariant({target:$el}) })" @endif>
                            <div class="px-4 py-2 border-2 rounded-lg text-[13px] font-medium text-gray-600
                                        peer-checked:border-primary peer-checked:text-primary peer-checked:bg-primary/5
                                        hover:border-gray-300 transition select-none">
                                {{$attr['name']}} — ৳{{number_format((float)$attr['price'])}}
                            </div>
                        </label>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Quantity --}}
            <div class="flex items-center gap-3 mb-5">
                <span class="text-sm font-bold text-dark shrink-0">Qty:</span>
                <div class="flex items-center border-2 border-gray-200 rounded-lg overflow-hidden h-11 bg-white w-32 shrink-0">
                    <button type="button" @click="if(qty>1) qty--"
                            class="w-10 h-full flex items-center justify-center text-gray-500 hover:text-primary hover:bg-gray-50 transition text-xl font-light">−</button>
                    <input type="number" x-model="qty" min="1"
                           class="w-full h-full text-center text-sm font-bold bg-transparent focus:outline-none border-0"
                           readonly>
                    <button type="button" @click="qty++"
                            class="w-10 h-full flex items-center justify-center text-gray-500 hover:text-primary hover:bg-gray-50 transition text-xl font-light">+</button>
                </div>
            </div>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                @if(!($client->widgets['show_order_button'] ?? true) && ($client->widgets['show_chat_button'] ?? false))
                    {{-- WhatsApp Only Mode --}}
                    @if($client->phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text={{ urlencode('I want to order: '.$product->name.' - '.$baseUrl.'/product/'.$product->slug) }}"
                       target="_blank"
                       class="flex-1 bg-[#25d366] hover:bg-[#128c7e] text-white h-12 flex justify-center items-center rounded-xl font-bold text-sm gap-2 transition shadow-md hover:shadow-lg">
                        <i class="fab fa-whatsapp text-xl"></i> Order via WhatsApp
                    </a>
                    @endif
                @else
                    {{-- Add to Cart --}}
                    <button type="button" @click="addToCart"
                            :disabled="isLoading || added || {{($product->stock ?? 0) <= 0 ? 'true' : 'false'}}"
                            class="flex-1 btn-primary h-12 rounded-xl font-bold text-sm flex justify-center items-center gap-2 shadow-md hover:shadow-lg transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!isLoading && !added" class="flex items-center gap-2">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </span>
                        <span x-show="isLoading" class="flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i> Adding...
                        </span>
                        <span x-show="added" class="flex items-center gap-2" x-cloak>
                            <i class="fas fa-check-circle"></i> Added to Cart!
                        </span>
                    </button>

                    {{-- Buy Now --}}
                    <button type="button" @click="buyNow"
                            :disabled="isLoading || {{($product->stock ?? 0) <= 0 ? 'true' : 'false'}}"
                            class="flex-1 btn-dark h-12 rounded-xl font-bold text-sm flex justify-center items-center gap-2 shadow-md hover:shadow-lg transition hover:bg-primary disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!isLoading" class="flex items-center gap-2"><i class="fas fa-bolt"></i> Buy Now</span>
                        <span x-show="isLoading" class="flex items-center gap-2"><i class="fas fa-spinner fa-spin"></i> Please wait...</span>
                    </button>
                @endif
            </div>

            {{-- Wishlist --}}
            <button type="button" @click="toggleWishlist"
                    class="flex items-center gap-2 text-sm text-gray-500 hover:text-red-500 transition mb-6 group w-max">
                <i class="far fa-heart group-hover:hidden"></i>
                <i class="fas fa-heart hidden group-hover:block text-red-500"></i>
                <span>Add to Wishlist</span>
            </button>

            {{-- Meta: Category + Share --}}
            <div class="border-t border-gray-100 pt-4 space-y-2 text-[12px] text-gray-500">
                @if($product->category)
                <p>
                    <strong class="text-dark font-semibold">Category:</strong>
                    <a href="{{$clean?$baseUrl.'/?category='.$product->category->slug:route('shop.show',['shop'=>$client->slug,'category'=>$product->category->slug])}}"
                       class="hover:text-primary transition ml-1">{{$product->category->name}}</a>
                </p>
                @endif

                <div class="flex items-center gap-3 pt-1">
                    <strong class="text-dark font-semibold">Share:</strong>
                    <div class="flex items-center gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}"
                           target="_blank" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-blue-600 hover:text-white flex items-center justify-center text-gray-500 transition text-xs">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($product->name) }}&url={{ urlencode(request()->fullUrl()) }}"
                           target="_blank" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-sky-400 hover:text-white flex items-center justify-center text-gray-500 transition text-xs">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text={{ urlencode($product->name.' - '.request()->fullUrl()) }}"
                           target="_blank" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-[#25d366] hover:text-white flex items-center justify-center text-gray-500 transition text-xs">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://pinterest.com/pin/create/button/?url={{ urlencode(request()->fullUrl()) }}&description={{ urlencode($product->name) }}"
                           target="_blank" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-red-600 hover:text-white flex items-center justify-center text-gray-500 transition text-xs">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Payment Icons --}}
            <div class="flex items-center gap-3 mt-4">
                <span class="text-[11px] text-gray-400">Secure checkout:</span>
                <i class="fab fa-cc-visa text-2xl text-blue-700"></i>
                <i class="fab fa-cc-mastercard text-2xl text-red-500"></i>
                <i class="fab fa-cc-paypal text-2xl text-blue-500"></i>
            </div>
        </div>

        {{-- ─── Right: Info Cards ─── --}}
        @php $infoCardsActive = $client->widgets['info_cards']['active'] ?? true; @endphp
        @if($infoCardsActive)
        <div class="lg:col-span-3">
            <div class="flex flex-row lg:flex-col gap-3 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0 hide-scroll">
                @php
                    $cards = [
                        ['icon'=>'fas fa-truck','title'=>'Delivery info','desc'=>'Delivery within 2-10 days depending on your location.'],
                        ['icon'=>'fas fa-undo-alt','title'=>'Easy Returns','desc'=>'Not right? We\'ll arrange pickup and a full refund.'],
                        ['icon'=>'fas fa-shield-alt','title'=>'Quality Guarantee','desc'=>'Our products are built to last. Quality comes first.'],
                    ];
                @endphp
                @foreach($cards as $i => $default)
                <div class="bg-white border border-gray-100 rounded-xl p-5 flex flex-row lg:flex-col items-center lg:items-center gap-4 lg:gap-3 lg:text-center hover:shadow-md hover:border-primary/20 transition duration-300 shrink-0 w-[75vw] sm:w-[50vw] lg:w-auto">
                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                        <i class="{{ $client->widgets['info_cards']['items'][$i]['icon'] ?? $default['icon'] }} text-xl text-primary"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-dark mb-1">{{ $client->widgets['info_cards']['items'][$i]['title'] ?? $default['title'] }}</h4>
                        <p class="text-[11px] text-gray-500 leading-relaxed">{{ $client->widgets['info_cards']['items'][$i]['description'] ?? $default['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>{{-- end main grid --}}

    {{-- === TABS === --}}
    <div class="mt-14" x-data="{ tab: 'description' }">
        <div class="flex overflow-x-auto hide-scroll border-b border-gray-200 gap-0">
            <button @click="tab='description'"
                    :class="tab==='description'?'border-b-2 border-primary text-primary font-bold':'text-gray-500 hover:text-primary'"
                    class="px-5 py-4 text-sm uppercase whitespace-nowrap transition bg-transparent shrink-0">Description</button>
            @if($product->additional_information)
            <button @click="tab='additional'"
                    :class="tab==='additional'?'border-b-2 border-primary text-primary font-bold':'text-gray-500 hover:text-primary'"
                    class="px-5 py-4 text-sm uppercase whitespace-nowrap transition bg-transparent shrink-0">Specifications</button>
            @endif
            <button @click="tab='reviews'"
                    :class="tab==='reviews'?'border-b-2 border-primary text-primary font-bold':'text-gray-500 hover:text-primary'"
                    class="px-5 py-4 text-sm uppercase whitespace-nowrap transition bg-transparent shrink-0">Reviews ({{$product->reviews_count ?? 0}})</button>
        </div>

        <div class="py-8 px-1 text-sm text-gray-600 leading-relaxed min-h-[200px]">
            <div x-show="tab==='description'" x-cloak>
                @if($product->description)
                    <div class="prose prose-sm max-w-none">{!! nl2br(e($product->description)) !!}</div>
                @else
                    <p class="text-gray-400 italic">No description available for this product.</p>
                @endif

                @if(isset($client->widgets['product_detail_bullets']) && count($client->widgets['product_detail_bullets']))
                <div class="mt-6 bg-gray-50 rounded-xl p-5">
                    <h4 class="text-dark font-bold text-sm mb-3 flex items-center gap-2"><i class="fas fa-list-ul text-primary"></i> Key Features</h4>
                    <ul class="space-y-2">
                        @foreach($client->widgets['product_detail_bullets'] as $bullet)
                        <li class="flex items-start gap-2 text-sm text-gray-600">
                            <i class="fas fa-check text-primary text-xs mt-1 shrink-0"></i> {{ $bullet }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <div x-show="tab==='additional'" x-cloak>
                @if($product->additional_information)
                    <div class="prose prose-sm max-w-none">{!! nl2br(e($product->additional_information)) !!}</div>
                @else
                    <p class="text-gray-400 italic">No additional specifications provided.</p>
                @endif
            </div>

            <div x-show="tab==='reviews'" x-cloak>
                @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
            </div>
        </div>
    </div>

    {{-- === RELATED PRODUCTS === --}}
    @if(isset($relatedProducts) && count($relatedProducts) > 0)
    <div class="mt-16">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-dark">You May Also Like</h2>
            <div class="w-14 h-1 bg-primary mx-auto mt-3 rounded-full"></div>
        </div>

        <div class="flex md:grid md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6 overflow-x-auto snap-x snap-mandatory hide-scroll pb-4 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0">
            @foreach($relatedProducts->take(5) as $rp)
            <div class="bg-white group rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition duration-300 shrink-0 w-[55vw] sm:w-[40vw] md:w-auto snap-start">
                <div class="relative bg-gray-50 aspect-square flex items-center justify-center p-5 overflow-hidden mix-blend-multiply">
                    @if($rp->sale_price && $rp->regular_price > 0)
                    @php $rpPct = round((($rp->regular_price - $rp->sale_price) / $rp->regular_price) * 100); @endphp
                    <div class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded z-10">-{{$rpPct}}%</div>
                    @endif

                    <a href="{{$clean?$baseUrl.'/product/'.$rp->slug:route('shop.product',['shop'=>$client->slug,'product'=>$rp->slug])}}" class="block w-full h-full">
                        @if($rp->thumbnail)
                        <img src="{{asset('storage/'.$rp->thumbnail)}}" class="w-full h-full object-contain group-hover:scale-105 transition duration-500">
                        @endif
                    </a>

                    {{-- Hover quick actions --}}
                    <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-2 opacity-0 translate-y-3 group-hover:opacity-100 group-hover:translate-y-0 transition duration-300 z-20">
                        <a href="{{$clean?$baseUrl.'/product/'.$rp->slug:route('shop.product',['shop'=>$client->slug,'product'=>$rp->slug])}}"
                           class="w-9 h-9 bg-white rounded-full flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white shadow-md transition text-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>

                <div class="p-3">
                    <a href="{{$clean?$baseUrl.'/product/'.$rp->slug:route('shop.product',['shop'=>$client->slug,'product'=>$rp->slug])}}"
                       class="text-[13px] text-gray-700 hover:text-primary transition line-clamp-2 font-medium mb-1.5 block leading-tight">
                        {{$rp->name}}
                    </a>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-dark">৳{{number_format((float)($rp->sale_price ?? $rp->regular_price ?? 0))}}</span>
                        @if($rp->sale_price && $rp->regular_price > 0)
                        <span class="text-xs text-gray-400 line-through">৳{{number_format((float)$rp->regular_price)}}</span>
                        @endif
                    </div>
                    <div class="flex items-center text-[#ffb522] text-[10px] gap-0.5 mt-1">
                        @php $rpRating = (float)($rp->average_rating ?? 5); @endphp
                        @for($i=1;$i<=5;$i++)
                            @if($i<=$rpRating)<i class="fas fa-star"></i>
                            @elseif($i-0.5<=$rpRating)<i class="fas fa-star-half-alt"></i>
                            @else<i class="far fa-star text-gray-300"></i>
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>{{-- end container --}}

{{-- === STICKY MOBILE CTA BAR === --}}
<div class="fixed bottom-[56px] left-0 right-0 z-40 md:hidden px-4 pb-2" x-data x-show="true">
    <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl border border-gray-100 p-3 flex gap-3">
        @if(!($client->widgets['show_order_button'] ?? true) && ($client->widgets['show_chat_button'] ?? false))
            @if($client->phone)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text={{ urlencode('I want to order: '.$product->name) }}"
               target="_blank"
               class="flex-1 bg-[#25d366] text-white h-12 rounded-xl flex items-center justify-center gap-2 font-bold text-sm">
                <i class="fab fa-whatsapp text-lg"></i> WhatsApp Order
            </a>
            @endif
        @else
            <button type="button" onclick="document.querySelector('[\\@click=\\'addToCart\\']')?.click(); window.scrollTo({top:0,behavior:'smooth'})"
                    class="flex-1 btn-primary h-12 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow">
                <i class="fas fa-cart-plus"></i> Add to Cart
            </button>
            <a href="{{$checkoutUrl}}"
               class="flex-1 btn-dark h-12 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow">
                <i class="fas fa-bolt"></i> Buy Now
            </a>
        @endif
    </div>
</div>

<script>
    function productData() {
        return {
            qty: 1,
            basePrice: {{ $initPrice }},
            currentPrice: {{ $initPrice }},
            selectedVariantName: '',
            activeImage: '{{ $product->thumbnail ? asset("storage/".$product->thumbnail) : "" }}',
            isLoading: false,
            added: false,

            updateVariant(event) {
                const p = parseFloat(event.target.value);
                if (!isNaN(p) && p > 0) this.currentPrice = p;
                this.selectedVariantName = event.target.getAttribute('data-name');
            },

            async addToCart() {
                if (this.isLoading || this.added) return;
                this.isLoading = true;
                this.added = false;
                try {
                    const fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    fd.append('product_id', {{ $product->id }});
                    fd.append('quantity', this.qty);
                    if (this.selectedVariantName) {
                        fd.append('attributes', this.selectedVariantName);
                        fd.append('price', this.currentPrice);
                    }

                    const res = await fetch('{{ $cartUrl }}', { method: 'POST', body: fd });

                    if (res.ok) {
                        this.added = true;
                        // Update header cart counters
                        document.querySelectorAll('[class*="fa-shopping"] ~ span, [class*="fa-cart"] ~ span').forEach(el => {
                            let n = parseInt(el.innerText||0);
                            el.innerText = n + parseInt(this.qty);
                            el.classList.add('scale-125');
                            setTimeout(() => el.classList.remove('scale-125'), 400);
                        });
                        setTimeout(() => { this.added = false; }, 2500);
                    } else {
                        alert('Failed to add to cart. Please try again.');
                    }
                } catch(e) {
                    console.error('Cart error:', e);
                    alert('Network error, please try again.');
                } finally {
                    this.isLoading = false;
                }
            },

            async buyNow() {
                await this.addToCart();
                if (this.added) {
                    window.location.href = '{{ $checkoutUrl }}';
                }
            },

            toggleWishlist() {
                // Wishlist logic placeholder
                console.log('Wishlist toggled for product {{ $product->id }}');
            }
        }
    }
</script>

@endsection
