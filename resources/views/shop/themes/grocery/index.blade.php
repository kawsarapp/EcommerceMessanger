@extends('shop.themes.grocery.layout')
@section('title', $client->shop_name . ' | Fresh Supermarket')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Fresh Hero Banner -->
@if($client->banner)
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-6 md:py-8">
    <div class="w-full h-[35vh] md:h-[50vh] rounded-[2rem] overflow-hidden relative shadow-soft group border border-slate-100 bg-emerald-50">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover z-0 group-hover:scale-105 transition-transform duration-1000 ease-in-out">
        
        <!-- Subtle Gradient for reading text -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/70 via-slate-900/30 to-transparent z-10"></div>
        
        <div class="absolute inset-y-0 left-0 z-20 flex flex-col justify-center p-8 md:p-16 w-full md:w-3/5">
            <span class="inline-block bg-secondary text-slate-900 text-xs font-black uppercase tracking-widest px-4 py-1.5 rounded-full mb-4 w-fit shadow-md transform -rotate-2">100% Organic</span>
            <h2 class="text-4xl md:text-6xl text-white font-black tracking-tight mb-6 leading-[1.1] drop-shadow-md">
                {{$client->meta_title ?? 'Fresh Groceries, Delivered Fast.'}}
            </h2>
            <a href="#aisles" class="w-fit bg-primary text-white font-bold text-lg px-8 py-4 rounded-full flex items-center gap-3 hover:bg-emerald-600 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                Shop Fresh Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endif

<div id="aisles" class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-12">
    
    <!-- Category Pills -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6 border-b border-slate-200 pb-6">
        <h3 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <i class="fas fa-store text-primary"></i> Supermarket Aisles
        </h3>
        
        <div class="flex gap-3 overflow-x-auto hide-scroll w-full md:w-auto p-1">
            <a href="?category=all" class="px-5 py-2.5 bg-white rounded-full text-sm font-extrabold transition-all whitespace-nowrap border-2 shadow-sm {{!request('category')||request('category')=='all'?'border-primary text-primary bg-primary/5':'border-slate-100 text-slate-500 hover:border-slate-300'}}">
                All Items
            </a>
            
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="px-5 py-2.5 bg-white rounded-full text-sm font-extrabold transition-all whitespace-nowrap border-2 shadow-sm {{request('category')==$c->slug?'border-primary text-primary bg-primary/5':'border-slate-100 text-slate-500 hover:border-slate-300'}}">
                    {{$c->name}}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Grocery Grid Layout -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6"
        x-data="{ init() { 
            let delay = 0;
            this.$el.querySelectorAll('.grocery-item').forEach(el => {
                setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0) scale(1)'; }, delay);
                delay += 50; 
            });
        } }">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="grocery-item opacity-0 translate-y-8 scale-95 group flex flex-col grocer-card">
                
                <!-- Image Container -->
                <div class="aspect-square bg-slate-50/30 relative p-6 flex items-center justify-center overflow-hidden border-b border-primary/5">
                    @if($p->sale_price)
                        <span class="absolute top-3 left-3 z-10 bg-secondary text-slate-900 text-[10px] font-black uppercase px-3 py-1 rounded-full shadow-md transform -rotate-3">100% Organic</span>
                    @endif
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute top-3 right-3 z-10 bg-red-100 text-red-600 border border-red-200 text-[10px] font-bold px-2.5 py-1 rounded-lg">Out of Stock</div>
                    @endif

                    <!-- Subtle backdrop circle -->
                    <div class="absolute w-2/3 h-2/3 bg-primary/5 rounded-full z-0 group-hover:scale-110 transition-transform duration-500"></div>
                    <img src="{{asset('storage/'.\->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain mix-blend-multiply z-10 transform group-hover:scale-110 transition-transform duration-500">
                </div>
                
                <!-- Info Section -->
                <div class="p-4 md:p-5 flex flex-col flex-1 bg-white relative z-20">
                    <p class="text-xs text-primary font-bold mb-1">{{$p->category->name ?? 'Fresh Produce'}}</p>
                    <h4 class="font-extrabold text-slate-800 leading-snug mb-3 line-clamp-2 group-hover:text-primary transition-colors">{{$p->name}}</h4>
                    
                    <div class="flex justify-between items-end mt-auto pt-2">
                        <div class="flex flex-col">
                            @if($p->sale_price)
                                <del class="text-[11px] text-slate-400 font-bold mb-0.5 mt-2">৳{{$p->regular_price}}</del>
                            @endif
                            <span class="font-black text-xl text-primary tracking-tight">৳{{number_format($p->sale_price ?? $p->regular_price)}} <span class="text-[10px] text-slate-400 font-bold tracking-widest uppercase">/ Pack</span></span>
                        </div>
                        <div class="w-10 h-10 pill-btn bg-slate-50 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white group-hover:shadow-md border border-slate-200 group-hover:border-primary cursor-pointer border-2">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-24 text-center bg-white rounded-[2rem] border border-dashed border-slate-300">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl text-slate-300 shadow-inner">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h3 class="text-xl font-black text-slate-600 mb-2">Aisle is Empty</h3>
                <p class="text-sm font-bold text-slate-400">We couldn't find any products in this category.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-16 flex justify-center">
        <!-- Styling generic tailwind paginator for grocery -->
        <style>
            .pagination-wrapper nav span, .pagination-wrapper nav a { border-radius: 9999px; font-weight: 800; border-color: #e2e8f0; color: #64748b; }
            .pagination-wrapper nav span[aria-current="page"] { border-radius: 9999px; background-color: var(--tw-color-primary); color: white; border-color: var(--tw-color-primary); }
        </style>
        <div class="pagination-wrapper">
            {{$products->links('pagination::tailwind')}}
        </div>
    </div>
</div>

    {{-- Homepage: Category-based product sections (when no filter) --}}
    @if(!request('category') || request('category') == 'all')
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

@endsection
