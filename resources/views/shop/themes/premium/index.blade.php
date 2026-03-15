@extends('shop.themes.premium.layout')
@section('title', $client->shop_name . ' | Premium Store')

@section('content')
@php 
    $baseUrl = $client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Hero Section with Parallax aesthetic -->
@if($client->banner)
<section class="relative w-full h-[65vh] lg:h-[75vh] flex items-center justify-center overflow-hidden">
    <!-- Blurred vibrant backing -->
    <div class="absolute inset-0 bg-primary/20 blur-3xl z-0 transform -translate-y-1/2 scale-150"></div>
    <!-- Main Background -->
    <div class="absolute inset-0 z-10">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover object-center" alt="Cover Banner">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>
    </div>
    
    <!-- Hero Content -->
    <div class="relative z-20 text-center px-4 max-w-4xl mx-auto transform translate-y-10 group-hover:translate-y-0 transition duration-1000">
        <span class="text-white/80 font-semibold tracking-widest uppercase mb-4 block drop-shadow-md text-sm">Welcome to the Collection</span>
        <h1 class="text-5xl md:text-7xl lg:text-8xl text-white font-extrabold tracking-tight mb-8 drop-shadow-xl">
            {{$client->meta_title ?? 'Elevate Your Edge.'}}
        </h1>
        <a href="#shop-section" class="inline-flex items-center justify-center px-8 py-4 text-sm font-bold tracking-widest text-gray-900 uppercase bg-white rounded-full hover:bg-gray-100 hover-lift mt-4">
            Explore Collection <i class="fas fa-arrow-down ml-3"></i>
        </a>
    </div>
</section>
@endif

<div id="shop-section" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
    <!-- Filters / Categories -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-16 gap-8">
        <h3 class="text-3xl md:text-4xl font-extrabold tracking-tight text-gray-900">
            Latest Arrivals <span class="text-primary text-5xl leading-none">.</span>
        </h3>
        
        <div class="flex gap-3 overflow-x-auto hide-scroll pb-2 w-full md:w-auto p-1 bg-gray-100/50 rounded-full border border-gray-200 backdrop-blur-sm">
            <a href="?category=all" class="px-5 py-2.5 rounded-full text-sm font-bold transition-all {{!request('category')||request('category')=='all'?'bg-white shadow-sm text-primary':'text-gray-500 hover:text-gray-900'}}">
                All Items
            </a>
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="px-5 py-2.5 rounded-full text-sm font-bold transition-all whitespace-nowrap {{request('category')==$c->slug?'bg-white shadow-sm text-primary':'text-gray-500 hover:text-gray-900'}}">
                    {{$c->name}}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-8 gap-y-12">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="group block">
                <!-- Image Wrapper -->
                <div class="w-full aspect-[4/5] bg-gray-100 rounded-3xl mb-6 relative overflow-hidden isolate shadow-sm group-hover:shadow-xl transition-all duration-500">
                    
                    @if($p->sale_price)
                        <span class="absolute top-4 left-4 z-20 bg-accent text-white text-xs font-extrabold px-4 py-1.5 rounded-full uppercase tracking-wider shadow-md">
                            Sale
                        </span>
                    @endif
                    
                    <!-- Hover overlay gradient -->
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/50 to-transparent opacity-0 group-hover:opacity-100 z-10 transition-opacity duration-300 pointer-events-none"></div>
                    
                    <img src="{{asset('storage/'.$p->thumbnail)}}" class="w-full h-full object-cover z-0 transform group-hover:scale-110 transition-transform duration-700 ease-in-out">
                    
                    <!-- Quick view button dummy -->
                    <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 translate-y-10 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 z-20">
                        <span class="bg-white text-gray-900 px-6 py-2.5 rounded-full text-sm font-bold shadow-lg">View Details</span>
                    </div>
                </div>
                
                <!-- Info Wrapper -->
                <div class="flex justify-between items-start px-2">
                    <div class="pr-4">
                        <p class="text-xs text-primary font-bold uppercase tracking-widest mb-1">{{$p->category->name ?? 'Premium'}}</p>
                        <h4 class="font-bold text-gray-900 text-lg leading-snug group-hover:text-primary transition-colors line-clamp-2">
                            {{$p->name}}
                        </h4>
                    </div>
                    <div class="text-right whitespace-nowrap">
                        <span class="font-extrabold text-xl text-gray-900 tracking-tight block">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                        @if($p->sale_price)
                            <del class="text-sm text-gray-400 font-medium">৳{{$p->regular_price}}</del>
                        @endif
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-20 text-center">
                <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-500">No products found here yet.</h3>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-20 flex justify-center">
        {{ $products->links('pagination::tailwind') }}
    </div>
</div>

    {{-- Homepage: Category-based product sections (when no filter) --}}
    @if(!request('category') || request('category') == 'all')
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

@endsection
