@if(isset($relatedProducts) && $relatedProducts->count() > 0)
<div class="mt-16 mb-10">
    <div class="flex items-center justify-between mb-8 border-b border-gray-100 pb-4">
        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Similar Products</h2>
        <a href="{{ $clean ?? false ? 'https://'.$clean : route('shop.show', $client->slug) }}?category={{ $product->category->slug ?? 'all' }}" class="text-sm font-bold text-primary hover:text-primary/80 flex items-center transition-colors">
            View All <i class="fas fa-arrow-right ml-2 text-xs"></i>
        </a>
    </div>

    {{-- Universal CSS scroll-snap responsive grid --}}
    <div class="flex overflow-x-auto hide-scroll pb-6 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 snap-x">
        @foreach($relatedProducts->take(8) as $rp)
            <div class="w-[70vw] sm:w-auto shrink-0 snap-start h-full">
                {{-- Fallback matching logic for product-card --}}
                @if(view()->exists('shop.themes.' . $client->theme_name . '.product-card'))
                    @include('shop.themes.' . $client->theme_name . '.product-card', ['p' => $rp, 'client' => $client, 'baseUrl' => $baseUrl ?? ''])
                @else
                    @include('shop.partials.product-card', ['p' => $rp, 'client' => $client, 'baseUrl' => $baseUrl ?? ''])
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif
