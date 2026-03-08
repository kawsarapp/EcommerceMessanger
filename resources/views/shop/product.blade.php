@extends('shop.layout')

@section('title', $product->name . ' - ' . $client->shop_name)

@section('content')
<div x-data="{ 
    mainImage: '{{ asset('storage/' . $product->thumbnail) }}',
    selectedColor: null,
    selectedSize: null,
    showVideoModal: false,
    showZoomModal: false,
    activeVideoUrl: '',

    playVideo(url) {
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            if (match && match[2].length === 11) {
                this.activeVideoUrl = 'https://www.youtube.com/embed/' + match[2] + '?autoplay=1&rel=0';
            } else {
                this.activeVideoUrl = url;
            }
        } else {
            this.activeVideoUrl = url;
        }
        this.showVideoModal = true;
    }
}">

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-24 md:mb-0">
        
        <nav class="flex text-sm text-gray-500 mb-6 overflow-x-auto whitespace-nowrap pb-2">
            <a href="{{ route('shop.show', $client->slug) }}" class="hover:text-primary transition">Home</a>
            <span class="mx-2 text-gray-300">/</span>
            <span class="text-gray-900 font-medium truncate">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            {{-- Product Gallery Component --}}
            @include('shop.partials.product-gallery')
            
            {{-- Product Info Component (Title, Price, Variants) --}}
            @include('shop.partials.product-info')
        </div>

        @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="mt-16">

            {{-- Reviews Section --}}
            @php
                $reviews = $product->reviews()->where('is_visible', true)->latest()->get();
            @endphp
            
            @if($reviews->count() > 0)
            <div class="mt-12 bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Customer Reviews ({{ $reviews->count() }})</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($reviews as $review)
                    <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                        <div class="flex justify-between items-start mb-3">
                            <h4 class="font-bold text-gray-800">{{ $review->customer_name ?? 'Verified Buyer' }}</h4>
                            <div class="text-yellow-400 text-sm tracking-widest">
                                {!! str_repeat('★', $review->rating) !!}{!! str_repeat('☆', 5 - $review->rating) !!}
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm italic">"{{ $review->comment }}"</p>
                        <p class="text-xs text-gray-400 mt-3 text-right">{{ $review->created_at->diffForHumans() }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Related Products --}}
            <h2 class="text-2xl font-bold font-heading text-gray-900 mb-6 mt-12">You May Also Like</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                @foreach($relatedProducts as $related)
                <a href="{{ route('shop.product.details', [$client->slug, $related->slug]) }}" class="group bg-white rounded-2xl border border-gray-100 p-3 hover:shadow-lg transition-all duration-300">
                    <div class="aspect-square bg-gray-50 rounded-xl overflow-hidden mb-3 relative">
                        <img src="{{ asset('storage/' . $related->thumbnail) }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm mb-1 truncate">{{ $related->name }}</h3>
                    <p class="text-primary font-bold">৳{{ $related->sale_price ?? $related->regular_price }}</p>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </main>

    {{-- Mobile Sticky Nav --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-3 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] pb-safe safe-area-pb">
        <div class="flex gap-3">
            <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('Hi, query: {{ $product->name }}')" 
               target="_blank"
               class="flex-1 bg-gray-100 text-gray-700 py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm active:bg-gray-200">
                <i class="fas fa-comment"></i> Chat
            </a>

            <a :href="'{{ $client->custom_domain ? route('shop.checkout.custom', $product->slug) : route('shop.checkout', [$client->slug, $product->slug]) }}' + '?qty=1' + (selectedColor ? '&color=' + selectedColor : '') + (selectedSize ? '&size=' + selectedSize : '')" 
               class="flex-[2] bg-primary text-white py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm shadow-lg shadow-blue-500/30 active:scale-95 transition">
                <i class="fas fa-shopping-cart text-lg"></i> Order Now
            </a>



        </div>
    </div>

    {{-- Video Modal --}}
    <div x-show="showVideoModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 p-4 backdrop-blur-md"
         x-transition.opacity>
        <div class="relative w-full max-w-4xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-gray-800" @click.away="showVideoModal = false">
            <button @click="showVideoModal = false; activeVideoUrl = ''" class="absolute top-4 right-4 z-50 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="video-wrapper">
                <iframe :src="activeVideoUrl" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    {{-- Zoom Modal --}}
    <div x-show="showZoomModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center bg-white p-4"
         x-transition.opacity>
        <div class="relative w-full h-full flex items-center justify-center" @click.away="showZoomModal = false">
            <button @click="showZoomModal = false" class="absolute top-4 right-4 z-50 bg-gray-100 hover:bg-gray-200 text-gray-800 p-3 rounded-full shadow-lg transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <img :src="mainImage" class="max-w-full max-h-full object-contain cursor-zoom-out shadow-2xl rounded-lg" @click="showZoomModal = false">
        </div>
    </div>

</div>

{{-- Facebook Pixel ViewContent Event --}}
    @if($client->pixel_id)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof fbq === 'function') {
                fbq('track', 'ViewContent', {
                    content_name: '{{ addslashes($product->name) }}',
                    content_ids: ['{{ $product->id }}'],
                    content_type: 'product',
                    value: {{ $product->sale_price ?? $product->regular_price }},
                    currency: 'BDT'
                });
            }
        });
    </script>
    @endif
    
@endsection