@extends('shop.themes.default.layout')
@section('title', $product->name . ' | Shop')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12" x-data="{ mainImg: '{{asset('storage/'.$product->thumbnail)}}', qty: 1, color: '', size: '' }">
    
    <!-- Clean Breadcrumb -->
    <nav class="mb-8 flex items-center text-xs font-bold uppercase tracking-wider text-slate-400 overflow-hidden">
        <a href="{{$baseUrl}}" class="hover:text-primary premium-transition">Home</a>
        <i class="fas fa-chevron-right text-[10px] mx-3 text-slate-300"></i>
        <span class="hover:text-primary premium-transition cursor-pointer">{{$product->category->name ?? 'Catalog'}}</span>
        <i class="fas fa-chevron-right text-[10px] mx-3 text-slate-300"></i>
        <span class="text-slate-800 truncate">{{$product->name}}</span>
    </nav>

    <div class="bg-white rounded-[2rem] border border-slate-100 p-6 sm:p-10 lg:p-12 shadow-soft mb-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16">
            
            <!-- Gallery (Left) -->
            <div class="lg:col-span-5 flex flex-col space-y-6">
                <!-- Main Focus Image -->
                <div class="w-full aspect-square bg-slate-50/50 rounded-3xl relative p-8 flex items-center justify-center group overflow-hidden border border-slate-100">
                    <img :src="mainImg" class="max-w-full max-h-full object-contain mix-blend-multiply premium-transition group-hover:scale-110 z-10 duration-[1.5s]">
                    
                    @if($product->sale_price)
                        <div class="absolute top-5 left-5 z-20 bg-red-500 text-white font-bold text-xs uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm">
                            On Sale
                        </div>
                    @endif
                </div>
                
                <!-- Gallery Thumbnails -->
                @if(!empty($product->gallery))
                <div class="flex gap-4 overflow-x-auto hide-scroll pb-2 px-1">
                    <button type="button" @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                        class="w-20 aspect-square rounded-2xl p-2 flex items-center justify-center transition-all shrink-0 bg-white" 
                        :class="mainImg == '{{asset('storage/'.$product->thumbnail)}}' ? 'border-2 border-primary shadow-sm ring-4 ring-primary/5' : 'border border-slate-200 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </button>
                    @foreach($product->gallery as $img)
                    <button type="button" @click="mainImg = '{{asset('storage/'.$img)}}'" 
                        class="w-20 aspect-square rounded-2xl p-2 flex items-center justify-center transition-all shrink-0 bg-white" 
                        :class="mainImg == '{{asset('storage/'.$img)}}' ? 'border-2 border-primary shadow-sm ring-4 ring-primary/5' : 'border border-slate-200 hover:border-slate-300'">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            
            <!-- Details & Actions (Right) -->
            <div class="lg:col-span-7 flex flex-col">
                <div class="mb-8">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-[1.1] mb-4 tracking-tight">{{$product->name}}</h1>
                    
                    <div class="flex items-center gap-4 text-sm text-slate-500 font-semibold tracking-wide uppercase mb-6">
                        <span>SKU: <span class="text-slate-800">PRD-{{$product->id}}</span></span>
                        <div class="w-1 h-1 bg-slate-300 rounded-full"></div>
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <span class="text-red-500"><i class="fas fa-circle text-[8px] mr-1"></i> Out of Stock</span>
                        @else
                            @if($client->show_stock ?? true)
