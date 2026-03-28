@extends('shop.themes.bdpro.layout')
@section('title', $client->shop_name . ' | শীর্ষস্থানীয় ইলেকট্রনিক্স শপ')

@section('content')

@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    /* Custom CSS for BDPro Section Headers */
    .section-title-lines {
        display: flex;
        align-items: center;
        text-align: center;
        color: #000;
        font-weight: 800;
        font-size: 24px;
        margin-bottom: 24px;
    }
    .section-title-lines::before,
    .section-title-lines::after {
        content: '';
        flex: 1;
        border-bottom: 2px solid #1a3673;
        margin: 0 20px;
        opacity: 0.2;
    }
    
    /* Hide scrollbar for carousel but allow scrolling */
    .horizontal-carousel {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        gap: 16px;
        padding-bottom: 12px;
    }
    .horizontal-carousel::-webkit-scrollbar { height: 6px; }
    .horizontal-carousel::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .horizontal-carousel::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .horizontal-carousel::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    .hero-card {
        border-radius: 12px;
        overflow: hidden;
        position: relative;
        height: 480px;
        flex: 1;
        background-size: cover;
        background-position: center;
        transition: flex 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    @media (max-width: 768px) { .hero-card { height: 280px; } }
    .hero-card::after {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0) 100%);
    }
    .hero-card-content {
        position: absolute; bottom: 20px; left: 20px; right: 20px; z-index: 10;
        color: white; transform: translateY(10px); transition: transform 0.3s;
    }
    .hero-card:hover .hero-card-content { transform: translateY(0); }
</style>

