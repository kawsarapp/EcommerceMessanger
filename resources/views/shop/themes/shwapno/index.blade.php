@extends('shop.themes.shwapno.layout')
@section('title', $client->shop_name . ' | ' . ($client->tagline ?? 'Online Shop'))

@section('content')

@php 
    $clean=preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')); 
    $baseUrl=$clean ? 'https://'.$clean : route('shop.show', $client->slug); 
@endphp

<style>
    .sw-metrics-box { display: flex; align-items: center; gap: 16px; padding: 18px 24px; background: white; border: 1px solid #f3f4f6; transition: border-color 0.2s; border-radius: 4px; }
    .sw-metrics-box:hover { border-color: #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .sw-metrics-icon { width: 50px; height: 50px; border-radius: 50%; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 20px; flex-shrink: 0; }
    
    .sw-card { background: white; border: 1px solid #f3f4f6; transition: all 0.2s; position: relative; display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; height: 100%; border-radius: 2px; }
    .sw-card:hover { border-color: #fee2e2; box-shadow: 0 4px 12px rgba(227,30,36,0.06); transform: translateY(-2px); }
    
    .sw-section-title { font-size: 18px; font-weight: 800; color: #333; text-transform: uppercase; text-align: center; margin-bottom: 24px; letter-spacing: 0.5px; }

    /* Horizontal Scroll Carousel — Desktop visible arrows */
    .sw-carousel-wrap { position: relative; }
    .sw-carousel-arrow {
        width: 36px; height: 36px;
        background: #ffd100;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        position: absolute; top: 50%; transform: translateY(-50%);
        z-index: 10; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        border: none; opacity: 0.85; transition: opacity 0.2s, transform 0.2s;
    }
    .sw-carousel-arrow:hover { opacity: 1; transform: translateY(-50%) scale(1.08); }
    .sw-carousel-arrow.left { left: -18px; }
    .sw-carousel-arrow.right { right: -18px; }
    @media(max-width: 1024px) { .sw-carousel-arrow { display: none; } }

    .sw-scroll-track {
        display: flex;
        overflow-x: auto;
        gap: 12px;
        padding-bottom: 8px;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }
    .sw-scroll-track::-webkit-scrollbar { display: none; }
    .sw-scroll-track > * { scroll-snap-align: start; flex-shrink: 0; }
</style>

<div class="max-w-[1340px] mx-auto px-4 lg:px-6">
    
    @if(!request('category') || request('category') == 'all')
    {{-- Hero Banner Component --}}
    @if($client->widget('hero_banner'))
        <div class="-mx-4 md:mx-0">
             <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" :categories="$categories ?? null" />
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-0 lg:gap-6 mt-4 mb-12">
        <div class="flex-1">
            
            {{-- Featured Category Tiles Slider --}}
            @php 
                $clientId = $client->id;
                $featuredCats = \App\Models\Category::where(function($q) use ($clientId) {
                    $q->where('client_id', $clientId)->orWhere('is_global', true);
                })->whereNotNull('banner_image')->where('is_visible', true)->take(8)->get();
            @endphp
            
            @if($featuredCats->count() > 0)
            <div class="sw-carousel-wrap mb-4" x-data="{
                scrollLeft() { $refs.slider.scrollBy({ left: -200, behavior: 'smooth' }); },
                scrollRight() { $refs.slider.scrollBy({ left: 200, behavior: 'smooth' }); }
            }">
                <button type="button" @click="scrollLeft()" class="sw-carousel-arrow left"><i class="fas fa-chevron-left text-xs text-swdark"></i></button>
                
                <div x-ref="slider" class="sw-scroll-track items-stretch">
                    @foreach($featuredCats as $c)
                    <a href="{{ $baseUrl }}?category={{ $c->slug }}" class="min-w-[130px] md:min-w-[165px] flex flex-col items-center relative overflow-hidden rounded group shadow-sm">
                        <div class="w-full h-28 md:h-40 overflow-hidden bg-gray-100">
                            <img src="{{ asset('storage/'.$c->banner_image) }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" loading="lazy" alt="{{ $c->name }}">
                        </div>
                        <div class="absolute bottom-2 bg-swyellow text-swdark font-bold text-[11px] md:text-xs px-5 py-1.5 rounded-full inline-block text-center shadow whitespace-nowrap border border-yellow-300">
                            {{ $c->name }}
                        </div>
                    </a>
                    @endforeach
                </div>
                
                <button type="button" @click="scrollRight()" class="sw-carousel-arrow right"><i class="fas fa-chevron-right text-xs text-swdark"></i></button>
            </div>
            @endif
        </div>
    </div>
    
    {{-- Info Metrics Bar (Trust Badges) --}}
    @php
        $trustActive = $client->widgets['trust_badges']['active'] ?? true;
        $trustColor  = $client->widgets['trust_badges']['color'] ?? '#ef4444';
        $trustTitle  = $client->widgets['trust_badges']['text'] ?? '';
        $trustItems  = $client->widgets['trust_badges']['items'] ?? [];
        $defaultTrust = [
            ['icon'=>'fa-box-open',  'title'=>'Fast Delivery',         'subtitle'=>$client->widgets['delivery_text']['text'] ?? 'Delivered to your door'],
            ['icon'=>'fa-shield-alt','title'=>'Authentic Products',     'subtitle'=>'100% genuine products'],
            ['icon'=>'fa-headset',   'title'=>'Customer Support',       'subtitle'=>$client->phone ?? ''],
            ['icon'=>'fa-wallet',    'title'=>'Flexible Payments',      'subtitle'=>'COD & digital payments'],
        ];
        $showItems = count($trustItems) > 0 ? $trustItems : $defaultTrust;
    @endphp
    @if($trustActive)
    @if($trustTitle) <h3 class="sw-section-title" style="text-align:left;font-size:16px;">{{ $trustTitle }}</h3> @endif
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-14">
        @foreach($showItems as $badge)
        <div class="sw-metrics-box">
            <div class="sw-metrics-icon" style="color:{{ $trustColor }};border-color:{{ $trustColor }}40;"><i class="fas {{ $badge['icon'] ?? 'fa-check' }}"></i></div>
            <div>
                <h4 class="text-sm font-bold text-gray-800">{{ $badge['title'] ?? '' }}</h4>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $badge['subtitle'] ?? '' }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif

    {{-- PRODUCTS SECTION 1 (Flash Sale / Featured) --}}
    @php
        $flashActive = $client->widgets['flash_sale']['active'] ?? true;
        $flashText   = $client->widgets['flash_sale']['text'] ?? 'FEATURED PRODUCTS';
        $flashColor  = $client->widgets['flash_sale']['color'] ?? '#ef4444';
    @endphp
    @if($flashActive && count($products) > 0)
    <div class="mb-14">
        <div class="flex items-center justify-between mb-5">
            <h3 class="sw-section-title mb-0">{{ $flashText }}</h3>
            @if($client->widgets['flash_sale']['link'] ?? false)
            <a href="{{ $client->widgets['flash_sale']['link'] }}" class="text-xs text-swred font-bold hover:underline flex items-center gap-1">View All <i class="fas fa-arrow-right text-[10px]"></i></a>
            @endif
        </div>
        
        <div class="sw-carousel-wrap" x-data="{
            scrollLeft() { $refs.slider.scrollBy({ left: -250, behavior: 'smooth' }); },
            scrollRight() { $refs.slider.scrollBy({ left: 250, behavior: 'smooth' }); }
        }">
            <button type="button" @click="scrollLeft()" class="sw-carousel-arrow left"><i class="fas fa-chevron-left text-xs text-swdark"></i></button>
            
            <div x-ref="slider" class="sw-scroll-track">
            @forelse($products->take(10) as $p)
                <div class="shrink-0 w-[160px] md:w-[200px] lg:w-[215px] snap-start h-full pb-2">
                    @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
                </div>
            @empty
                <div class="py-16 text-center text-gray-400">No products available.</div>
            @endforelse
            </div>
            
            <button type="button" @click="scrollRight()" class="sw-carousel-arrow right"><i class="fas fa-chevron-right text-xs text-swdark"></i></button>
        </div>
    </div>
    @endif
    
    {{-- PROMO BANNER (Full width) --}}
    @if($client->homepage_banner_active && $client->homepage_banner_image)
    <div class="mb-14">
        <a href="{{ $client->homepage_banner_link ?? '#' }}" class="block w-full rounded overflow-hidden shadow-sm hover:opacity-95 transition relative h-auto min-h-[160px] md:min-h-[220px]">
            <img src="{{ asset('storage/'.$client->homepage_banner_image) }}" class="absolute inset-0 w-full h-full object-cover object-center" loading="lazy" alt="{{ $client->homepage_banner_title ?? '' }}">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/80 to-blue-900/20 mix-blend-multiply"></div>
            <div class="relative z-10 p-6 md:p-12 flex flex-col items-start justify-center h-full max-w-3xl">
                @if(!empty($client->homepage_banner_title))
                    <h2 class="text-white text-3xl md:text-5xl font-black italic tracking-tighter drop-shadow-md mb-2">{!! nl2br(e($client->homepage_banner_title)) !!}</h2>
                @endif
                
                @if(!empty($client->homepage_banner_subtitle))
                    <p class="text-blue-100 text-sm md:text-base font-medium drop-shadow mb-6 max-w-2xl">{{ $client->homepage_banner_subtitle }}</p>
                @endif

                @if(!empty($client->homepage_banner_timer) && \Carbon\Carbon::parse($client->homepage_banner_timer)->isFuture())
                <div x-data="{
                        end: new Date('{{ \Carbon\Carbon::parse($client->homepage_banner_timer)->toIso8601String() }}').getTime(),
                        distance: 0,
                        get days() { return Math.max(0, Math.floor(this.distance / (1000*60*60*24))); },
                        get hours() { return Math.max(0, Math.floor((this.distance % (1000*60*60*24)) / (1000*60*60))); },
                        get minutes() { return Math.max(0, Math.floor((this.distance % (1000*60*60)) / (1000*60))); },
                        get seconds() { return Math.max(0, Math.floor((this.distance % (1000*60)) / 1000)); }
                    }"
                    x-init="setInterval(() => { distance = end - new Date().getTime() }, 1000)"
                    class="flex gap-3 text-center">
                    @foreach(['days'=>'Days','hours'=>'Hours','minutes'=>'Mins','seconds'=>'Secs'] as $unit => $label)
                    <div class="bg-white/20 backdrop-blur-sm border border-white/30 text-white rounded px-3 py-2 min-w-[60px]">
                        <span class="block text-xl md:text-2xl font-black leading-none" x-text="{{ $unit }}">0</span>
                        <span class="text-[10px] uppercase font-bold text-blue-100">{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </a>
    </div>
    @endif

    {{-- PRODUCTS SECTION 2 --}}
    @php
        $sec2Text = $client->widgets['everyday_essentials']['text'] ?? ($client->widgets['products_section']['title'] ?? 'ALL PRODUCTS');
        $skipCount = $flashActive ? 10 : 0;
    @endphp
    @if($products->skip($skipCount)->count() > 0 || $products->count() > 0)
    <div class="mb-20">
        <h3 class="sw-section-title">{{ $sec2Text }}</h3>
        
        <div class="sw-carousel-wrap" x-data="{
            scrollLeft() { $refs.slider.scrollBy({ left: -250, behavior: 'smooth' }); },
            scrollRight() { $refs.slider.scrollBy({ left: 250, behavior: 'smooth' }); }
        }">
            <button type="button" @click="scrollLeft()" class="sw-carousel-arrow left"><i class="fas fa-chevron-left text-xs text-swdark"></i></button>
            
            <div x-ref="slider" class="sw-scroll-track">
            @forelse($products->skip($skipCount) as $p)
                <div class="shrink-0 w-[160px] md:w-[200px] lg:w-[215px] snap-start h-full pb-2">
                    @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
                </div>
            @empty
                {{-- Section 1 already showed products, this is fine being empty --}}
            @endforelse
            </div>
            
            <button type="button" @click="scrollRight()" class="sw-carousel-arrow right"><i class="fas fa-chevron-right text-xs text-swdark"></i></button>
        </div>
        
        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="mt-8 pt-6 border-t border-gray-200">
            <style>
                .pg nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
                .pg nav a, .pg nav span { min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; background: white; color: #64748b; border: 1px solid #e2e8f0; transition: all 0.2s; font-weight: 500; border-radius: 4px; }
                .pg nav a:hover { border-color: #e31e24; color: #e31e24; }
                .pg nav span[aria-current="page"] { background: #e31e24; color: white !important; border-color: #e31e24; }
            </style>
            <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </div>
    @endif

</div>

@endsection
