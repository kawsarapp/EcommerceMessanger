@extends('shop.themes.default.layout')
@section('title', $client->shop_name . ' | Storefront')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : route('shop.show', $client->slug); 
@endphp

<style>
    /* Dynamic Entrance Animations */
    .stagger-1 { animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both; }
    .stagger-2 { animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both; }
    .stagger-3 { animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both; }
    .stagger-4 { animation: slideUpFade 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both; }
    
    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

@if($client->widget('hero_banner'))
    <div class="stagger-1 relative z-10 mx-4 sm:mx-6 mt-4 md:mt-6 mb-8 lg:mb-12 rounded-[2rem] md:rounded-[3rem] overflow-hidden shadow-2xl shadow-primary/10 border border-white/50">
        <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" />
    </div>
@endif

<div id="shop" class="pb-16 md:pb-24 max-w-7xl mx-auto px-4 sm:px-6">

    {{-- Homepage Offer Banner (Timer + Link) --}}
    <div class="stagger-2">
        @include('shop.partials.homepage-offer-banner', ['client' => $client])
    </div>

    @if($client->widget('category_filter'))
        <div class="stagger-3 glass-panel rounded-3xl p-4 md:p-6 mb-8 md:mb-12">
            <x-shop.widgets.category-filter :client="$client" :config="$client->widgetConfig('category_filter')" :categories="$categories" />
        </div>
    @endif

    {{-- When a specific category is selected, show flat grid --}}
    @if((request('category') && request('category') != 'all') || request()->filled('search'))
        <div class="stagger-4 glass-panel rounded-3xl p-6 md:p-10">
            <x-shop.widgets.product-grid :client="$client" :config="['text' => 'Search Results']" :products="$products" title="Search Results" />

            <!-- Pagination -->
            <div class="mt-12 flex justify-center w-full">
                <style>
                    .pagination-wrapper nav span, .pagination-wrapper nav a { border-radius: 0.75rem; font-weight: 700; font-size: 0.875rem; border:none; color: #64748b; background: rgba(255,255,255,0.8); backdrop-filter: blur(8px); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); transition: all 0.3s ease; }
                    .pagination-wrapper nav span:hover, .pagination-wrapper nav a:hover { background-color: white; color: var(--tw-color-primary); transform: translateY(-2px); }
                    .pagination-wrapper nav span[aria-current="page"] { background-color: var(--tw-color-primary) !important; color: white !important; box-shadow: 0 4px 14px 0 var(--tw-color-primary) !important; opacity: 0.9; }
                </style>
                <div class="pagination-wrapper">
                    {{$products->links('pagination::tailwind')}}
                </div>
            </div>
        </div>
    @else
        {{-- Homepage: Featured Products --}}
        @if($client->widget('flash_sale'))
            <div class="stagger-4 relative z-10 glass-panel rounded-3xl p-6 md:p-10 mb-8 md:mb-12">
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-bl-[100px] pointer-events-none"></div>
                <x-shop.widgets.product-grid :client="$client" :config="$client->widgetConfig('flash_sale')" :products="$products->take(10)" title="{{ $client->widgets['flash_sale']['text'] ?? 'Featured Products' }}" />
            </div>
        @endif
        
        {{-- Category-based product sections --}}
        <div class="stagger-4 space-y-8 md:space-y-12">
            @include('shop.partials.homepage-categories', ['client' => $client])
        </div>
    @endif

    @if($client->widget('trust_badges'))
        <div class="mt-16 bg-white/40 backdrop-blur-md rounded-[2.5rem] border border-white p-8">
            <x-shop.widgets.trust-badges :client="$client" :config="$client->widgetConfig('trust_badges')" />
        </div>
    @endif

</div>
@endsection
