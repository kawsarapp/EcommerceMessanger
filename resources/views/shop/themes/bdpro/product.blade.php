@extends('shop.themes.bdpro.layout')
@section('title', $product->meta_title ?? (strtoupper($product->name) . ' | ' . $client->shop_name))

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<div class="max-w-[1400px] mx-auto px-4 py-8" x-data="{ 
    mainImg: '{{asset('storage/'.$product->thumbnail)}}',
    qty: 1,
    color: '',
    size: '',
    hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
    variants: {{ $product->has_variants ? $product->variants->toJson() : '[]' }},
    basePrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    currentPrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    currentVariant: null,
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
}" x-init="$watch('color', () => updatePrice()); $watch('size', () => updatePrice()); updatePrice();">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8 overflow-x-auto whitespace-nowrap hide-scroll">
        <a href="{{$baseUrl}}" class="hover:text-primary transition"><i class="fas fa-home"></i> Home</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <a href="{{$baseUrl}}?category={{$product->category->slug ?? 'all'}}" class="hover:text-primary transition">{{$product->category->name ?? 'Products'}}</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <span class="text-gray-800 font-medium truncate">{{$product->name}}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
        
        {{-- Images (5/12) --}}
        <div class="lg:col-span-5 flex flex-col gap-4">
            <div class="w-full aspect-square bg-white rounded-xl border border-gray-200 overflow-hidden relative">
                <img :src="mainImg" class="w-full h-full object-contain p-4" loading="lazy" alt="{{$product->name}}">
                @if($product->sale_price)
                    @php $savePct = round((($product->regular_price - $product->sale_price)/$product->regular_price)*100); @endphp
                    <span class="absolute top-4 right-4 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                        -{{$savePct}}%
                    </span>
                @endif
            </div>
            
            <div class="flex gap-4 overflow-x-auto hide-scroll px-1 pb-2">
                <div @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                     class="w-20 h-20 shrink-0 border-2 rounded-lg p-1 cursor-pointer transition-all bg-white"
                     :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary shadow-sm' : 'border-gray-200 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-contain" alt="{{$product->name}}">
                </div>
                @foreach($product->gallery ?? [] as $img)
                <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                     class="w-20 h-20 shrink-0 border-2 rounded-lg p-1 cursor-pointer transition-all bg-white"
                     :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary shadow-sm' : 'border-gray-200 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-contain" alt="{{$product->name}}">
                </div>
                @endforeach
            </div>

            @if($product->video_url)
            <a href="{{$product->video_url}}" target="_blank" class="w-full mt-4 flex items-center justify-center gap-2 bg-red-50 text-red-600 font-bold py-3 rounded-xl border border-red-200 hover:bg-red-100 transition">
                <i class="fab fa-youtube text-xl"></i> Watch Video Review
            </a>
            @endif
        </div>

        {{-- Details (4/12) --}}
        <div class="lg:col-span-4 flex flex-col">
            
            <h1 class="text-2xl md:text-3xl font-extrabold text-dark leading-tight mb-4">{{$product->name}}</h1>
            
            @php 
                $reviews = $product->reviews()->where('is_visible', true)->get();
                $rc = $reviews->count();
                $avg = $rc > 0 ? round($reviews->avg('rating'), 1) : 0;
            @endphp
            @if($rc > 0)
            <div class="flex items-center gap-2 mb-4 text-sm">
                <div class="flex text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $i <= round($avg) ? '' : 'text-gray-300' }}"></i>
                    @endfor
                </div>
                <span class="text-blue-600 font-medium">{{$rc}} Reviews</span>
            </div>
            @endif

            <ul class="text-sm text-gray-600 space-y-2 mb-6">
                <li><span class="text-gray-400 w-20 inline-block">Brand:</span> <span class="font-medium text-dark">{{$product->brand ?? 'No Brand'}}</span></li>
                <li><span class="text-gray-400 w-20 inline-block">Category:</span> <span class="font-medium text-primary">{{$product->category->name ?? 'General'}}</span></li>
                <li>
                    <span class="text-gray-400 w-20 inline-block">Status:</span> 
                    <span class="font-medium {{ (isset($product->stock_status) && $product->stock_status == 'out_of_stock') ? 'text-red-500' : 'text-green-600' }}">
                        {{ (isset($product->stock_status) && $product->stock_status == 'out_of_stock') ? 'Out of Stock' : 'In Stock' }}
                    </span>
                </li>
            </ul>

            <div class="bg-gray-50 border border-gray-100 rounded-xl p-5 mb-6">
                <div class="flex items-end gap-3 mb-1">
                    <span class="text-primary text-3xl font-extrabold" x-text="'৳' + new Intl.NumberFormat('en-IN').format(currentPrice)">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    @if($product->sale_price)
                        <del class="text-gray-400 text-lg font-medium">৳{{number_format($product->regular_price)}}</del>
                    @endif
                </div>
                @if($product->sale_price)
                <p class="text-xs text-green-600 font-medium">Extra discount applied. Order now!</p>
                @endif
            </div>

            @if($product->key_features)
            <div class="mb-6">
                <h4 class="font-bold text-dark text-sm mb-3">Key Features:</h4>
                <ul class="space-y-2 text-sm text-gray-600">
                    @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-1"></i> <span>{{$feature}}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif

        </div>

        {{-- Order Column (3/12) --}}
        <div class="lg:col-span-3">
            <div class="bg-white border-2 border-primary/20 rounded-2xl p-6 shadow-sm sticky top-24">
                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-6">
                    
                    @include('shop.partials.product-variations')

                    <div class="pt-4 border-t border-gray-100">
                        <label class="font-bold text-dark text-sm block mb-3">Quantity</label>
                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden w-28 h-10">
                            <button type="button" @click="if(qty>1)qty--" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border-r border-gray-300">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <input type="number" name="qty" x-model="qty" class="w-12 h-full text-center font-bold text-dark p-0 border-none focus:ring-0" readonly>
                            <button type="button" @click="qty++" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border-l border-gray-300">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="w-full bg-gray-200 text-gray-500 font-bold py-3.5 rounded-lg border border-gray-300 cursor-not-allowed">
                                OUT OF STOCK
                            </button>
                        @else
                            @if($client->show_order_button ?? true)
                                @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])
                                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3.5 rounded-lg shadow-md hover:shadow-lg transition flex justify-center items-center gap-2">
                                    <i class="fas fa-shopping-cart"></i> Buy Now
                                </button>
                            @endif

                            @if($client->fb_page_id)
                            <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-full mt-3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-lg flex justify-center items-center gap-2 transition">
                                <i class="fab fa-facebook-messenger"></i> Message
                            </a>
                            @endif
                        @endif
                    </div>
                </form>
            </div>
            
            <div class="mt-6 flex flex-col gap-3">
                @if($client->delivery_charge_inside ?? false)
                <div class="bg-gray-50 rounded-lg p-3 text-[11px] text-gray-600 border border-gray-200 flex items-center gap-3">
                    <i class="fas fa-truck text-primary text-lg"></i>
                    <div>
                        <p class="font-bold text-dark text-xs">Delivery Note</p>
                        <p>Inside Dhaka: ৳{{$client->delivery_charge_inside}} | Outside: ৳{{$client->delivery_charge_outside ?? 120}}</p>
                    </div>
                </div>
                @endif
                <div class="bg-gray-50 rounded-lg p-3 text-[11px] text-gray-600 border border-gray-200 flex items-center gap-3">
                    <i class="fas fa-shield-alt text-green-500 text-lg"></i>
                    <div>
                        <p class="font-bold text-dark text-xs">Buyer Protection</p>
                        <p>100% Genuine. Secure Payments.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Description Segment --}}
    <div class="mt-16 mb-12 border-t border-gray-200 pt-10">
        <h3 class="text-xl font-extrabold text-dark mb-6">Product Details</h3>
        <div class="prose prose-sm md:prose-base max-w-none text-gray-700 font-sans">
            @if($product->description ?? $product->short_description)
                {!! clean($product->description ?? $product->short_description) !!}
            @else
                <p>No detailed description available.</p>
            @endif
        </div>
    </div>
    
    @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])

    @include('shop.partials.related-products', ['client' => $client, 'product' => $product])

</div>

@include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@include('shop.partials.product-sticky-bar')
@endsection
