@extends('shop.themes.pikabo.layout')
@section('title', $client->shop_name . ' | Online Shopping')

@section('content')

@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    /* Custom CSS for Pikabo */
    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .hero-banner {
        width: 100%;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 24px;
        background-color: #f1f5f9;
        aspect-ratio: 21/9;
        background-size: cover;
        background-position: center;
        background-image: url('https://images.unsplash.com/photo-1601506521937-0121a7fc2a6b?auto=format&fit=crop&w=1600&q=80');
    }

    .category-card {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s;
    }
    .category-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .category-badge {
        background-color: var(--tw-color-primary);
        color: white;
        text-align: center;
        padding: 6px 4px;
        font-size: 11px;
        font-weight: 700;
        margin-top: auto;
    }
    .category-img-container {
        padding: 16px;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc, #eff6ff);
    }

    .product-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        position: relative;
        transition: all 0.2s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .product-card:hover { border-color: var(--tw-color-primary); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .product-card-img {
        aspect-ratio: 1;
        object-fit: contain;
        width: 100%;
        margin-bottom: 12px;
    }
    .super-offer-badge {
        position: absolute;
        top: 0;
        right: 12px;
        background: #ff0000;
        color: white;
        font-weight: 900;
        font-size: 10px;
        padding: 2px 6px;
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
    }
    .super-offer-logo {
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        width: 80%;
    }
</style>

<div class="max-w-[1400px] mx-auto px-4 mt-6">

    @if(!request('category') || request('category') == 'all')
    {{-- Hero Area --}}
    @if($client->widget('hero_banner'))
        <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" :categories="$categories ?? null" />
    @endif

    {{-- Official Warranty Banner / Trust Badges --}}
    @if($client->widgets['trust_badges']['active'] ?? true)
    <div class="bg-white border border-gray-200 rounded-lg py-4 px-6 flex items-center justify-between mb-10 overflow-x-auto gap-4 hide-scroll">
        <div class="flex items-center gap-3 shrink-0"><i class="fas fa-undo text-primary/80 text-xl"></i> <span class="font-medium text-sm text-gray-700">{{ $client->widgets['trust_badges']['badge_1'] ?? 'Easy Returns' }}</span></div>
        <div class="flex items-center gap-3 shrink-0"><i class="fas fa-shield-alt text-primary/80 text-xl"></i> <span class="font-medium text-sm text-gray-700">{{ $client->widgets['trust_badges']['badge_2'] ?? '100% Authentic' }}</span></div>
        <div class="flex items-center gap-3 shrink-0"><i class="fas fa-truck text-primary/80 text-xl"></i> <span class="font-medium text-sm text-gray-700">{{ $client->widgets['trust_badges']['badge_3'] ?? 'Fast Delivery' }}</span></div>
        <div class="flex items-center gap-3 shrink-0"><i class="fas fa-credit-card text-primary/80 text-xl"></i> <span class="font-medium text-sm text-gray-700">{{ $client->widgets['trust_badges']['badge_4'] ?? 'Secure Payment' }}</span></div>
    </div>
    @endif

    {{-- Categories --}}
    <div class="mb-12">
        <div class="section-title">
            <span>{{ $client->widgets['categories']['title'] ?? 'Shop By Categories' }}</span>
            <a href="{{$baseUrl}}?category=all" class="bg-gray-100 hover:bg-gray-200 text-dark text-xs font-semibold px-4 py-1.5 rounded transition">View All</a>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @php
                $catStyles = [
                    ['bg' => 'bg-blue-400'],
                    ['bg' => 'bg-green-400'],
                    ['bg' => 'bg-yellow-400'],
                    ['bg' => 'bg-emerald-100'],
                    ['bg' => 'bg-indigo-400'],
                    ['bg' => 'bg-pink-400']
                ];
            @endphp
            @if(isset($categories) && count($categories) > 0)
                @foreach($categories->take(6) as $index => $c)
                @php $style = $catStyles[$index % count($catStyles)] @endphp
                <a href="{{$baseUrl}}?category={{$c->slug}}" class="category-card">
                    <div class="category-img-container {{$style['bg']}} bg-opacity-20">
                        @if($c->image)
                            <img src="{{asset('storage/'.$c->image)}}" class="w-full h-24 object-contain mix-blend-multiply" alt="{{$c->name}}">
                        @else
                            <i class="fas fa-box text-4xl text-gray-400"></i>
                        @endif
                    </div>
                    <div class="p-2 text-center text-[10px] text-gray-500 font-medium border-t border-gray-100">{{ $c->products_count ?? 0 }} Products</div>
                    <div class="category-badge">{{$c->name}}</div>
                </a>
                @endforeach
            @else
                {{-- Fallback --}}
                @for($i=1; $i<=6; $i++)
                <div class="category-card">
                    <div class="category-img-container bg-primary/10"><i class="fas fa-tv text-4xl text-primary/20"></i></div>
                    <div class="p-2 text-center text-[10px] text-gray-500">Official Warranty</div>
                    <div class="category-badge">Electronics</div>
                </div>
                @endfor
            @endif
        </div>
    </div>
    @endif

    {{-- Flash Sale Row --}}
    @php
        $activeFlashSale = \App\Models\FlashSale::where('client_id', $client->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at', 'asc')
            ->first();
            
        $flashProducts = collect([]);
        if ($activeFlashSale) {
            $pIds = is_array($activeFlashSale->product_ids) ? $activeFlashSale->product_ids : (json_decode($activeFlashSale->product_ids, true) ?? []);
            if (!empty($pIds)) {
                $flashProducts = \App\Models\Product::whereIn('id', $pIds)->where('stock_status', 'in_stock')->take(10)->get();
            }
        }
        
        $flashText = $client->widgets['flash_sale']['text'] ?? 'FLASH SALE';
        $flashCountdown = $activeFlashSale ? max(0, (int) now()->diffInSeconds($activeFlashSale->ends_at, false)) : 0;
    @endphp
    
    @if($activeFlashSale && count($flashProducts) > 0)
    <div class="mb-12 border border-primary rounded-lg overflow-hidden">
        <div class="bg-primary flex justify-between items-center px-4 py-3">
            <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-6">
                <h2 class="text-xl font-bold text-white uppercase italic tracking-wider">{{ $flashText }}</h2>
                <div class="flex gap-1.5 text-white/90" x-data="{
                    time: {{ $flashCountdown }},
                    h: '00', m: '00', s: '00',
                    init() {
                        if(this.time <= 0) return;
                        setInterval(() => {
                            if(this.time > 0) {
                                this.time--;
                                this.h = String(Math.floor(this.time / 3600)).padStart(2, '0');
                                this.m = String(Math.floor((this.time % 3600) / 60)).padStart(2, '0');
                                this.s = String(this.time % 60).padStart(2, '0');
                            }
                        }, 1000);
                        this.h = String(Math.floor(this.time / 3600)).padStart(2, '0');
                        this.m = String(Math.floor((this.time % 3600) / 60)).padStart(2, '0');
                        this.s = String(this.time % 60).padStart(2, '0');
                    }
                }">
                    <div class="bg-black/20 px-2 py-1 rounded text-lg font-bold" x-text="h">00</div><span class="text-xl font-bold">:</span>
                    <div class="bg-black/20 px-2 py-1 rounded text-lg font-bold" x-text="m">00</div><span class="text-xl font-bold">:</span>
                    <div class="bg-black/20 px-2 py-1 rounded text-lg font-bold" x-text="s">00</div>
                </div>
            </div>
            <a href="{{$baseUrl}}?category=all" class="text-white text-sm font-semibold hover:underline">View All<i class="fas fa-chevron-right text-[10px] ml-1"></i></a>
        </div>
        <div class="bg-primary/5 p-4" x-data="{ scrollLeft() { $refs.flock.scrollBy({left: -200, behavior: 'smooth'}); }, scrollRight() { $refs.flock.scrollBy({left: 200, behavior: 'smooth'}); } }">
            <div class="relative group">
                <button type="button" @click="scrollLeft()" class="absolute -left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-500 hover:text-primary z-10 opacity-0 group-hover:opacity-100 transition shadow-md"><i class="fas fa-chevron-left"></i></button>
                <button type="button" @click="scrollRight()" class="absolute -right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-500 hover:text-primary z-10 opacity-0 group-hover:opacity-100 transition shadow-md"><i class="fas fa-chevron-right"></i></button>
                
                <div x-ref="flock" class="flex gap-4 overflow-x-auto hide-scroll pb-2">
                    @foreach($flashProducts->take(10) as $p)
                    <div class="min-w-[160px] md:min-w-[180px] shrink-0 h-full pb-2">
                        @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Best Deals on Official Appliances --}}
    <div class="mb-12 flex gap-6">
        
        {{-- Sidebar (Filter replica visually) --}}
        @if(!request('category') || request('category') == 'all')
        <div class="w-64 hidden lg:block shrink-0">
            <h3 class="font-normal text-lg mb-4">Filter</h3>
            <div class="border-t border-gray-200 py-3 flex justify-between items-center cursor-pointer hover:text-primary"><span class="text-sm font-medium">Price</span> <i class="fas fa-chevron-down text-xs text-gray-400"></i></div>
            <div class="border-t border-gray-200 py-3 flex justify-between items-center cursor-pointer hover:text-primary"><span class="text-sm font-medium">Brand</span> <i class="fas fa-chevron-down text-xs text-gray-400"></i></div>
            <div class="border-t border-gray-200 py-3 flex justify-between items-center cursor-pointer hover:text-primary"><span class="text-sm font-medium">Display Size (Inches)</span> <i class="fas fa-chevron-down text-xs text-gray-400"></i></div>
            <div class="border-t border-gray-200 py-3 flex justify-between items-center cursor-pointer hover:text-primary"><span class="text-sm font-medium">RAM(GB)</span> <i class="fas fa-chevron-down text-xs text-gray-400"></i></div>
            <div class="border-t border-gray-200 py-3 flex justify-between items-center cursor-pointer hover:text-primary"><span class="text-sm font-medium">Processor</span> <i class="fas fa-chevron-down text-xs text-gray-400"></i></div>
            <div class="border-t border-gray-200 py-3 flex justify-between items-center cursor-pointer hover:text-primary"><span class="text-sm font-medium">5G</span> <i class="fas fa-chevron-down text-xs text-gray-400"></i></div>
        </div>
        @endif

        {{-- Product Grid --}}
        <div class="flex-1">
            <div class="section-title mb-4">
                <div class="flex flex-col">
                    <span class="text-lg font-bold">{{ $client->widgets['products']['title'] ?? 'সেরা পণ্য সমূহ' }}</span>
                    <span class="text-xs font-normal text-gray-500 mt-1">{{ count($products) }} Items in Best Deals</span>
                </div>
                <div class="hidden sm:flex items-center text-sm">
                    <span class="text-gray-500 mr-2">Sort By:</span>
                    <select class="border border-gray-200 rounded px-3 py-1 bg-white focus:outline-none focus:border-primary text-xs">
                        <option>Default</option>
                        <option>Price Low to High</option>
                        <option>Price High to Low</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($products as $p)
                <div class="h-full">
                    @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
                </div>
                @empty
                    <div class="col-span-full py-20 text-center border text-gray-400 text-sm">No products found.</div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
            <div class="mt-10">
                <style>
                    .pg nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
                    .pg nav a, .pg nav span { min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; font-weight: 500; font-size: 13px; background: white; border: 1px solid #e2e8f0; color: #475569;}
                    .pg nav a:hover { border-color: var(--tw-color-primary); color: var(--tw-color-primary); }
                    .pg nav span[aria-current="page"] { background: var(--tw-color-primary); color: white !important; border-color: var(--tw-color-primary); }
                </style>
                <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
