@extends('shop.layout')

@section('title', $product->name . ' - ' . $client->shop_name)

@section('content')
<div x-data="{ 
    mainImage: '{{ asset('storage/' . $product->thumbnail) }}',
    selectedColor: null,
    selectedSize: null,
    showVideoModal: false,
    showZoomModal: false,
    showChatOptions: false,
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
            <button type="button" @click="showChatOptions = true"
               class="flex-1 bg-gray-100 text-gray-700 py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm active:bg-gray-200">
                <i class="fas fa-comment"></i> Chat
            </button>

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


    {{-- 🔥 NEW: Chat Options Action Sheet / Modal --}}
    <div x-show="showChatOptions" x-cloak class="fixed inset-0 z-[110] flex items-end md:items-center justify-center sm:p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" 
             @click="showChatOptions = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        
        <div class="relative w-full sm:max-w-sm bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden transform transition-all pb-safe"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">
            
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">How do you want to chat?</h3>
                    <button @click="showChatOptions = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-3">
                    @if($client->fb_page_id)
                    <a href="https://m.me/{{ $client->fb_page_id }}?text={{ urlencode('Hi, I want to know about: ' . $product->name) }}" 
                       target="_blank" 
                       class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-blue-50 hover:border-blue-100 bg-blue-50/50 hover:bg-blue-50 transition group">
                        <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl shadow-md group-hover:scale-110 transition-transform">
                            <i class="fab fa-facebook-messenger"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 text-lg">Messenger</h4>
                            <p class="text-xs text-gray-500">Fastest reply from our AI</p>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-500 transition"></i>
                    </a>
                    @endif

                    @if($client->is_whatsapp_active && $client->phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text={{ urlencode('Hi, I want to know about this product: ' . route('shop.product.details', [$client->slug, $product->slug])) }}" 
                       target="_blank" 
                       class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-green-50 hover:border-green-100 bg-green-50/50 hover:bg-green-50 transition group">
                        <div class="w-12 h-12 bg-green-500 text-white rounded-full flex items-center justify-center text-2xl shadow-md group-hover:scale-110 transition-transform">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 text-lg">WhatsApp</h4>
                            <p class="text-xs text-gray-500">Chat with our support team</p>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 group-hover:text-green-500 transition"></i>
                    </a>
                    @endif
                </div>
                
                <p class="text-center text-xs text-gray-400 mt-6">Usually replies within a few minutes.</p>
            </div>
        </div>
    </div>
    
</div> @endsection