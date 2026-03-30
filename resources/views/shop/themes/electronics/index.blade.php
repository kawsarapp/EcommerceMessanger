@extends('shop.themes.electronics.layout')
@section('title', $client->shop_name . ' | Tech & Gadgets')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Dark Tech Hero -->
@if($client->banner)
<section class="max-w-[100rem] mx-auto px-4 md:px-8 pt-8 pb-12">
    <div class="w-full h-[50vh] md:h-[60vh] rounded-2xl tech-border overflow-hidden relative group">
        <!-- Glow backing -->
        <div class="absolute inset-0 bg-primary/20 mix-blend-overlay z-10 transition duration-700 group-hover:bg-primary/0"></div>
        
        <img src="{{asset('storage/'.$client- loading="lazy">banner)}}" class="w-full h-full object-cover z-0">
        
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-dark via-dark/80 to-transparent z-20"></div>
        
        <div class="absolute inset-y-0 left-0 z-30 flex flex-col justify-center p-8 md:p-16 w-full md:w-2/3" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
            <div class="inline-flex items-center gap-2 bg-dark/80 backdrop-blur-md neon-border px-4 py-2 mb-6 w-fit transition-all duration-700" :class="loaded ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-10'">
                <span class="w-2.5 h-2.5 bg-primary animate-pulse shadow-[0_0_8px_var(--tw-color-primary)]"></span>
                <span class="text-[11px] font-bold text-primary uppercase tracking-[0.3em] font-mono neon-text">System Online</span>
            </div>
            
            <h2 class="text-4xl md:text-6xl text-white font-black tracking-tight mb-6 leading-none transition-all duration-700 delay-100" :class="loaded ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-10'">
                {{$client->meta_title ?? 'Upgrade Your Rig.'}}
            </h2>
            <p class="text-gray-400 font-mono mb-8 max-w-md hidden sm:block transition-all duration-700 delay-200" :class="loaded ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-10'">> INITIALIZING... DISCOVER CUTTING-EDGE HARDWARE ENGINEERED FOR MAXIMUM PERFORMANCE.</p>
            
            <a href="#hardware" class="w-fit bg-primary/10 border border-primary text-primary font-mono font-bold px-8 py-3.5 flex items-center gap-3 hover:bg-primary hover:text-dark transition-all duration-300 shadow-[0_0_15px_rgba(14,165,233,0.2)] hover:shadow-[0_0_25px_rgba(14,165,233,0.6)] delay-300" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'">
                [ BROWSE COMPONENTS ] <i class="fas fa-terminal"></i>
            </a>
        </div>
    </div>
</section>
@endif

<div id="hardware" class="max-w-[100rem] mx-auto px-4 md:px-8 py-12">
    
    <!-- Filtering Header Tech Style -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
        <div class="flex items-center gap-4">
            <div class="w-1.5 h-8 bg-primary rounded-full"></div>
            <h3 class="text-2xl md:text-3xl font-black text-white tracking-tight">Hardware Catalog</h3>
        </div>
        
        <div class="flex gap-3 overflow-x-auto hide-scroll w-full lg:w-auto bg-dark tech-border p-1.5 rounded-xl">
            <a href="?category=all" class="px-5 py-2 rounded-lg text-xs font-bold transition-colors whitespace-nowrap {{!request('category')||request('category')=='all'?'bg-panel text-white shadow-sm':'text-gray-500 hover:text-gray-300'}}">All Tech</a>
            
            @foreach($categories as $c)
                <a href="?category={{$c->slug}}" class="px-5 py-2 rounded-lg text-xs font-bold transition-colors whitespace-nowrap {{request('category')==$c->slug?'bg-panel text-white shadow-sm':'text-gray-500 hover:text-gray-300'}}">
                    {{$c->name}}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Tech Grid Layout -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6"
        x-data="{ init() { 
            let delay = 0;
            this.$el.querySelectorAll('.cyber-item').forEach(el => {
                setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'scale(1)'; }, delay);
                delay += 75; 
            });
        } }">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="cyber-item opacity-0 scale-95 group flex flex-col hud-panel overflow-hidden transition-all duration-300 hover:neon-border hover:shadow-[0_0_20px_rgba(14,165,233,0.3)]">
                <!-- Image Container -->
                <div class="aspect-square bg-[#0a0f18] relative p-4 flex items-center justify-center border-b border-primary/20">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMGg0MHY0MEgweiIgZmlsbD0ibm9uZSIvPjxwYXRoIGQ9Ik0wIDM5LjVoNDBWNDBIMHptMzkuNSAwdjQwSDQwdi00MHoiIGZpbGw9InJnYmEoMjU1LCAyNTUsIDI1NSwgMC4wMikiLz48L3N2Zz4=')] opacity-50 z-0 pointer-events-none"></div>
                    @if($p->sale_price)
                        <span class="absolute top-3 left-3 z-10 bg-primary/20 border border-primary text-primary text-[10px] font-mono font-black px-2 py-1 tracking-wider uppercase shadow-[0_0_10px_var(--tw-color-primary)]">SYS_DISCOUNT</span>
                    @endif
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute top-3 right-3 z-10 bg-dark tech-border text-gray-400 text-[10px] font-bold px-2 py-1 rounded">OOS</div>
                    @endif

                    <img src="{{asset('storage/'.\->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain mix-blend-multiply group-hover:scale-105 transition duration-500">
                </div>
                
                <!-- Info Section -->
                <div class="p-4 md:p-5 flex flex-col flex-1">
                    <p class="text-[10px] text-primary font-mono font-bold tracking-widest uppercase mb-2">{{$p->category->name ?? 'Component'}}</p>
                    <h4 class="font-bold text-sm text-gray-200 leading-snug mb-3 line-clamp-2 group-hover:text-primary transition-colors flex-1">{{$p->name}}</h4>
                    
                    <div class="flex justify-between items-end border-t border-gray-800 pt-3 mt-auto">
                        <div class="flex flex-col">
                            @if($p->sale_price)
                                <del class="text-[10px] text-gray-500 font-mono font-medium line-through decoration-red-500">৳{{$p->regular_price}}</del>
                            @endif
                            <span class="font-black text-lg text-primary font-mono tracking-tight neon-text">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-dark tech-border flex items-center justify-center text-gray-400 group-hover:bg-primary group-hover:text-white transition group-hover:border-primary">
                            <i class="fas fa-shopping-cart text-xs"></i>
                        </div>
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-24 text-center bg-panel rounded-xl tech-border border-dashed">
                <i class="fas fa-microchip text-4xl text-gray-700 mb-4 block"></i>
                <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Database Empty. No models found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-16 flex justify-center">
        <!-- Pagination UI auto handled by tailwind paginator but visually dark themed via css or generic output -->
        <style>
            .pagination-wrapper nav span, .pagination-wrapper nav a { background-color: #111827; border-color: #1f2937; color: #9ca3af; }
            .pagination-wrapper nav span[aria-current="page"] { background-color: var(--tw-color-primary); color: white; border-color: var(--tw-color-primary); }
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
