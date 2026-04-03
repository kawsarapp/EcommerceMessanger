@extends('shop.themes.luxury.layout')
@section('title', $client->shop_name . ' | Exquisite Collection')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Dark Luxury Hero -->
@if($client->banner)
<section class="w-full h-[85vh] relative luxury-gradient group overflow-hidden border-b border-white/5">
    <div class="absolute inset-0 opacity-60 z-10 transition-transform duration-[3s] group-hover:scale-105">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover">
    </div>
    <div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/40 to-transparent z-20"></div>
    
    <div class="absolute inset-0 z-30 flex flex-col justify-center items-center text-center p-8">
        <span class="block text-[10px] font-medium uppercase tracking-[0.5em] text-primary mb-6">The New Masterpiece</span>
        <h2 class="text-5xl md:text-8xl font-serif text-white tracking-wide uppercase leading-tight font-light gold-text">{{$client->meta_title ?? 'Elegance Redefined.'}}</h2>
        <a href="#discover" class="mt-12 text-xs font-medium uppercase tracking-[0.3em] text-white border border-white/20 px-10 py-4 hover:bg-white hover:text-black transition duration-500">Discover</a>
    </div>
</section>
@endif

<div id="discover" class="max-w-[100rem] mx-auto px-4 sm:px-12 py-24 md:py-32">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-20 gap-8">
        <h3 class="text-3xl md:text-4xl font-serif text-white uppercase tracking-widest font-light">Fine Creations</h3>
        
        <div class="flex gap-8 overflow-x-auto hide-scroll text-[10px] font-semibold uppercase tracking-[0.2em]">
            <a href="?category=all" class="whitespace-nowrap pb-2 border-b {{!request('category')||request('category')=='all'?'border-primary text-primary':'border-transparent text-gray-500 hover:text-gray-300 transition'}}">Showcase</a>
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="whitespace-nowrap pb-2 border-b {{request('category')==$c->slug?'border-primary text-primary':'border-transparent text-gray-500 hover:text-gray-300 transition'}}">{{$c->name}}</a>
            @endforeach
        </div>
    </div>

    <!-- Luxury Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-12 gap-y-20"
         x-data="{ init() { 
            let delay = 0;
            this.$el.querySelectorAll('.lux-item').forEach(el => {
                setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, delay);
                delay += 150; 
            });
        } }">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="lux-item opacity-0 translate-y-8 group block cursor-pointer text-center transition-all duration-[1.5s] ease-out">
                <!-- Dark Imagry -->
                <div class="aspect-square bg-surface mb-8 relative overflow-hidden transition-all duration-700">
                    <div class="absolute inset-0 bg-dark/10 group-hover:bg-transparent z-10 transition duration-500"></div>
                    <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" class="w-full h-full object-cover object-center transform group-hover:scale-110 transition duration-[2s] ease-out mix-blend-lighten z-0">
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute inset-0 flex items-center justify-center z-20 bg-dark/70 backdrop-blur-sm">
                            <span class="text-[9px] text-white uppercase tracking-[0.4em] border border-white/20 px-6 py-2">Unavailable</span>
                        </div>
                    @endif
                </div>
                
                <!-- Info -->
                <div class="px-4">
                    <p class="text-[9px] text-primary font-light uppercase tracking-[0.3em] mb-3">{{$p->category->name ?? 'Collection'}}</p>
                    <h4 class="font-serif text-xl sm:text-2xl text-white font-light tracking-wide mb-3 transition group-hover:text-gray-300">{{$p->name}}</h4>
                    <div class="text-sm font-light tracking-widest">
                        <span class="text-gray-300">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                        @if($p->sale_price)
                            <del class="text-[10px] text-gray-600 ml-3">৳{{number_format($p->regular_price)}}</del>
                        @endif
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-32 text-center">
                <p class="text-[10px] font-medium text-gray-600 uppercase tracking-[0.3em]">No creations available.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-32 flex justify-center border-t border-white/5 pt-12">
        {{$products->links('pagination::tailwind')}}
    </div>
</div>

    {{-- Homepage: Category-based product sections (when no filter) --}}
    @if(!request('category') || request('category') == 'all')
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

@endsection
