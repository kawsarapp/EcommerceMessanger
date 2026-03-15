@extends('shop.themes.modern.layout')
@section('title', $client->shop_name . ' | Modern Essentials')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Giant Hero Minimalist -->
@if($client->banner)
<section class="w-full h-[65vh] md:h-[80vh] relative bg-gray-100 group overflow-hidden">
    <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover object-center transform group-hover:scale-105 transition duration-1000 ease-in-out cursor-pointer">
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent flex flex-col justify-end p-8 md:p-16">
        <div class="max-w-[90rem] w-full mx-auto">
            <h2 class="text-5xl md:text-8xl text-white font-black tracking-tighter uppercase leading-[0.9] drop-shadow-sm">{{$client->meta_title ?? 'New Era.'}}</h2>
        </div>
    </div>
</section>
@endif

<div class="max-w-[90rem] mx-auto px-6 py-24">
    
    <div class="flex flex-col lg:flex-row justify-between lg:items-end mb-16 border-b border-gray-200 pb-8 gap-8">
        <div>
            <span class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Curated Collection</span>
            <h3 class="text-4xl md:text-5xl font-black tracking-tighter uppercase">Essentials.</h3>
        </div>
        
        <div class="flex gap-6 overflow-x-auto hide-scroll w-full lg:w-auto">
            <a href="?category=all" class="text-xs font-black uppercase tracking-[0.15em] {{!request('category')||request('category')=='all'?'text-black':'text-gray-400 hover:text-gray-900 transition'}}">All Objects</a>
            
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="text-xs font-black uppercase tracking-[0.15em] {{request('category')==$c->slug?'text-black':'text-gray-400 hover:text-gray-900 transition'}}">{{$c->name}}</a>
            @endforeach
        </div>
    </div>

    <!-- Product Super Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-8 gap-y-16">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="group block cursor-pointer">
                <!-- Object Image -->
                <div class="aspect-[4/5] bg-gray-100 mb-6 relative overflow-hidden modern-hover rounded-sm">
                    @if($p->sale_price)
                        <span class="absolute top-4 left-4 z-10 bg-black text-white text-[10px] font-black px-3 py-1.5 uppercase tracking-[0.2em]">Sale</span>
                    @endif
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute inset-0 bg-white/60 backdrop-blur-sm z-20 flex items-center justify-center">
                            <span class="bg-black text-white text-[10px] font-black px-4 py-2 uppercase tracking-[0.2em]">Sold Out</span>
                        </div>
                    @endif
                    
                    <img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-cover mix-blend-multiply group-hover:scale-110 transition duration-[1.5s] ease-out">
                </div>
                
                <!-- Object Info -->
                <div class="flex justify-between items-start">
                    <div class="pr-2">
                        <h4 class="font-bold text-lg text-gray-900 leading-snug group-hover:underline decoration-2 underline-offset-4">{{$p->name}}</h4>
                        <p class="text-[10px] text-gray-400 mt-2 font-black uppercase tracking-[0.15em]">{{$p->category->name ?? 'Modern Object'}}</p>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <span class="font-black text-lg text-gray-900">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                        @if($p->sale_price)
                            <del class="text-[11px] text-gray-400 font-bold tracking-widest">৳{{$p->regular_price}}</del>
                        @endif
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-32 text-center">
                <p class="text-sm font-black text-gray-300 uppercase tracking-widest">No Products Found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-24 border-t border-gray-200 pt-8 flex justify-center">
        {{$products->links('pagination::tailwind')}}
    </div>
</div>

    {{-- Homepage: Category-based product sections (when no filter) --}}
    @if(!request('category') || request('category') == 'all')
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

@endsection