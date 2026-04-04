@extends('shop.themes.fashion.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[100rem] mx-auto px-4 sm:px-8 py-10 md:py-16"  x-data="{ mainImg: '{{ asset('storage/'.($product->thumbnail ?? 'images/placeholder.png')) }}' }">
    
    <div class="text-center mb-8">
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] inline-block border-b border-gray-200 pb-1 mb-6">{{$product->category->name ?? 'Boutique'}}</span>
        <h1 class="font-heading font-black text-4xl md:text-6xl text-primary leading-none px-4">{{$product->name}}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-stretch">
        
        <!-- Fashion Photography (Left) -->
        <div class="space-y-4">
            <div class="w-full aspect-[3/4] bg-gray-50 border border-gray-100">
                <img :src="mainImg" class="w-full h-full object-cover object-top hover:object-center transition-all duration-[3s] cursor-crosshair" loading="lazy">
            </div>
            
            <div class="flex gap-4 overflow-x-auto hide-scroll py-2 px-1">
                <img src="{{asset('storage/'.$product->thumbnail)}}" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                     class="w-20 md:w-28 aspect-[3/4] object-cover cursor-pointer hover:opacity-100 transition border"
                     :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-black opacity-100' : 'border-transparent opacity-60'">
                     
                @foreach($product->gallery ?? [] as $img)
                <img src="{{asset('storage/'.$img)}}" @click="mainImg = '{{asset('storage/'.$img)}}'" 
                     class="w-20 md:w-28 aspect-[3/4] object-cover cursor-pointer hover:opacity-100 transition border"
                     :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-black opacity-100' : 'border-transparent opacity-60'" loading="lazy">
                @endforeach
            </div>
        </div>
        
        <!-- Details & Checkout (Right) -->
        <div class="flex flex-col py-8">
            <div class="mb-10 text-center lg:text-left border-b border-gray-100 pb-8">
                <div class="text-3xl font-medium tracking-wide">
                    ৳{{number_format($product->sale_price ?? $product->regular_price)}}
                    @if($product->sale_price)
                        <span class="text-xl text-red-500 ml-2 relative -top-1">৳{{number_format($product->regular_price)}}</span>
                    @endif
                </div>
                @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])
                @include('shop.partials.stock-alert-badge', ['product' => $product, 'client' => $client])
            </div>

            @include('shop.partials.product-variations')
            
            <div class="mt-16 bg-gray-50/50 p-8 border border-gray-100">
                <h3 class="font-heading font-semibold text-2xl mb-6">Description</h3>
                <div class="prose prose-sm max-w-none text-gray-500 font-medium leading-[2]">
                    {!! clean($product->description ?? $product->short_description) !!}
                </div>
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
