@extends('shop.themes.shwapno.layout')
@section('title', $client->shop_name . ' | Online Grocery')

@section('content')

@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    /* Shwapno Component CSS */
    .sw-metrics-box { display: flex; align-items: center; gap: 16px; padding: 18px 24px; background: white; border: 1px solid #f3f4f6; transition: border-color 0.2s; border-radius: 4px; }
    .sw-metrics-box:hover { border-color: #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .sw-metrics-icon { width: 50px; height: 50px; border-radius: 50%; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 20px; shrink-0; }
    
    .sw-card { background: white; border: 1px solid #f3f4f6; transition: all 0.2s; position: relative; display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; height: 100%; border-radius: 2px; }
    .sw-card:hover { border-color: #fee2e2; box-shadow: 0 4px 12px rgba(227,30,36,0.06); transform: translateY(-2px); }
    
    .sw-section-title { font-size: 18px; font-weight: 800; color: #333; text-transform: uppercase; text-align: center; margin-bottom: 24px; letter-spacing: 0.5px; }
</style>

<div class="max-w-[1340px] mx-auto px-4 lg:px-6">
    
    @if(!request('category') || request('category') == 'all')
    {{-- Top Section: Sidebar + Banners --}}
    <div class="flex flex-col lg:flex-row gap-0 lg:gap-6 mb-12">
        
        {{-- Left Sidebar Category Menu --}}
        @php
            $catActive = $client->widgets['category_filter']['active'] ?? true;
            $catText = $client->widgets['category_filter']['text'] ?? 'SHOP BY CATEGORY';
        @endphp
        
        @if($catActive)
        <div class="hidden lg:block w-[256px] shrink-0 sw-sidebar self-start shadow-sm rounded-sm mt-4">
            
            @php $icons = ['fa-hamburger', 'fa-baby', 'fa-baby-carriage', 'fa-broom', 'fa-paw', 'fa-heartbeat', 'fa-tshirt', 'fa-blender', 'fa-pen-clip', 'fa-puzzle-piece', 'fa-mobile-alt']; @endphp
            
            @if(isset($categories) && count($categories)>0)
                @foreach($categories->take(11) as $c)
                <a href="{{$baseUrl}}?category={{$c->slug}}" class="sw-sidebar-item group">
                    <span class="flex items-center gap-3"><i class="fas {{$icons[$loop->index % 11]}} text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> {{$c->name}}</span> 
                    <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
                </a>
                @endforeach
            @else
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-hamburger text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Food</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-baby text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Baby Food & Care</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-baby-carriage text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Diapers</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-broom text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Home Cleaning</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-paw text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Pet Care</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-heartbeat text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Beauty & Health</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-tshirt text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Fashion & Lifestyle</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-blender text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Home & Kitchen</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-pen-clip text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Stationeries</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-puzzle-piece text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Toys & Sports</span> <i class="fas fa-chevron-right text-[10px] text-gray-300"></i></a>
                <a href="#" class="sw-sidebar-item group"><span class="flex items-center gap-3"><i class="fas fa-mobile-alt text-gray-400 text-sm group-hover:text-swred w-5 text-center"></i> Gadget</span></a>
            @endif
        </div>
        @endif

        {{-- Right Visual Area --}}
        <div class="flex-1 mt-4 {{ !$catActive ? 'w-full' : '' }}">
            {{-- Big Hero Banner mimicking "4 WAYS TO SAVE ON EVERY CART" --}}
            @php
                $heroActive = $client->widgets['hero_banner']['active'] ?? true;
                $heroText = $client->widgets['hero_banner']['text'] ?? '4 WAYS\nTO SAVE\nON EVERY\nCART!';
                $heroTextFormatted = nl2br(e($heroText));
                $heroLink = $client->widgets['hero_banner']['link'] ?? '#';
                $heroBg = $client->banner ? asset('storage/'.$client->banner) : 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1200&q=80';
            @endphp
            
            @if($heroActive)
            <a href="{{ $heroLink }}" class="w-full bg-swred relative overflow-hidden group cursor-pointer border border-red-700 flex items-center p-8 lg:p-12 mb-4 rounded-sm min-h-[160px] md:min-h-[260px] block">
                <img src="{{ $heroBg }}" class="absolute inset-0 w-full h-full object-cover opacity-20 mix-blend-overlay group-hover:scale-105 transition duration-[2s]" loading="lazy">
                <div class="relative z-10 w-full flex justify-between items-center h-full">
                    <div class="flex gap-2 lg:gap-4 h-full py-4 items-center" style="{{ isset($client->widgets['hero_banner']['color']) ? 'filter: hue-rotate(calc('.hexdec(substr($client->widgets['hero_banner']['color'], 1, 2)).'deg))' : '' }}">
                        <div class="bg-red-800 text-white w-14 lg:w-20 h-24 lg:h-36 -rotate-[15deg] flex flex-col items-center justify-center p-2 rounded shadow-lg border border-red-500/50">
                            <span class="text-[10px] lg:text-xs">৳</span>
                            <span class="text-xl lg:text-3xl font-black">25</span>
                            <span class="text-[10px] lg:text-xs font-bold">OFF</span>
                        </div>
                        <div class="bg-red-600 text-white w-16 lg:w-24 h-28 lg:h-40 -rotate-[5deg] flex flex-col items-center justify-center p-2 rounded shadow-xl border border-red-400/50 z-10 mt-4">
                            <span class="text-xs lg:text-sm">৳</span>
                            <span class="text-3xl lg:text-5xl font-black">75</span>
                            <span class="text-xs lg:text-sm font-bold">OFF</span>
                        </div>
                        <div class="bg-swyellow text-swred w-20 lg:w-28 h-32 lg:h-48 rotate-[5deg] flex flex-col items-center justify-center p-2 rounded shadow-2xl border border-yellow-300 z-20 mt-8">
                            <span class="text-sm lg:text-base">৳</span>
                            <span class="text-4xl lg:text-6xl font-black shrink-0 leading-none mb-1">100</span>
                            <span class="text-sm lg:text-base font-bold tracking-widest leading-none">OFF</span>
                        </div>
                    </div>
                    
                    <div class="hidden md:flex flex-col items-end text-white text-right drop-shadow-md relative z-20">
                        <h2 class="text-3xl xl:text-5xl font-black uppercase leading-tight mb-2">
                            {!! $heroTextFormatted !!}
                        </h2>
                        <button class="bg-white text-swred font-black px-6 py-2 rounded-full uppercase text-sm mt-4 hover:bg-gray-100 transition shadow">EXPLORE NOW</button>
                    </div>
                </div>
            </a>
            @endif
            
            {{-- Quick Product Tiles --}}
            <div class="flex gap-4 overflow-x-auto pb-4 hide-scroll relative items-center group">
                <button class="w-6 h-6 bg-swyellow rounded-sm hidden lg:flex items-center justify-center absolute left-0 z-10 opacity-0 group-hover:opacity-100 transition shadow"><i class="fas fa-chevron-left text-xs"></i></button>
                
                @php 
                    $clientId = $client->id;
                    $featuredCats = \App\Models\Category::where(function($q) use ($clientId) {
                        $q->where('client_id', $clientId)->orWhere('is_global', true);
                    })->whereNotNull('banner_image')->where('is_visible', true)->take(6)->get();
                @endphp
                
                @if($featuredCats->count() > 0)
                    @foreach($featuredCats as $c)
                    <a href="{{$baseUrl}}?category={{$c->slug}}" class="min-w-[140px] md:min-w-[180px] flex-1 flex flex-col items-center relative overflow-hidden rounded group shadow-sm">
                        <div class="w-full h-32 md:h-44 overflow-hidden bg-gray-200">
                            <img src="{{ asset('storage/'.$c->banner_image) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" loading="lazy" alt="{{$c->name}}">
                        </div>
                        <div class="absolute bottom-2 bg-swyellow text-swdark font-bold text-[11px] md:text-sm px-8 py-1.5 rounded-full inline-block text-center shadow whitespace-nowrap border border-yellow-300">
                            {{$c->name}}
                        </div>
                    </a>
                    @endforeach
                @else
                    {{-- Default fallback placeholder if no categories have banner images --}}
                    @php $quickLinks = [
                        ['name'=>'Eggs', 'img'=>'https://images.unsplash.com/photo-1587486913049-53fc88980bfc?w=200'],
                        ['name'=>'Tea', 'img'=>'https://images.unsplash.com/photo-1597318181409-cf64d0b5d8a2?w=200'],
                        ['name'=>'Soft Drinks', 'img'=>'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=200'],
                        ['name'=>'Frozen', 'img'=>'https://images.unsplash.com/photo-1588147250640-1f33f6dc3d02?w=200'],
                        ['name'=>'Coffee', 'img'=>'https://images.unsplash.com/photo-1559525839-b184a4d698c7?w=200']
                    ]; @endphp
                    @foreach($quickLinks as $q)
                    <a href="#" class="min-w-[140px] md:min-w-[180px] flex-1 flex flex-col items-center relative overflow-hidden rounded group shadow-sm">
                        <div class="w-full h-32 md:h-44 overflow-hidden bg-gray-200">
                            <img src="{{$q['img']}}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" loading="lazy">
                        </div>
                        <div class="absolute bottom-2 bg-swyellow text-swdark font-bold text-[11px] md:text-sm px-8 py-1.5 rounded-full inline-block text-center shadow whitespace-nowrap border border-yellow-300">
                            {{$q['name']}}
                        </div>
                    </a>
                    @endforeach
                @endif
                
                <button class="w-6 h-6 bg-swyellow rounded-sm hidden lg:flex items-center justify-center absolute right-0 z-10 opacity-0 group-hover:opacity-100 transition shadow"><i class="fas fa-chevron-right text-xs"></i></button>
            </div>
        </div>
    </div>
    
    {{-- Info Metrics Bar --}}
    @php
        $trustActive = $client->widgets['trust_badges']['active'] ?? true;
        $trustColor = $client->widgets['trust_badges']['color'] ?? '#ef4444';
        $trustText = $client->widgets['trust_badges']['text'] ?? '';
    @endphp
    @if($trustActive)
    @if($trustText) <h3 class="sw-section-title" style="text-align: left; font-size: 16px;">{{ $trustText }}</h3> @endif
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-16">
        <div class="sw-metrics-box">
            <div class="sw-metrics-icon" style="color: {{ $trustColor }}; border-color: {{ $trustColor }}40;"><i class="fas fa-box-open"></i></div>
            <div>
                <h4 class="text-sm font-bold text-gray-800">60 Mins Delivery</h4>
                <p class="text-[11px] text-gray-500 mt-0.5">Free shipping over 1500Tk</p>
            </div>
        </div>
        <div class="sw-metrics-box">
            <div class="sw-metrics-icon" style="color: {{ $trustColor }}; border-color: {{ $trustColor }}40;"><i class="fas fa-shield-alt"></i></div>
            <div>
                <h4 class="text-sm font-bold text-gray-800">Authorized Products</h4>
                <p class="text-[11px] text-gray-500 mt-0.5">within 30 days for an exchange</p>
            </div>
        </div>
        <div class="sw-metrics-box">
            <div class="sw-metrics-icon" style="color: {{ $trustColor }}; border-color: {{ $trustColor }}40;"><i class="fas fa-headset"></i></div>
            <div>
                <h4 class="text-sm font-bold text-gray-800">Customer Service Support</h4>
                <p class="text-[11px] text-gray-500 mt-0.5">8am to 10pm</p>
            </div>
        </div>
        <div class="sw-metrics-box">
            <div class="sw-metrics-icon" style="color: {{ $trustColor }}; border-color: {{ $trustColor }}40;"><i class="fas fa-wallet"></i></div>
            <div>
                <h4 class="text-sm font-bold text-gray-800">Flexible Payments</h4>
                <p class="text-[11px] text-gray-500 mt-0.5">Pay with multiple credit cards</p>
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- DYNAMIC PRODUCTS SECTION 1 (Flash Sale / Featured) --}}
    @php
        $flashActive = $client->widgets['flash_sale']['active'] ?? true;
        $flashText = $client->widgets['flash_sale']['text'] ?? 'RECOMMENDED FOR YOU';
        $flashColor = $client->widgets['flash_sale']['color'] ?? '#ef4444';
    @endphp
    @if($flashActive && count($products) > 0)
    <div class="mb-16">
        <h3 class="sw-section-title">{{ $flashText }}</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 lg:gap-4 relative group">
            <button class="w-8 h-8 bg-swyellow rounded-full hidden lg:flex items-center justify-center absolute -left-4 top-1/2 -translate-y-1/2 z-10 shadow"><i class="fas fa-chevron-left text-sm"></i></button>
            
            @forelse($products->take(5) as $p)
                <div class="sw-card group/card" style="--badge-color: {{ $flashColor }}">
                    {{-- Discount Square Badge --}}
                    @if($p->sale_price)<span class="absolute top-0 left-0 text-white text-[10px] font-bold px-1.5 py-1 z-10 flex flex-col items-center leading-none rounded-br-sm shadow-sm" style="background-color: var(--badge-color);"><span class="text-[8px]">৳{{ $p->regular_price - $p->sale_price }}</span><span>OFF</span></span>@endif
                    
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block flex items-center justify-center h-40 mb-2 mt-4">
                        <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain group-hover/card:scale-105 transition duration-300">
                    </a>
                    
                    <div class="text-center mt-auto flex flex-col items-center">
                        <span class="text-[10px] italic text-gray-400 mb-1">Delivery 1-2 hours</span>
                        
                        <a href="{{$baseUrl.'/product/'.$p->slug}}" class="w-full">
                            <h4 class="text-xs font-bold text-gray-800 line-clamp-2 h-8 leading-snug mb-2 hover:text-swred transition">{{$p->name}}</h4>
                        </a>
                        
                        <div class="flex items-center justify-center gap-1.5 mb-2 h-6">
                            @if($p->sale_price)
                                <del class="text-[11px] text-gray-400 font-medium">৳{{number_format($p->regular_price, 0)}}</del>
                            @endif
                            <span class="font-bold text-swred text-sm">৳{{number_format($p->sale_price ?? $p->regular_price, 0)}}</span>
                            <span class="text-[10px] text-gray-500 ml-1 font-medium">Per Piece</span>
                        </div>
                        
                        <form action="{{$baseUrl.'/checkout/'.$p->slug}}" method="GET" class="w-full mt-2">
                            <button class="w-full sw-btn-pill sw-btn-red py-2 text-xs w-[85%] mx-auto hover:shadow-md">
                                <i class="fas fa-plus mr-1"></i> Add to Bag
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center text-gray-400">No products available.</div>
            @endforelse
            
            <button class="w-8 h-8 bg-swyellow rounded-full hidden lg:flex items-center justify-center absolute -right-4 top-1/2 -translate-y-1/2 z-10 shadow"><i class="fas fa-chevron-right text-sm"></i></button>
        </div>
    </div>
    @endif
    
    {{-- PROMO BANNER (Full width) --}}
    @if($client->homepage_banner_active && $client->homepage_banner_image)
    <div class="mb-16">
        <a href="{{ $client->homepage_banner_link ?? '#' }}" class="block w-full rounded overflow-hidden shadow-sm hover:opacity-95 transition relative">
            <img src="{{ asset('storage/'.$client->homepage_banner_image) }}" class="w-full h-32 md:h-48 object-cover object-center" loading="lazy">
            <div class="absolute inset-0 bg-blue-900/40 mix-blend-multiply"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-white text-4xl md:text-6xl font-black italic tracking-tighter drop-shadow-md px-4 text-center">{!! nl2br(e($client->homepage_banner_title)) !!}</span>
            </div>
        </a>
    </div>
    @endif

    {{-- DYNAMIC PRODUCTS SECTION 2 --}}
    <div class="mb-20">
        <h3 class="sw-section-title">EVERYDAY ESSENTIALS 🔥</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 lg:gap-4 relative group">
            @forelse($products->skip(5)->take(5) as $p)
                <div class="sw-card group/card">
                    @if($p->sale_price)<span class="absolute top-0 left-0 bg-swred text-white text-[10px] font-bold px-1.5 py-1 z-10 flex flex-col items-center leading-none rounded-br-sm shadow-sm"><span class="text-[8px]">৳{{ $p->regular_price - $p->sale_price }}</span><span>OFF</span></span>@endif
                    
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block flex items-center justify-center h-40 mb-2 mt-4">
                        <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain group-hover/card:scale-105 transition duration-300">
                    </a>
                    
                    <div class="text-center mt-auto flex flex-col items-center">
                        <span class="text-[10px] italic text-gray-400 mb-1">Delivery 1-2 hours</span>
                        
                        <a href="{{$baseUrl.'/product/'.$p->slug}}" class="w-full">
                            <h4 class="text-xs font-bold text-gray-800 line-clamp-2 h-8 leading-snug mb-2 hover:text-swred transition">{{$p->name}}</h4>
                        </a>
                        
                        <div class="flex items-center justify-center gap-1.5 mb-2 h-6">
                            @if($p->sale_price)
                                <del class="text-[11px] text-gray-400 font-medium">৳{{number_format($p->regular_price, 0)}}</del>
                            @endif
                            <span class="font-bold text-swred text-sm">৳{{number_format($p->sale_price ?? $p->regular_price, 0)}}</span>
                            <span class="text-[10px] text-gray-500 ml-1 font-medium">Per Piece</span>
                        </div>
                        
                        <form action="{{$baseUrl.'/checkout/'.$p->slug}}" method="GET" class="w-full mt-2">
                            <button class="w-full sw-btn-pill sw-btn-red py-2 text-xs w-[85%] mx-auto hover:shadow-md">
                                <i class="fas fa-plus mr-1"></i> Add to Bag
                            </button>
                        </form>
                    </div>
                </div>
            @empty
            @endforelse
        </div>
        
        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="mt-8 pt-6 border-t border-gray-200">
            <style>
                .pg nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
                .pg nav a, .pg nav span { min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; background: white; color: #64748b; border: 1px solid #e2e8f0; transition: all 0.2s; font-weight: 500;}
                .pg nav a:hover { border-color: var(--swred); color: var(--swred); }
                .pg nav span[aria-current="page"] { background: var(--swred); color: white !important; border-color: var(--swred); }
            </style>
            <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </div>

</div>

@endsection
