@extends('shop.themes.fashion.layout')
@section('title', $client->shop_name . ' | The Collection')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Vogue Style Hero Split -->
@if($client->banner)
<section class="w-full h-[70vh] md:h-[85vh] flex flex-col md:flex-row border-b border-gray-100 relative overflow-hidden">
    <div class="w-full md:w-1/2 h-full bg-nude flex items-center justify-center p-8 md:p-16 relative z-10" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
        <div class="max-w-md text-center md:text-left z-10 w-full transition-all duration-1000 ease-[cubic-bezier(0.2,0.8,0.2,1)] delay-300" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'">
            <span class="block text-xs font-medium uppercase tracking-[0.2em] text-gray-500 mb-4 tracking-[0.3em]">Latest Arrival</span>
            <h2 class="text-5xl md:text-7xl lg:text-8xl font-heading font-black leading-[1.1] text-black mb-8 italic">{{$client->meta_title ?? 'New Season.'}}</h2>
            <a href="#collection" class="inline-block border-b bg-transparent border-black text-xs font-bold uppercase tracking-[0.2em] pb-1.5 hover:text-gray-500 hover:border-gray-500 transition-all">Shop Now</a>
        </div>
    </div>
    <div class="w-full md:w-1/2 h-full absolute md:relative inset-0 md:inset-auto block group overflow-hidden bg-gray-100 z-0">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover object-center transform group-hover:scale-105 transition duration-[3s] ease-out">
        <div class="absolute inset-0 bg-white/30 md:hidden pointer-events-none"></div>
    </div>
</section>
@endif

<div id="collection" class="max-w-[100rem] mx-auto px-4 sm:px-8 py-24">
    
    <div class="text-center mb-16">
        <h3 class="text-4xl md:text-5xl font-heading font-black mb-8">The Collection</h3>
        
        <div class="flex justify-center flex-wrap gap-4 overflow-x-auto hide-scroll px-4">
            <a href="?category=all" class="text-xs font-medium uppercase tracking-[0.2em] px-6 py-2 rounded-full border border-gray-200 {{!request('category')||request('category')=='all'?'bg-primary text-white':'text-gray-500 hover:bg-gray-50 transition'}}">Show All</a>
            
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="text-xs font-medium uppercase tracking-[0.2em] px-6 py-2 rounded-full border border-gray-200 {{request('category')==$c->slug?'bg-primary text-white':'text-gray-500 hover:bg-gray-50 transition'}}">{{$c->name}}</a>
            @endforeach
        </div>
    </div>

    <!-- Fashion Product Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-x-8 md:gap-y-16"
        x-data="{ init() { 
            let delay = 0;
            this.$el.querySelectorAll('.vogue-item').forEach(el => {
                setTimeout(() => { el.classList.remove('opacity-0', 'translate-y-12'); el.classList.add('vogue-fade-in'); }, delay);
                delay += 100; 
            });
        } }">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="vogue-item opacity-0 group block cursor-pointer">
                <!-- Fashion Tall Image -->
                <div class="aspect-[2/3] md:aspect-[3/4] bg-[#f9f9f9] relative overflow-hidden mb-4">
                    @if($p->sale_price)
                        <span class="absolute top-4 right-4 z-10 text-[10px] font-bold text-red-500 uppercase tracking-widest">Sale</span>
                    @endif
                    
                    <img src="{{asset('storage/'.\->thumbnail)}}" loading="lazy" class="w-full h-full object-cover object-top group-hover:scale-105 transition duration-[1.5s] ease-in-out">
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute inset-x-0 bottom-0 bg-white/90 py-2 text-center border-t border-gray-200">
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em]">Out of Stock</span>
                        </div>
                    @else
                        <!-- Quick Add overlay -->
                        <div class="absolute inset-x-0 bottom-0 bg-white/95 py-4 text-center transform translate-y-full group-hover:translate-y-0 transition duration-300 border-t border-gray-100 backdrop-blur-sm hidden md:block">
                            <span class="text-xs text-black font-semibold tracking-widest uppercase">View Item</span>
                        </div>
                    @endif
                </div>
                
                <!-- Fashion Info -->
                <div class="text-center px-2 mt-6">
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-[0.2em] mb-2">{{$p->category->name ?? 'Premium'}}</p>
                    <h4 class="font-heading font-medium text-lg text-gray-900 leading-snug truncate group-hover:italic transition-all">{{$p->name}}</h4>
                    <div class="mt-3 text-sm font-semibold tracking-widest">
                        <span class="text-black">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                        @if($p->sale_price)
                            <del class="text-gray-400 ml-3 text-xs">৳{{$p->regular_price}}</del>
                        @endif
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-32 text-center flex flex-col items-center">
                <i class="fas fa-gem text-4xl text-gray-200 mb-4"></i>
                <p class="text-sm font-medium text-gray-400 uppercase tracking-widest">Collection Empty.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-20 flex justify-center border-t border-gray-100 pt-8">
        {{$products->links('pagination::tailwind')}}
    </div>
</div>

    {{-- Homepage: Category-based product sections (when no filter) --}}
    @if(!request('category') || request('category') == 'all')
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

@endsection
