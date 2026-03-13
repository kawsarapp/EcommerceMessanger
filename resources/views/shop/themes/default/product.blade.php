@extends('shop.themes.default.layout')
@section('title', $product->name . ' | Details')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <!-- Clean Breadcrumb -->
    <nav class="mb-6 flex text-sm text-gray-500 font-medium tracking-wide">
        <a href="{{$baseUrl}}" class="hover:text-primary transition">Home</a>
        <span class="mx-2 text-gray-300">/</span>
        <span class="hover:text-primary transition cursor-pointer">{{$product->category->name ?? 'Catalog'}}</span>
        <span class="mx-2 text-gray-300">/</span>
        <span class="text-gray-900 truncate max-w-[200px] sm:max-w-xs">{{$product->name}}</span>
    </nav>

    <div class="bg-white rounded border border-gray-200 p-6 lg:p-10 shadow-sm mb-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-16">
            
            <!-- Left: Imagery Gallery -->
            <div class="flex flex-col space-y-4">
                <!-- Main Image wrapper -->
                <div class="w-full aspect-square bg-gray-50 rounded border border-gray-200 relative p-8 flex items-center justify-center overflow-hidden">
                    <img :src="mainImg" class="max-w-full max-h-full object-contain mix-blend-multiply transition-transform duration-300 z-10">
                    
                    @if($product->sale_price)
                        <div class="absolute top-4 left-4 z-20 bg-red-600 text-white font-bold text-xs uppercase tracking-wider px-3 py-1 rounded shadow-sm">
                            Sale
                        </div>
                    @endif
                </div>
                
                <!-- Thumbnails -->
                <div class="flex gap-3 overflow-x-auto hide-scroll pb-1">
                    <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="w-20 aspect-square bg-gray-50 rounded p-2 flex items-center justify-center border transition-all shrink-0" :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary ring-1 ring-primary/20' : 'border-gray-200 hover:border-gray-300'">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </button>
                    @foreach($product->gallery ?? [] as $img)
                    <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="w-20 aspect-square bg-gray-50 rounded p-2 flex items-center justify-center border transition-all shrink-0" :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary ring-1 ring-primary/20' : 'border-gray-200 hover:border-gray-300'">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </button>
                    @endforeach
                </div>
            </div>
            
            <!-- Right: Product Information & Cart Options -->
            <div class="flex flex-col">
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight mb-3">{{$product->name}}</h1>
                    
                    <div class="flex items-center gap-4 mb-3 text-sm text-gray-500 font-medium">
                        <span>SKU: <span class="text-gray-900 uppercase">PRD-{{$product->id}}</span></span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        
                        @if(isset($product->stock_status))
                            @if($product->stock_status == 'out_of_stock')
                                <span class="text-red-600 font-semibold"><i class="fas fa-times mr-1"></i> Out of Stock</span>
                            @else
                                <span class="text-green-600 font-semibold"><i class="fas fa-check mr-1"></i> In Stock</span>
                            @endif
                        @endif
                    </div>

                    <div class="flex items-end gap-3 mt-4">
                        <span class="text-3xl font-bold text-gray-900 tracking-tight">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                        @if($product->sale_price)
                            <del class="text-lg text-gray-500 font-medium leading-none pb-1">৳{{number_format($product->regular_price)}}</del>
                        @endif
                    </div>
                </div>

                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8 flex-1 flex flex-col pt-2">
                    
                    <div class="space-y-6 flex-1">
                        @if($product->colors)
                        <div>
                            <span class="text-sm font-semibold text-gray-900 block mb-2">Color: <span class="font-normal text-gray-600 ml-1 capitalize" x-text="color || 'Please select'"></span></span>
                            <div class="flex gap-2 flex-wrap">
                                @foreach($product->colors as $c)
                                <label class="cursor-pointer">
                                    <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                                    <span class="block px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 font-medium text-sm transition-all peer-checked:bg-primary/5 peer-checked:border-primary peer-checked:text-primary hover:border-gray-400">{{$c}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($product->sizes)
                        <div>
                            <span class="text-sm font-semibold text-gray-900 block mb-2">Size: <span class="font-normal text-gray-600 ml-1 capitalize" x-text="size || 'Please select'"></span></span>
                            <div class="flex gap-2 flex-wrap">
                                @foreach($product->sizes as $s)
                                <label class="cursor-pointer">
                                    <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                                    <span class="block px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 font-medium text-sm transition-all peer-checked:bg-primary/5 peer-checked:border-primary peer-checked:text-primary hover:border-gray-400">{{$s}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Quantity & Action -->
                    <div class="flex flex-col sm:flex-row gap-4 mt-6">
                        <div class="h-12 bg-white rounded border border-gray-300 flex w-full sm:w-32 items-center px-1 shadow-sm">
                            <button type="button" @click="if(qty>1)qty--" class="flex-1 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 transition"><i class="fas fa-minus text-sm"></i></button>
                            <input type="number" name="qty" x-model="qty" class="w-10 text-center bg-transparent border-none font-semibold text-gray-900 p-0 focus:ring-0 text-lg" readonly>
                            <button type="button" @click="qty++" class="flex-1 h-full flex items-center justify-center text-gray-500 hover:text-gray-900 transition"><i class="fas fa-plus text-sm"></i></button>
                        </div>
                        
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="flex-1 h-12 bg-gray-200 text-gray-500 rounded font-semibold text-base cursor-not-allowed">Product Unavailable</button>
                        @else
                            <button type="submit" class="flex-1 h-12 bg-primary text-white rounded font-semibold transition-all hover:bg-gray-800 text-base shadow-sm flex items-center justify-center gap-2">
                                <i class="fas fa-shopping-cart text-sm"></i> Buy It Now
                            </button>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>
    
    <!-- Details Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-8">
            <div class="bg-white border text-gray-900 border-gray-200 rounded p-6 md:p-10 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Product Description</h2>
                <div class="prose prose-sm max-w-none font-normal text-gray-600 leading-relaxed">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
            </div>
        </div>
        
        @if($product->key_features)
        <div class="lg:col-span-4">
            <div class="bg-gray-50 border border-gray-200 rounded p-6 shadow-sm">
                <h2 class="text-lg font-bold text-gray-900 mb-5">Key Features</h2>
                <ul class="space-y-3">
                    @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                        <li class="flex items-start gap-3 text-sm font-medium text-gray-700">
                            <i class="fas fa-circle text-[6px] text-gray-400 mt-2"></i>
                            <span>{{$feature}}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            
            <div class="mt-6 bg-white border border-gray-200 rounded p-6 shadow-sm text-center">
                 <i class="fas fa-shield-alt text-3xl text-gray-300 mb-3"></i>
                 <h4 class="font-bold text-gray-900 text-sm mb-1">Secure Checkout</h4>
                 <p class="text-xs text-gray-500">Your information is protected and safe.</p>
            </div>
        </div>
        @endif

    </div>

</main>
@endsection