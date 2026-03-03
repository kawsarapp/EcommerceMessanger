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
            
            <div class="space-y-4">
                <div class="aspect-square bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 relative group cursor-zoom-in"
                     @click="showZoomModal = true">
                    
                    <img :src="mainImage" class="w-full h-full object-contain p-2 md:p-6 group-hover:scale-105 transition-transform duration-500">
                    
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none bg-black/5">
                        <div class="bg-white/90 text-gray-800 p-3 rounded-full shadow-lg backdrop-blur-sm">
                            <i class="fas fa-search-plus text-xl"></i>
                        </div>
                    </div>

                    @if($product->sale_price && $product->regular_price > $product->sale_price)
                    <span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg animate-pulse">
                        -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}% OFF
                    </span>
                    @endif
                </div>

                <div class="flex gap-3 overflow-x-auto scrollbar-hide py-2">
                    <div @click="mainImage = '{{ asset('storage/' . $product->thumbnail) }}'" 
                         class="w-20 h-20 flex-shrink-0 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1 transition-all"
                         :class="mainImage === '{{ asset('storage/' . $product->thumbnail) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200 hover:border-gray-300'">
                        <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-full h-full object-cover rounded-lg">
                    </div>
                    @if($product->gallery)
                        @foreach($product->gallery as $img)
                        <div @click="mainImage = '{{ asset('storage/' . $img) }}'" 
                             class="w-20 h-20 flex-shrink-0 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1 transition-all"
                             :class="mainImage === '{{ asset('storage/' . $img) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200 hover:border-gray-300'">
                            <img src="{{ asset('storage/' . $img) }}" class="w-full h-full object-cover rounded-lg">
                        </div>
                        @endforeach
                    @endif
                </div>

                @if($product->video_url)
                <button @click="playVideo('{{ $product->video_url }}')" 
                        class="w-full bg-red-50 text-red-600 border border-red-100 py-3.5 rounded-xl font-bold flex items-center justify-center gap-3 hover:bg-red-100 hover:shadow-md transition">
                    <i class="fas fa-play-circle text-2xl animate-pulse"></i>
                    <span>Watch Product Video</span>
                </button>
                @endif
            </div>

            <div class="flex flex-col h-full">
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 relative overflow-hidden flex-1">
                    
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-40 h-40 bg-primary/5 rounded-full blur-3xl"></div>

                    <div class="relative z-10">
                        <span class="text-primary text-xs font-bold tracking-wider uppercase bg-primary/10 px-2.5 py-1 rounded-md">{{ $product->category->name ?? 'Product' }}</span>
                        <h1 class="text-2xl md:text-4xl font-bold font-heading text-gray-900 mt-4 leading-tight">{{ $product->name }}</h1>
                    </div>

                    <div class="flex items-end gap-3 my-6 pb-6 border-b border-gray-100 relative z-10">
                        <span class="text-3xl md:text-5xl font-extrabold text-gray-900 tracking-tight">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                        @if($product->sale_price && $product->regular_price > $product->sale_price)
                        <div class="flex flex-col mb-1.5">
                            <span class="text-sm text-gray-400 line-through font-medium">৳{{ number_format($product->regular_price) }}</span>
                            <span class="text-xs text-green-600 font-bold bg-green-50 px-1.5 py-0.5 rounded">Save ৳{{ number_format($product->regular_price - $product->sale_price) }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="space-y-6 mb-8 relative z-10">
                        @if($product->colors)
                        <div>
                            <label class="text-sm font-bold text-gray-900 block mb-3">Select Color</label>
                            <div class="flex flex-wrap gap-3">
                                @php $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors; @endphp
                                @foreach($colors as $color)
                                <button @click="selectedColor = '{{ $color }}'"
                                        class="px-4 py-2.5 rounded-xl border text-sm font-bold transition-all flex items-center gap-2"
                                        :class="selectedColor === '{{ $color }}' ? 'border-primary bg-primary text-white shadow-lg shadow-primary/30' : 'border-gray-200 hover:border-gray-300 text-gray-600 bg-white'">
                                    @if(selectedColor !== '{{ $color }}')
                                    <span class="w-3 h-3 rounded-full border border-gray-300" style="background-color: {{ strtolower($color) }}"></span>
                                    @else
                                    <i class="fas fa-check text-xs"></i>
                                    @endif
                                    {{ $color }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($product->sizes)
                        <div>
                            <label class="text-sm font-bold text-gray-900 block mb-3">Select Size</label>
                            <div class="flex flex-wrap gap-3">
                                @php $sizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes; @endphp
                                @foreach($sizes as $size)
                                <button @click="selectedSize = '{{ $size }}'"
                                        class="min-w-[3.5rem] h-12 px-3 rounded-xl border text-sm font-bold flex items-center justify-center transition-all"
                                        :class="selectedSize === '{{ $size }}' ? 'border-primary bg-primary text-white shadow-lg shadow-primary/30' : 'border-gray-200 hover:border-gray-300 text-gray-600 bg-white hover:bg-gray-50'">
                                    {{ $size }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="prose prose-sm md:prose-base text-gray-600 max-w-none">
                        <h3 class="font-bold text-gray-900 mb-3 text-lg">Product Details</h3>
                        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 leading-relaxed">
                            {!! $product->description ?? $product->short_description !!}
                        </div>
                    </div>

                    <div class="hidden md:flex gap-4 mt-8 pt-6 border-t border-gray-100">
                        <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('Hi, I have a question about: {{ $product->name }}')" 
                           target="_blank"
                           class="flex-1 border-2 border-gray-200 hover:border-gray-300 text-gray-700 py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-2 transition hover:bg-gray-50">
                            <i class="fas fa-comment-dots"></i> Chat
                        </a>
                        <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('I want to buy: {{ $product->name }} (Code: {{ $product->sku }})' + (selectedColor ? ' Color: '+selectedColor : '') + (selectedSize ? ' Size: '+selectedSize : ''))" 
                           target="_blank"
                           class="flex-[2] bg-primary hover:bg-primaryDark text-white py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-3 transition shadow-xl shadow-blue-500/20 transform hover:-translate-y-1">
                            <i class="fab fa-facebook-messenger text-2xl"></i> Order Now
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="mt-16">
            <h2 class="text-2xl font-bold font-heading text-gray-900 mb-6">You May Also Like</h2>
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

    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-3 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] pb-safe safe-area-pb">
        <div class="flex gap-3">
            <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('Hi, query: {{ $product->name }}')" 
               target="_blank"
               class="flex-1 bg-gray-100 text-gray-700 py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm active:bg-gray-200">
                <i class="fas fa-comment"></i> Chat
            </a>
            <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('Order: {{ $product->name }} (Code: {{ $product->sku }})' + (selectedColor ? ' Color: '+selectedColor : '') + (selectedSize ? ' Size: '+selectedSize : ''))" 
               target="_blank"
               class="flex-[2] bg-primary text-white py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm shadow-lg shadow-blue-500/30 active:scale-95 transition">
                <i class="fab fa-facebook-messenger text-lg"></i> Buy Now
            </a>
        </div>
    </div>

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
@endsection