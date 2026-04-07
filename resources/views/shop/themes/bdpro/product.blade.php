@extends('shop.themes.bdpro.layout')
@section('title', $product->meta_title ?? (strtoupper($product->name) . ' | ' . $client->shop_name))

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<div class="max-w-[1200px] mx-auto px-4 py-6 md:py-10" x-data="{ 
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
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6 overflow-x-auto whitespace-nowrap hide-scroll">
        <a href="{{$baseUrl}}" class="hover:text-primary transition flex items-center gap-1"><i class="fas fa-home"></i> Home</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <a href="{{$baseUrl}}?category={{$product->category->slug ?? 'all'}}" class="hover:text-primary transition">{{$product->category->name ?? 'Products'}}</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <span class="text-gray-800 font-medium truncate">{{$product->name}}</span>
    </nav>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-12">
            
            {{-- Images Section (Left Side) --}}
            <div class="md:col-span-1 lg:col-span-5 flex flex-col gap-4 sticky top-24 h-max">
                <div class="w-full aspect-square bg-white rounded-2xl border border-gray-100 overflow-hidden relative group">
                    <img :src="mainImg" class="w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-105" loading="lazy" alt="{{$product->name}}">
                    @if($product->sale_price)
                        @php $savePct = round((($product->regular_price - $product->sale_price)/$product->regular_price)*100); @endphp
                        <span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm">
                            -{{$savePct}}% OFF
                        </span>
                    @endif
                </div>
                
                <div class="flex gap-3 overflow-x-auto hide-scroll py-2">
                    <div @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                         class="w-20 h-20 shrink-0 border-2 rounded-xl p-1.5 cursor-pointer transition-all bg-white"
                         :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary shadow-md scale-105' : 'border-gray-100 hover:border-gray-300'">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-contain rounded-lg" alt="{{$product->name}}">
                    </div>
                    @foreach($product->gallery ?? [] as $img)
                    <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                         class="w-20 h-20 shrink-0 border-2 rounded-xl p-1.5 cursor-pointer transition-all bg-white"
                         :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary shadow-md scale-105' : 'border-gray-100 hover:border-gray-300'">
                        <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-contain rounded-lg" alt="{{$product->name}}">
                    </div>
                    @endforeach
                </div>

                @if($product->video_url)
                <a href="{{$product->video_url}}" target="_blank" class="w-full mt-2 flex items-center justify-center gap-2 bg-red-50 text-red-600 font-bold py-3.5 rounded-xl hover:bg-red-600 hover:text-white transition duration-300">
                    <i class="fab fa-youtube text-xl"></i> Watch Video Review
                </a>
                @endif
            </div>

            {{-- Product Details Section (Right Side) --}}
            <div class="md:col-span-1 lg:col-span-7 flex flex-col">
                
                <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-gray-900 leading-snug mb-3">
                    {{$product->name}}
                </h1>
                
                @php 
                    $reviews = $product->reviews()->where('is_visible', true)->get();
                    $rc = $reviews->count();
                    $avg = $rc > 0 ? round($reviews->avg('rating'), 1) : 0;
                @endphp
                
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100 text-sm">
                    @if($rc > 0)
                    <div class="flex items-center gap-1.5 bg-gray-50 px-3 py-1 rounded-full">
                        <div class="flex text-yellow-400 text-xs">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= round($avg) ? '' : 'text-gray-300' }}"></i>
                            @endfor
                        </div>
                        <span class="text-gray-600 font-medium ml-1">{{$rc}} Reviews</span>
                    </div>
                    @endif
                    
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400">Status:</span> 
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ (isset($product->stock_status) && $product->stock_status == 'out_of_stock') ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">
                            {{ (isset($product->stock_status) && $product->stock_status == 'out_of_stock') ? 'Out of Stock' : 'In Stock' }}
                        </span>
                    </div>
                </div>

                <div class="bg-gray-50/50 rounded-2xl p-6 mb-8 border border-gray-100">
                    <div class="flex items-center gap-4 mb-2">
                        <span class="text-primary text-4xl font-black tracking-tight" x-text="'৳' + new Intl.NumberFormat('en-IN').format(currentPrice)">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                        @if($product->sale_price)
                            <del class="text-gray-400 text-xl font-medium mt-1">৳{{number_format($product->regular_price)}}</del>
                        @endif
                    </div>
                    @if($product->sale_price)
                    <div class="inline-flex items-center gap-1.5 text-xs text-green-600 font-bold bg-green-50 px-2 py-1 rounded-md mt-2">
                        <i class="fas fa-tag"></i> Special discount applied
                    </div>
                    @endif
                </div>

                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8">
                    
                    @if($product->has_variants)
                    <div class="bg-white rounded-xl">
                        @include('shop.partials.product-variations')
                    </div>
                    @endif

                    <!-- Quantity & Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 sm:items-end">
                        <div class="w-full sm:w-auto">
                            <label class="font-bold text-gray-700 text-sm block mb-2">Quantity</label>
                            <div class="flex items-center bg-white border border-gray-300 rounded-xl overflow-hidden h-[52px] w-full sm:w-36">
                                <button type="button" @click="if(qty>1)qty--" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border-r border-gray-300 h-full">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="qty" x-model="qty" class="w-16 h-full text-center font-bold text-lg text-gray-900 p-0 border-none focus:ring-0" readonly>
                                <button type="button" @click="qty++" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border-l border-gray-300 h-full">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col gap-3">
                            @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                                <button type="button" disabled class="w-full h-[52px] bg-gray-200 text-gray-500 font-bold rounded-xl border border-gray-300 cursor-not-allowed">
                                    OUT OF STOCK
                                </button>
                            @else
                                @if($client->show_order_button ?? true)
                                    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])
                                    <button type="submit" class="w-full h-[52px] bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:shadow-primary/50 transition-all flex justify-center items-center gap-2 text-lg">
                                        <i class="fas fa-shopping-bag"></i> Order Now
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </form>
                
                {{-- Info Badges --}}
                <div class="grid grid-cols-2 gap-3 mt-8 pt-8 border-t border-gray-100">
                    @if($client->delivery_charge_inside ?? false)
                    <div class="bg-gray-50 rounded-xl p-4 flex items-start gap-3">
                        <div class="bg-primary/10 w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                            <i class="fas fa-truck text-primary text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">Delivery Note</p>
                            <p class="text-xs text-gray-500 mt-0.5">Inside Dhaka: ৳{{$client->delivery_charge_inside}}<br>Outside: ৳{{$client->delivery_charge_outside ?? 120}}</p>
                        </div>
                    </div>
                    @endif
                    <div class="bg-gray-50 rounded-xl p-4 flex items-start gap-3">
                        <div class="bg-green-100 w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                            <i class="fas fa-shield-alt text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">Secure Shopping</p>
                            <p class="text-xs text-gray-500 mt-0.5">100% genuine products.<br>Safe payments.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Description Segment --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 mt-8">
        <h3 class="text-2xl font-extrabold text-gray-900 mb-6 flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                <i class="fas fa-info-circle text-sm"></i>
            </span>
            Product Description
        </h3>
        <div class="prose prose-sm md:prose-base max-w-none text-gray-600 font-sans leading-relaxed">
            @if($product->description ?? $product->short_description)
                {!! clean($product->description ?? $product->short_description) !!}
            @else
                <p>No detailed description available.</p>
            @endif
        </div>
        
        @if($product->key_features)
        <div class="mt-8 pt-8 border-t border-gray-100">
            <h4 class="font-bold text-gray-900 text-lg mb-4">Key Features</h4>
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-600">
                @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                    <li class="flex items-start gap-2 bg-gray-50 p-3 rounded-lg"><i class="fas fa-check-circle text-green-500 mt-0.5"></i> <span>{{$feature}}</span></li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    
    <div class="mt-8">
        @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])
    </div>

    <div class="mt-8">
        @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
    </div>

</div>

@include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@include('shop.partials.product-sticky-bar')
@endsection
