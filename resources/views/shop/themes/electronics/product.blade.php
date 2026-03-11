@extends('shop.themes.electronics.layout')

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

    shareProduct() {
        if (navigator.share) {
            navigator.share({
                title: '{{ addslashes($product->name) }}',
                text: 'Check out this awesome tech at {{ $client->shop_name }}!',
                url: '{{ $productUrl }}'
            }).catch(console.error);
        } else {
            navigator.clipboard.writeText('{{ $productUrl }}');
            alert('Product link copied to clipboard!');
        }
    }
}">

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-24 md:mb-12">
        
        {{-- 🔥 Tech Style Breadcrumb & Share --}}
        <div class="flex items-center justify-between mb-8 border-b border-slate-200 pb-4">
            <nav class="flex text-xs md:text-sm text-slate-500 overflow-x-auto whitespace-nowrap scrollbar-hide flex-1 font-mono tracking-wide">
                <a href="{{ $baseUrl }}" class="hover:text-primary transition uppercase"><i class="fas fa-home mr-1"></i> Store</a>
                <span class="mx-3 text-slate-300">/</span>
                <span class="text-slate-900 font-bold truncate max-w-[150px] sm:max-w-md uppercase">{{ $product->name }}</span>
            </nav>

            <button @click="shareProduct()" class="ml-4 w-9 h-9 flex items-center justify-center bg-slate-100 rounded-lg text-slate-600 hover:text-white hover:bg-slate-900 transition shadow-sm" title="Share Product">
                <i class="fas fa-share-alt text-sm"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            {{-- Product Gallery Component --}}
            <div class="lg:col-span-5 xl:col-span-5">
                @include('shop.themes.electronics.product-gallery')
            </div>
            
            {{-- Product Info Component --}}
            <div class="lg:col-span-7 xl:col-span-7">
                @include('shop.themes.electronics.product-info')
            </div>
        </div>

        {{-- 🔥 You May Also Like (Related Tech) --}}
        @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="mt-20 pt-12 border-t border-slate-200">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold font-heading text-slate-900 flex items-center gap-2">
                    <i class="fas fa-microchip text-primary"></i> Compatible Tech
                </h2>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                @foreach($relatedProducts as $related)
                <div class="group bg-white rounded-xl border border-slate-200 hover:border-primary shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden relative">
                    
                    @if($related->sale_price && $related->regular_price > $related->sale_price)
                        <div class="absolute top-2 left-2 z-10 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">
                            -{{ round((($related->regular_price - $related->sale_price)/$related->regular_price)*100) }}%
                        </div>
                    @endif

                    <a href="{{ $cleanDomain ? $baseUrl.'/product/'.$related->slug : route('shop.product.details', [$client->slug, $related->slug]) }}" class="relative aspect-square bg-slate-50 overflow-hidden block p-4">
                        <img src="{{ asset('storage/' . $related->thumbnail) }}" class="w-full h-full object-contain mix-blend-multiply transform group-hover:scale-110 transition-transform duration-500">
                    </a>
                    <div class="p-4 flex flex-col flex-1 border-t border-slate-100">
                        <h3 class="font-semibold text-slate-800 text-sm mb-2 leading-snug line-clamp-2 group-hover:text-primary transition-colors">
                            <a href="{{ $cleanDomain ? $baseUrl.'/product/'.$related->slug : route('shop.product.details', [$client->slug, $related->slug]) }}">{{ $related->name }}</a>
                        </h3>
                        <div class="mt-auto flex items-end justify-between">
                            <span class="font-extrabold text-slate-900 text-lg font-mono tracking-tight">৳{{ number_format($related->sale_price ?? $related->regular_price) }}</span>
                            @if($related->sale_price)
                                <span class="text-[10px] text-slate-400 line-through font-mono">৳{{ number_format($related->regular_price) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </main>

    {{-- 🔥 Mobile Sticky Nav (Dark Tech Version) --}}
    <div class="md:hidden fixed bottom-[60px] left-0 right-0 bg-slate-900/95 backdrop-blur-xl border-t border-slate-800 p-3 z-40 shadow-[0_-10px_30px_rgba(0,0,0,0.5)]">
        <div class="flex gap-3 max-w-md mx-auto">
            <button type="button" @click="showChatOptions = true"
               class="flex-1 bg-slate-800 border border-slate-700 text-white py-3.5 rounded-lg font-bold text-center flex items-center justify-center gap-2 text-sm shadow-sm active:scale-95 transition transform">
                <i class="fas fa-comment-dots text-primary"></i> Chat
            </button>

            <a :href="'{{ $cleanDomain ? $baseUrl.'/checkout/'.$product->slug : route('shop.checkout', [$client->slug, $product->slug]) }}' + '?qty=1' + (selectedColor ? '&color=' + selectedColor : '') + (selectedSize ? '&size=' + selectedSize : '')" 
               class="flex-[2] bg-primary hover:bg-primaryDark text-white py-3.5 rounded-lg font-bold text-center flex items-center justify-center gap-2 text-sm shadow-lg active:scale-95 transition transform uppercase tracking-wider">
                <i class="fas fa-bolt"></i> Buy Now
            </a>
        </div>
    </div>

    {{-- Video Modal (Dark) --}}
    <div x-show="showVideoModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/95 p-4 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="relative w-full max-w-5xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-slate-800" @click.away="showVideoModal = false; activeVideoUrl = ''">
            <button @click="showVideoModal = false; activeVideoUrl = ''" class="absolute top-4 right-4 z-50 bg-slate-800 hover:bg-red-500 text-white w-10 h-10 flex items-center justify-center rounded-lg transition transform hover:scale-105">
                <i class="fas fa-times"></i>
            </button>
            <div class="video-wrapper">
                <iframe :src="activeVideoUrl" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    {{-- Zoom Modal (Dark) --}}
    <div x-show="showZoomModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/95 backdrop-blur-md p-4"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative w-full h-full flex items-center justify-center" @click.away="showZoomModal = false">
            <button @click="showZoomModal = false" class="absolute top-6 right-6 z-50 bg-slate-800 hover:bg-red-500 text-white w-12 h-12 flex items-center justify-center rounded-lg shadow-lg transition transform hover:scale-105">
                <i class="fas fa-times text-xl"></i>
            </button>
            <img :src="mainImage" class="max-w-full max-h-full object-contain cursor-zoom-out drop-shadow-2xl mix-blend-screen bg-white rounded-2xl p-4" @click="showZoomModal = false"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-90 opacity-0" x-transition:enter-end="scale-100 opacity-100">
        </div>
    </div>

    {{-- 🔥 Chat Options Action Sheet (Dark Tech Style) --}}
    <div x-show="showChatOptions" x-cloak class="fixed inset-0 z-[110] flex items-end md:items-center justify-center sm:p-4">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" 
             @click="showChatOptions = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        
        <div class="relative w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden transform transition-all pb-safe"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0">
            
            <div class="p-6 md:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold font-heading text-slate-900">Connect with Support</h3>
                    <button @click="showChatOptions = false" class="text-slate-400 hover:text-red-500 bg-slate-100 rounded-lg w-8 h-8 flex items-center justify-center transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-3">
                    @if($client->fb_page_id)
                    <a href="https://m.me/{{ $client->fb_page_id }}?text={{ urlencode('Hi, I want to know about: ' . $product->name . ' - ' . $productUrl) }}" 
                       target="_blank" 
                       class="w-full flex items-center gap-4 p-4 rounded-xl border border-blue-200 bg-blue-50 hover:bg-blue-600 hover:text-white transition group">
                        <div class="w-12 h-12 bg-blue-600 text-white rounded-lg flex items-center justify-center text-2xl shadow-sm group-hover:bg-white group-hover:text-blue-600 transition-colors">
                            <i class="fab fa-facebook-messenger"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg group-hover:text-white text-blue-900">Messenger</h4>
                            <p class="text-xs font-medium opacity-70">Fastest reply from our AI</p>
                        </div>
                        <i class="fas fa-chevron-right opacity-50 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    @endif

                    @if($client->is_whatsapp_active && $client->phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text={{ urlencode('Hi, I want to know about this product: ' . $productUrl) }}" 
                       target="_blank" 
                       class="w-full flex items-center gap-4 p-4 rounded-xl border border-green-200 bg-green-50 hover:bg-[#25D366] hover:text-white transition group">
                        <div class="w-12 h-12 bg-[#25D366] text-white rounded-lg flex items-center justify-center text-2xl shadow-sm group-hover:bg-white group-hover:text-[#25D366] transition-colors">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg group-hover:text-white text-green-900">WhatsApp</h4>
                            <p class="text-xs font-medium opacity-70">Chat with human support</p>
                        </div>
                        <i class="fas fa-chevron-right opacity-50 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
</div> 
@endsection