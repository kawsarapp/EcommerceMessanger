@extends('shop.themes.luxury.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[100rem] mx-auto px-4 sm:px-12 py-16 md:py-24" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <div class="flex flex-col lg:flex-row gap-16 lg:gap-24 items-start">
        
        <!-- Left: Image (Stately & Tall) -->
        <div class="w-full lg:w-1/2 flex flex-col gap-6">
            <div class="w-full aspect-square md:aspect-[4/5] bg-surface relative overflow-hidden group border border-white/5">
                <img :src="mainImg" class="w-full h-full object-cover mix-blend-lighten transition-transform duration-[3s] hover:scale-105">
            </div>
            
            <div class="flex gap-4 overflow-x-auto hide-scroll justify-center">
                <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="w-16 h-16 md:w-20 md:h-20 bg-surface border transition" :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary' : 'border-transparent opacity-50 hover:opacity-100'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover mix-blend-lighten">
                </button>
                @foreach($product->gallery ?? [] as $img)
                <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="w-16 h-16 md:w-20 md:h-20 bg-surface border transition" :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary' : 'border-transparent opacity-50 hover:opacity-100'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-cover mix-blend-lighten">
                </button>
                @endforeach
            </div>
        </div>
        
        <!-- Right: Information & Acquisition -->
        <div class="w-full lg:w-1/2 flex flex-col">
            
            <div class="border-b border-white/10 pb-12 text-center lg:text-left">
                <span class="text-[9px] font-semibold text-primary uppercase tracking-[0.4em] mb-6 block">{{$product->category->name ?? 'Exquisite Collection'}}</span>
                <h1 class="font-serif text-4xl md:text-5xl lg:text-6xl text-white font-light mb-8 leading-tight tracking-wide">{{$product->name}}</h1>
                
                <div class="flex items-center justify-center lg:justify-start gap-4">
                    <span class="text-2xl md:text-3xl font-light tracking-widest text-gray-200">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    @if($product->sale_price)
                        <del class="text-sm font-light text-gray-600 tracking-widest">৳{{number_format($product->regular_price)}}</del>
                    @endif
                </div>
            </div>

            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="border-b border-white/10 py-12 space-y-12">
                
                @if($product->colors)
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-[0.3em] text-gray-400 block mb-6 text-center lg:text-left">Material / Shade</span>
                    <div class="flex gap-4 flex-wrap justify-center lg:justify-start">
                        @foreach($product->colors as $c)
                        <label class="cursor-pointer group">
                            <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                            <span class="block px-8 py-3 border border-gray-600 text-xs font-medium tracking-widest text-gray-300 peer-checked:bg-white peer-checked:border-white peer-checked:text-black transition uppercase duration-300">{{$c}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
                
                @if($product->sizes)
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-[0.3em] text-gray-400 block mb-6 text-center lg:text-left">Dimension / Size</span>
                    <div class="flex gap-4 flex-wrap justify-center lg:justify-start">
                        @foreach($product->sizes as $s)
                        <label class="cursor-pointer group">
                            <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                            <span class="min-w-[4rem] text-center px-6 py-3 border border-gray-600 text-xs font-medium tracking-widest text-gray-300 peer-checked:bg-white peer-checked:border-white peer-checked:text-black transition uppercase duration-300">{{$s}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-6 items-center">
                    <div class="flex border border-gray-600 w-32 h-14 justify-between items-center text-white px-2">
                        <button type="button" @click="if(qty>1)qty--" class="text-gray-400 hover:text-white px-3 transition leading-none text-xl font-light">-</button>
                        <input type="number" name="qty" x-model="qty" class="w-10 text-center border-none font-medium text-sm p-0 bg-transparent focus:ring-0 text-white" readonly>
                        <button type="button" @click="qty++" class="text-gray-400 hover:text-white px-3 transition leading-none text-xl font-light">+</button>
                    </div>
                    
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <button type="button" disabled class="flex-1 h-14 w-full bg-surface border border-white/5 text-gray-600 text-[10px] tracking-[0.3em] font-semibold uppercase cursor-not-allowed text-center">Currently Unavailable</button>
                    @else
                        <button type="submit" class="flex-1 h-14 w-full bg-white text-black hover:bg-gray-200 text-[10px] tracking-[0.3em] font-semibold uppercase transition duration-300 shadow-[0_0_20px_rgba(255,255,255,0.1)]">Acquire This Piece</button>
                    @endif
                </div>
            </form>
            
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
</main>
@endsection