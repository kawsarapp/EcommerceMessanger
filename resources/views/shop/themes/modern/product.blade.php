@extends('shop.themes.modern.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-[90rem] mx-auto px-6 py-12 md:py-24" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <!-- Minimal Breadcrumb -->
    <div class="mb-12">
        <a href="{{$baseUrl}}" class="text-xs font-black uppercase tracking-[0.15em] text-gray-400 hover:text-black transition inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-24 items-start">
        
        <!-- Imagery Left Column (7 cols) -->
        <div class="lg:col-span-7 flex flex-col-reverse md:flex-row gap-6">
            <!-- Thumbnails Horizontal on Mobile, Vertical on Desktop -->
            <div class="flex md:flex-col gap-4 overflow-x-auto hide-scroll md:overflow-y-auto md:max-h-[80vh] w-full md:w-24 shrink-0">
                <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="relative aspect-[3/4] w-20 md:w-full bg-gray-100 mb-2 focus:outline-none ring-1 ring-gray-200 hover:ring-black transition">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover mix-blend-multiply opacity-70 hover:opacity-100" :class="{'opacity-100': mainImg == '{{asset('storage/'.$product->thumbnail)}}'}">
                </button>
                @foreach($product->gallery ?? [] as $img)
                <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="relative aspect-[3/4] w-20 md:w-full bg-gray-100 mb-2 focus:outline-none ring-1 ring-gray-200 hover:ring-black transition">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-cover mix-blend-multiply opacity-70 hover:opacity-100" :class="{'opacity-100': mainImg == '{{asset('storage/'.$img)}}'}">
                </button>
                @endforeach
            </div>
            
            <!-- Main Display -->
            <div class="w-full aspect-[4/5] bg-[#f5f5f5] flex-1 relative overflow-hidden group">
                <img :src="mainImg" class="w-full h-full object-cover mix-blend-multiply cursor-zoom-in transition duration-500 hover:scale-110 object-center">
            </div>
        </div>
        
        <!-- Info Right Column (5 cols) -->
        <div class="lg:col-span-5 flex flex-col lg:sticky lg:top-32">
            
            <div class="mb-8">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-4 block">{{$product->category->name ?? 'Essential'}}</span>
                <h1 class="text-4xl md:text-6xl font-black tracking-tighter uppercase leading-[0.9] text-black mb-6">{{$product->name}}</h1>
                
                <div class="flex items-end gap-4">
                    <span class="text-3xl font-black text-gray-900 tracking-tight">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    @if($product->sale_price)
                        <del class="text-lg text-gray-400 font-bold mb-0.5 tracking-widest">৳{{number_format($product->regular_price)}}</del>
                    @endif
                </div>
            </div>

            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="border-y border-gray-200 py-10 my-10 space-y-10">
                
                <!-- Advanced Selectors -->
                @if($product->colors)
                <div>
                    <span class="text-xs font-black tracking-[0.2em] uppercase text-gray-900 block mb-4">Color</span>
                    <div class="flex gap-3 flex-wrap">
                        @foreach($product->colors as $c)
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                            <span class="block px-6 py-3 border border-gray-300 peer-checked:bg-black peer-checked:text-white peer-checked:border-black text-sm font-bold uppercase tracking-widest transition-colors duration-200">{{$c}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
                
                @if($product->sizes)
                <div>
                    <span class="text-xs font-black tracking-[0.2em] uppercase text-gray-900 block mb-4">Size</span>
                    <div class="flex gap-3 flex-wrap">
                        @foreach($product->sizes as $s)
                        <label class="cursor-pointer">
                            <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                            <span class="block w-14 h-12 flex items-center justify-center border border-gray-300 peer-checked:bg-black peer-checked:text-white peer-checked:border-black text-sm font-bold uppercase transition-colors duration-200">{{$s}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Add to Bag Row -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <div class="flex border border-black w-full sm:w-1/3">
                        <button type="button" @click="if(qty>1)qty--" class="flex-1 px-4 py-4 text-gray-500 hover:text-black hover:bg-gray-50 flex items-center justify-center font-bold text-xl transition"><i class="fas fa-minus text-sm"></i></button>
                        <input type="number" name="qty" x-model="qty" class="w-16 text-center bg-transparent border-none font-black text-xl p-0 focus:ring-0" readonly>
                        <button type="button" @click="qty++" class="flex-1 px-4 py-4 text-gray-500 hover:text-black hover:bg-gray-50 flex items-center justify-center font-bold text-xl transition"><i class="fas fa-plus text-sm"></i></button>
                    </div>
                    
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <button type="button" disabled class="w-full sm:w-2/3 bg-gray-200 text-gray-500 py-5 font-black text-sm uppercase tracking-[0.2em] cursor-not-allowed border border-gray-200">Out of Stock</button>
                    @else
                        @if($client->show_order_button ?? true)
                            <button type="submit" class="w-full sm:w-2/3 bg-black text-white py-5 font-black text-sm uppercase tracking-[0.2em] hover:bg-gray-900 hover:shadow-2xl transition duration-300">Proceed to Checkout</button>
                            @endif

                            {{-- Chat Button --}}
                            @include('shop.partials.chat-button', ['client' => $client])
                    @endif
                </div>

                <div class="text-xs font-bold text-gray-500 text-center uppercase tracking-widest mt-6">
                    <i class="fas fa-box shrink-0 mr-2"></i> Free Shipping on orders above 2500 BDT.
                </div>
            </form>
            
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

@endsection