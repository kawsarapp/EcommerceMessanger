@extends('shop.themes.default.layout')
@section('title', $client->shop_name . ' | Storefront')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Classic Hero -->
@if($client->banner)
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">
    <div class="w-full h-[40vh] md:h-[55vh] rounded-xl overflow-hidden relative shadow-subtle border border-gray-200 bg-gray-100 group">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover z-0 transition-transform duration-1000 group-hover:scale-[1.03]">
        
        <!-- Clean dark overlay for text contrast -->
        <div class="absolute inset-0 bg-gray-900/40 z-10 transition-colors group-hover:bg-gray-900/50"></div>
        
        <div class="absolute inset-y-0 left-0 z-20 flex flex-col justify-center p-8 md:p-16 w-full md:w-2/3">
            <span class="inline-block bg-white text-gray-900 text-xs font-bold uppercase tracking-widest px-4 py-1.5 mb-5 w-fit shadow-sm rounded-sm">Featured</span>
            <h2 class="text-4xl md:text-5xl text-white font-bold tracking-tight mb-6 leading-tight drop-shadow-md">
                {{$client->meta_title ?? 'Explore Our Premium Collection'}}
            </h2>
            <p class="text-gray-100 font-medium mb-8 max-w-lg hidden sm:block text-shadow-sm text-lg">Shop with confidence. Quality products, fast shipping, and reliable customer service.</p>
            
            <a href="#catalog" class="w-fit bg-primary text-white font-semibold text-base px-8 py-3.5 rounded shadow-sm hover:bg-gray-800 transition-colors flex items-center gap-2">
                Browse Collection
            </a>
        </div>
    </div>
</section>
@endif

<div id="catalog" class="max-w-7xl mx-auto px-4 sm:px-6 py-10 md:py-16">
    
    <!-- Clean Filtering -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Our Products</h3>
        
        <div class="flex gap-2 overflow-x-auto hide-scroll w-full md:w-auto p-1 border-b md:border-b-0 border-gray-200">
            <a href="?category=all" class="px-5 py-2 text-sm font-semibold transition-colors whitespace-nowrap {{!request('category')||request('category')=='all'?'text-primary border-b-2 border-primary':'text-gray-500 hover:text-gray-800'}}">
                All Products
            </a>
            
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="px-5 py-2 text-sm font-semibold transition-colors whitespace-nowrap {{request('category')==$c->slug?'text-primary border-b-2 border-primary':'text-gray-500 hover:text-gray-800'}}">
                    {{$c->name}}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Classic Grid Layout -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="group flex flex-col bg-white rounded border border-gray-200 overflow-hidden shadow-sm hover:shadow-hover transition-all duration-300">
                
                <!-- Image Container -->
                <div class="aspect-square bg-gray-50 relative p-4 flex items-center justify-center overflow-hidden border-b border-gray-100">
                    @if($p->sale_price)
                        <span class="absolute top-2 left-2 z-10 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm uppercase tracking-wide">Sale</span>
                    @endif
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-20 flex items-center justify-center">
                            <span class="bg-gray-900 text-white text-xs font-bold px-3 py-1 rounded">Sold Out</span>
                        </div>
                    @endif

                    <img src="{{asset('storage/'.$p->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply z-10 transform group-hover:scale-105 transition-transform duration-300">
                </div>
                
                <!-- Info Section -->
                <div class="p-4 flex flex-col flex-1 bg-white">
                    <p class="text-[11px] text-gray-500 font-medium mb-1 uppercase tracking-wide truncate">{{$p->category->name ?? 'Category'}}</p>
                    <h4 class="font-semibold text-gray-900 leading-snug mb-3 line-clamp-2 group-hover:text-primary transition-colors text-sm">{{$p->name}}</h4>
                    
                    <div class="flex justify-between items-end mt-auto pt-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-base text-gray-900">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                            @if($p->sale_price)
                                <del class="text-xs text-gray-400 font-medium mt-0.5">৳{{number_format($p->regular_price)}}</del>
                            @endif
                        </div>
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-24 text-center bg-gray-50 rounded border border-dashed border-gray-300">
                <i class="fas fa-search text-4xl text-gray-300 mb-4 block"></i>
                <h3 class="text-lg font-bold text-gray-700 mb-1">No products found</h3>
                <p class="text-sm text-gray-500">Try adjusting your category selection.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-16 flex justify-center">
        <!-- Styling generic tailwind paginator for default clean look -->
        <style>
            .pagination-wrapper nav span, .pagination-wrapper nav a { border-radius: 0.25rem; font-weight: 500; font-size: 0.875rem; border-color: #e5e7eb; color: #4b5563; }
            .pagination-wrapper nav span[aria-current="page"] { background-color: var(--tw-color-primary); color: white; border-color: var(--tw-color-primary); }
        </style>
        <div class="pagination-wrapper">
            {{$products->links('pagination::tailwind')}}
        </div>
    </div>
</div>
@endsection