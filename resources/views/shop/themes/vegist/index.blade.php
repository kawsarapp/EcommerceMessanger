@extends('shop.themes.vegist.layout')

@section('title', $client->shop_name . ' - ' . ($client->tagline ?? 'Organic & Supermarket Store'))

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

{{-- Info Strip --}}
@php
    $featureActive = $client->widgets['feature_strip']['active'] ?? true;
@endphp
@if($featureActive)
<div class="border-b border-gray-100 py-6 hidden md:block">
    <div class="max-w-[1400px] mx-auto px-4 xl:px-8 grid grid-cols-4 divide-x divide-gray-100 text-center">
        <div class="flex items-center justify-center gap-3 hover:-translate-y-1 transition duration-300">
            <i class="fas fa-truck text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">{{ $client->widgets['feature_strip']['items'][0]['title'] ?? 'Free shipping' }}</h4>
                <span class="text-[11px] text-gray-500">{{ $client->widgets['feature_strip']['items'][0]['subtitle'] ?? 'Free delivery over $100' }}</span>
            </div>
        </div>
        <div class="flex items-center justify-center gap-3 hover:-translate-y-1 transition duration-300">
            <i class="fas fa-gift text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">{{ $client->widgets['feature_strip']['items'][1]['title'] ?? 'Gift voucher' }}</h4>
                <span class="text-[11px] text-gray-500">{{ $client->widgets['feature_strip']['items'][1]['subtitle'] ?? 'Extra 20% off' }}</span>
            </div>
        </div>
        <div class="flex items-center justify-center gap-3 hover:-translate-y-1 transition duration-300">
            <i class="fas fa-money-bill-wave text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">{{ $client->widgets['feature_strip']['items'][2]['title'] ?? 'Money back' }}</h4>
                <span class="text-[11px] text-gray-500">{{ $client->widgets['feature_strip']['items'][2]['subtitle'] ?? '100% money back' }}</span>
            </div>
        </div>
        <div class="flex items-center justify-center gap-3 hover:-translate-y-1 transition duration-300">
            <i class="fas fa-shield-alt text-2xl text-primary opacity-80"></i>
            <div>
                <h4 class="text-sm font-bold text-dark">{{ $client->widgets['feature_strip']['items'][3]['title'] ?? 'Safe payment' }}</h4>
                <span class="text-[11px] text-gray-500">{{ $client->widgets['feature_strip']['items'][3]['subtitle'] ?? 'Secure checkout' }}</span>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Hero Section --}}
@if($client->widget('hero_banner'))
    <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" :categories="$categories ?? null" />
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
    
    <div class="flex md:grid md:grid-cols-4 lg:grid-cols-6 gap-4 md:gap-6 overflow-x-auto snap-x snap-mandatory hide-scroll pb-4 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0 scroll-smooth">
        @foreach($categories as $category)
        <a href="{{$clean?$baseUrl.'/?category='.$category->slug:route('shop.show', ['shop' => $client->slug, 'category' => $category->slug])}}" class="group shrink-0 w-[40vw] md:w-auto snap-start">
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
            <a href="{{$clean?$baseUrl.'/?category=all':route('shop.show', ['shop' => $client->slug, 'category' => 'all'])}}" class="text-primary border-b-2 border-primary pb-4 -mb-[18px]">All Products</a>
        </div>
    </div>

    @if($flashProducts && count($flashProducts) > 0)
    <div class="flex md:grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6 overflow-x-auto snap-x snap-mandatory hide-scroll pb-4 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0 scroll-smooth">
        @foreach($flashProducts as $p)
        <div class="shrink-0 w-[45vw] sm:w-[35vw] md:w-auto snap-start h-full">
            @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
        </div>
        @endforeach
    </div>
    @endif
</div>
@endif

{{-- Twin Banner Break --}}
@if($client->homepage_banner_active ?? true)
<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-[#fcf5ef] rounded p-12 flex flex-col items-start justify-center min-h-[300px] relative overflow-hidden group hover:shadow-lg transition duration-300">
            <span class="text-primary font-medium text-[11px] uppercase tracking-widest mb-2 z-10">{{ $client->widgets['twin_banner']['left_subtitle'] ?? 'Fresh farm fruit' }}</span>
            <h3 class="text-2xl md:text-3xl font-black text-dark mb-6 leading-tight max-w-[200px] z-10">{{ $client->homepage_banner_title ?? 'Fresh organic fruithouse' }}</h3>
            <a href="{{ $client->homepage_banner_link ?? $baseUrl.'?category=all' }}" class="btn-primary z-10">Shop now</a>
            
            @if($client->homepage_banner_image)
            <img src="{{asset('storage/'.$client->homepage_banner_image)}}" class="absolute right-0 bottom-0 h-[90%] object-contain group-hover:scale-105 transition duration-500 origin-bottom-right">
            @endif
        </div>
        <div class="bg-[#f0f5e5] rounded p-12 flex flex-col items-start justify-center min-h-[300px] relative overflow-hidden group hover:shadow-lg transition duration-300">
            <span class="text-green-600 font-medium text-[11px] uppercase tracking-widest mb-2 z-10">{{ $client->widgets['twin_banner']['right_subtitle'] ?? 'Fresh farm vegetables' }}</span>
            <h3 class="text-2xl md:text-3xl font-black text-dark mb-6 leading-tight max-w-[200px] z-10">{{ $client->widgets['twin_banner']['right_title'] ?? 'Organic farmfood' }}</h3>
            <a href="{{ $client->widgets['twin_banner']['right_link'] ?? $baseUrl.'?category=all' }}" class="btn-primary z-10 !bg-green-500 hover:!bg-green-600">Shop now</a>
        </div>
    </div>
</div>
@endif

{{-- Trending Products Grid --}}
<div class="max-w-[1400px] mx-auto px-4 xl:px-8 py-16">
    <div class="text-center mb-10">
        <h2 class="text-2xl font-bold text-dark">{{ $client->widgets['trending_title']['text'] ?? 'Trending products' }}</h2>
        <div class="w-16 h-1 bg-primary mx-auto mt-4 rounded-full"></div>
    </div>

    @if(isset($products) && count($products) > 0)
    <div class="flex md:grid md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6 overflow-x-auto snap-x snap-mandatory hide-scroll pb-4 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0 scroll-smooth">
        @foreach($products as $p)
        <div class="shrink-0 w-[45vw] sm:w-[35vw] md:w-auto snap-start h-full">
            @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
