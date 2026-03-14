@extends('shop.themes.premium.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
    $baseUrl = $client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    <div class="bg-white rounded-[2.5rem] p-6 lg:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-gray-100">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-stretch">
            
            <!-- ====== Image Gallery Gallery ====== -->
            <div class="flex flex-col space-y-4">
                <!-- Main Image -->
                <div class="aspect-[4/5] bg-gray-50 rounded-2xl overflow-hidden relative group">
                    @if($product->sale_price)
                        <span class="absolute top-6 left-6 z-20 bg-accent text-white text-sm font-extrabold px-5 py-2 rounded-full uppercase tracking-wider shadow-lg">Save ৳{{$product->regular_price - $product->sale_price}}</span>
                    @endif
                    <img :src="mainImg" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="{{$product->name}}">
                </div>
                
                <!-- Thumbnails -->
                <div class="flex gap-4 overflow-x-auto hide-scroll pb-2">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" 
                         @click="mainImg = $el.src" 
                         :class="{ 'ring-2 ring-primary ring-offset-2 opacity-100': mainImg === '{{asset('storage/'.$product->thumbnail)}}', 'opacity-60': mainImg !== '{{asset('storage/'.$product->thumbnail)}}' }"
                         class="w-24 h-32 object-cover rounded-xl cursor-pointer hover:opacity-100 transition-all duration-300 border border-gray-200">
                    
                    @foreach($product->gallery ?? [] as $img)
                    <img src="{{asset('storage/'.$img)}}" 
                         @click="mainImg = $el.src" 
                         :class="{ 'ring-2 ring-primary ring-offset-2 opacity-100': mainImg === '{{asset('storage/'.$img)}}', 'opacity-60': mainImg !== '{{asset('storage/'.$img)}}' }"
                         class="w-24 h-32 object-cover rounded-xl cursor-pointer hover:opacity-100 transition-all duration-300 border border-gray-200">
                    @endforeach
                </div>
            </div>

            <!-- ====== Product Info & Add to Cart ====== -->
            <div class="flex flex-col justify-center">
                <!-- Breadcrumbs & Stock Status -->
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-sm font-bold text-primary bg-primary/10 px-4 py-1.5 rounded-full">{{$product->category->name ?? 'Premium Item'}}</span>
                    
                    @if(isset($product->stock_status))
                        @if($product->stock_status == 'out_of_stock')
                            <span class="bg-red-50 text-red-600 text-sm font-bold px-4 py-1.5 rounded-full flex items-center gap-2"><i class="fas fa-times-circle"></i> Out of Stock</span>
                        @else
                            <span class="bg-green-50 text-green-600 text-sm font-bold px-4 py-1.5 rounded-full flex items-center gap-2"><i class="fas fa-check-circle"></i> In Stock</span>
                        @endif
                    @endif
                </div>

                <!-- Title -->
                <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight text-gray-900 mb-6 leading-[1.1]">{{$product->name}}</h1>
                
                <!-- Pricing Box -->
                <div class="bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-100 flex items-center gap-5">
                    <span class="text-4xl font-extrabold text-gray-900 tracking-tighter">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    @if($product->sale_price)
                        <div class="flex flex-col">
                            <del class="text-gray-400 font-semibold text-lg leading-tight">৳{{number_format($product->regular_price)}}</del>
                            <span class="text-accent text-xs font-bold uppercase tracking-widest mt-0.5">Discount Applied</span>
                        </div>
                    @endif
                </div>

                <!-- Checkout Form -->
                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8">
                    
                    @if($product->colors)
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-gray-900 font-bold text-sm tracking-wide">Select Color: <span x-text="color" class="text-primary ml-1 font-semibold"></span></span>
                        </div>
                        <div class="flex gap-3 flex-wrap">
                            @foreach($product->colors as $c)
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                                <span class="px-6 py-3 rounded-xl border-2 border-gray-200 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary text-gray-600 font-bold text-sm transition-all block hover:border-gray-300">
                                    {{$c}}
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($product->sizes)
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-gray-900 font-bold text-sm tracking-wide">Select Size: <span x-text="size" class="text-primary ml-1 font-semibold"></span></span>
                        </div>
                        <div class="flex gap-3 flex-wrap">
                            @foreach($product->sizes as $s)
                            <label class="cursor-pointer">
                                <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                                <span class="min-w-[4rem] flex justify-center py-3 rounded-xl border-2 border-gray-200 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary text-gray-600 font-bold text-sm transition-all hover:border-gray-300">
                                    {{$s}}
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-4 lg:gap-6 mt-10">
                        <!-- Quantity -->
                        <div class="flex items-center justify-between bg-gray-50 rounded-2xl border-2 border-gray-100 p-2 sm:w-1/3 shadow-inner">
                            <button type="button" @click="if(qty > 1) qty--" class="w-12 h-12 rounded-xl flex items-center justify-center text-gray-500 hover:bg-white hover:text-gray-900 transition hover:shadow-sm"><i class="fas fa-minus"></i></button>
                            <input type="number" name="qty" x-model="qty" class="w-12 text-center bg-transparent border-none font-extrabold text-xl p-0 focus:ring-0 text-gray-900" readonly>
                            <button type="button" @click="qty++" class="w-12 h-12 rounded-xl flex items-center justify-center text-gray-500 hover:bg-white hover:text-gray-900 transition hover:shadow-sm"><i class="fas fa-plus"></i></button>
                        </div>
                        
                        <!-- Submit -->
                        <button type="submit" class="sm:w-2/3 btn-premium text-white py-5 rounded-2xl font-bold text-lg shadow-lg flex justify-center items-center gap-3">
                            Buy It Now <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                    
                    <!-- Trust Badges -->
                    <div class="flex items-center justify-center gap-6 mt-8 pt-8 border-t border-gray-100 text-gray-500 text-sm font-medium">
                        <div class="flex items-center gap-2"><i class="fas fa-shield-alt text-primary/70 text-xl"></i> Secure Checkout</div>
                        <div class="flex items-center gap-2"><i class="fas fa-undo text-primary/70 text-xl"></i> Easy Returns</div>
                    </div>

                </form>
            </div>
        </div>
        
    </div>

    <!-- Details Section -->
    <div class="mt-16 lg:mt-24 max-w-4xl mx-auto">
        <h2 class="text-2xl font-extrabold text-center mb-10 text-gray-900">Product Story & Details</h2>
        <div class="prose prose-lg max-w-none text-gray-600 font-medium leading-relaxed bg-white p-8 lg:p-12 rounded-[2rem] shadow-sm border border-gray-100">
            {!! clean($product->description ?? $product->long_description) !!}
        </div>
    </div>
</main>

    {{-- Dynamic Reviews Section --}}
    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])

@endsection
