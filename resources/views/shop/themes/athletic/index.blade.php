@extends('shop.themes.athletic.layout')
@section('title', ($client->meta_title ?? 'সব পণ্য') . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Hero Banner -->
@if($client->banner)
<section class="max-w-[100rem] mx-auto px-4 sm:px-8 mt-8 mb-20 relative group overflow-hidden">
    <div class="w-full h-[60vh] md:h-[75vh] relative card-brutal overflow-hidden">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover object-center transform group-hover:scale-110 transition duration-1000 ease-in-out cursor-pointer filter grayscale hover:grayscale-0" alt="{{$client->shop_name}} Banner">
        <div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/40 to-transparent flex flex-col justify-end p-8 md:p-16">
            <h2 class="text-6xl md:text-9xl text-white font-display font-bold uppercase leading-[0.85] tracking-tighter mix-blend-difference drop-shadow-2xl">
                {{$client->meta_title ?? $client->shop_name}}
            </h2>
            @if($client->meta_description)
            <p class="text-white/80 font-sans font-semibold text-lg md:text-2xl mt-4 max-w-xl">{{$client->meta_description}}</p>
            @endif
            <a href="#grid" class="btn-speed mt-8 px-10 py-5 w-fit shadow-primary-lg">
                <span class="text-xl md:text-3xl tracking-widest">পণ্য দেখুন</span>
            </a>
        </div>
    </div>
</section>
@endif

<div id="grid" class="max-w-[100rem] mx-auto px-4 sm:px-8 py-16">
    
    <!-- Section Header & Category Filters -->
    <div class="flex flex-col md:flex-row justify-between md:items-end mb-16 border-b-[6px] border-dark pb-6 gap-8 relative">
        <h3 class="text-5xl md:text-7xl font-display font-bold uppercase tracking-tighter text-dark bg-primary px-4 py-2 text-white w-fit -skew-x-[8deg] ml-2">সর্বশেষ পণ্য</h3>
        
        <div class="flex gap-4 overflow-x-auto hide-scroll pb-2 w-full md:w-auto font-display text-xl uppercase tracking-wider font-bold">
            <a href="?category=all" class="px-6 py-2 border-4 border-dark whitespace-nowrap {{!request('category')||request('category')=='all'?'bg-dark text-primary shadow-primary-sm':'bg-white text-dark hover:bg-gray-100'}} transition-all -skew-x-[6deg]">
                <span class="block skew-x-[6deg]">সব পণ্য</span>
            </a>
            
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="px-6 py-2 border-4 border-dark whitespace-nowrap {{request('category')==$c->slug?'bg-dark text-primary shadow-primary-sm':'bg-white text-dark hover:bg-gray-100'}} transition-all -skew-x-[6deg]">
                    <span class="block skew-x-[6deg]">{{$c->name}}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-8 gap-y-16"
         x-data="{ init() { 
            let delay = 0;
            this.$el.querySelectorAll('.grid-item').forEach(el => {
                setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0) skewX(0deg)'; }, delay);
                delay += 100; 
            });
        } }">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="grid-item opacity-0 translate-y-12 block group font-sans transition-all duration-[600ms] ease-out">
                
                <!-- Product Image -->
                <div class="aspect-[4/5] bg-gray-100 relative overflow-hidden card-brutal mb-6">
                    @if($p->sale_price)
                        <div class="absolute top-4 left-4 z-20 bg-primary text-white text-xl font-display font-bold px-6 py-2 uppercase tracking-widest -skew-x-[12deg] shadow-dark-sm">
                            <span class="block skew-x-[12deg]">SALE</span>
                        </div>
                    @endif
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute inset-0 bg-dark/80 backdrop-blur-sm z-30 flex items-center justify-center">
                            <span class="bg-dark border-4 border-primary text-primary text-2xl font-display font-bold px-8 py-4 uppercase tracking-widest -skew-x-[12deg]">
                                <span class="block skew-x-[12deg]">স্টক শেষ</span>
                            </span>
                        </div>
                    @endif
                    
                    <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" alt="{{$p->name}}" class="w-full h-full object-cover mix-blend-multiply group-hover:scale-125 transition duration-[1.5s] ease-in-out">
                </div>
                
                <!-- Product Info -->
                <div class="flex flex-col px-2">
                    <p class="text-sm font-display font-bold uppercase tracking-[0.2em] text-primary mb-1">{{$p->category->name ?? 'পণ্য'}}</p>
                    <h4 class="font-display font-bold text-3xl uppercase text-dark leading-tight line-clamp-2 group-hover:underline decoration-4 underline-offset-4">{{$p->name}}</h4>
                    
                    <div class="mt-4 flex items-end gap-3">
                        <span class="font-display font-bold text-4xl tracking-tighter text-dark leading-none">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                        @if($p->sale_price)
                            <del class="text-xl text-gray-400 font-sans font-extrabold uppercase line-through decoration-primary decoration-[3px]">৳{{$p->regular_price}}</del>
                        @endif
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-40 border-8 border-dashed border-gray-200 text-center bg-gray-50 flex flex-col items-center justify-center">
                <i class="fas fa-box-open text-8xl text-gray-300 mb-6 block"></i>
                <h3 class="text-5xl font-display font-bold uppercase text-gray-400">কোনো পণ্য পাওয়া যায়নি</h3>
                <p class="text-gray-400 font-sans mt-4">এই ক্যাটাগরিতে এখনো কোনো পণ্য যোগ করা হয়নি।</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-24 border-t-[6px] border-dark pt-12 flex justify-center">
        {{$products->links('pagination::tailwind')}}
    </div>
</div>

{{-- Homepage Offer Banner --}}
@include('shop.partials.homepage-offer-banner', ['client' => $client])

{{-- Homepage Categories --}}
@if(!request('category') || request('category') == 'all')
    @include('shop.partials.homepage-categories', ['client' => $client])
@endif

@endsection
