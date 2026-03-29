@extends('shop.themes.kids.layout')
@section('title', $product->name . ' | Yay! Kids Corner')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 md:px-10 py-10" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <div class="mb-8 font-bold text-sm text-slate-400 tracking-wide flex items-center justify-center sm:justify-start gap-3 bg-white w-fit px-6 py-3 rounded-full shadow-sm border border-slate-100">
        <a href="{{$baseUrl}}" class="hover:text-primary transition flex items-center gap-1"><i class="fas fa-home"></i> Home</a> 
        <i class="fas fa-star text-[10px] text-funyellow"></i> 
        <span class="text-slate-500">{{$product->category->name ?? 'ToyBox'}}</span>
    </div>

    <div class="bg-white rounded-[3rem] p-6 sm:p-10 lg:p-12 shadow-cloud border border-slate-100 mb-16 relative overflow-hidden">
        <!-- playful background shapes -->
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-funblue/5 rounded-full z-0"></div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-primary/5 rounded-full z-0"></div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 relative z-10">
            
            <!-- Left: Imagery Gallery -->
            <div class="flex flex-col space-y-6">
                <!-- Main Image wrapper with soft background -->
                <div class="w-full aspect-square bg-slate-50 rounded-[2.5rem] border-4 border-slate-100 relative p-8 flex items-center justify-center overflow-hidden group">
                    <img :src="mainImg" class="max-w-[85%] max-h-[85%] object-contain mix-blend-multiply drop-shadow-xl transition-transform duration-500 scale-100 hover:scale-[1.1] z-10 hover:rotate-2" loading="lazy">
                    
                    @if($product->sale_price)
                        <div class="absolute top-6 left-6 z-20 bg-funyellow text-slate-900 font-black text-lg px-5 py-2 rounded-full shadow-md border-4 border-white transform rotate-3 flex items-center gap-2 bouncy">
                            SALE! <i class="fas fa-gift"></i>
                        </div>
                    @endif
                </div>
                
                <!-- Thumbnails -->
                <div class="flex justify-center lg:justify-start gap-4 overflow-x-auto hide-scroll pb-2 px-2">
                    <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="w-24 aspect-square bg-white rounded-2xl p-2 flex items-center justify-center border-4 transition-all shrink-0 bouncy shadow-sm" :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary' : 'border-slate-100 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$product- loading="lazy">thumbnail)}}" class="max-w-[85%] max-h-[85%] object-contain mix-blend-multiply">
                    </button>
                    @foreach($product->gallery ?? [] as $img)
                    <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="w-24 aspect-square bg-white rounded-2xl p-2 flex items-center justify-center border-4 transition-all shrink-0 bouncy shadow-sm" :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary' : 'border-slate-100 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-[85%] max-h-[85%] object-contain mix-blend-multiply" loading="lazy">
                    </button>
                    @endforeach
                </div>
            </div>
            
            <!-- Right: Product Information & Cart Options -->
            <div class="flex flex-col pt-4">
                <div class="pb-8 mb-8 relative text-center lg:text-left">
                    <h1 class="text-4xl lg:text-5xl font-heading text-slate-800 leading-tight mb-6 drop-shadow-sm">{{$product->name}}</h1>
                    
                    <div class="flex flex-col sm:flex-row items-center lg:items-end justify-center lg:justify-start gap-4">
                        <span class="text-5xl lg:text-6xl font-heading text-primary bg-primary/10 px-6 py-3 rounded-3xl border-2 border-primary/20 shadow-inner">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                        @if($product->sale_price)
                            <div class="bg-red-50 px-4 py-2 rounded-2xl border-2 border-red-100 flex flex-col items-center">
                                <span class="text-xs font-bold text-red-400 uppercase tracking-widest mb-1">Was</span>
                                <del class="text-xl text-red-500 font-heading leading-none">৳{{number_format($product->regular_price)}}</del>
                            </div>
                        @endif
                    </div>
                </div>

                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8 flex-1 flex flex-col bg-slate-50 p-6 md:p-8 rounded-[2rem] border-2 border-slate-100 shadow-inner relative">
                    
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <div class="absolute -top-4 -right-4 bg-red-500 text-white font-black text-sm px-6 py-2 rounded-full border-4 border-white shadow-lg transform rotate-6">
                            All Gone!
                        </div>
                    @endif

                    <div class="space-y-8 flex-1 text-center lg:text-left">
                        @if($product->colors)
                        <div>
                            <span class="text-sm font-black text-slate-500 block mb-4 uppercase tracking-widest"><i class="fas fa-palette text-primary mr-1"></i> Pick a Color <span class="text-primary ml-1 capitalize" x-text="color"></span></span>
                            <div class="flex gap-3 flex-wrap justify-center lg:justify-start">
                                @foreach($product->colors as $c)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                                    <span class="block px-6 py-3 rounded-full border-4 border-white bg-slate-200 text-slate-600 font-bold text-sm transition-all peer-checked:bg-primary peer-checked:border-primary peer-checked:text-white shadow-sm hover:scale-105 active:scale-95 duration-200">{{$c}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($product->sizes)
                        <div>
                            <span class="text-sm font-black text-slate-500 block mb-4 uppercase tracking-widest"><i class="fas fa-ruler text-funblue mr-1"></i> Choose Size <span class="text-funblue ml-1 capitalize" x-text="size"></span></span>
                            <div class="flex gap-3 flex-wrap justify-center lg:justify-start">
                                @foreach($product->sizes as $s)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                                    <span class="block px-6 py-3 rounded-full border-4 border-white bg-slate-200 text-slate-600 font-bold text-sm transition-all peer-checked:bg-funblue peer-checked:border-funblue peer-checked:text-white shadow-sm hover:scale-105 active:scale-95 duration-200">{{$s}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Quantity & Action -->
                    <div class="flex flex-col sm:flex-row gap-4 mt-4 pt-4">
                        <div class="h-16 bg-white rounded-full flex border-4 border-slate-100 shadow-inner w-full sm:w-48 items-center px-2 justify-between">
                            <button type="button" @click="if(qty>1)qty--" class="w-12 h-12 flex items-center justify-center rounded-full text-slate-400 bg-slate-100 hover:bg-slate-200 hover:text-primary transition font-bold text-lg"><i class="fas fa-minus"></i></button>
                            <input type="number" name="qty" x-model="qty" class="flex-1 text-center bg-transparent border-none font-heading text-2xl text-slate-800 p-0 focus:ring-0" readonly>
                            <button type="button" @click="qty++" class="w-12 h-12 flex items-center justify-center rounded-full text-slate-400 bg-slate-100 hover:bg-slate-200 hover:text-primary transition font-bold text-lg"><i class="fas fa-plus"></i></button>
                        </div>
                        
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="flex-1 h-16 bg-slate-300 text-slate-500 rounded-full font-heading text-xl cursor-not-allowed border-4 border-white shadow-sm">Sniff... Out of Stock</button>
                        @else
                            <button type="submit" class="flex-1 h-16 bg-primary text-white rounded-full font-heading text-xl transition-all shadow-float hover:-translate-y-1 hover:bg-pink-600 border-4 border-white flex items-center justify-center gap-3">
                                Add To Cart Yay! <i class="fas fa-shopping-cart text-funyellow"></i>
                            </button>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>
    
    <!-- Details Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 mt-16">
        
        <div class="lg:col-span-8 bg-white border-4 border-slate-100 rounded-[3rem] p-8 md:p-14 shadow-cloud relative">
            <div class="absolute -top-8 -left-8 w-16 h-16 bg-funyellow rounded-full flex items-center justify-center text-white text-3xl shadow-md border-4 border-white transform -rotate-12 pointer-events-none">
                <i class="fas fa-book-open"></i>
            </div>
            
            <h2 class="text-3xl font-heading text-slate-800 mb-8 border-b-4 border-slate-50 pb-6 text-center sm:text-left">Story Time!</h2>
            <div class="prose prose-slate max-w-none text-lg font-bold text-slate-600 leading-[1.8]">
                {!! clean($product->description ?? $product->long_description) !!}
            </div>
        </div>
        
        @if($product->key_features)
        <div class="lg:col-span-4 bg-funblue/10 border-4 border-white rounded-[3rem] p-8 md:p-10 self-start shadow-cloud transform md:rotate-2">
            
            <h2 class="text-2xl font-heading text-slate-800 mb-8 text-center"><i class="fas fa-star text-funyellow mr-2"></i> Cool Stuff</h2>
            <ul class="space-y-6">
                @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                    <li class="flex items-start gap-4 p-4 bg-white rounded-2xl border-2 border-slate-100 shadow-sm bouncy cursor-default">
                        <div class="w-10 h-10 rounded-full bg-funyellow/20 flex items-center justify-center shrink-0 border border-funyellow/50">
                            <i class="fas fa-check text-funblue text-lg"></i>
                        </div>
                        <span class="text-base font-bold text-slate-700 mt-1.5">{{$feature}}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

    </div>


        @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
    @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])
</main>

    {{-- Dynamic Reviews Section --}}
    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@endsection
