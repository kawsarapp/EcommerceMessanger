@extends('shop.themes.bdpro.layout')
@section('title', $client->shop_name . ' | শীর্ষস্থানীয় ইলেকট্রনিক্স শপ')

@section('content')

@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .section-title-lines {
        display: flex;
        align-items: center;
        text-align: center;
        color: #000;
        font-weight: 800;
        font-size: 24px;
        margin-bottom: 24px;
    }
    .section-title-lines::before,
    .section-title-lines::after {
        content: '';
        flex: 1;
        border-bottom: 2px solid var(--tw-color-primary, var(--tw-color-primary));
        margin: 0 20px;
        opacity: 0.2;
    }
</style>

<div class="max-w-[1400px] mx-auto px-4 mt-6">

    {{-- Hero Banner --}}
    @if($client->widget('hero_banner'))
        <div class="mb-10">
            <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" :categories="$categories ?? null" />
        </div>
    @endif

    {{-- Main Products Grid Feed --}}
    <div class="mb-12">
        @if(request('category') && request('category') != 'all')
            <h2 class="text-xl font-bold border-l-4 border-primary pl-3 mb-6">{{ $categories->where('slug', request('category'))->first()?->name ?? 'Category Products' }}</h2>
        @else
            <h2 class="section-title-lines">{{ $client->widgets['products_section']['title'] ?? 'Specially for You' }}</h2>
        @endif

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
        <div class="mt-12">
            <style>
                .pg nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
                .pg nav a, .pg nav span { min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-weight: 600; font-size: 13px; background: white; color: #64748b; border: 1px solid #e2e8f0; transition: all 0.2s; }
                .pg nav a:hover { border-color: var(--tw-color-primary, #000); color: var(--tw-color-primary, #000); }
                .pg nav span[aria-current="page"] { background: var(--tw-color-primary, #000); color: white !important; border-color: var(--tw-color-primary, #000); }
            </style>
            <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </div>

</div>

@endsection
