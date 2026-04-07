@extends('shop.themes.vegist.layout')

@section('title', $product->name . ' - ' . $client->shop_name)

@section('content')
@php 
    $clean       = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
    $baseUrl     = $clean ? 'https://'.$clean : route('shop.show', $client->slug);
    // Cart & Checkout URLs
    $cartAddUrl   = $clean ? $baseUrl.'/cart/add' : route('shop.cart.add', $client->slug);
    $cartPageUrl  = $clean ? $baseUrl.'/cart'     : route('shop.cart', $client->slug);
    $checkoutUrl  = $clean
        ? $baseUrl.'/checkout/'.$product->slug
        : route('shop.checkout', ['slug' => $client->slug, 'productSlug' => $product->slug]);
    $initPrice   = (float)($product->sale_price ?? $product->regular_price ?? 0);
    $inStock     = ($product->stock_status ?? 'in_stock') === 'in_stock';
    $stockQty    = (int)($product->stock_quantity ?? 0);
    // Normalize gallery field
    $productGallery = [];
    if ($product->gallery) {
        $productGallery = is_string($product->gallery) ? (json_decode($product->gallery, true) ?? []) : (array)$product->gallery;
    }
    // Normalize variants from sizes/colors
    $productSizes  = is_array($product->sizes) ? $product->sizes : (json_decode($product->sizes ?? '[]', true) ?? []);
    $productColors = is_array($product->colors) ? $product->colors : (json_decode($product->colors ?? '[]', true) ?? []);
    $avgRating     = (float)($product->avg_rating ?? 5);
    $totalReviews  = (int)($product->total_reviews ?? 0);
    // Discount %
    $pct = 0;
    if ($product->sale_price && $product->regular_price > 0) {
        $pct = round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100);
    }
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

