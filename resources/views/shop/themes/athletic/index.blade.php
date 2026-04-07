@extends('shop.themes.athletic.layout')
@section('title', ($client->meta_title ?? 'সব পণ্য') . ' | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Hero Banner -->
@if($client->widget('hero_banner'))
    <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" :categories="$categories ?? null" />
@endif

<section id="products" class="max-w-[100rem] mx-auto px-4 sm:px-8 py-16">
    
    <!-- Section Header & Category Filters -->
    <div class="flex flex-col md:flex-row justify-between md:items-end mb-16 border-b-[6px] border-dark pb-6 gap-8 relative">
        <h3 class="text-5xl md:text-7xl font-display font-bold uppercase tracking-tighter text-dark bg-primary px-4 py-2 text-white w-fit -skew-x-[8deg] ml-2">{{ $client->widgets['products_section']['title'] ?? 'সর্বশেষ পণ্য' }}</h3>
        
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
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5 mt-4"
         x-data="{ init() { 
            let delay = 0;
            this.$el.querySelectorAll('.mat-fade-item').forEach(el => {
                setTimeout(() => { el.classList.remove('opacity-0', 'translate-y-8'); }, delay);
                delay += 50; 
            });
        } }">
        @forelse($products as $p) 
            <div class="mat-fade-item opacity-0 translate-y-8 transition-all duration-500 ease-out will-change-transform">
                <div class="mat-card h-full rounded-2xl overflow-hidden hover:mat-elevated flex flex-col">
                    @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 flex flex-col items-center justify-center mat-card mat-elevated">
                <i class="fas fa-box-open text-4xl text-slate-300 mb-4"></i>
                <h3 class="text-lg font-bold text-slate-800 mb-1">কোনো পণ্য পাওয়া যায়নি</h3>
                <p class="text-sm text-slate-500">অন্য ক্যাটাগরি দেখুন।</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-12 flex justify-center">
        <div class="bd-pagination">{{$products->links('pagination::tailwind')}}</div>
    </div>
</section>

{{-- Homepage Offer Banner --}}
@include('shop.partials.homepage-offer-banner', ['client' => $client])

{{-- Homepage Categories --}}
@if(!request('category') || request('category') == 'all')
    @include('shop.partials.homepage-categories', ['client' => $client])
@endif

@endsection
