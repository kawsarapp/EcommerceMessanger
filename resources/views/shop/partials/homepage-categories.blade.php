{{-- 
    Homepage Category Products Partial
    Shows products grouped by category with dynamic count
    Required: $client, $categories (with products eager loaded)
--}}
@php 
$baseUrl = $client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : route('shop.show', $client->slug);

$homepageCategories = \App\Models\Category::where(function($q) use ($client) {
        $q->where('client_id', $client->id)->orWhereNull('client_id');
    })
    ->where('is_visible', true)
    ->orderBy('sort_order')
    ->get();
@endphp

@foreach($homepageCategories as $cat)
    @php
        $limit = $cat->homepage_products_count ?? 4;
        $catProducts = \App\Models\Product::where('client_id', $client->id)
            ->where('category_id', $cat->id)
            ->latest()
            ->take($limit)
            ->get();
    @endphp

    @if($catProducts->count() > 0)
    <section class="mb-16">
        {{-- Category Banner (optional) --}}
        @if(!empty($cat->banner_image))
        <div class="mb-8 rounded-2xl overflow-hidden relative group">
            @if(!empty($cat->banner_link))
            <a href="{{ $cat->banner_link }}" target="_blank" class="block">
            @endif
                <img src="{{ asset('storage/' . $cat->banner_image) }}" class="w-full h-36 sm:h-48 object-cover transform group-hover:scale-105 transition-transform duration-700" alt="{{ $cat->name }} Banner">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                <div class="absolute bottom-4 left-6 z-10">
                    <h3 class="text-white font-extrabold text-2xl drop-shadow-lg">{{ $cat->name }}</h3>
                </div>
            @if(!empty($cat->banner_link))
            </a>
            @endif
        </div>
        @endif

        {{-- Category Header --}}
        <div class="flex justify-between items-end mb-6">
            <div>
                <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $cat->name }}</h3>
                <p class="text-slate-500 text-sm font-medium mt-1">{{ $catProducts->count() }} {{ $catProducts->count() > 1 ? 'products' : 'product' }} available</p>
            </div>
            <a href="{{ $baseUrl }}?category={{ $cat->slug }}" class="text-sm font-bold text-primary hover:text-primary/80 transition flex items-center gap-1.5 shrink-0">
                View All <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>

        {{-- Product Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            @foreach($catProducts as $product)
                @include('shop.partials.product-card', ['product' => $product, 'client' => $client])
            @endforeach
        </div>
    </section>
    @endif
@endforeach
