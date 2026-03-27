@extends('shop.themes.bdshop.layout')
@section('title', $client->shop_name . ' | সেরা দামে অনলাইন শপিং')

@section('content')
@php
$baseUrl = $client->custom_domain ? 'https://' . preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : route('shop.show', $client->slug);
@endphp

{{-- Hero Banner / Slider --}}
@if($client->widget('hero_banner'))
    <section class="bg-white">
        <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" />
    </section>
@endif

{{-- Flash Sale Section --}}
@if($client->widget('flash_sale'))
<section class="bg-gradient-to-r from-red-500 to-orange-500 py-3">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-center gap-3 text-white">
            <i class="fas fa-bolt text-2xl flash-animate"></i>
            <span class="font-bold text-lg">ফ্ল্যাশ সেল!</span>
            <span class="text-sm">সীমিত সময়ের অফার</span>
        </div>
    </div>
</section>
@endif

{{-- Trust Badges --}}
@if($client->widget('trust_badges'))
<section class="bg-white py-4 border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-4 gap-2 sm:gap-4">
            <div class="flex items-center justify-center gap-2 p-2 sm:p-3">
                <i class="fas fa-truck text-primary text-lg sm:text-xl"></i>
                <span class="text-[10px] sm:text-xs font-semibold text-slate-600 hidden sm:block">সারাদেশে ডেলিভারি</span>
            </div>
            <div class="flex items-center justify-center gap-2 p-2 sm:p-3">
                <i class="fas fa-shield-check text-primary text-lg sm:text-xl"></i>
                <span class="text-[10px] sm:text-xs font-semibold text-slate-600 hidden sm:block">১০০% অরিজিনাল</span>
            </div>
            <div class="flex items-center justify-center gap-2 p-2 sm:p-3">
                <i class="fas fa-undo text-primary text-lg sm:text-xl"></i>
                <span class="text-[10px] sm:text-xs font-semibold text-slate-600 hidden sm:block">সহজ রিটার্ন</span>
            </div>
            <div class="flex items-center justify-center gap-2 p-2 sm:p-3">
                <i class="fas fa-money-bill-wave text-primary text-lg sm:text-xl"></i>
                <span class="text-[10px] sm:text-xs font-semibold text-slate-600 hidden sm:block">ক্যাশ অন ডেলিভারি</span>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Category Filter Pills --}}
@if($client->widget('category_filter'))
    <x-shop.widgets.category-filter :client="$client" :config="$client->widgetConfig('category_filter')" :categories="$categories" />
@endif

{{-- Main Products Section --}}
<section id="products" class="max-w-7xl mx-auto px-3 sm:px-4 py-6 sm:py-8">
    {{-- Section Header --}}
    <div class="flex items-center justify-between mb-4 sm:mb-6">
        <div class="flex items-center gap-3">
            <div class="w-1 h-6 bg-primary rounded-full"></div>
            <h2 class="text-lg sm:text-xl font-bold text-dark">
                @if(request('category') && request('category') !== 'all')
                    {{ $categories->where('slug', request('category'))->first()?->name ?? 'পণ্য সমূহ' }}
                @else
                    সকল পণ্য
                @endif
            </h2>
            <span class="text-xs sm:text-sm text-slate-400 font-medium bg-slate-100 px-2 py-0.5 rounded-full">{{ $products->total() }}টি</span>
        </div>
        {{-- Sort Dropdown (Desktop) --}}
        <div class="hidden sm:flex items-center gap-2">
            <span class="text-xs text-slate-500">সাজান:</span>
            <select class="text-xs border-0 bg-slate-100 rounded-lg px-3 py-1.5 focus:ring-primary font-medium">
                <option>নতুন আগে</option>
                <option>দাম: কম থেকে বেশি</option>
                <option>দাম: বেশি থেকে কম</option>
            </select>
        </div>
    </div>

    {{-- Product Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3 md:gap-4">
        @forelse($products as $p)
            @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
        @empty
            <div class="col-span-full py-16 flex flex-col items-center justify-center bg-white rounded-xl border border-slate-200">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-box-open text-3xl text-slate-300"></i>
                </div>
                <h3 class="text-lg font-bold text-dark mb-1">কোনো পণ্য পাওয়া যায়নি</h3>
                <p class="text-sm text-slate-500">অন্য ক্যাটাগরি দেখুন বা সার্চ করুন।</p>
                <a href="{{ $baseUrl }}" class="mt-4 px-6 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary/90 transition">
                    সকল পণ্য দেখুন
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8 sm:mt-10 flex justify-center">
        <style>
            .bd-pagination nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
            .bd-pagination nav span, .bd-pagination nav a {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 36px;
                height: 36px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.875rem;
                border: none;
                color: #64748b;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .bd-pagination nav a:hover { background-color: var(--tw-color-primary); color: white; }
            .bd-pagination nav span[aria-current="page"] { background-color: var(--tw-color-primary) !important; color: white !important; }
        </style>
        <div class="bd-pagination">{{ $products->links('pagination::tailwind') }}</div>
    </div>
</section>

{{-- Homepage Category Sections --}}
@if(!request('category') || request('category') == 'all')
    @include('shop.partials.homepage-categories', ['client' => $client])
@endif

@endsection
