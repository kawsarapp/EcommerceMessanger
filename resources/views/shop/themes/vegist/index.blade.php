@extends('shop.themes.vegist.layout')

@section('title', $client->shop_name . ' - ' . ($client->tagline ?? 'Organic & Supermarket Store'))

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

{{-- Info Strip --}}
<div class="border-b border-gray-100 py-6 hidden md:block">
    <div class="max-w-[1400px] mx-auto px-4 xl:px-8 grid grid-cols-4 divide-x divide-gray-100 text-center">
        <div class="flex items-center justify-center gap-3">
            <i class="fas fa-truck text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">Free shipping</h4>
                <span class="text-[11px] text-gray-500">Free delivery over $100</span>
            </div>
        </div>
        <div class="flex items-center justify-center gap-3">
            <i class="fas fa-gift text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">Gift voucher</h4>
                <span class="text-[11px] text-gray-500">Extra 20% off</span>
            </div>
        </div>
        <div class="flex items-center justify-center gap-3">
            <i class="fas fa-money-bill-wave text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">Money back</h4>
                <span class="text-[11px] text-gray-500">100% money back</span>
            </div>
        </div>
        <div class="flex items-center justify-center gap-3">
            <i class="fas fa-shield-alt text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">Safe payment</h4>
                <span class="text-[11px] text-gray-500">Secure checkout</span>
            </div>
        </div>
    </div>
</div>

{{-- Hero Section --}}
@php
    $heroActive = $client->widgets['hero_banner']['active'] ?? true;
    $heroText = $client->widgets['hero_banner']['text'] ?? 'Organic vegetable';
    $heroBtnText = $client->widgets['hero_banner']['button_text'] ?? 'Shop now';
    $heroLink = $client->widgets['hero_banner']['link'] ?? '#';
    $heroColor = $client->widgets['hero_banner']['color'] ?? '#222222';
    $heroImage = $client->widgets['hero_banner']['image'] ? asset('storage/'.$client->widgets['hero_banner']['image']) : null;
    if(!$heroImage && $client->banner) $heroImage = asset('storage/'.$client->banner);
@endphp

@if($heroActive)
<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Left Minor Banner (Static Design representation) --}}
        <div class="bg-lightgreen rounded-lg overflow-hidden relative group hidden lg:flex flex-col items-center justify-between p-8 text-center min-h-[400px]">
            <div>
                <h2 class="text-2xl font-black text-dark tracking-tight mb-4">Vegetable<br>supermarket</h2>
                <a href="{{$heroLink}}" class="btn-primary inline-block">Shop now</a>
            </div>
            <!-- We assume client has uploaded a banner, if not we fallback to a CSS colored box -->
            <div class="mt-8 flex-1 w-full bg-[url('https://images.unsplash.com/photo-1610832958506-aa56368176cf?q=80&w=400&auto=format&fit=crop')] bg-cover bg-center rounded-lg shadow-sm"></div>
        </div>

        {{-- Main Hero Banner --}}
        <div class="lg:col-span-2 bg-lightgreen rounded-lg overflow-hidden relative group flex items-center min-h-[300px] lg:min-h-[400px]" style="{{ $heroImage ? 'background-image: url('.$heroImage.'); background-size: cover; background-position: center;' : '' }}">
            <div class="relative z-10 p-8 md:p-16 max-w-lg">
                <span class="text-primary font-medium tracking-wider text-sm md:text-base uppercase mb-2 block">100% natural</span>
                <h1 class="text-4xl md:text-5xl font-black tracking-tight mb-8 leading-[1.1]" style="color: {{$heroColor}}">{{ $heroText }}</h1>
                <a href="{{$heroLink}}" class="btn-primary inline-block">{{ $heroBtnText }}</a>
            </div>
            @if(!$heroImage)
            <div class="absolute inset-0 z-0 flex justify-end items-center opacity-30 pointer-events-none">
                <i class="fas fa-leaf text-[250px] text-green-600 -mr-10"></i>
            </div>
            @endif
        </div>

    </div>
