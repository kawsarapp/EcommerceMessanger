@extends('shop.themes.kids.layout')
@section('title', $client->shop_name . ' | Kids Corner')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

<!-- Hero Banner (Bubbly and bright) -->
@if($client->banner)
<section class="max-w-7xl mx-auto px-4 sm:px-6 md:px-10 py-8 md:py-12">
    <div class="w-full h-[45vh] md:h-[65vh] rounded-[3rem] overflow-hidden relative shadow-cloud border-4 border-white bg-funblue/10 group">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover z-0 transition-transform duration-700 group-hover:scale-105 filter group-hover:brightness-110">
        
        <!-- Gradient overlay to pop text -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/60 via-slate-900/20 to-transparent z-10 pointer-events-none"></div>
        
        <div class="absolute inset-0 z-20 flex flex-col justify-center p-8 md:p-20 md:w-2/3 items-center md:items-start text-center md:text-left">
            <div class="bg-funyellow text-slate-900 text-sm font-black uppercase tracking-widest px-6 py-2 rounded-full mb-6 shadow-md transform -rotate-3 border-2 border-white bouncy cursor-pointer">
                <i class="fas fa-magic mr-1"></i> Endless Fun!
            </div>
            
            <h2 class="text-4xl md:text-6xl text-white font-heading tracking-wide mb-8 leading-tight drop-shadow-lg" style="text-shadow: 2px 4px 0px rgba(0,0,0,0.2);">
                {{$client->meta_title ?? 'Ready for Adventure?'}}
            </h2>
            
            <a href="#playzone" class="w-fit bg-primary text-white text-xl font-bold px-10 py-5 rounded-full flex items-center gap-3 hover:bg-pink-600 shadow-float bouncy border-4 border-white/20 hover:border-white transition-all">
                Let's Play <i class="fas fa-paper-plane text-2xl transform group-hover:translate-x-2 transition-transform"></i>
            </a>
        </div>
    </div>
</section>
@endif

<div id="playzone" class="max-w-7xl mx-auto px-4 sm:px-6 md:px-10 py-10 md:py-16">
    
    <!-- Category Bubbles -->
    <div class="flex flex-col items-center mb-16 gap-8">
        <h3 class="text-3xl md:text-4xl font-heading text-slate-800 text-center relative">
            <span class="relative z-10">Explore The Toybox</span>
            <span class="absolute bottom-1 left-0 w-full h-4 bg-primary/20 -z-0 rounded-full transform rotate-1"></span>
        </h3>
        
        <div class="flex gap-4 overflow-x-auto hide-scroll w-full p-4  justify-start md:justify-center">
            <a href="?category=all" class="bouncy px-8 py-4 bg-white rounded-[2rem] text-lg font-bold transition-all whitespace-nowrap shadow-sm border-2 border-slate-100 {{!request('category')||request('category')=='all'?'hidden':'opacity-100 hover:border-primary hover:text-primary text-slate-500'}}">
                Show Everything
            </a>
            @if(!request('category') || request('category')=='all')
                <div class="px-8 py-4 bg-primary text-white rounded-[2rem] text-lg font-bold whitespace-nowrap shadow-md border-4 border-white transform scale-105">
                     <i class="fas fa-star mr-1"></i> All Toys
                </div>
            @endif
            
            @foreach($categories as $c)
                @if(request('category')==$c->slug)
                    <div class="px-8 py-4 bg-funblue text-white rounded-[2rem] text-lg font-bold whitespace-nowrap shadow-md border-4 border-white transform scale-105">
                         <i class="fas fa-star text-funyellow mr-1"></i> {{$c->name}}
                    </div>
                @else
                    <a href="?category={{$c->slug}}" class="bouncy px-8 py-4 bg-white rounded-[2rem] text-lg font-bold transition-all whitespace-nowrap shadow-sm border-2 border-slate-100 text-slate-500 hover:border-funblue hover:text-funblue">
                        {{$c->name}}
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Fun Product Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 md:gap-10"
        x-data="{ init() { 
            let delay = 0;
            this.$el.querySelectorAll('.toy-item').forEach(el => {
                setTimeout(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0) scale(1)'; }, delay);
                delay += 75; 
            });
        } }">
        @forelse($products as $p) 
            <a href="{{$baseUrl.'/product/'.$p->slug}}" class="toy-item opacity-0 translate-y-12 scale-90 group flex flex-col bg-white rounded-[2.5rem] border-4 border-transparent hover:border-funblue/30 overflow-hidden shadow-cloud hover:shadow-float transition-all duration-500 relative">
                
                @if($p->sale_price)
                    <div class="absolute top-4 left-4 z-20 bg-funyellow text-slate-800 text-sm font-black px-4 py-2 rounded-full shadow-md transform -rotate-12 border-2 border-white bouncy">
                        SALE!
                    </div>
                @endif

                <!-- Image Area -->
                <div class="aspect-[4/5] bg-slate-50 relative p-6 flex items-center justify-center overflow-hidden w-full">
                    <!-- blob background -->
                    <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMiIgZmlsbD0iIzBlYTVlOSIvPjwvc3ZnPg==')] pointer-events-none"></div>
                    
                    @if(isset($p->stock_status) && $p->stock_status == 'out_of_stock')
                        <div class="absolute inset-0 bg-white/70 backdrop-blur-sm z-10 flex items-center justify-center">
                            <span class="bg-red-500 text-white font-black text-sm px-4 py-2 rounded-full shadow-lg border-2 border-white transform -rotate-6">Oops, Gone!</span>
                        </div>
                    @endif

                    <img src="{{asset('storage/'.\->thumbnail)}}" loading="lazy" class="max-w-[85%] max-h-[85%] object-contain mix-blend-multiply z-10 transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500 ease-out">
                </div>
                
                <!-- Info Section -->
                <div class="p-5 md:p-6 flex flex-col flex-1 bg-white relative z-20 text-center border-t-2 border-slate-50">
                    <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-2"><i class="fas fa-puzzle-piece text-funblue/50 mr-1"></i> {{$p->category->name ?? 'Toys'}}</p>
                    <h4 class="font-heading text-lg md:text-xl text-slate-800 leading-tight mb-4 group-hover:text-primary transition-colors flex-1">{{$p->name}}</h4>
                    
                    <div class="flex flex-col items-center justify-end mt-auto gap-2">
                        @if($p->sale_price)
                            <del class="text-sm font-bold text-slate-400">৳{{$p->regular_price}}</del>
                        @endif
                        <span class="font-heading text-2xl text-primary tracking-wide bg-primary/5 px-4 py-1.5 rounded-full border border-primary/10">৳{{number_format($p->sale_price ?? $p->regular_price)}}</span>
                    </div>
                </div>
            </a> 
        @empty
            <div class="col-span-full py-28 text-center bg-white rounded-[3rem] border-4 border-dashed border-slate-200">
                <i class="fas fa-ghost text-6xl text-slate-200 mb-6 block animate-bounce"></i>
                <h3 class="text-2xl font-heading text-slate-500 mb-2">Oh no! So empty...</h3>
                <p class="text-base font-bold text-slate-400">We couldn't find any toys here right now.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-20 flex justify-center">
        <!-- Pagination UI -->
        <style>
            .pagination-wrapper nav span, .pagination-wrapper nav a { border-radius: 9999px; font-weight: 800; border-color: #f1f5f9; background-color: white; color: #94a3b8; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); font-family: 'Quicksand', sans-serif;}
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
