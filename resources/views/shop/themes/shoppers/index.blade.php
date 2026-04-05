@extends('shop.themes.shoppers.layout')
@section('title', $client->shop_name . ' | Cosmetics & Beauty')

@section('content')

@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .section-title-wrap { border: 1px solid #e5e7eb; padding: 12px 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; background: #fff; border-top: 2px solid var(--tw-color-primary, #ef4444); }
    .section-title { font-size: 14px; color: #4b5563; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
</style>

<div class="max-w-[1240px] mx-auto px-4 mt-6">

    {{-- Hero Banner --}}
    @if($client->widget('hero_banner'))
        <div class="mb-10">
            <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" :categories="$categories ?? null" />
        </div>
    @endif

    {{-- Feature Boxes --}}
    @if($client->widget('feature_strip'))
    @php $features = $client->widgetConfig('feature_strip'); @endphp
    @if(isset($features['items']) && is_array($features['items']) && count($features['items']) > 0)
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0 border border-gray-100 mb-12 bg-white">
        @foreach(array_slice($features['items'], 0, 4) as $item)
        <div class="border border-r border-b lg:border-b-0 p-4 flex items-center gap-3">
            <div class="w-12 h-12 bg-gray-50 rounded flex items-center justify-center shrink-0">
                <i class="fas {{ $item['icon'] ?? 'fa-check' }} text-primary text-xl"></i>
            </div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">{{ mb_strtoupper($item['title'] ?? '') }}</h4>
                <p class="text-[10px] text-gray-500">{{ $item['subtitle'] ?? '' }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @else
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0 border border-gray-100 mb-12 bg-white">
        <div class="border border-r border-b lg:border-b-0 p-4 flex items-center gap-3">
            <div class="w-12 h-12 bg-primary/10 rounded flex items-center justify-center shrink-0"><i class="fas fa-rocket text-primary text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">FREE SHIPPING!</h4>
                <p class="text-[10px] text-gray-500">On Orders Over 3000 Taka.</p>
            </div>
        </div>
        <div class="border border-r border-b lg:border-b-0 p-4 flex items-center gap-3">
            <div class="w-12 h-12 bg-green-100 rounded flex items-center justify-center shrink-0"><i class="fas fa-sync-alt text-green-500 text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">EXCHANGE POLICY</h4>
                <p class="text-[10px] text-gray-500">Fast & Hassle Free</p>
            </div>
        </div>
        <div class="border border-r border-b sm:border-b-0 p-4 flex items-center gap-3">
            <div class="w-12 h-12 bg-purple-100 rounded flex items-center justify-center shrink-0"><i class="fas fa-headset text-purple-500 text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">ONLINE SUPPORT</h4>
                <p class="text-[10px] text-gray-500">24/7 Everyday</p>
            </div>
        </div>
        <div class="border lg:border-none p-4 flex items-center gap-3">
            <div class="w-12 h-12 bg-yellow-100 rounded flex items-center justify-center shrink-0"><i class="fas fa-gift text-yellow-500 text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">REWARD POINTS</h4>
                <p class="text-[10px] text-gray-500">Earn 1% Cashback</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Products Grid Feed --}}
    <div class="mb-12">
        <div class="section-title-wrap">
            @if(request('category') && request('category') != 'all')
                <h3 class="section-title">{{ $categories->where('slug', request('category'))->first()?->name ?? 'Category Products' }}</h3>
            @else
                <h3 class="section-title">{{ $client->widgets['products_section']['title'] ?? 'Our Collections' }}</h3>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4">
            @forelse($products as $p)
                @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
            @empty
                <div class="col-span-full py-20 text-center border border-dashed border-gray-200 rounded-xl bg-white">
                    <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-bold text-gray-700">No products found</h3>
                    <p class="text-gray-500">Please try a different category or clear filters.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="mt-12 mb-16">
            <style>
                .pg nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
                .pg nav a, .pg nav span { min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; font-weight: 600; font-size: 13px; background: white; color: #64748b; border: 1px solid #e2e8f0; transition: all 0.2s; }
                .pg nav a:hover { border-color: var(--tw-color-primary, #000); color: var(--tw-color-primary, #000); }
                .pg nav span[aria-current="page"] { background: var(--tw-color-primary, #000); color: white !important; border-color: var(--tw-color-primary, #000); }
            </style>
            <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </div>

</div>

@endsection