</div>
@endif

{{-- Category Widget --}}
@php
    $catActive = $client->widgets['category_filter']['active'] ?? true;
    $catTitle = $client->widgets['category_filter']['text'] ?? 'Shop by category';
@endphp
@if($catActive && isset($categories) && count($categories) > 0)
<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-12">
    <div class="text-center mb-10">
        <h2 class="text-2xl font-bold text-dark">{{ $catTitle }}</h2>
        <div class="w-16 h-1 bg-primary mx-auto mt-4 rounded-full"></div>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 md:gap-6">
        @foreach($categories as $category)
        <a href="{{$clean?$baseUrl.'/?category='.$category->slug:route('shop.show', ['shop' => $client->slug, 'category' => $category->slug])}}" class="group">
            <div class="bg-white border border-gray-100 hover:border-primary transition duration-300 p-6 flex flex-col items-center justify-center gap-4 min-h-[140px] shadow-sm hover:shadow-md">
                @if($category->image)
                    <img src="{{asset('storage/'.$category->image)}}" class="w-16 h-16 object-cover group-hover:scale-110 transition duration-300">
                @else
                    <div class="w-16 h-16 rounded-full bg-lightgreen flex items-center justify-center text-primary text-2xl group-hover:scale-110 transition duration-300">
                        <i class="fas fa-seedling"></i>
                    </div>
                @endif
                <span class="text-[12px] font-medium text-gray-600 group-hover:text-primary transition text-center uppercase">{{ $category->name }}</span>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Flash / Featured Offer Row --}}
@php
    // Attempt to load genuine active flash sale
    $activeFlashSale = \App\Models\FlashSale::where('client_id', $client->id)
        ->where('is_active', true)
        ->where('starts_at', '<=', now())
        ->where('ends_at', '>=', now())
        ->orderBy('ends_at', 'asc')
        ->first();

    $flashActive = $client->widgets['flash_sale']['active'] ?? true;
    $flashTitle = $client->widgets['flash_sale']['text'] ?? 'Featured products';
    $isRealFlashSale = false;
    $flashCountdownStr = null;
    $flashProducts = collect([]);

    if ($activeFlashSale) {
        $isRealFlashSale = true;
        $flashTitle = $activeFlashSale->title ?? 'Flash Sale';
        $flashCountdownStr = \Carbon\Carbon::parse($activeFlashSale->ends_at)->toIso8601String();
        
        $pIds = is_array($activeFlashSale->product_ids) ? $activeFlashSale->product_ids : (json_decode($activeFlashSale->product_ids, true) ?? []);
        if (!empty($pIds)) {
            $flashProducts = \App\Models\Product::whereIn('id', $pIds)->where('stock_status', 'in_stock')->take(8)->get();
        }
    } 
    
    if($flashProducts->isEmpty() && isset($products)) {
        $flashProducts = collect($products->items())->take(8);
    }
@endphp

