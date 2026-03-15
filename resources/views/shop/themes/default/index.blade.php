@extends('shop.themes.default.layout')
@section('title', $client->shop_name . ' | Storefront')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : route('shop.show', $client->slug); 
@endphp

<!-- Modern Elegant Hero -->
@if($client->banner)
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">
    <div class="w-full h-[35vh] md:h-[60vh] rounded-[2rem] overflow-hidden relative group">
        <!-- Image with smooth zoom -->
        <img src="{{asset('storage/'.$client->banner)}}" class="absolute inset-0 w-full h-full object-cover origin-center transition-transform duration-[1.5s] ease-out group-hover:scale-105">
        
        <!-- Elegant Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/80 via-slate-900/40 to-transparent"></div>
        
        <div class="absolute inset-y-0 left-0 z-10 flex flex-col justify-center p-8 md:p-16 w-full lg:w-2/3">
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 text-white text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full mb-6 w-fit">
                <i class="fas fa-sparkles text-yellow-300"></i> New Collection
            </div>
            
            <h2 class="text-4xl md:text-6xl text-white font-extrabold tracking-tight mb-6 leading-[1.1]">
                {{$client->meta_title ?? 'Discover Quality & Elegance'}}
            </h2>
            
            <p class="text-slate-200 font-medium text-lg leading-relaxed mb-10 max-w-xl hidden sm:block">
                Upgrade your lifestyle with our premium selection of products, carefully curated for you.
            </p>
            
            <a href="#shop" class="w-fit bg-primary text-white font-bold text-sm uppercase tracking-wide px-8 py-4 rounded-xl shadow-lg hover:shadow-primary/30 premium-transition hover:-translate-y-1 flex items-center gap-3">
                Shop Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endif

<div id="shop" class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-20">

    {{-- Homepage Offer Banner (Timer + Link) --}}
    @include('shop.partials.homepage-offer-banner', ['client' => $client])

    {{-- Category Filter Pills --}}
    <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-6">
        <div>
            <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Our Products</h3>
            <p class="text-slate-500 font-medium text-sm">Find exactly what you are looking for.</p>
        </div>
        
        <div class="flex gap-2 overflow-x-auto hide-scroll w-full md:w-auto pb-2">
            <a href="?category=all" class="px-5 py-2.5 rounded-xl text-sm font-bold premium-transition whitespace-nowrap {{!request('category')||request('category')=='all'?'bg-primary text-white shadow-md':'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'}}">
                All Items
            </a>
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="px-5 py-2.5 rounded-xl text-sm font-bold premium-transition whitespace-nowrap {{request('category')==$c->slug?'bg-primary text-white shadow-md':'bg-white border border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'}}">
                    {{$c->name}}
                </a>
            @endforeach
        </div>
    </div>

    {{-- When a specific category is selected, show flat grid --}}
    @if((request('category') && request('category') != 'all') || request()->filled('search'))
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
            @forelse($products as $p) 
                @include('shop.partials.product-card', ['product' => $p, 'client' => $client])
            @empty
                <div class="col-span-full py-28 flex flex-col items-center justify-center bg-white rounded-3xl border border-dashed border-slate-200">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 mb-4">
                        <i class="fas fa-box-open text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">No products found</h3>
                    <p class="text-sm font-medium text-slate-500">Please try selecting a different category.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-16 flex justify-center">
            <style>
                .pagination-wrapper nav span, .pagination-wrapper nav a { border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; border:none;  color: #64748b; background: white; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
                .pagination-wrapper nav span:hover, .pagination-wrapper nav a:hover { background-color: #f8fafc; color: #0f172a; }
                .pagination-wrapper nav span[aria-current="page"] { background-color: var(--tw-color-primary) !important; color: white !important; box-shadow: 0 4px 6px -1px var(--tw-color-primary) !important; }
            </style>
            <div class="pagination-wrapper">
                {{$products->links('pagination::tailwind')}}
            </div>
        </div>
    @else
        {{-- Homepage: Category-based product sections --}}
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

</div>
@endsection