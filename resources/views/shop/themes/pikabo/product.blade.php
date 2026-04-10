@extends('shop.themes.pikabo.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .prod-btn-outline {
        border: 1px solid var(--tw-color-primary);
        color: var(--tw-color-primary);
        background: transparent;
        font-weight: 600;
        transition: all 0.2s;
    }
    .prod-btn-outline:hover {
        background: rgba(0,0,0,0.04);
    }
    .prod-btn-solid {
        background: var(--tw-color-primary);
        color: white;
        font-weight: 600;
        transition: all 0.2s;
    }
    .prod-btn-solid:hover {
        opacity: 0.85;
    }
    
    .super-offer-large {
        position: absolute;
        top: 40px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        width: 140px;
    }
    .special-price-tag {
        position: absolute;
        top: 130px;
        left: 50%;
        transform: translateX(-50%);
        background: #ffde00;
        color: black;
        font-weight: 900;
        padding: 4px 12px;
        font-size: 16px;
        z-index: 10;
        width: 220px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .warranty-logo {
        position: absolute;
        bottom: 20px;
        left: 20px;
        width: 100px;
        z-index: 10;
    }
    .thumbnail-scroll::-webkit-scrollbar { display: none; }
</style>

<div class="bg-[#f5f6f8] min-h-screen py-6" x-data="productApp()" x-init="$watch('color', () => updatePrice()); $watch('size', () => updatePrice()); updatePrice();" @variant-change.window="color = $event.detail.color; size = $event.detail.size">
<script>
function productApp() {
    return {
        mainImg: '{{asset('storage/'.$product->thumbnail)}}', 
        qty: 1, 
        color: '', 
        size: '',
        hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
        variants: {!! json_encode($product->has_variants ? $product->variants : [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        currentPrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
        
        updatePrice() {
            if(this.hasVariants) {
                let c = this.color ? this.color.trim() : null;
                let s = this.size ? this.size.trim() : null;
                let matched = this.variants.find(v => {
                    let matchesColor = c ? (v.color && v.color.trim() === c) : true;
                    let matchesSize = s ? (v.size && v.size.trim() === s) : true;
                    return matchesColor && matchesSize;
                });
                
                if(matched) {
                    this.currentPrice = parseInt(matched.price || {{ $product->sale_price ?? $product->regular_price ?? 0 }});
                    if (matched.image) {
                        this.mainImg = '/storage/' + matched.image;
                    }
                } else {
                    this.currentPrice = {{ $product->sale_price ?? $product->regular_price ?? 0 }};
                }
            } else {
                this.currentPrice = {{ $product->sale_price ?? $product->regular_price ?? 0 }};
            }
        },
        
        get displayPrice() {
            return this.currentPrice ? this.currentPrice.toLocaleString() : '{{ number_format($product->sale_price ?? $product->regular_price) }}';
        },

        get stockStatus() {
            return '{{ $product->stock_status }}';
        },

        get availableStock() {
            return {{ $product->stock_quantity ?? 0 }};
        },
        tab: 'description',
        zoomPos: '50% 50%',
        showZoom: false
    };
}
</script>
    <div class="max-w-[1400px] mx-auto px-4">
        
        <div class="bg-white p-6 rounded shadow-sm border border-gray-100 flex flex-col lg:flex-row gap-8">
            
            {{-- Left Side: Image Gallery --}}
            <div class="lg:w-[45%] flex gap-4">
                {{-- Vertical Thumbnails --}}
                <div class="w-16 flex flex-col gap-2 overflow-y-auto thumbnail-scroll max-h-[500px]">
                    <div @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                         :class="{ 'border-primary border-2': mainImg === '{{asset('storage/'.$product->thumbnail)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$product->thumbnail)}}' }"
                         class="w-16 h-16 border rounded cursor-pointer p-1 flex items-center justify-center bg-white shrink-0">
                         @if($product->sale_price)
                            <span class="absolute text-[8px] bg-primary text-white font-bold px-1 top-0 left-0 bg-yellow-400 text-black z-10">%</span>
                         @endif
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain">
                    </div>
                    
                    @foreach($product->gallery ?? [] as $img)
                    <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                         :class="{ 'border-primary border-2': mainImg === '{{asset('storage/'.$img)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$img)}}' }"
                         class="w-16 h-16 border rounded cursor-pointer p-1 flex items-center justify-center bg-white shrink-0">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain">
                    </div>
                    @endforeach
                </div>

                {{-- Main Image Area --}}
                <div class="flex-1 border border-gray-100 rounded p-4 relative flex justify-center items-center min-h-[400px] overflow-hidden bg-white group"
                     style="aspect-ratio:1/1;"
                     @mousemove="if(window.innerWidth > 768){ zoomPos=(($event.offsetX/$el.offsetWidth)*100)+'% '+(($event.offsetY/$el.offsetHeight)*100)+'%' }"
                     @mouseenter="showZoom = window.innerWidth > 768"
                     @mouseleave="showZoom = false">
                     
                    @if($product->sale_price)
                        <div class="absolute top-4 left-4 bg-primary text-white text-xs font-extrabold px-3 py-1.5 rounded-lg shadow-sm z-20">-{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}% OFF</div>
                    @endif
                    
                    <img :src="mainImg" class="w-full h-full object-contain transition-opacity duration-300 z-10" :class="showZoom ? 'opacity-0' : 'opacity-100'" alt="{{$product->name}}">
                    
                    {{-- Zoom Overlay --}}
                    <div x-show="showZoom"
                         class="absolute inset-0 pointer-events-none bg-no-repeat z-20 hidden md:block"
                         :style="'background-image:url(\'' + mainImg + '\'); background-position:' + zoomPos + '; background-size:250%;'">
                    </div>
                </div>
            </div>

            {{-- Right Side: Info & Form --}}
            <div class="lg:w-[35%] flex flex-col">
                {{-- Title & Meta --}}
                <div class="flex justify-between items-start mb-2">
                    <h1 class="text-xl font-bold text-gray-800 leading-snug">{{$product->name}}</h1>
                    <button class="text-gray-400 hover:text-primary flex items-center gap-1 text-xs whitespace-nowrap"><i class="fas fa-share"></i> Share</button>
                </div>
                
                <div class="flex items-center gap-2 text-[10px] font-semibold text-gray-500 mb-2">
                    @php 
                        $rating = $product->avg_rating ?? $product->average_rating ?? 0;
                        $reviewsCount = $product->total_reviews ?? $product->reviews_count ?? 0;
                    @endphp
                    <div class="flex text-yellow-400 text-xs">
                        @for($i=1; $i<=5; $i++)
                            @if($i <= $rating)
                                <i class="fas fa-star"></i>
                            @elseif($i - 0.5 <= $rating)
                                <i class="fas fa-star-half-alt"></i>
                            @else
                                <i class="far fa-star text-gray-300"></i>
                            @endif
                        @endfor
                    </div>
                    <span>({{ $reviewsCount }} Reviews)</span>
                    <span class="text-gray-300 mx-1">|</span>
                    <a href="#reviews-section" class="text-primary hover:underline">Add Your Review</a>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-600 mb-4 pb-4 border-b border-gray-100">
                    @if($product->brand)
                    <span>Brand: <a href="{{ $baseUrl }}?brand={{ urlencode($product->brand) }}" class="text-primary hover:underline">{{$product->brand}}</a></span>
                    <span class="text-gray-300">|</span>
                    @endif
                    <span>Sold by: <a href="{{$baseUrl}}" class="text-primary hover:underline">{{$client->shop_name}}</a></span>
                </div>

                {{-- Pricing --}}
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-xl font-bold text-primary">&#2547;<span x-text="displayPrice"></span></span>
                    @if($product->sale_price)
                        <del class="text-sm text-gray-400">&#2547;{{number_format($product->regular_price)}}</del>
                        <span class="text-[10px] font-bold text-primary border border-primary px-1 py-0.5 rounded">-{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}%</span>
                    @endif
                </div>

                {{-- Promotional Banner Placeholder --}}
                @if($client->banner)
                <div class="mb-6 rounded overflow-hidden">
                    <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-20 object-cover" alt="{{$client->shop_name}} Offers">
                </div>
                @endif

                @include('shop.partials.product-variations')

                {{-- Warranty & Return Policy (Dynamic from product) --}}
                @if($product->warranty || $product->return_policy)
                <div class="text-xs text-gray-700 space-y-2">
                    @if($product->warranty)
                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <i class="fas fa-shield-alt text-primary"></i>
                        <span class="font-semibold text-gray-600">Warranty:</span>
                        <span>{{$product->warranty}}</span>
                    </div>
                    @endif
                    @if(($client->show_return_warranty ?? true) && $product->return_policy)
                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <i class="fas fa-undo text-primary"></i>
                        <span class="font-semibold text-gray-600">Return Policy:</span>
                        <span>{{$product->return_policy}}</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Rightmost Sidebar: Dynamic Info Cards (from widgets.info_cards) --}}
            @php
                $infoCardsActive = $client->widgets['info_cards']['active'] ?? true;
                $defaultCards = [
                    ['icon' => 'fas fa-truck',       'title' => 'Fast Delivery',         'desc' => $client->widgets['delivery_time']['text'] ?? 'Quick delivery across Bangladesh.'],
                    ['icon' => 'fas fa-undo',        'title' => 'Easy Return',            'desc' => $product->return_policy ?? 'Hassle-free return policy.'],
                    ['icon' => 'fas fa-shield-alt',  'title' => 'Warranty',               'desc' => $product->warranty ?? ($client->shop_name . ' quality guaranteed.')],
                ];
            @endphp
            @if($infoCardsActive)
            <div class="lg:w-[20%] border-l border-gray-100 pl-6 hidden lg:block">
                <h4 class="font-bold text-xs text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">{{ $client->widgets['info_cards']['heading'] ?? 'Why Shop With Us' }}</h4>

                {{-- Loyalty Points (only if enabled & product has points) --}}
                @if($client->widget('loyalty') && $product->earnable_points > 0)
                <div class="bg-primary/10 border border-primary/20 p-4 rounded mb-4">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="bg-primary text-white w-8 h-8 rounded flex items-center justify-center shadow-sm">
                            <i class="fas fa-star text-xs"></i>
                        </div>
                        <span class="font-bold text-sm text-dark">Loyalty Points</span>
                    </div>
                    <div class="text-xs text-gray-600 pl-11">Earn {{ $product->earnable_points }} Points per item</div>
                </div>
                @endif

                {{-- Dynamic Info Cards --}}
                <div class="space-y-4 text-xs text-gray-600">
                    @foreach($defaultCards as $i => $default)
                    @php
                        $cardIcon  = $client->widgets['info_cards']['items'][$i]['icon']  ?? $default['icon'];
                        $cardTitle = $client->widgets['info_cards']['items'][$i]['title'] ?? $default['title'];
                        $cardDesc  = $client->widgets['info_cards']['items'][$i]['description'] ?? $default['desc'];
                    @endphp
                    @if($cardDesc)
                    <div class="flex items-start gap-3">
                        <i class="{{ $cardIcon }} mt-0.5 text-primary"></i>
                        <div>
                            <span class="font-semibold block text-gray-800">{{ $cardTitle }}</span>
                            <span class="text-gray-500 block mt-0.5">{{ $cardDesc }}</span>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- Tabs & Description Segment --}}
        <div class="mb-12 mt-8" id="reviews-section">
            {{-- Tab Headers --}}
            <div class="flex border-b border-gray-200 gap-1 overflow-x-auto hide-scroll bg-white rounded-t">
                <button @click="tab = 'description'" :class="{'text-primary border-b-2 border-primary font-bold': tab === 'description', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'description'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Description</button>
                <button @click="tab = 'specifications'" :class="{'text-primary border-b-2 border-primary font-bold': tab === 'specifications', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'specifications'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Specifications</button>
                <button @click="tab = 'reviews'" :class="{'text-primary border-b-2 border-primary font-bold': tab === 'reviews', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'reviews'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Reviews ({{ $product->reviews_count ?? 0 }})</button>
            </div>
            
            {{-- Tab Contents --}}
            <div class="p-6 bg-white border border-t-0 border-gray-200 rounded-b min-h-[400px] text-sm text-gray-600 leading-relaxed font-medium">
                <div x-show="tab === 'description' || !tab" class="animate-fade-in text-justify max-w-4xl">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
                
                <div x-show="tab === 'specifications'" class="animate-fade-in hidden">
                    <div class="max-w-xl bg-gray-50 rounded-lg border border-gray-200 p-6">
                        <div class="grid grid-cols-2 gap-y-4 text-sm">
                            @if($product->brand)
                            <div class="font-bold text-dark">Brand</div><div class="text-right">{{$product->brand}}</div>
                            @endif
                            @if($product->sku)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">SKU</div><div class="text-right border-t border-gray-200 pt-4">{{$product->sku}}</div>
                            @endif
                            @if($product->material)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Material</div><div class="text-right border-t border-gray-200 pt-4">{{$product->material}}</div>
                            @endif
                            @if($product->weight)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Weight</div><div class="text-right border-t border-gray-200 pt-4">{{$product->weight}}</div>
                            @endif
                            @if($product->warranty)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Warranty</div><div class="text-right border-t border-gray-200 pt-4">{{$product->warranty}}</div>
                            @endif
                            @if(($client->show_return_warranty ?? true) && $product->return_policy)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Return Policy</div><div class="text-right border-t border-gray-200 pt-4">{{$product->return_policy}}</div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div x-show="tab === 'reviews'" class="animate-fade-in hidden">
                    @include('shop.partials.related-products', ['client' => $client, 'product' => $product, 'relatedProducts' => App\Models\Product::where('client_id', $client->id)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->limit(8)->get()])

@include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
                </div>
            </div>
        </div>

    </div>
</div>

<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  event: 'view_item',
  ecommerce: {
    currency: 'BDT',
    value: {{ $product->sale_price ?? $product->regular_price }},
    items: [{
      item_id: '{{ $product->id }}',
      item_name: '{{ $product->name }}',
      price: {{ $product->sale_price ?? $product->regular_price }},
      quantity: 1
    }]
  }
});
</script>
@include('shop.partials.product-sticky-bar')
@endsection

