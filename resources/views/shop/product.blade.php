@extends('shop.layout')

@section('title', $product->name . ' - ' . $client->shop_name)

@section('content')
@php
    // 🔥 Custom Domain Clean URL Logic
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
    $productUrl = $cleanDomain ? $baseUrl.'/product/'.$product->slug : route('shop.product.details', [$client->slug, $product->slug]);
@endphp

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
    },

    // 🔥 NEW: Native Share Feature
    shareProduct() {
        if (navigator.share) {
            navigator.share({
                title: '{{ addslashes($product->name) }}',
                text: 'Check out this awesome product at {{ $client->shop_name }}!',
                url: '{{ $productUrl }}'
            }).catch(console.error);
        } else {
            navigator.clipboard.writeText('{{ $productUrl }}');
            alert('Product link copied to clipboard!');
        }
    }
}">

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-24 md:mb-12">
        
        {{-- Top Navigation & Share --}}
        <div class="flex items-center justify-between mb-6">
            <nav class="flex text-sm text-gray-500 overflow-x-auto whitespace-nowrap pb-2 scrollbar-hide flex-1">
                <a href="{{ $baseUrl }}" class="hover:text-primary transition font-medium"><i class="fas fa-home mr-1"></i> Home</a>
                <span class="mx-2 text-gray-300">/</span>
                <span class="text-gray-900 font-bold truncate max-w-[200px] sm:max-w-md">{{ $product->name }}</span>
            </nav>

            <button @click="shareProduct()" class="ml-4 w-9 h-9 flex items-center justify-center bg-white border border-gray-200 rounded-full text-gray-600 hover:text-primary hover:border-primary hover:bg-blue-50 transition shadow-sm tooltip" title="Share Product">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            {{-- Product Gallery Component --}}
            @include('shop.partials.product-gallery')
            
            {{-- Product Info Component (Title, Price, Variants) --}}
            @include('shop.partials.product-info')
        </div>

        @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="mt-16 border-t border-gray-100 pt-12">

            {{-- Reviews Section --}}
            @php
                $reviews = $product->reviews()->where('is_visible', true)->latest()->get();
            @endphp
            
            @if($reviews->count() > 0)
            <div class="mb-16 bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-400/5 rounded-full blur-3xl -mr-10 -mt-10"></div>
                
                <h2 class="text-2xl font-bold font-heading text-gray-900 mb-8 border-b border-gray-100 pb-4 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-400"></i> Customer Reviews ({{ $reviews->count() }})
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($reviews as $review)
                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 hover:shadow-md transition duration-300 flex flex-col h-full">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-primary to-blue-400 text-white rounded-full flex items-center justify-center font-bold text-lg shadow-sm">
                                    {{ substr($review->customer_name ?? 'V', 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm">{{ $review->customer_name ?? 'Verified Buyer' }}</h4>
                                    <div class="text-yellow-400 text-xs tracking-widest mt-0.5">
                                        {!! str_repeat('★', $review->rating) !!}{!! str_repeat('☆', 5 - $review->rating) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm italic flex-1 relative z-10">
                            <i class="fas fa-quote-left text-gray-200 absolute -top-2 -left-2 text-2xl -z-10"></i>
                            "{{ $review->comment }}"
                        </p>
                        <p class="text-xs text-gray-400 mt-4 text-right font-medium"><i class="far fa-clock"></i> {{ $review->created_at->diffForHumans() }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Related Products --}}
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold font-heading text-gray-900">You May Also Like</h2>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                @foreach($relatedProducts as $related)
                <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden relative">
                    
                    @if($related->sale_price && $related->regular_price > $related->sale_price)
                        <div class="absolute top-2 left-2 z-10 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-md">
                            -{{ round((($related->regular_price - $related->sale_price)/$related->regular_price)*100) }}%
                        </div>
                    @endif

                    <a href="{{ $cleanDomain ? $baseUrl.'/product/'.$related->slug : route('shop.product.details', [$client->slug, $related->slug]) }}" class="relative aspect-square bg-gray-50 overflow-hidden block">
                        <img src="{{ asset('storage/' . $related->thumbnail) }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition-colors"></div>
                    </a>
                    <div class="p-4 flex flex-col flex-1">
                        <h3 class="font-bold text-gray-800 text-sm mb-1 leading-snug line-clamp-2 group-hover:text-primary transition-colors">
                            <a href="{{ $cleanDomain ? $baseUrl.'/product/'.$related->slug : route('shop.product.details', [$client->slug, $related->slug]) }}">{{ $related->name }}</a>
                        </h3>
                        <div class="mt-auto pt-2">
                            <span class="font-bold text-primary text-lg">৳{{ number_format($related->sale_price ?? $related->regular_price) }}</span>
                            @if($related->sale_price)
                                <span class="text-xs text-gray-400 line-through ml-1">৳{{ number_format($related->regular_price) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </main>


    {{-- 🔥 Glassmorphism Mobile Sticky Nav --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-lg border-t border-gray-200/50 p-3 z-40 shadow-[0_-10px_30px_rgba(0,0,0,0.05)] pb-safe">
        <div class="flex gap-3 max-w-md mx-auto">
            <button type="button" @click="showChatOptions = true"
               class="flex-1 bg-white border border-gray-200 text-gray-700 py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm shadow-sm active:bg-gray-50 transition transform active:scale-95">
                <i class="fas fa-comment-dots text-primary"></i> Chat
            </button>

            <a :href="'{{ $cleanDomain ? $baseUrl.'/checkout/'.$product->slug : route('shop.checkout', [$client->slug, $product->slug]) }}' + '?qty=1' + (selectedColor ? '&color=' + selectedColor : '') + (selectedSize ? '&size=' + selectedSize : '')" 
               class="flex-[2] bg-primary hover:bg-primaryDark text-white py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm shadow-lg shadow-blue-500/30 active:scale-95 transition transform">
                <i class="fas fa-shopping-cart text-lg"></i> Order Now
            </a>
        </div>
    </div>

    {{-- Video Modal --}}
    <div x-show="showVideoModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 p-4 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="relative w-full max-w-5xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-gray-800" @click.away="showVideoModal = false; activeVideoUrl = ''">
            <button @click="showVideoModal = false; activeVideoUrl = ''" class="absolute top-4 right-4 z-50 bg-white/10 hover:bg-white/20 text-white w-10 h-10 flex items-center justify-center rounded-full backdrop-blur transition transform hover:scale-110">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="video-wrapper">
                <iframe :src="activeVideoUrl" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    {{-- Zoom Modal --}}
    <div x-show="showZoomModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center bg-white/95 backdrop-blur-sm p-4"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative w-full h-full flex items-center justify-center" @click.away="showZoomModal = false">
            <button @click="showZoomModal = false" class="absolute top-6 right-6 z-50 bg-gray-100 hover:bg-gray-200 text-gray-800 w-12 h-12 flex items-center justify-center rounded-full shadow-lg transition transform hover:scale-110">
                <i class="fas fa-times text-xl"></i>
            </button>
            <img :src="mainImage" class="max-w-full max-h-full object-contain cursor-zoom-out shadow-2xl rounded-2xl" @click="showZoomModal = false"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-90 opacity-0" x-transition:enter-end="scale-100 opacity-100">
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


    {{-- 🔥 Chat Options Action Sheet / Modal --}}
    <div x-show="showChatOptions" x-cloak class="fixed inset-0 z-[110] flex items-end md:items-center justify-center sm:p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" 
             @click="showChatOptions = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        
        <div class="relative w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden transform transition-all pb-safe"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">
            
            <div class="p-6 md:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold font-heading text-gray-900">How do you want to chat?</h3>
                    <button @click="showChatOptions = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    @if($client->fb_page_id)
                    <a href="https://m.me/{{ $client->fb_page_id }}?text={{ urlencode('Hi, I want to know about: ' . $product->name . ' - ' . $productUrl) }}" 
                       target="_blank" 
                       class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-blue-50 hover:border-blue-200 bg-blue-50/30 hover:bg-blue-50 transition group">
                        <div class="w-14 h-14 bg-gradient-to-tr from-blue-600 to-blue-400 text-white rounded-full flex items-center justify-center text-3xl shadow-md group-hover:scale-110 transition-transform">
                            <i class="fab fa-facebook-messenger"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 text-lg">Messenger</h4>
                            <p class="text-xs font-medium text-gray-500 mt-0.5">Fastest reply from our AI</p>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-500 transition transform group-hover:translate-x-1"></i>
                    </a>
                    @endif

                    @if($client->is_whatsapp_active && $client->phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text={{ urlencode('Hi, I want to know about this product: ' . $productUrl) }}" 
                       target="_blank" 
                       class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-green-50 hover:border-green-200 bg-green-50/30 hover:bg-green-50 transition group">
                        <div class="w-14 h-14 bg-gradient-to-tr from-green-500 to-green-400 text-white rounded-full flex items-center justify-center text-3xl shadow-md group-hover:scale-110 transition-transform">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 text-lg">WhatsApp</h4>
                            <p class="text-xs font-medium text-gray-500 mt-0.5">Chat with our support team</p>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 group-hover:text-green-500 transition transform group-hover:translate-x-1"></i>
                    </a>
                    @endif
                </div>
                
                <p class="text-center text-xs font-medium text-gray-400 mt-8 flex items-center justify-center gap-1.5">
                    <i class="fas fa-bolt text-yellow-400"></i> Usually replies within a few minutes.
                </p>
            </div>
        </div>
    </div>
    
</div> 
@endsection