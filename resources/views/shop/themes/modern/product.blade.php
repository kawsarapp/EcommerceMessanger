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

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24 items-start" x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)">
        
        <!-- Imagery Left Column (7 cols) -->
        <div class="lg:col-span-7 flex flex-col-reverse md:flex-row gap-6 transition-all duration-500 ease-out" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Thumbnails Horizontal on Mobile, Vertical on Desktop -->
            <div class="flex md:flex-col gap-4 overflow-x-auto hide-scroll md:overflow-y-auto md:max-h-[80vh] w-full md:w-24 shrink-0">
                <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="relative aspect-[3/4] w-20 md:w-full bg-gray-100 mb-2 focus:outline-none ring-1 ring-gray-200 hover:ring-black transition">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover mix-blend-multiply opacity-70 hover:opacity-100" :class="{'opacity-100': mainImg == '{{asset('storage/'.$product->thumbnail)}}'}">
                </button>
                @foreach($product->gallery ?? [] as $img)
                <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="relative aspect-[3/4] w-20 md:w-full bg-gray-100 mb-2 focus:outline-none ring-1 ring-gray-200 hover:ring-black transition">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-cover mix-blend-multiply opacity-70 hover:opacity-100" :class="{'opacity-100': mainImg == '{{asset('storage/'.$img)}}'}" loading="lazy">
                </button>
                @endforeach
            </div>
            
            <!-- Main Display -->
            <div class="w-full aspect-[4/5] bg-[#f5f5f5] flex-1 relative overflow-hidden group">
                <img :src="mainImg" class="w-full h-full object-cover mix-blend-multiply cursor-zoom-in transition duration-500 hover:scale-110 object-center" loading="lazy">
            </div>
        </div>
        
        <!-- Info Right Column (5 cols) -->
        <div class="lg:col-span-5 flex flex-col lg:sticky lg:top-32 transition-all duration-500 ease-out delay-100" :class="show ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-4'">
            
            <div class="mb-8">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-4 block">{{$product->category->name ?? 'Essential'}}</span>
                <h1 class="text-4xl md:text-6xl font-black tracking-tighter uppercase leading-[0.9] text-black mb-6">{{$product->name}}</h1>
                
                <div class="flex items-end gap-4">
                    <span class="text-3xl font-black text-gray-900 tracking-tight" x-text="'৳' + new Intl.NumberFormat('en-IN').format(currentPrice)">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])

                    @if($product->sale_price)
                        <del class="text-lg text-gray-400 font-bold mb-0.5 tracking-widest">৳{{number_format($product->regular_price)}}</del>
                    @endif
                </div>
            </div>

            @include('shop.partials.product-variations')
            
            <div class="pt-6">
                <!-- Accordion style minimalist details -->
                <div class="border-b border-gray-200 pb-8">
                    <h3 class="text-sm font-black uppercase tracking-[0.15em] mb-4 text-gray-900">The Details</h3>
                    <div class="prose prose-sm max-w-none text-gray-500 font-medium leading-loose">
                        {!! clean($product->description ?? $product->short_description) !!}
                    </div>
                </div>
                @if($product->key_features)
                <div class="border-b border-gray-200 py-8">
                    <h3 class="text-sm font-black uppercase tracking-[0.15em] mb-4 text-gray-900">Specifications</h3>
                    <ul class="list-disc pl-5 space-y-2 text-gray-500 text-sm font-medium">
                        @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                            <li>{{$feature}}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

        </div>
    </div>

        @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
    @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])
</main>

    {{-- Dynamic Reviews Section --}}
    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@include('shop.partials.product-sticky-bar')
@endsection
