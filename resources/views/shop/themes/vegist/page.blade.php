@extends('shop.themes.vegist.layout')
@section('title', $page->title . ' - ' . $client->shop_name)

@section('content')

{{-- Breadcrumb --}}
<div class="bg-[#fcfdfa] py-6 mb-8 border-b border-gray-100">
    <div class="max-w-[1000px] mx-auto px-4 xl:px-8 text-center text-[12px] text-gray-500 font-medium tracking-wide">
        <a href="{{ route('shop.show', $client->slug) }}" class="hover:text-primary transition">Home</a>
        <span class="mx-2">/</span>
        <span class="text-dark">{{ $page->title }}</span>
    </div>
</div>

<div class="max-w-[1000px] mx-auto px-4 xl:px-8 pb-16">
    <div class="bg-white p-8 md:p-12 border border-gray-100 rounded-sm shadow-sm">
        <h1 class="text-2xl md:text-3xl font-bold text-dark mb-6 text-center">{{ $page->title }}</h1>
        <div class="w-16 h-1 bg-primary mx-auto mb-10 rounded-full"></div>
        
        <div class="prose max-w-none text-gray-600 text-sm leading-relaxed" style="color: #666; font-family: 'Poppins', sans-serif;">
            {!! $page->content !!}
        </div>
    </div>
</div>

@endsection
