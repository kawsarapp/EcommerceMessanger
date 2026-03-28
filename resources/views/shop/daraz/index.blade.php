@extends('shop.daraz.layout')
@section('title', $client->shop_name . ' | সেরা দামে অনলাইন শপিং')

@section('content')
@php
$baseUrl = $client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

{{-- Hero Banner --}}
@if($client->widget('hero_banner'))
<section class="bg-white">
    <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" />
</section>
@endif

{{-- Trust Bar --}}
<section class="bg-white border-b border-gray-100 py-3 hidden md:block">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-center gap-8 text-sm text-gray-600">
            <span class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> ১০০% অরিজিনাল পণ্য</span>
            <span class="flex items-center gap-2"><i class="fas fa-shipping-fast text-primary"></i> সারাদেশে ডেলিভারি</span>
            <span class="flex items-center gap-2"><i class="fas fa-headset text-blue-500"></i> ২৪/৭ সাপোর্ট</span>
            <span class="flex items-center gap-2"><i class="fas fa-undo-alt text-purple-500"></i> সহজ রিটার্ন</span>
        </div>
    </div>
</section>

{{-- Category Filter --}}
@if($client->widget('category_filter'))
    <x-shop.widgets.category-filter :client="$client" :config="$client->widgetConfig('category_filter')" :categories="$categories" />
@endif

{{-- Products Section --}}
<section class="max-w-7xl mx-auto px-4 py-6 md:py-10">
    {{-- Section Header --}}
    <div class="flex items-center gap-3 mb-6">
        <div class="w-1.5 h-8 hero-gradient rounded-full"></div>
        <div>
            <h2 class="text-xl md:text-2xl font-bold text-dark">
                @if(request('category') && request('category') !== 'all')
                    {{ $categories->where('slug', request('category'))->first()?->name ?? 'পণ্য সমূহ' }}
                @else
                    সকল পণ্য
                @endif
            </h2>
            <p class="text-sm text-gray-500">{{ $products->total() }}টি পণ্য পাওয়া গেছে</p>
        </div>
    </div>

    {{-- Product Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
        @forelse($products as $p)
            @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
        @empty
            <div class="col-span-full py-20">
                <div class="max-w-md mx-auto text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-box-open text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">কোনো পণ্য পাওয়া যায়নি</h3>
                    <p class="text-gray-500 mb-6">অন্য ক্যাটাগরি দেখুন বা সার্চ করুন।</p>
                    <a href="{{ $baseUrl }}" class="btn-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-full font-semibold transition">
                        <i class="fas fa-arrow-left"></i> সকল পণ্য দেখুন
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    <div class="mt-10">
        <style>
            .pg nav { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; }
            .pg nav a, .pg nav span { min-width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 600; font-size: 14px; background: white; color: #64748b; border: 1px solid #e5e7eb; transition: all 0.2s; }
            .pg nav a:hover { border-color: var(--primary); color: var(--primary); }
            .pg nav span[aria-current="page"] { background: var(--primary); color: white !important; border: none; }
        </style>
        <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
    </div>
    @endif
</section>

{{-- Homepage Categories --}}
@if(!request('category') || request('category') == 'all')
    @include('shop.partials.homepage-categories', ['client' => $client])
@endif

@endsection