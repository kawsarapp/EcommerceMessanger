@extends('shop.themes.fashion.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[100rem] mx-auto px-4 sm:px-8 py-10 md:py-16" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <div class="text-center mb-8">
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] inline-block border-b border-gray-200 pb-1 mb-6">{{$product->category->name ?? 'Boutique'}}</span>
        <h1 class="font-heading font-black text-4xl md:text-6xl text-primary leading-none px-4">{{$product->name}}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-stretch">
        
        <!-- Fashion Photography (Left) -->
        <div class="space-y-4">
            <div class="w-full aspect-[3/4] bg-gray-50 border border-gray-100">
                <img :src="mainImg" class="w-full h-full object-cover object-top hover:object-center transition-all duration-[3s] cursor-crosshair">
            </div>
            
            <div class="flex gap-4 overflow-x-auto hide-scroll py-2 px-1">
                <img src="{{asset('storage/'.$product->thumbnail)}}" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                     class="w-20 md:w-28 aspect-[3/4] object-cover cursor-pointer hover:opacity-100 transition border"
                     :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-black opacity-100' : 'border-transparent opacity-60'">
                     
                @foreach($product->gallery ?? [] as $img)
                <img src="{{asset('storage/'.$img)}}" @click="mainImg = '{{asset('storage/'.$img)}}'" 
                     class="w-20 md:w-28 aspect-[3/4] object-cover cursor-pointer hover:opacity-100 transition border"
                     :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-black opacity-100' : 'border-transparent opacity-60'">
                @endforeach
            </div>
        </div>
        
        <!-- Details & Checkout (Right) -->
        <div class="flex flex-col py-8">
            <div class="mb-10 text-center lg:text-left">
                <div class="text-3xl font-medium tracking-wide">
                    ৳{{number_format($product->sale_price ?? $product->regular_price)}}
                    @if($product->sale_price)
                        <span class="text-xl text-red-500 ml-2 relative -top-1">৳{{number_format($product->regular_price)}}</span>
                    @endif
                </div>
            </div>

            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="border-t border-gray-100 pt-10 space-y-10 flex-1">
                
                @if($product->colors)
                <div>
                    <span class="text-xs font-semibold tracking-[0.2em] uppercase text-gray-400 block mb-4 text-center lg:text-left">Select Color</span>
                    <div class="flex gap-4 flex-wrap justify-center lg:justify-start">
                        @foreach($product->colors as $c)
                        <label class="cursor-pointer group">
                            <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                            <span class="block px-6 py-2 border border-gray-200 text-sm font-medium tracking-widest text-gray-500 peer-checked:bg-primary peer-checked:border-primary peer-checked:text-white transition group-hover:border-gray-400">{{$c}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
                
                @if($product->sizes)
                <div>
                    <span class="text-xs font-semibold tracking-[0.2em] uppercase text-gray-400 block mb-4 text-center lg:text-left">Select Size</span>
                    <div class="flex gap-4 flex-wrap justify-center lg:justify-start">
                        @foreach($product->sizes as $s)
                        <label class="cursor-pointer group">
                            <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                            <span class="w-12 h-12 flex items-center justify-center rounded-full border border-gray-200 text-sm font-medium text-gray-500 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary transition group-hover:border-gray-400">{{$s}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex flex-col sm:flex-row items-center gap-6 pt-6">
                    <div class="flex border-b border-gray-300 pb-2 w-32 justify-between">
                        <button type="button" @click="if(qty>1)qty--" class="text-gray-400 hover:text-black transition px-2"><i class="fas fa-minus text-xs"></i></button>
                        <input type="number" name="qty" x-model="qty" class="w-10 text-center border-none font-medium text-lg p-0 bg-transparent focus:ring-0" readonly>
                        <button type="button" @click="qty++" class="text-gray-400 hover:text-black transition px-2"><i class="fas fa-plus text-xs"></i></button>
                    </div>
                    
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <button type="button" disabled class="w-full bg-gray-100 text-gray-400 py-5 font-semibold text-xs tracking-[0.2em] uppercase cursor-not-allowed text-center">Sold Out</button>
                    @else
                        @if($client->show_order_button ?? true)
                            <button type="submit" class="w-full bg-primary text-white hover:bg-black py-5 font-semibold text-xs tracking-[0.2em] uppercase transition text-center">Add to Cart</button>
                            @endif

                            {{-- Chat Button --}}
                            @include('shop.partials.chat-button', ['client' => $client])
                    @endif
                </div>
            </form>
            
            <div class="mt-16 bg-gray-50/50 p-8 border border-gray-100">
                <h3 class="font-heading font-semibold text-2xl mb-6">Description</h3>
                <div class="prose prose-sm max-w-none text-gray-500 font-medium leading-[2]">
                    {!! clean($product->description ?? $product->short_description) !!}
                </div>
            </div>

        </div>
    </div>
</main>

    {{-- Dynamic Reviews Section --}}
    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@endsection