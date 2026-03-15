@extends('shop.themes.grocery.layout')
@section('title', $product->name . ' | Fresh Details')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-10" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <!-- Friendly Breadcrumb -->
    <div class="mb-6 font-bold text-sm text-slate-400 tracking-wide flex items-center gap-3">
        <a href="{{$baseUrl}}" class="hover:text-primary transition flex items-center gap-1"><i class="fas fa-home"></i> Home</a> 
        <i class="fas fa-chevron-right text-[10px] opacity-50"></i> 
        <span class="text-slate-500 truncate">{{$product->category->name ?? 'Produce'}}</span>
        <i class="fas fa-chevron-right text-[10px] opacity-50"></i> 
        <span class="text-slate-800 truncate max-w-[200px] sm:max-w-xs">{{$product->name}}</span>
    </div>

    <div class="bg-white rounded-[2.5rem] p-6 lg:p-12 shadow-sm border border-slate-100 mb-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">
            
            <!-- Left: Imagery Gallery -->
            <div class="flex flex-col space-y-6">
                <!-- Main Image wrapper with soft background -->
                <div class="w-full aspect-square bg-slate-50/80 rounded-[2rem] border border-slate-100 relative p-8 flex items-center justify-center overflow-hidden">
                    <!-- Subtle decorative blob behind product -->
                    <div class="absolute w-3/4 h-3/4 bg-primary/5 rounded-full filter blur-xl z-0"></div>
                    
                    <img :src="mainImg" class="max-w-full max-h-full object-contain mix-blend-multiply drop-shadow-lg transition-transform duration-500 scale-100 hover:scale-[1.05] z-10 relative">
                    
                    @if($product->sale_price)
                        <div class="absolute top-6 left-6 z-20 bg-secondary text-slate-900 font-black text-sm px-4 py-2 rounded-full shadow-md transform -rotate-3 flex items-center gap-2">
                            <i class="fas fa-tag"></i> Special Offer
                        </div>
                    @endif
                </div>
                
                <!-- Thumbnails -->
                <div class="flex gap-4 overflow-x-auto hide-scroll pb-2 px-1">
                    <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" class="w-24 aspect-square bg-slate-50 rounded-2xl p-2 flex items-center justify-center border-2 transition-all shrink-0 relative overflow-hidden group" :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary shadow-sm' : 'border-slate-100 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform">
                    </button>
                    @foreach($product->gallery ?? [] as $img)
                    <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" class="w-24 aspect-square bg-slate-50 rounded-2xl p-2 flex items-center justify-center border-2 transition-all shrink-0 relative overflow-hidden group" :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-primary shadow-sm' : 'border-slate-100 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform">
                    </button>
                    @endforeach
                </div>
            </div>
            
            <!-- Right: Product Information & Cart Options -->
            <div class="flex flex-col">
                <div class="border-b border-slate-100 pb-8 mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-primary/10 text-primary font-black text-xs px-3 py-1.5 rounded-lg uppercase tracking-wide"><i class="fas fa-leaf mr-1"></i> {{$product->category->name ?? 'Fresh Daily'}}</span>
                        
                        @if(isset($product->stock_status))
                            @if($product->stock_status == 'out_of_stock')
                                <span class="bg-red-100 text-red-600 border border-red-200 text-xs font-black px-3 py-1.5 rounded-lg whitespace-nowrap"><i class="fas fa-times-circle mr-1"></i> Out of Stock</span>
                            @else
                                <span class="bg-emerald-100 text-emerald-600 border border-emerald-200 text-xs font-black px-3 py-1.5 rounded-lg whitespace-nowrap"><i class="fas fa-check-circle mr-1"></i> In Stock</span>
                            @endif
                        @endif
                    </div>

                    <h1 class="text-3xl lg:text-4xl font-black text-slate-800 leading-tight mb-4 tracking-tight">{{$product->name}}</h1>
                    
                    <div class="flex items-end gap-4 mt-6">
                        <span class="text-5xl font-black text-slate-900 tracking-tighter">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                        @if($product->sale_price)
                            <div class="flex flex-col pb-1">
                                <del class="text-xl text-slate-400 font-bold leading-none mb-1">৳{{number_format($product->regular_price)}}</del>
                                <span class="text-red-500 font-black text-xs uppercase tracking-wider">Save ৳{{$product->regular_price - $product->sale_price}}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8 flex-1 flex flex-col">
                    
                    <div class="space-y-6 flex-1">
                        @if($product->colors)
                        <div>
                            <span class="text-sm font-bold text-slate-500 block mb-3">Choice / Type <span class="text-primary ml-1" x-text="color"></span></span>
                            <div class="flex gap-3 flex-wrap">
                                @foreach($product->colors as $c)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                                    <span class="block px-5 py-2.5 rounded-xl border-2 border-slate-200 bg-white text-slate-600 font-bold text-sm transition-all peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary hover:border-slate-300">{{$c}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($product->sizes)
                        <div>
                            <span class="text-sm font-bold text-slate-500 block mb-3">Weight / Size <span class="text-primary ml-1" x-text="size"></span></span>
                            <div class="flex gap-3 flex-wrap">
                                @foreach($product->sizes as $s)
                                <label class="cursor-pointer group">
                                    <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                                    <span class="block px-5 py-2.5 rounded-xl border-2 border-slate-200 bg-white text-slate-600 font-bold text-sm transition-all peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary hover:border-slate-300">{{$s}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Quantity & Action -->
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 flex flex-col sm:flex-row gap-4 mt-8">
                        <!-- Qty Selector -->
                        <div class="h-14 bg-white rounded-xl flex border border-slate-200 shadow-sm w-full sm:w-40 items-center px-2">
                            <button type="button" @click="if(qty>1)qty--" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-primary transition font-bold"><i class="fas fa-minus text-sm"></i></button>
                            <input type="number" name="qty" x-model="qty" class="flex-1 text-center bg-transparent border-none font-black text-xl text-slate-800 p-0 focus:ring-0" readonly>
                            <button type="button" @click="qty++" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-primary transition font-bold"><i class="fas fa-plus text-sm"></i></button>
                        </div>
                        
                        <!-- Submit -->
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="flex-1 h-14 bg-slate-200 text-slate-400 rounded-xl font-black text-lg cursor-not-allowed">Product Unavailable</button>
                        @else
                            <button type="submit" class="flex-1 h-14 bg-primary text-white rounded-xl font-black transition-all hover:bg-emerald-600 hover:shadow-lg hover:-translate-y-1 text-lg flex items-center justify-center gap-3 shadow-md">
                                Buy Fresh Now <i class="fas fa-shopping-basket"></i>
                            </button>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>
    
    <!-- Details Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <div class="lg:col-span-8 bg-white border border-slate-100 rounded-[2.5rem] p-8 md:p-12 shadow-sm">
            <h2 class="text-2xl font-black text-slate-800 mb-6 flex items-center gap-3"><i class="fas fa-apple-alt text-red-500"></i> What's Inside?</h2>
            <div class="prose prose-slate max-w-none text-base font-semibold text-slate-600 leading-relaxed">
                {!! clean($product->description ?? $product->long_description) !!}
            </div>
        </div>
        
        @if($product->key_features)
        <div class="lg:col-span-4 bg-emerald-50 border border-emerald-100 rounded-[2rem] p-8 self-start relative overflow-hidden">
            <!-- decorative overlay -->
            <i class="fas fa-leaf absolute -bottom-10 -right-10 text-9xl text-emerald-500/10 transform rotate-45"></i>
            
            <h2 class="text-xl font-black text-emerald-800 mb-6 relative z-10 hidden md:block">Highlights</h2>
            <ul class="space-y-4 relative z-10">
                @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                    <li class="flex items-start gap-4">
                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm shrink-0 border border-emerald-100">
                            <i class="fas fa-check text-primary text-sm"></i>
                        </div>
                        <span class="text-sm font-bold text-slate-700 mt-1.5">{{$feature}}</span>
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