@if($flashActive)
<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-12">
    <div class="flex justify-between items-end mb-10 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-6">
            <h2 class="text-2xl font-bold text-dark flex items-center gap-3">
                @if($isRealFlashSale) <i class="fas fa-bolt text-primary"></i> @endif
                {{ $flashTitle }}
            </h2>
            
            {{-- Countdown Timer --}}
            @if($isRealFlashSale && $flashCountdownStr)
            <div x-data="{
                    end: new Date('{{ $flashCountdownStr }}').getTime(),
                    distance: 0,
                    get days() { return Math.floor(this.distance / (1000 * 60 * 60 * 24)); },
                    get hours() { return Math.floor((this.distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)); },
                    get minutes() { return Math.floor((this.distance % (1000 * 60 * 60)) / (1000 * 60)); },
                    get seconds() { return Math.floor((this.distance % (1000 * 60)) / 1000); }
                }"
                x-init="setInterval(() => { distance = end - new Date().getTime() }, 1000)"
                class="hidden sm:flex gap-2 items-center bg-red-50 px-3 py-1.5 rounded text-red-600 font-mono text-sm border border-red-100 shadow-inner">
                <div class="flex flex-col items-center leading-none"><span x-text="days < 10 ? '0'+Math.max(0, days) : Math.max(0, days)" class="font-bold">00</span><span class="text-[8px] uppercase">Days</span></div>
                <span class="font-bold pb-2">:</span>
                <div class="flex flex-col items-center leading-none"><span x-text="hours < 10 ? '0'+Math.max(0, hours) : Math.max(0, hours)" class="font-bold">00</span><span class="text-[8px] uppercase">Hrs</span></div>
                <span class="font-bold pb-2">:</span>
                <div class="flex flex-col items-center leading-none"><span x-text="minutes < 10 ? '0'+Math.max(0, minutes) : Math.max(0, minutes)" class="font-bold">00</span><span class="text-[8px] uppercase">Min</span></div>
                <span class="font-bold pb-2">:</span>
                <div class="flex flex-col items-center leading-none"><span x-text="seconds < 10 ? '0'+Math.max(0, seconds) : Math.max(0, seconds)" class="font-bold">00</span><span class="text-[8px] uppercase">Sec</span></div>
            </div>
            @endif
        </div>
        <div class="hidden md:flex gap-6 text-[13px] font-medium text-gray-500">
            <a href="#" class="text-primary border-b-2 border-primary pb-4 -mb-[18px]">All Products</a>
        </div>
    </div>

    @if($flashProducts && count($flashProducts) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($flashProducts as $p)
        <div class="flex items-center gap-4 bg-white hover:bg-[#fcfdfa] p-4 rounded border border-transparent hover:border-gray-100 transition group relative">
            
            {{-- Discount Tag --}}
            @if($p->sale_price)
            <div class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-sm z-10 shadow">
                -{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}%
            </div>
            @elseif($isRealFlashSale)
            <div class="absolute top-2 left-2 bg-primary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-sm z-10 shadow">
                SALE
            </div>
            @endif

            <a href="{{$clean?$baseUrl.'/product/'.$p->slug:route('shop.product', ['shop' => $client->slug, 'product' => $p->slug])}}" class="shrink-0 w-28 h-28 bg-gray-50 flex items-center justify-center overflow-hidden mix-blend-multiply border border-gray-100 rounded">
                @if($p->thumbnail)
                    <img src="{{asset('storage/'.$p->thumbnail)}}" class="max-w-full max-h-full object-contain group-hover:scale-105 transition duration-500">
                @endif
            </a>

            <div class="flex flex-col justify-center gap-1.5">
                <a href="{{$clean?$baseUrl.'/product/'.$p->slug:route('shop.product', ['shop' => $client->slug, 'product' => $p->slug])}}" class="text-[13px] font-medium text-gray-600 hover:text-primary line-clamp-2 leading-tight">
                    {{$p->name}}
                </a>
                
                {{-- Price --}}
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[14px] font-bold text-dark">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                    @if($p->sale_price)
                    <span class="text-[12px] text-gray-400 line-through">৳{{number_format($p->regular_price)}}</span>
                    @endif
                </div>

                {{-- Stars --}}
                <div class="flex items-center text-[#ffb522] text-[10px] gap-0.5 mt-1">
                    @php $rating = $p->average_rating ?? 5; @endphp
                    @for($i=1; $i<=5; $i++)
                        @if($i <= $rating) <i class="fas fa-star"></i>
                        @elseif($i - 0.5 <= $rating) <i class="fas fa-star-half-alt"></i>
                        @else <i class="far fa-star text-gray-300"></i>
                        @endif
                    @endfor
                    <span class="text-gray-400 ml-1">({{ $p->reviews_count ?? 0 }} reviews)</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endif

{{-- Twin Banner Break --}}
@if($client->homepage_banner_active)
<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-[#fcf5ef] rounded p-12 flex flex-col items-start justify-center min-h-[300px] relative overflow-hidden group">
            <span class="text-primary font-medium text-[11px] uppercase tracking-widest mb-2 z-10">Fresh farm fruit</span>
            <h3 class="text-2xl md:text-3xl font-black text-dark mb-6 leading-tight max-w-[200px] z-10">{{ $client->homepage_banner_title ?? 'Fresh organic fruithouse' }}</h3>
            <a href="{{ $client->homepage_banner_link ?? '#' }}" class="btn-primary z-10">Shop now</a>
            
            @if($client->homepage_banner_image)
            <img src="{{asset('storage/'.$client->homepage_banner_image)}}" class="absolute right-0 bottom-0 h-[90%] object-contain group-hover:scale-105 transition duration-500 origin-bottom-right">
            @endif
        </div>
        <div class="bg-[#f0f5e5] rounded p-12 flex flex-col items-start justify-center min-h-[300px] relative overflow-hidden group">
            <span class="text-green-600 font-medium text-[11px] uppercase tracking-widest mb-2 z-10">Fresh farm vegetables</span>
            <h3 class="text-2xl md:text-3xl font-black text-dark mb-6 leading-tight max-w-[200px] z-10">Organic farmfood</h3>
            <a href="#" class="btn-primary z-10 !bg-green-500 hover:!bg-green-600">Shop now</a>
        </div>
    </div>
</div>
@endif

{{-- Trending Products Grid --}}
<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-16">
    <div class="text-center mb-10">
        <h2 class="text-2xl font-bold text-dark">Trending products</h2>
        <div class="w-16 h-1 bg-primary mx-auto mt-4 rounded-full"></div>
    </div>

    @if(isset($products) && count($products) > 0)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        @foreach($products as $p)
        <div class="bg-white group">
            <div class="relative bg-gray-50 aspect-square flex items-center justify-center p-6 overflow-hidden mb-4 mix-blend-multiply border border-transparent group-hover:border-gray-100 transition">
                
                {{-- Percentage Badge --}}
                {{-- Percentage Badge --}}
                @if($p->sale_price)
                @php $percent = round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100); @endphp
                <div class="absolute top-3 right-3 bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 z-10 shadow-sm">
                    -{{$percent}}%
                </div>
                @endif
                
                <a href="{{$clean?$baseUrl.'/product/'.$p->slug:route('shop.product', ['shop' => $client->slug, 'product' => $p->slug])}}" class="block w-full h-full">
                    @if($p->thumbnail)
                        <img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-contain group-hover:scale-105 transition duration-500 mix-blend-multiply">
                    @endif
                </a>

                {{-- Quick Actions --}}
                <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition duration-300 z-20">
                    <button class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white shadow-lg transition">
                        <i class="far fa-heart"></i>
                    </button>
                    <a href="{{$clean?$baseUrl.'/product/'.$p->slug:route('shop.product', ['shop' => $client->slug, 'product' => $p->slug])}}" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white shadow-lg transition">
                        <i class="fas fa-shopping-bag"></i>
                    </a>
                </div>
            </div>

            <div class="text-left px-1">
                <a href="{{$clean?$baseUrl.'/product/'.$p->slug:route('shop.product', ['shop' => $client->slug, 'product' => $p->slug])}}" class="text-[13px] text-gray-600 hover:text-primary transition line-clamp-1 mb-1">
                    {{$p->name}}
                </a>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[15px] font-bold text-dark">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                    @if($p->sale_price)
                    <span class="text-[12px] text-gray-400 line-through">৳{{number_format($p->regular_price)}}</span>
                    @endif
                </div>
                <div class="flex items-center text-[#ffb522] text-[10px] gap-0.5">
                    @php $rating = $p->average_rating ?? 5; @endphp
                    @for($i=1; $i<=5; $i++)
                        @if($i <= $rating) <i class="fas fa-star"></i>
                        @elseif($i - 0.5 <= $rating) <i class="fas fa-star-half-alt"></i>
                        @else <i class="far fa-star text-gray-300"></i>
                        @endif
                    @endfor
                    <span class="text-gray-400 ml-1">({{ $p->reviews_count ?? 0 }} reviews)</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
