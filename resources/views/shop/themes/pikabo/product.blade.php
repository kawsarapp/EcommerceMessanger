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

<div class="bg-[#f5f6f8] min-h-screen py-6" x-data="productApp()">
<script>
function productApp() {
    return {
        mainImg: '{{asset('storage/'.$product->thumbnail)}}', 
        qty: 1, 
        color: '', 
        size: '',
        hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
        variants: {!! json_encode($product->has_variants ? $product->variants : [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        
        get currentVariant() {
            if (!this.hasVariants) return null;
            let c = this.color ? this.color.trim() : null;
            let s = this.size ? this.size.trim() : null;
            return this.variants.find(v => {
                let matchesColor = c ? (v.color && v.color.trim() === c) : true;
                let matchesSize = s ? (v.size && v.size.trim() === s) : true;
                return matchesColor && matchesSize;
            });
        },
        
        get displayPrice() {
            if (this.currentVariant && this.currentVariant.price > 0) {
                return parseFloat(this.currentVariant.price).toLocaleString();
            }
            return '{{ number_format($product->sale_price ?? $product->regular_price) }}';
        },

        get stockStatus() {
            if (this.hasVariants) {
                if (!this.color && !this.size) return '{{ $product->stock_status }}';
                if (this.currentVariant) {
                    return this.currentVariant.stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
                }
                return 'out_of_stock';
            }
            return '{{ $product->stock_status }}';
        },

        get availableStock() {
            if (this.hasVariants) {
                return this.currentVariant ? this.currentVariant.stock_quantity : 0;
            }
            return {{ $product->stock_quantity ?? 0 }};
        }
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
                         :class="{ 'border-bdblue border-2': mainImg === '{{asset('storage/'.$product->thumbnail)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$product->thumbnail)}}' }"
                         class="w-16 h-16 border rounded cursor-pointer p-1 flex items-center justify-center bg-white shrink-0">
                         @if($product->sale_price)
                            <span class="absolute text-[8px] bg-red-500 text-white font-bold px-1 top-0 left-0 bg-yellow-400 text-black z-10">%</span>
                         @endif
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain">
                    </div>
                    
                    @foreach($product->gallery ?? [] as $img)
                    <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                         :class="{ 'border-bdblue border-2': mainImg === '{{asset('storage/'.$img)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$img)}}' }"
                         class="w-16 h-16 border rounded cursor-pointer p-1 flex items-center justify-center bg-white shrink-0">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain">
                    </div>
                    @endforeach
                </div>

                {{-- Main Image Area --}}
                <div class="flex-1 border border-gray-100 rounded p-4 relative flex justify-center items-center min-h-[400px]">
                    @if($product->sale_price)
                        <div class="absolute top-4 left-4 bg-red-600 text-white font-extrabold px-3 py-1.5 rounded-lg shadow-sm z-10">-{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}% OFF</div>
                    @endif
                    
                    <img :src="mainImg" class="max-w-full max-h-[400px] object-contain transition-opacity duration-300 z-0" alt="{{$product->name}}">
                    
                    <button class="absolute top-4 right-4 text-gray-300 hover:text-red-500 transition"><i class="fas fa-heart text-2xl"></i></button>
                    
                    <div class="absolute bottom-4 left-0 right-0 flex gap-4 px-4 w-full justify-between">
                        
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])
<button type="button" class="w-1/2 prod-btn-outline py-2.5 rounded text-sm uppercase">Add to Cart</button>
                        <button type="submit" form="checkout-form" class="w-1/2 prod-btn-solid py-2.5 rounded text-sm uppercase">Buy Now</button>
                    </div>
                </div>
            </div>

            {{-- Right Side: Info & Form --}}
            <div class="lg:w-[35%] flex flex-col">
                {{-- Title & Meta --}}
                <div class="flex justify-between items-start mb-2">
                    <h1 class="text-xl font-bold text-gray-800 leading-snug">{{$product->name}}</h1>
                    <button class="text-gray-400 hover:text-bdblue flex items-center gap-1 text-xs whitespace-nowrap"><i class="fas fa-share"></i> Share</button>
                </div>
                
                <div class="flex items-center gap-2 text-[10px] font-semibold text-gray-500 mb-2">
                    @php 
                        $rating = $product->average_rating ?? 0;
                        $reviewsCount = $product->reviews_count ?? 0;
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
                    <a href="#reviews-section" class="text-bdblue hover:underline">Add Your Review</a>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-600 mb-4 pb-4 border-b border-gray-100">
                    <span>Brand: <a href="#" class="text-bdblue hover:underline">{{$product->brand ?? 'Generic'}}</a></span>
                    <span class="text-gray-300">|</span>
                    <span>Sold by: <a href="#" class="text-bdblue hover:underline">{{$client->shop_name}} Official</a></span>
                </div>

                {{-- Pricing --}}
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-xl font-bold text-bdblue">?<span x-text="displayPrice"></span></span>
                    @if($product->sale_price)
                        <del class="text-sm text-gray-400">?{{number_format($product->regular_price)}}</del>
                        <span class="text-[10px] font-bold text-red-500 border border-red-500 px-1 py-0.5 rounded">-{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}%</span>
                    @endif
                </div>

                {{-- Promotional Banner Placeholder --}}
                @if($client->banner)
                <div class="mb-6 rounded overflow-hidden">
                    <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-20 object-cover" alt="{{$client->shop_name}} Offers">
                </div>
                @endif

                @include('shop.partials.product-variations')

                {{-- EMI & Warranty Box Info --}}
                <div class="text-xs text-gray-700 space-y-4">
                    <div class="flex justify-between items-center bg-gray-50 p-2 rounded">
                        <span>EMI from : ?{{ number_format(($product->sale_price ?? $product->regular_price) / 12, 2) }}/month</span>
                        <a href="#" class="text-bdblue hover:underline flex items-center gap-1 font-semibold">Know More <i class="fas fa-chevron-right text-[8px]"></i></a>
                    </div>
                    <div class="flex items-center gap-2 p-2">
                        <span class="font-semibold text-gray-600 w-20">Warranty :</span>
                        <span>{{$product->warranty ?? '12 Months Official Warranty'}}</span>
                    </div>
                    
                    <div class="pt-4">
                        <h4 class="font-bold text-gray-800 mb-2">Available Offer</h4>
                        <div class="flex items-start gap-2 mb-4">
                            <i class="fas fa-tag text-red-500 text-sm mt-0.5"></i>
                            <span class="text-gray-600 leading-tight">Please visit this link for Bimaify Insurance Details: <a href="#" class="text-bdblue hover:underline">Bimaify Insurance</a></span>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-100 p-3 rounded flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-shield-alt text-bdblue text-lg"></i>
                                <span class="font-semibold text-gray-700">{{$client->shop_name}} Assured</span>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 text-[10px]"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rightmost Sidebar: Features / Points --}}
            <div class="lg:w-[20%] border-l border-gray-100 pl-6 hidden lg:block">
                <h4 class="font-bold text-xs text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Available Offer</h4>
                
                <div class="bg-[#f0f8ff] border border-blue-100 p-4 rounded mb-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="bg-bdblue text-white w-8 h-8 rounded flex items-center justify-center shadow-sm">
                            <i class="fas fa-star text-xs"></i>
                        </div>
                        <span class="font-bold text-sm text-dark">Club Points</span>
                    </div>
                    <div class="text-xs text-gray-600 pl-11">Earn {{ round(($product->sale_price ?? $product->regular_price) * 0.02) }} Club Points</div>
                </div>

                {{-- Feature list --}}
                <div class="mt-6 space-y-4 text-xs text-gray-600">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-undo mt-0.5 text-gray-400"></i>
                        <div>
                            <span class="font-semibold block text-gray-800">3 Days Easy Return</span>
                            <a href="#" class="text-bdblue hover:underline">Know More</a>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-truck mt-0.5 text-gray-400"></i>
                        <div>
                            <span class="font-semibold block text-gray-800">Fast Delivery Nationwide</span>
                            <span class="text-gray-500 block mt-1">Within 48 Hours in Dhaka</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-certificate mt-0.5 text-gray-400"></i>
                        <div>
                            <span class="font-semibold block text-gray-800">100% Authentic Products</span>
                            <span class="text-gray-500 block mt-1">Direct from Official Distributors</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Tabs & Description Segment --}}
        <div class="mb-12 mt-8" id="reviews-section">
            {{-- Tab Headers --}}
            <div class="flex border-b border-gray-200 gap-1 overflow-x-auto hide-scroll bg-white rounded-t">
                <button @click="tab = 'description'" :class="{'text-bdblue border-b-2 border-bdblue font-bold': tab === 'description', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'description'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Description</button>
                <button @click="tab = 'specifications'" :class="{'text-bdblue border-b-2 border-bdblue font-bold': tab === 'specifications', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'specifications'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Specifications</button>
                <button @click="tab = 'reviews'" :class="{'text-bdblue border-b-2 border-bdblue font-bold': tab === 'reviews', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'reviews'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Reviews ({{ $product->reviews_count ?? 0 }})</button>
            </div>
            
            {{-- Tab Contents --}}
            <div class="p-6 bg-white border border-t-0 border-gray-200 rounded-b min-h-[400px] text-sm text-gray-600 leading-relaxed font-medium">
                <div x-show="tab === 'description' || !tab" class="animate-fade-in text-justify max-w-4xl">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
                
                <div x-show="tab === 'specifications'" class="animate-fade-in hidden">
                    <div class="max-w-xl bg-gray-50 rounded-lg border border-gray-200 p-6">
                        <div class="grid grid-cols-2 gap-y-4 text-sm">
                            <div class="font-bold text-dark">Brand</div><div class="text-right">{{$product->brand ?? 'Generic'}}</div>
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">SKU</div><div class="text-right border-t border-gray-200 pt-4">{{$product->id}}{{$product->client_id*87}}</div>
                            @if($product->material)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Material</div><div class="text-right border-t border-gray-200 pt-4">{{$product->material}}</div>
                            @endif
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Warranty</div><div class="text-right border-t border-gray-200 pt-4">{{$product->warranty ?? 'N/A'}}</div>
                            @if($client->show_return_warranty ?? true)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Return Policy</div><div class="text-right border-t border-gray-200 pt-4">{{$product->return_policy ?? '7 Days Easy Return'}}</div>
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

