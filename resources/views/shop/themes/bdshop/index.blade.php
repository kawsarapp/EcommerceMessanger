@extends('shop.themes.bdshop.layout')
@section('title', $client->shop_name . ' | সেরা দামে অনলাইন শপিং')

@section('content')
@php 
$baseUrl=$client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); 
@endphp

{{-- Hero Banner --}}
@if($client->widget('show_hero_banner') && $client->banner)
<section class="max-w-[1280px] mx-auto px-4 pt-4">
    <div class="relative rounded-xl overflow-hidden h-[200px] sm:h-[300px] md:h-[400px] group">
        <img src="{{asset('storage/'.$client->banner)}}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Banner">
        <div class="absolute inset-0 bg-gradient-to-r from-dark/70 via-dark/30 to-transparent"></div>
        <div class="absolute inset-0 flex flex-col justify-center p-6 sm:p-10 md:p-16">
            <span class="inline-flex items-center gap-1.5 bg-primary text-white text-[10px] sm:text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full w-fit mb-3 sm:mb-4">
                <i class="fas fa-fire"></i> হট ডিল
            </span>
            <h2 class="text-2xl sm:text-4xl md:text-5xl text-white font-extrabold leading-tight mb-2 sm:mb-4">
                {{$client->meta_title ?? 'সেরা দামে সেরা পণ্য'}}
            </h2>
            <p class="text-white/80 text-sm sm:text-base font-medium mb-4 sm:mb-6 max-w-lg hidden sm:block">
                সারাদেশে ক্যাশ অন ডেলিভারি। ফ্রি রিটার্ন পলিসি।
            </p>
            <a href="#products" class="bg-primary hover:bg-primary/90 text-white font-bold text-xs sm:text-sm uppercase tracking-wider px-5 sm:px-8 py-2.5 sm:py-3.5 rounded-lg transition shadow-lg hover:shadow-xl w-fit flex items-center gap-2">
                এখনই কিনুন <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endif

{{-- Trust Badges --}}
@if($client->widget('show_trust_badges'))
<section class="max-w-[1280px] mx-auto px-4 py-4 sm:py-6">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl p-3 sm:p-4 flex items-center gap-3 border border-slate-100">
            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center text-primary shrink-0"><i class="fas fa-truck-fast text-lg"></i></div>
            <div><span class="text-xs sm:text-sm font-bold text-dark block">দ্রুত ডেলিভারি</span><span class="text-[10px] sm:text-xs text-slate-500">সারাদেশে</span></div>
        </div>
        <div class="bg-white rounded-xl p-3 sm:p-4 flex items-center gap-3 border border-slate-100">
            <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-500 shrink-0"><i class="fas fa-shield-check text-lg"></i></div>
            <div><span class="text-xs sm:text-sm font-bold text-dark block">১০০% অরিজিনাল</span><span class="text-[10px] sm:text-xs text-slate-500">গ্যারান্টিসহ</span></div>
        </div>
        <div class="bg-white rounded-xl p-3 sm:p-4 flex items-center gap-3 border border-slate-100">
            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-500 shrink-0"><i class="fas fa-money-bill-wave text-lg"></i></div>
            <div><span class="text-xs sm:text-sm font-bold text-dark block">ক্যাশ অন ডেলিভারি</span><span class="text-[10px] sm:text-xs text-slate-500">পণ্য পেয়ে পেমেন্ট</span></div>
        </div>
        <div class="bg-white rounded-xl p-3 sm:p-4 flex items-center gap-3 border border-slate-100">
            <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 shrink-0"><i class="fas fa-headset text-lg"></i></div>
            <div><span class="text-xs sm:text-sm font-bold text-dark block">২৪/৭ সাপোর্ট</span><span class="text-[10px] sm:text-xs text-slate-500">যেকোনো সময়</span></div>
        </div>
    </div>
</section>
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

@endsection