<div class="max-w-[1400px] mx-auto px-4 mt-6">

    {{-- 5-Column Hero Banner Gallery (BDShop Style) --}}
    @if(!request('category') || request('category') == 'all')
    <div class="flex flex-col md:flex-row gap-4 mb-16">
        
        <div class="hero-card shadow-sm" style="background-image: url('https://images.unsplash.com/photo-1593640495253-23196b27a87f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80');">
            <div class="absolute top-6 w-full text-center z-10 px-4"><img src="https://ajkerdeal.com/images/category/flash.png" class="h-16 mx-auto mb-2 opacity-0"><h2 class="text-white font-black text-3xl leading-tight drop-shadow-lg">সুরক্ষিত<br>ওয়েবসাইট</h2></div>
            <div class="hero-card-content">
                <h3 class="font-bold text-lg">Electronics Hub</h3>
                <p class="text-xs text-white/80 mb-2">Latest gadgets</p>
                <div class="text-[10px] uppercase font-bold tracking-wider flex items-center gap-1 group-hover:text-yellow-400">Shop Now <i class="fas fa-arrow-right"></i></div>
            </div>
        </div>

        <div class="hero-card shadow-sm" style="background-image: url('https://images.unsplash.com/photo-1558002038-1055907df827?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80');">
            <div class="absolute top-6 w-full text-center z-10 px-4"><h2 class="text-white font-black text-3xl leading-tight drop-shadow-lg text-yellow-300">বিশ্বস্ত<br>কাস্টমার সাপোর্ট</h2></div>
            <div class="hero-card-content">
                <h3 class="font-bold text-lg">Smart Living</h3>
                <p class="text-xs text-white/80 mb-2">Smart home solutions</p>
                <div class="text-[10px] uppercase font-bold tracking-wider flex items-center gap-1 group-hover:text-yellow-400">Explore <i class="fas fa-arrow-right"></i></div>
            </div>
        </div>

        <div class="hero-card shadow-sm" style="background-image: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80');">
            <div class="absolute top-6 w-full text-center z-10 px-4"><h2 class="text-white font-black text-3xl leading-tight drop-shadow-lg">১০০%<br>অথেন্টিক<br>প্রোডাক্ট</h2></div>
            <div class="hero-card-content">
                <h3 class="font-bold text-lg">Gaming Zone</h3>
                <p class="text-xs text-white/80 mb-2">Gaming gear</p>
                <div class="text-[10px] uppercase font-bold tracking-wider flex items-center gap-1 group-hover:text-yellow-400">Discover <i class="fas fa-arrow-right"></i></div>
            </div>
        </div>

        <div class="hero-card shadow-sm" style="background-image: url('https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80');">
            <div class="absolute top-6 w-full text-center z-10 px-4"><h2 class="text-white font-black text-4xl leading-tight drop-shadow-lg text-[#2A82C9]">বাংলাদেশের<br>সেরা অফার</h2></div>
            <div class="hero-card-content">
                <h3 class="font-bold text-lg">Audio Paradise</h3>
                <p class="text-xs text-white/80 mb-2">Sound systems</p>
                <div class="text-[10px] uppercase font-bold tracking-wider flex items-center gap-1 group-hover:text-yellow-400">Listen <i class="fas fa-arrow-right"></i></div>
            </div>
        </div>

        <div class="hero-card shadow-sm" style="background-image: url('https://images.unsplash.com/photo-1628155930542-3c7a64e2c848?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80');">
            <div class="absolute top-6 w-full text-center z-10 px-4"><h2 class="text-white font-black text-3xl leading-tight drop-shadow-lg text-emerald-300">প্রত্যন্ত অঞ্চলে<br>দ্রুত ডেলিভারি</h2></div>
            <div class="hero-card-content">
                <h3 class="font-bold text-lg">Mobile World</h3>
                <p class="text-xs text-white/80 mb-2">Latest smartphones</p>
                <div class="text-[10px] uppercase font-bold tracking-wider flex items-center gap-1 group-hover:text-yellow-400">Browse <i class="fas fa-arrow-right"></i></div>
            </div>
        </div>
        
    </div>
    @endif

    {{-- OUR COLLECTIONS SECTION --}}
    @if(!request('category') || request('category') == 'all')
    <div class="mb-16">
        <h2 class="section-title-lines">Our Collections</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {{-- Most Sold Items --}}
            <div class="bg-white rounded p-4 border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2">
                    <h3 class="font-bold text-dark flex items-center gap-2"><i class="fas fa-fire text-red-500"></i> Most Sold Items</h3>
                    <a href="{{$baseUrl}}?category=all" class="text-xs text-blue-500 hover:text-blue-700 font-semibold">See More &rarr;</a>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($products->take(4) as $p)
                        <div class="border border-gray-100 rounded p-3 hover:border-gray-300 transition group bg-white relative">
                            <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded z-10">HOT</span>
                            @if($p->sale_price)<span class="absolute top-2 right-2 bg-red-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded z-10">-{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}%</span>@endif
                            
                            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block bg-gray-50 mb-3 rounded overflow-hidden aspect-square flex items-center justify-center">
                                <img src="{{asset('storage/'.$p->thumbnail)}}" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                            </a>
                            <a href="{{$baseUrl.'/product/'.$p->slug}}">
                                <h4 class="text-xs font-semibold text-gray-700 line-clamp-2 h-8 mb-2 group-hover:text-bdblue transition">{{$p->name}}</h4>
                                <div class="font-bold text-dark text-sm">৳{{number_format($p->sale_price ?? $p->regular_price)}}</div>
                                @if($p->sale_price)<del class="text-[10px] text-gray-400">৳{{number_format($p->regular_price)}}</del>@endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Most Discount --}}
            <div class="bg-white rounded p-4 border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2">
                    <h3 class="font-bold text-dark flex items-center gap-2"><i class="fas fa-tags text-emerald-500"></i> Most Discount</h3>
                    <a href="{{$baseUrl}}?category=all" class="text-xs text-blue-500 hover:text-blue-700 font-semibold">See More &rarr;</a>
                </div>
                <!-- Simulating a different array for demo, using same products array but reversed -->
                <div class="grid grid-cols-2 gap-3">
                    @foreach($products->reverse()->take(4) as $p)
                        <div class="border border-gray-100 rounded p-3 hover:border-gray-300 transition group bg-white relative">
                            @if($p->sale_price)<span class="absolute top-2 left-2 bg-emerald-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded z-10">-{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}% OFF</span>@endif
                            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block bg-gray-50 mb-3 rounded overflow-hidden aspect-square flex items-center justify-center">
                                <img src="{{asset('storage/'.$p->thumbnail)}}" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                            </a>
                            <a href="{{$baseUrl.'/product/'.$p->slug}}">
                                <h4 class="text-xs font-semibold text-gray-700 line-clamp-2 h-8 mb-2 group-hover:text-bdblue transition">{{$p->name}}</h4>
                                <div class="font-bold text-dark text-sm">৳{{number_format($p->sale_price ?? $p->regular_price)}}</div>
                                @if($p->sale_price)<del class="text-[10px] text-gray-400">৳{{number_format($p->regular_price)}}</del>@endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- New Coming --}}
            <div class="bg-white rounded p-4 border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2">
                    <h3 class="font-bold text-dark flex items-center gap-2"><i class="fas fa-star text-yellow-400"></i> New Coming</h3>
                    <a href="{{$baseUrl}}?category=all" class="text-xs text-blue-500 hover:text-blue-700 font-semibold">See More &rarr;</a>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($products->shuffle()->take(4) as $p)
                        <div class="border border-gray-100 rounded p-3 hover:border-gray-300 transition group bg-white relative">
                            <span class="absolute top-2 left-2 bg-emerald-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded z-10">NEW</span>
                            @if($p->sale_price)<span class="absolute top-2 right-2 bg-red-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded z-10">-{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}%</span>@endif
                            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block bg-gray-50 mb-3 rounded overflow-hidden aspect-square flex items-center justify-center">
                                <img src="{{asset('storage/'.$p->thumbnail)}}" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                            </a>
                            <a href="{{$baseUrl.'/product/'.$p->slug}}">
                                <h4 class="text-xs font-semibold text-gray-700 line-clamp-2 h-8 mb-2 group-hover:text-bdblue transition">{{$p->name}}</h4>
                                <div class="font-bold text-dark text-sm">৳{{number_format($p->sale_price ?? $p->regular_price)}}</div>
                                @if($p->sale_price)<del class="text-[10px] text-gray-400">৳{{number_format($p->regular_price)}}</del>@endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
    @endif

    {{-- Main Products Grid Feed --}}
    <div class="mb-12">
        @if(request('category') && request('category') != 'all')
            <h2 class="text-xl font-bold border-l-4 border-bdblue pl-3 mb-6">{{ $categories->where('slug', request('category'))->first()?->name ?? 'Category Products' }}</h2>
        @else
            <h2 class="section-title-lines">Specially for You</h2>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4">
            @forelse($products as $p)
                <div class="border border-gray-100 rounded-lg p-3 hover:border-bdblue hover:shadow-md transition group bg-white relative flex flex-col h-full">
                    @if($p->sale_price)
                        <span class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded z-10 flex items-center gap-1">
                            <i class="fas fa-fire-alt text-[8px]"></i>{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}%
                        </span>
                    @endif
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <span class="absolute top-2 right-2 bg-gray-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full z-10 bg-opacity-90 backdrop-blur-sm">Sold Out</span>
                    @else
                        @php $randLeft = rand(1, 10); @endphp
                        @if($randLeft < 4)
                        <span class="absolute bottom-16 left-2 bg-red-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full z-10 border border-white">🔥 {{$randLeft}} left</span>
                        @endif
                    @endif
                    
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block bg-gray-50 mb-3 rounded-md overflow-hidden aspect-square flex items-center justify-center relative">
                        <img src="{{asset('storage/'.$p->thumbnail)}}" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                    </a>
                    
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="flex flex-col flex-1">
                        <h4 class="text-[13px] font-medium text-gray-700 line-clamp-2 mb-2 group-hover:text-bdblue transition leading-snug flex-1">{{$p->name}}</h4>
                        <div class="font-bold text-bdblue text-base mt-auto">৳{{number_format($p->sale_price ?? $p->regular_price)}}</div>
                        @if($p->sale_price)<del class="text-[11px] text-gray-400 font-medium">৳{{number_format($p->regular_price)}}</del>@endif
                    </a>
                </div>
            @empty
                <div class="col-span-full py-20 text-center border border-dashed border-gray-200 rounded-xl bg-white">
                    <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-bold text-gray-700">No products found</h3>
                    <p class="text-gray-500">Please try a different category or clear filters.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="mt-12">
            <style>
                .pg nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
                .pg nav a, .pg nav span { min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-weight: 600; font-size: 13px; background: white; color: #64748b; border: 1px solid #e2e8f0; transition: all 0.2s; }
                .pg nav a:hover { border-color: var(--primary); color: var(--primary); }
                .pg nav span[aria-current="page"] { background: var(--primary); color: white !important; border-color: var(--primary); }
            </style>
            <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </div>

</div>

@endsection
