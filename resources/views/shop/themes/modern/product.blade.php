@extends('shop.themes.modern.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[90rem] mx-auto px-6 py-12 md:py-24" x-data="{ 
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
    
    <!-- Minimal Breadcrumb -->
    <div class="mb-12">
        <a href="{{$baseUrl}}" class="text-xs font-black uppercase tracking-[0.15em] text-gray-400 hover:text-black transition inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start" x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)">
        
        <!-- Imagery Left Column (7 cols) -->
        <div class="lg:col-span-7 flex flex-col-reverse md:flex-row gap-4 transition-all duration-500 ease-out" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Thumbnails Horizontal on Mobile, Vertical on Desktop -->
            <div class="flex md:flex-col gap-3 overflow-x-auto hide-scroll md:overflow-y-auto md:max-h-[80vh] w-full md:w-24 shrink-0">
                <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="relative aspect-square w-20 md:w-full bg-white rounded-md mb-1 overflow-hidden focus:outline-none ring-2 hover:ring-primary transition" :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'ring-primary' : 'ring-transparent border border-gray-200'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover">
                </button>
                @foreach($product->gallery ?? [] as $img)
                <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="relative aspect-square w-20 md:w-full bg-white rounded-md mb-1 overflow-hidden focus:outline-none ring-2 hover:ring-primary transition" :class="mainImg == '{{asset('storage/'.$img)}}' ? 'ring-primary' : 'ring-transparent border border-gray-200'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-cover" loading="lazy">
                </button>
                @endforeach
            </div>
            
            <!-- Main Display -->
            <div class="w-full aspect-square md:aspect-auto md:h-[600px] bg-white rounded-xl shadow-sm flex-1 relative overflow-hidden group border border-white/20 p-2">
                <img :src="mainImg" class="w-full h-full object-contain transition duration-500 hover:scale-[1.02] object-center" loading="lazy">
            </div>
        </div>
        
        <!-- Info Right Column (5 cols) -->
        <div class="lg:col-span-5 flex flex-col lg:sticky lg:top-32 bg-white/95 backdrop-blur-md rounded-2xl p-6 lg:p-10 shadow-lg border border-white/20 transition-all duration-500 ease-out delay-100" :class="show ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-4'">
            
            <div class="mb-6 border-b border-gray-100 pb-6">
                <span class="text-xs font-bold uppercase tracking-widest text-primary mb-3 block">{{$product->category->name ?? 'Essential'}}</span>
                <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight leading-tight text-gray-900 mb-4">{{$product->name}}</h1>
                
                <div class="flex items-end gap-4">
                    <span class="text-3xl font-black text-primary tracking-tight" x-text="'৳' + new Intl.NumberFormat('en-IN').format(currentPrice)">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    @if($product->sale_price)
                        <del class="text-lg text-gray-400 font-bold mb-1 tracking-wider">৳{{number_format($product->regular_price)}}</del>
                    @endif
                </div>
            </div>

            @include('shop.partials.product-variations')
            
            <!-- Modern Minimal System Tabs -->
            <div class="mt-8 pt-8 border-t border-gray-100" x-data="{ activeTab: 'description' }">
                <!-- Tab Headers -->
                <div class="flex flex-wrap border-b border-gray-200 gap-6">
                    <button @click="activeTab = 'description'" class="pb-3 text-sm font-bold uppercase tracking-widest transition-all border-b-2" :class="activeTab === 'description' ? 'border-primary text-primary' : 'border-transparent text-gray-400 hover:text-gray-900'">Description</button>
                    
                    @if($product->key_features)
                    <button @click="activeTab = 'features'" class="pb-3 text-sm font-bold uppercase tracking-widest transition-all border-b-2" :class="activeTab === 'features' ? 'border-primary text-primary' : 'border-transparent text-gray-400 hover:text-gray-900'">Features</button>
                    @endif
                    
                    @if($product->video_url)
                    <button @click="activeTab = 'video'" class="pb-3 text-sm font-bold uppercase tracking-widest transition-all border-b-2" :class="activeTab === 'video' ? 'border-primary text-primary' : 'border-transparent text-gray-400 hover:text-gray-900'">Video</button>
                    @endif
                    
                    <button @click="activeTab = 'reviews'" class="pb-3 text-sm font-bold uppercase tracking-widest transition-all border-b-2" :class="activeTab === 'reviews' ? 'border-primary text-primary' : 'border-transparent text-gray-400 hover:text-gray-900'">Reviews</button>
                </div>

                <!-- Tab Contents -->
                <div class="pt-6 relative min-h-[300px]">
                    <!-- Description Tab -->
                    <div x-show="activeTab === 'description'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="prose prose-sm max-w-none text-gray-600 font-medium leading-relaxed">
                        {!! clean($product->description ?? $product->short_description) !!}
                    </div>

                    <!-- Features Tab -->
                    @if($product->key_features)
                    <div x-show="activeTab === 'features'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        <ul class="space-y-3">
                            @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                                <li class="flex items-start text-sm text-gray-700 font-medium">
                                    <span class="text-primary mr-3"><i class="fas fa-check-circle"></i></span>
                                    <span>{{$feature}}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Video Tab -->
                    @if($product->video_url)
                    <div x-show="activeTab === 'video'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        @php
                            $youtubeUrl = $product->video_url;
                            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $youtubeUrl, $matches);
                            $videoId = $matches[1] ?? null;
                        @endphp
                        @if($videoId)
                            <div class="relative w-full rounded-xl overflow-hidden shadow-sm" style="padding-top: 56.25%;">
                                <iframe class="absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/{{ $videoId }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <a href="{{ $product->video_url }}" target="_blank" class="text-primary font-semibold hover:underline flex items-center gap-2">
                                    <i class="fab fa-youtube text-red-600 text-xl"></i> Watch Video Review
                                </a>
                            </div>
                        @endif
                    </div>
                    @endif

                    <!-- Reviews Tab -->
                    <div x-show="activeTab === 'reviews'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="-mx-6 px-6 sm:mx-0 sm:px-0">
                        @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="mt-20">
        @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
        @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])
    </div>
</main>

@include('shop.partials.product-sticky-bar')
@endsection