<span class="text-emerald-500"><i class="fas fa-circle text-[8px] mr-1"></i> In Stock ({{ $product->stock_quantity }})</span>
@else
<span class="text-emerald-500"><i class="fas fa-circle text-[8px] mr-1"></i> In Stock</span>
@endif
                        @endif
                    </div>

                    @php
                        $reviews = $product->reviews()->where('is_visible', true)->get();
                        $avgRating = $reviews->avg('rating') ?? 0;
                        $totalReviews = $reviews->count();
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="flex text-amber-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($avgRating))
                                    <i class="fas fa-star"></i>
                                @elseif($i - $avgRating < 1 && $avgRating > 0)
                                    <i class="fas fa-star-half-alt"></i>
                                @else
                                    <i class="far fa-star text-slate-200"></i>
                                @endif
                            @endfor
                        </div>
                        @if($totalReviews > 0)
                            <span class="text-slate-400 text-sm ml-1 font-medium">({{ $totalReviews }} {{ $totalReviews > 1 ? 'Reviews' : 'Review' }})</span>
                        @else
                            <span class="text-slate-400 text-sm ml-1 font-medium">(No reviews yet)</span>
                        @endif
                    </div>


                    <div class="flex items-end gap-3 mt-6">
                        <span class="text-4xl font-extrabold text-slate-900 tracking-tight">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                        @if($product->sale_price)
                            <del class="text-xl text-slate-400 font-semibold mb-1">৳{{number_format($product->regular_price)}}</del>
                        @endif
                    </div>
                </div>

                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="space-y-8 flex-1 flex flex-col">
                    
                    <div class="space-y-6">
                        @if($product->colors)
                        <div>
                            <span class="text-xs font-bold text-slate-800 block mb-3 uppercase tracking-widest">Color Variation <span class="text-primary font-normal ml-2 capitalize" x-text="color"></span></span>
                            <div class="flex gap-3 flex-wrap">
                                @foreach($product->colors as $c)
                                <label class="cursor-pointer relative group">
                                    <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                                    <span class="block px-6 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-bold text-sm premium-transition peer-checked:bg-slate-900 peer-checked:border-slate-900 peer-checked:text-white hover:border-slate-400">{{$c}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($product->sizes)
                        <div>
                            <span class="text-xs font-bold text-slate-800 block mb-3 uppercase tracking-widest">Size Options <span class="text-primary font-normal ml-2 capitalize" x-text="size"></span></span>
                            <div class="flex gap-3 flex-wrap">
                                @foreach($product->sizes as $s)
                                <label class="cursor-pointer">
                                    <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                                    <span class="block px-6 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-bold text-sm premium-transition peer-checked:bg-slate-900 peer-checked:border-slate-900 peer-checked:text-white hover:border-slate-400">{{$s}}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <hr class="border-slate-100">

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-2">
                        <!-- Quantity Control -->
                        <div class="h-14 bg-slate-50 rounded-xl border border-slate-200 flex w-full sm:w-36 items-center px-1">
                            <button type="button" @click="if(qty>1)qty--" class="flex-1 h-full flex items-center justify-center text-slate-500 hover:text-slate-900 premium-transition"><i class="fas fa-minus text-sm"></i></button>
                            <input type="number" name="qty" x-model="qty" class="w-12 text-center bg-transparent border-none font-bold text-slate-900 p-0 focus:ring-0 text-lg" readonly>
                            <button type="button" @click="qty++" class="flex-1 h-full flex items-center justify-center text-slate-500 hover:text-slate-900 premium-transition"><i class="fas fa-plus text-sm"></i></button>
                        </div>
                        
                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="flex-1 h-14 bg-slate-100 text-slate-400 rounded-xl font-bold uppercase tracking-widest text-sm cursor-not-allowed">Unavailable</button>
                        @else
                            @if($client->show_order_button ?? true)
                            <button type="submit" class="flex-1 h-14 bg-primary text-white rounded-xl font-bold uppercase tracking-widest text-sm premium-transition hover:bg-slate-800 hover:shadow-lg hover:shadow-primary/20 hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fas fa-shopping-bag text-base"></i> Buy Now
                            </button>
                            @endif
                            
                            {{-- Chat Button --}}
                            @include('shop.partials.chat-button', ['client' => $client])
                        @endif
                    </div>
                    
                    <!-- Trust Badges inside Form -->
                    <div class="grid grid-cols-3 gap-4 pt-6 mt-6 border-t border-slate-100">
                        <div class="flex flex-col items-center justify-center text-center gap-2 text-slate-400">
                            <i class="fas fa-truck text-xl"></i>
                            <span class="text-[10px] font-bold uppercase tracking-wider">Fast Delivery</span>
                        </div>
                        <div class="flex flex-col items-center justify-center text-center gap-2 text-slate-400">
                            <i class="fas fa-shield-alt text-xl"></i>
                            <span class="text-[10px] font-bold uppercase tracking-wider">Secure Checkout</span>
                        </div>
                        <div class="flex flex-col items-center justify-center text-center gap-2 text-slate-400">
                            <i class="fas fa-undo text-xl"></i>
                            <span class="text-[10px] font-bold uppercase tracking-wider">Easy Returns</span>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
    
    <!-- Info Section (Bottom) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-8">
            <div class="bg-white border rounded-[2rem] border-slate-100 p-8 md:p-12 shadow-soft">
                <h2 class="text-2xl font-bold text-slate-900 mb-8 tracking-tight flex items-center gap-3">
                    <i class="fas fa-align-left text-slate-300"></i> Product Overview
                </h2>
                <div class="prose prose-slate max-w-none font-medium text-slate-600 leading-relaxed prose-p:mb-5">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
            </div>
        </div>
        
        @if($product->key_features)
        <div class="lg:col-span-4 space-y-8">
            <div class="bg-slate-50 rounded-[2rem] p-8 md:p-10 border border-slate-100">
                <h2 class="text-xl font-bold text-slate-900 mb-6 tracking-tight">Key Features</h2>
                <ul class="space-y-4">
                    @foreach(is_string($product->key_features) ? json_decode($product->key_features,true) : $product->key_features as $feature)
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 mt-0.5"><i class="fas fa-check text-primary text-[10px]"></i></div>
                            <span class="text-sm font-semibold text-slate-700 leading-relaxed">{{$feature}}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            
            <div class="relative overflow-hidden bg-primary rounded-[2rem] p-8 text-center">
                 <!-- abstract blobs -->
                 <div class="absolute -top-10 -right-10 w-32 h-32 bg-white opacity-5 rounded-full blur-2xl"></div>
                 <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white opacity-5 rounded-full blur-2xl"></div>
                 
                 <i class="fas fa-headset text-4xl text-white/30 mb-4 relative z-10"></i>
                 <h4 class="font-bold text-white text-lg mb-2 relative z-10">Need Help?</h4>
                 <p class="text-sm font-medium text-white/70 relative z-10">We are here to answer your questions.</p>
                 <a href="{{$baseUrl}}" class="inline-block mt-4 text-xs font-bold uppercase tracking-widest text-white border-b border-white/30 pb-1 relative z-10 hover:border-white transition-colors">Contact Support</a>
            </div>
        </div>
        @endif

    </div>

    {{-- Dynamic Reviews Section --}}
    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])


        @include('shop.partials.related-products', ['client' => $client, 'product' => $product])
    @include('shop.partials.product-warranty', ['client' => $client, 'product' => $product])
</main>
@endsection