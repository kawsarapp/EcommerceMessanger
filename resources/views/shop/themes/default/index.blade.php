@extends('shop.themes.default.layout')
@section('title', $client->shop_name . ' | Storefront')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : route('shop.show', $client->slug); 
@endphp

@if($client->widget('hero_banner'))
    <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" />
@endif

<div id="shop" class="pb-12 md:pb-20">

    {{-- Homepage Offer Banner (Timer + Link) --}}
    @include('shop.partials.homepage-offer-banner', ['client' => $client])

    @if($client->widget('category_filter'))
        <x-shop.widgets.category-filter :client="$client" :config="$client->widgetConfig('category_filter')" :categories="$categories" />
    @endif

    {{-- When a specific category is selected, show flat grid --}}
    @if((request('category') && request('category') != 'all') || request()->filled('search'))
        <x-shop.widgets.product-grid :client="$client" :config="['text' => 'Search Results']" :products="$products" title="Search Results" />

        <!-- Pagination -->
        <div class="mt-8 flex justify-center max-w-7xl mx-auto px-4">
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
        {{-- Homepage: Featured Products --}}
        @if($client->widget('flash_sale'))
            <x-shop.widgets.product-grid :client="$client" :config="$client->widgetConfig('flash_sale')" :products="$products->take(10)" title="Featured Products" />
        @endif
        
        {{-- Category-based product sections --}}
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

    @if($client->widget('trust_badges'))
        <x-shop.widgets.trust-badges :client="$client" :config="$client->widgetConfig('trust_badges')" />
    @endif

</div>
@endsection