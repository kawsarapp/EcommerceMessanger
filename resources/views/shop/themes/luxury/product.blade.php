@extends('shop.themes.luxury.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[100rem] mx-auto px-4 sm:px-12 py-16 md:py-24"  x-data="{ mainImg: '{{ asset('storage/'.($product->thumbnail ?? 'images/placeholder.png')) }}' }">
    
    <div class="flex flex-col lg:flex-row gap-16 lg:gap-24 items-start"  >
        
        <!-- Left: Image (Stately & Tall) -->
        <div class="w-full lg:w-1/2 flex flex-col gap-6 transition-all duration-[2s] ease-out" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'">
            <div class="w-full aspect-square md:aspect-[4/5] bg-surface relative overflow-hidden group border border-white/5">
                <img :src="mainImg" class="w-full h-full object-cover mix-blend-lighten transition-transform duration-[3s] hover:scale-105" loading="lazy">
            </div>
            
            <div class="flex gap-4 overflow-x-auto hide-scroll justify-center">
                <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="w-16 h-16 md:w-20 md:h-20 bg-surface border transition" :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary' : 'border-transparent opacity-50 hover:opacity-100'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover mix-blend-lighten">
                </button>
                @foreach($product->gallery ?? [] as $img)
                <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="w-16 h-16 md:w-20 md:h-20 bg-surface border transition" :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary' : 'border-transparent opacity-50 hover:opacity-100'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-cover mix-blend-lighten" loading="lazy">
                </button>
                @endforeach
            </div>
        </div>
        
        <!-- Right: Information & Acquisition -->
        <div class="w-full lg:w-1/2 flex flex-col transition-all duration-[2.5s] ease-out delay-300" :class="show ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-12'">
            
            <div class="border-b border-white/10 pb-12 text-center lg:text-left">
                <span class="text-[9px] font-semibold text-primary uppercase tracking-[0.4em] mb-6 block">{{$product->category->name ?? 'Exquisite Collection'}}</span>
                <h1 class="font-serif text-4xl md:text-5xl lg:text-6xl text-white font-light mb-8 leading-tight tracking-wide">{{$product->name}}</h1>
                
                <div class="flex items-center justify-center lg:justify-start gap-4">
                    <span class="text-2xl md:text-3xl font-light tracking-widest text-gray-200">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])

                    @if($product->sale_price)
                        <del class="text-sm font-light text-gray-600 tracking-widest">৳{{number_format($product->regular_price)}}</del>
                    @endif
                </div>
            </div>

            @include('shop.partials.product-variations')
            
            <div class="pt-12">
                <h3 class="font-sans text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-6 text-center lg:text-left">The Lore & Craftsmanship</h3>
                <div class="prose prose-sm prose-invert max-w-none font-sans text-gray-400 text-xs font-light leading-[2.2] tracking-wide text-center lg:text-left">
                    {!! clean($product->description ?? $product->short_description) !!}
                </div>
                
                @if($product->key_features)
                <div class="mt-10 border-t border-white/5 pt-10">
                    <h3 class="font-sans text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-6 text-center lg:text-left">Specifications</h3>
                    <ul class="space-y-3 text-xs font-light text-gray-400 tracking-wide text-center lg:text-left">
                        @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                            <li><span class="text-primary mr-2">/</span> {{$feature}}</li>
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