<div x-data="{ 
    mainImg: '{{$product->thumbnail ? asset('storage/'.$product->thumbnail) : ''}}',
    qty: 1, 
    color: '', 
    size: '',
    hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
    variants: {{ $product->has_variants ? $product->variants->toJson() : '[]' }},
    basePrice: {{ (float)($product->sale_price ?? $product->regular_price ?? 0) }},
    currentPrice: {{ (float)($product->sale_price ?? $product->regular_price ?? 0) }},
    currentVariant: null,
    zoomPos: '50% 50%',
    showZoom: false,
    showLightbox: false,
    lightboxImg: '',
    updatePrice() {
        if(this.hasVariants) {
            let matched = this.variants.find(v => 
                (v.color === this.color || (!v.color && !this.color)) && 
                (v.size === this.size || (!v.size && !this.size))
            );
            if(matched) {
                this.currentVariant = matched;
                this.currentPrice = parseInt(matched.price || this.basePrice);
                if(matched.image) {
                    this.mainImg = '/storage/' + matched.image;
                }
            } else {
                this.currentVariant = null;
                this.currentPrice = this.basePrice;
            }
        }
    }
}" x-init="$watch('color', () => updatePrice()); $watch('size', () => updatePrice()); updatePrice();" @variant-change.window="color = $event.detail.color; size = $event.detail.size" class="max-w-[1400px] mx-auto px-4 xl:px-8 pb-24 md:pb-16">

    {{-- === MAIN PRODUCT GRID === --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">

        {{-- ─── Left: Image Gallery ─── --}}
        <div class="lg:col-span-4">

            {{-- Main Image --}}
            <div class="relative bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center overflow-hidden"
                 style="aspect-ratio:1/1;"
                 @mousemove="if(window.innerWidth > 768){ zoomPos=(($event.offsetX/$el.offsetWidth)*100)+'% '+(($event.offsetY/$el.offsetHeight)*100)+'%' }"
                 @mouseenter="showZoom = window.innerWidth > 768"
                 @mouseleave="showZoom = false"
                 @click="showLightbox = true; lightboxImg = mainImg">

                @if($pct > 0)
                <div class="absolute top-3 left-3 bg-primary text-white text-xs font-bold px-2 py-1 rounded z-20 shadow">-{{$pct}}%</div>
                @endif

                {{-- Normal image (desktop hides on zoom) --}}
                <img :src="mainImg" alt="{{$product->name}}"
                     class="w-full h-full object-contain p-6 transition-opacity duration-200 cursor-zoom-in"
                     :class="showZoom ? 'opacity-0' : 'opacity-100'">

                {{-- Desktop Zoom Overlay --}}
                <div x-show="showZoom"
                     class="absolute inset-0 pointer-events-none bg-no-repeat z-10 hidden md:block"
                     :style="'background-image:url(\'' + mainImg + '\'); background-position:' + zoomPos + '; background-size:260%;'">
                </div>

                {{-- Mobile tap hint --}}
                <div class="absolute bottom-3 right-3 bg-black/30 text-white text-[9px] px-2 py-1 rounded-full flex items-center gap-1 md:hidden">
                    <i class="fas fa-expand text-[9px]"></i> Tap to enlarge
                </div>
                {{-- Desktop zoom hint --}}
                <div class="absolute bottom-3 right-3 bg-white/80 text-gray-500 text-[10px] px-2 py-1 rounded-full hidden md:flex items-center gap-1">
                    <i class="fas fa-search text-[10px]"></i> Hover to zoom
                </div>
            </div>

            {{-- Lightbox Modal (Mobile & Desktop) --}}
            <div x-show="showLightbox" x-cloak
                 class="fixed inset-0 bg-black/90 z-[9999] flex items-center justify-center p-4"
                 @click.self="showLightbox = false"
                 @keydown.escape.window="showLightbox = false">
                <button @click="showLightbox = false"
                        class="absolute top-4 right-4 text-white text-2xl w-10 h-10 flex items-center justify-center bg-white/20 rounded-full hover:bg-white/40 transition z-10">
                    <i class="fas fa-times"></i>
                </button>
                <img :src="lightboxImg" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
            </div>

            {{-- Thumbnails --}}
            @if($product->thumbnail || count($productGallery) > 0)
            <div class="flex gap-2 mt-3 overflow-x-auto hide-scroll pb-1">
                @if($product->thumbnail)
                <div @click="mainImg='{{asset('storage/'.$product->thumbnail)}}'; lightboxImg=mainImg"
                     class="border-2 rounded-lg cursor-pointer p-1.5 bg-white transition shrink-0 w-16 h-16"
                     :class="mainImg==='{{asset('storage/'.$product->thumbnail)}}'?'border-primary shadow':'border-gray-100 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-contain">
                </div>
                @endif
                @foreach($productGallery as $img)
                <div @click="mainImg='{{asset('storage/'.$img)}}'; lightboxImg=mainImg"
                     class="border-2 rounded-lg cursor-pointer p-1.5 bg-white transition shrink-0 w-16 h-16"
                     :class="mainImg==='{{asset('storage/'.$img)}}'?'border-primary shadow':'border-gray-100 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-contain">
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
                    @for($i=1;$i<=5;$i++)
                        @if($i<=$avgRating)<i class="fas fa-star text-[#ffb522] text-xs"></i>
                        @elseif($i-0.5<=$avgRating)<i class="fas fa-star-half-alt text-[#ffb522] text-xs"></i>
                        @else<i class="far fa-star text-gray-300 text-xs"></i>
                        @endif
                    @endfor
                    <span class="text-gray-400 text-xs ml-1">({{$totalReviews}} Reviews)</span>
                </div>
                <div class="text-[12px]">
                    @if($inStock)
                        <span class="text-green-600 font-medium"><i class="fas fa-circle text-[7px] mr-1"></i>In Stock @if($stockQty > 0)<span class="text-gray-400">({{$stockQty}} left)</span>@endif</span>
                    @else
                        <span class="text-primary font-medium"><i class="fas fa-circle text-[7px] mr-1"></i>Out of Stock</span>
                    @endif
                </div>
                @if($product->sku)
                <div class="text-[12px] text-gray-400">SKU: <span class="text-gray-600">{{$product->sku}}</span></div>
                @endif
            </div>

            {{-- Price --}}
            <div class="flex items-baseline gap-3 mb-5">
                <span class="text-3xl font-black text-dark">&#2547;<span x-text="new Intl.NumberFormat('en-IN').format(currentPrice)"></span></span>
                @if($pct > 0)
                <span class="text-lg text-gray-400 line-through">&#2547;{{number_format((float)$product->regular_price)}}</span>
                <span class="text-xs bg-primary/5 text-primary border border-primary/20 px-2 py-0.5 rounded font-semibold">Save {{$pct}}%</span>
                @endif
            </div>

            {{-- Short Description --}}
            @if($product->short_description)
            <p class="text-[13px] text-gray-500 mb-6 leading-relaxed border-l-2 border-primary/30 pl-3">
                {{$product->short_description}}
            </p>
            @endif

            {{-- Variations / Buy Form --}}


            {{-- Wishlist --}}
            <button type="button" @click="toggleWishlist"
                    class="flex items-center gap-2 text-sm text-gray-500 hover:text-primary transition mb-6 group w-max">
                <i class="far fa-heart group-hover:hidden"></i>
                <i class="fas fa-heart hidden group-hover:block text-primary"></i>
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
                           target="_blank" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-primary hover:text-white flex items-center justify-center text-gray-500 transition text-xs">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Payment Icons --}}
            <div class="flex items-center gap-3 mt-4">
                <span class="text-[11px] text-gray-400">Secure checkout:</span>
                <i class="fab fa-cc-visa text-2xl text-blue-700"></i>
                <i class="fab fa-cc-mastercard text-2xl text-primary"></i>
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
                    class="px-5 py-4 text-sm uppercase whitespace-nowrap transition bg-transparent shrink-0">Reviews ({{$totalReviews}})</button>
        </div>

        <div class="py-8 px-1 text-sm text-gray-600 leading-relaxed min-h-[200px]">
            <div x-show="tab==='description'" x-cloak>
                @php
                    // Safe description rendering — strip dangerous tags but allow <br>
                    $descHtml = '';
                    if ($product->description) {
                        $descHtml = nl2br(strip_tags(trim($product->description), '<b><strong><em><ul><ol><li><br><p><h2><h3><h4>'));
                    } elseif ($product->long_description) {
                        $descHtml = nl2br(strip_tags(trim($product->long_description), '<b><strong><em><ul><ol><li><br><p><h2><h3><h4>'));
                    }
                @endphp
                @if($descHtml)
                    <div class="text-sm text-gray-600 leading-[1.9] space-y-2">{!! $descHtml !!}</div>
                @else
                    <p class="text-gray-400 italic">No description available for this product.</p>
                @endif

                {{-- Key Features from product model --}}
                @if($product->key_features && count((array)$product->key_features) > 0)
                <div class="mt-6 bg-gray-50 rounded-xl p-5">
                    <h4 class="text-dark font-bold text-sm mb-3 flex items-center gap-2"><i class="fas fa-list-ul text-primary"></i> Key Features</h4>
                    <ul class="space-y-2">
                        @foreach((array)$product->key_features as $feature)
                        <li class="flex items-start gap-2 text-sm text-gray-600">
                            <i class="fas fa-check text-primary text-xs mt-1 shrink-0"></i> {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @elseif(isset($client->widgets['product_detail_bullets']) && count($client->widgets['product_detail_bullets']))
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

                {{-- Warranty / return policy --}}
                @if($product->warranty || $product->return_policy)
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @if($product->warranty)
                    <div class="bg-green-50 border border-green-100 rounded-lg p-3 flex items-start gap-2">
                        <i class="fas fa-shield-alt text-green-500 mt-0.5"></i>
                        <div><span class="font-semibold text-dark text-xs">Warranty:</span><p class="text-xs text-gray-600 mt-0.5">{{$product->warranty}}</p></div>
                    </div>
                    @endif
                    @if($product->return_policy)
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex items-start gap-2">
                        <i class="fas fa-undo text-blue-500 mt-0.5"></i>
                        <div><span class="font-semibold text-dark text-xs">Return Policy:</span><p class="text-xs text-gray-600 mt-0.5">{{$product->return_policy}}</p></div>
                    </div>
                    @endif
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
                @if($product->reviews && $product->reviews->count() > 0)
                <div class="space-y-5">
                    @foreach($product->reviews as $review)
                    <div class="border border-gray-100 rounded-xl p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">
                                {{ strtoupper(substr($review->customer_name ?? 'C', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-dark">{{ $review->customer_name ?? 'Customer' }}</p>
                                <div class="flex text-[#ffb522] text-xs gap-0.5 mt-0.5">
                                    @for($s=1;$s<=5;$s++)
                                        @if($s<=$review->rating)<i class="fas fa-star"></i>
                                        @else<i class="far fa-star text-gray-300"></i>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            <span class="ml-auto text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        @if($review->comment)
                        <p class="text-sm text-gray-600 leading-relaxed border-l-2 border-gray-100 pl-3 mt-2">{{ $review->comment }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Summary Rating Block --}}
                <div class="mt-6 bg-gray-50 rounded-xl p-5 flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-4xl font-black text-dark">{{ number_format($avgRating, 1) }}</div>
                        <div class="flex text-[#ffb522] text-sm justify-center gap-0.5 mt-1">
                            @for($s=1;$s<=5;$s++)
                                @if($s<=$avgRating)<i class="fas fa-star"></i>
                                @elseif($s-0.5<=$avgRating)<i class="fas fa-star-half-alt"></i>
                                @else<i class="far fa-star text-gray-300"></i>
                                @endif
                            @endfor
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $totalReviews }} reviews</p>
                    </div>
                    <div class="flex-1">
                        @for($s=5;$s>=1;$s--)
                        @php $sCount = $product->reviews->where('rating', $s)->count(); $sWidth = $totalReviews>0 ? round(($sCount/$totalReviews)*100) : 0; @endphp
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs text-gray-500 w-4">{{$s}}</span>
                            <i class="fas fa-star text-[#ffb522] text-xs"></i>
                            <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                <div class="bg-[#ffb522] h-1.5 rounded-full" style="width:{{$sWidth}}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 w-5">{{$sCount}}</span>
                        </div>
                        @endfor
                    </div>
                </div>
                @else
                    <p class="text-gray-400 italic text-sm text-center py-8">
                        <i class="far fa-comment-dots text-3xl text-gray-200 block mb-3"></i>
                        No reviews yet. Be the first to review this product!
                    </p>
                @endif
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
            @php $rpPct = ($rp->sale_price && $rp->regular_price > 0) ? round((($rp->regular_price - $rp->sale_price) / $rp->regular_price) * 100) : 0; @endphp
            <div class="bg-white group rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition duration-300 shrink-0 w-[55vw] sm:w-[40vw] md:w-auto snap-start">
                <div class="relative bg-gray-50 aspect-square flex items-center justify-center p-5 overflow-hidden mix-blend-multiply">
                    @if($rpPct > 0)
                    <div class="absolute top-2 left-2 bg-primary text-white text-[10px] font-bold px-1.5 py-0.5 rounded z-10">-{{$rpPct}}%</div>
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
                        <span class="text-sm font-bold text-dark">&#2547;{{number_format((float)($rp->sale_price ?? $rp->regular_price ?? 0))}}</span>
                        @if($rpPct > 0)
                        <span class="text-xs text-gray-400 line-through">&#2547;{{number_format((float)$rp->regular_price)}}</span>
                        @endif
                    </div>
                    <div class="flex items-center text-[#ffb522] text-[10px] gap-0.5 mt-1">
                        @php $rpRating = (float)($rp->avg_rating ?? 5); @endphp
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



@include('shop.partials.product-sticky-bar')
@endsection
