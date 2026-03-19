@extends('shop.themes.bdshop.layout')
@section('title', $client->shop_name . ' | সেরা দামে অনলাইন শপিং')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

{{-- Hero Banner --}}
@if($client->widget('hero_banner'))
    <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" />
@endif

{{-- Category Filter Pills (if active) --}}
@if($client->widget('category_filter'))
    <x-shop.widgets.category-filter :client="$client" :config="$client->widgetConfig('category_filter')" :categories="$categories" />
@endif

{{-- Trust Badges --}}
@if($client->widget('trust_badges'))
    <x-shop.widgets.trust-badges :client="$client" :config="$client->widgetConfig('trust_badges')" />
@endif

{{-- Products Section --}}
<section id="products" class="max-w-[1280px] mx-auto px-4 pb-6 sm:pb-10">
    <div class="flex items-center justify-between mb-4 sm:mb-6">
        <h2 class="text-lg sm:text-2xl font-extrabold text-dark">
            @if(request('category') && request('category') !== 'all')
                {{ $categories->where('slug', request('category'))->first()?->name ?? 'পণ্য সমূহ' }}
            @else
                সকল পণ্য
            @endif
            <span class="text-slate-400 text-sm font-medium ml-2">({{ $products->total() }}টি)</span>
        </h2>
    </div>

    {{-- Product Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5 sm:gap-4">
        @forelse($products as $p)
            @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
        @empty
            <div class="col-span-full py-20 flex flex-col items-center justify-center bg-white rounded-xl">
                <i class="fas fa-box-open text-4xl text-slate-300 mb-4"></i>
                <h3 class="text-lg font-bold text-slate-800 mb-1">কোনো পণ্য পাওয়া যায়নি</h3>
                <p class="text-sm text-slate-500">অন্য ক্যাটাগরি দেখুন।</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8 sm:mt-12 flex justify-center">
        <style>
            .bd-pagination nav span, .bd-pagination nav a { border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; border:none; color: #64748b; background: white; }
            .bd-pagination nav span:hover, .bd-pagination nav a:hover { background-color: var(--tw-color-primary); color:white; }
            .bd-pagination nav span[aria-current="page"] { background-color: var(--tw-color-primary) !important; color: white !important; }
        </style>
        <div class="bd-pagination">{{$products->links('pagination::tailwind')}}</div>
    </div>
</section>


    {{-- Homepage: Category-based product sections (when no filter) --}}
    @if(!request('category') || request('category') == 'all')
        @include('shop.partials.homepage-categories', ['client' => $client])
    @endif

@endsection
