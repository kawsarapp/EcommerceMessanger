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
            <x-shop.widgets.hero-banner :client="$client" :config="$client->widgetConfig('hero_banner')" :categories="null" />
        </div>
    @endif

    {{-- Main Products Grid Feed --}}
    <section id="products" class="mb-12">
        @if(request('category') && request('category') != 'all')
            <h2 class="text-xl font-bold border-l-4 border-primary pl-3 mb-6">{{ $categories->where('slug', request('category'))->first()?->name ?? 'Category Products' }}</h2>
        @else
            <h2 class="section-title-lines">{{ $client->widgets['products_section']['title'] ?? 'Specially for You' }}</h2>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-5 mt-4"
             x-data="{ init() { 
                let delay = 0;
                this.$el.querySelectorAll('.mat-fade-item').forEach(el => {
                    setTimeout(() => { el.classList.remove('opacity-0', 'translate-y-8'); }, delay);
                    delay += 50; 
                });
            } }">
            @forelse($products as $p)
                <div class="mat-fade-item opacity-0 translate-y-8 transition-all duration-500 ease-out will-change-transform">
                    <div class="mat-card h-full rounded-2xl overflow-hidden hover:mat-elevated flex flex-col">
                        @include('shop.partials.product-card', ['product' => $p, 'baseUrl' => $baseUrl, 'client' => $client])
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 flex flex-col items-center justify-center mat-card mat-elevated">
                    <i class="fas fa-box-open text-4xl text-slate-300 mb-4"></i>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">কোনো পণ্য পাওয়া যায়নি</h3>
                    <p class="text-sm text-slate-500">অন্য ক্যাটাগরি দেখুন।</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-12 flex justify-center">
            <div class="bd-pagination">{{$products->links('pagination::tailwind')}}</div>
        </div>
    </section>

</div>

@endsection
