<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $product->name }} - {{ $client->shop_name }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#4338ca',
                        accent: '#ec4899',
                        dark: '#0f172a',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .prose img { border-radius: 8px; max-width: 100%; }
        .glass-nav { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(229, 231, 235, 0.5); }
        
        /* Video Container for Responsive Aspect Ratio */
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000; border-radius: 16px; }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 min-h-screen flex flex-col"
      x-data="{ 
          mainImage: '{{ asset('storage/' . $product->thumbnail) }}',
          selectedColor: null,
          selectedSize: null,
          qty: 1,
          showVideoModal: false,
          showZoomModal: false,
          activeVideoUrl: '',

          // Image Switcher
          setImage(url) {
              this.mainImage = url;
          },

          // Video Player Logic
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
          closeVideo() {
              this.showVideoModal = false;
              this.activeVideoUrl = '';
          }
      }">

    <header class="glass-nav sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 h-16 md:h-20 flex justify-between items-center">
            <a href="{{ route('shop.show', $client->slug) }}" class="flex items-center gap-2 group text-gray-600 hover:text-primary transition">
                <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl flex items-center justify-center shadow-sm group-hover:border-primary group-hover:text-primary transition-all">
                    <i class="fas fa-arrow-left"></i>
                </div>
                <span class="font-bold hidden sm:block">Back to Shop</span>
            </a>
            
            <h1 class="text-lg font-bold truncate max-w-[200px] sm:max-w-md text-gray-800">{{ $client->shop_name }}</h1>

            <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 shadow-lg hover:shadow-blue-500/30 transition transform hover:scale-110">
                <i class="fab fa-facebook-messenger text-xl"></i>
            </a>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-20 md:mb-0">
        
        <nav class="flex text-sm text-gray-500 mb-6 overflow-x-auto whitespace-nowrap pb-2">
            <a href="{{ route('shop.show', $client->slug) }}" class="hover:text-primary transition">Home</a>
            <span class="mx-2 text-gray-300">/</span>
            <span class="text-gray-900 font-medium truncate">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
            
            <div class="space-y-4">
                <div class="aspect-square bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 relative group cursor-zoom-in"
                     @click="showZoomModal = true">
                    
                    <img :src="mainImage" class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-500">
                    
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                        <div class="bg-black/50 text-white p-3 rounded-full backdrop-blur-sm">
                            <i class="fas fa-search-plus text-xl"></i>
                        </div>
                    </div>

                    @if($product->sale_price && $product->regular_price > $product->sale_price)
                    <span class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg animate-pulse">
                        -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}% OFF
                    </span>
                    @endif
                </div>

                @if($product->gallery)
                <div class="flex gap-3 overflow-x-auto scrollbar-hide py-2">
                    <div @click="setImage('{{ asset('storage/' . $product->thumbnail) }}')" 
                         class="w-20 h-20 flex-shrink-0 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1"
                         :class="mainImage === '{{ asset('storage/' . $product->thumbnail) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200 hover:border-gray-300'">
                        <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-full h-full object-cover rounded-lg">
                    </div>
                    @foreach($product->gallery as $img)
                    <div @click="setImage('{{ asset('storage/' . $img) }}')" 
                         class="w-20 h-20 flex-shrink-0 rounded-xl border-2 cursor-pointer overflow-hidden bg-white p-1"
                         :class="mainImage === '{{ asset('storage/' . $img) }}' ? 'border-primary ring-2 ring-primary/20 scale-95' : 'border-gray-200 hover:border-gray-300'">
                        <img src="{{ asset('storage/' . $img) }}" class="w-full h-full object-cover rounded-lg">
                    </div>
                    @endforeach
                </div>
                @endif

                @if($product->video_url)
                <button @click="playVideo('{{ $product->video_url }}')" 
                        class="w-full bg-red-50 text-red-600 border border-red-200 py-3 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-red-100 transition shadow-sm">
                    <i class="fas fa-play-circle text-2xl"></i>
                    <span>Watch Product Video</span>
                </button>
                @endif
            </div>

            <div class="flex flex-col">
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 h-full relative overflow-hidden">
                    
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>

                    <div class="mb-4 relative z-10">
                        <span class="text-primary text-xs font-bold tracking-wider uppercase bg-primary/10 px-2 py-1 rounded-md">{{ $product->category->name ?? 'General' }}</span>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-3 leading-snug">{{ $product->name }}</h1>
                    </div>

                    <div class="flex items-end gap-3 mb-6 pb-6 border-b border-gray-100 relative z-10">
                        <span class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                        @if($product->sale_price && $product->regular_price > $product->sale_price)
                        <div class="flex flex-col mb-1">
                            <span class="text-sm text-gray-400 line-through font-medium">৳{{ number_format($product->regular_price) }}</span>
                            <span class="text-xs text-green-600 font-bold">Save ৳{{ number_format($product->regular_price - $product->sale_price) }}</span>
                        </div>
                        @endif
                        
                        <div class="ml-auto">
                            @if($product->stock_quantity > 0)
                                <span class="bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-xs font-bold uppercase flex items-center gap-1">
                                    <i class="fas fa-check-circle"></i> In Stock
                                </span>
                            @else
                                <span class="bg-red-100 text-red-700 px-3 py-1.5 rounded-lg text-xs font-bold uppercase flex items-center gap-1">
                                    <i class="fas fa-times-circle"></i> Out of Stock
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-6 mb-8 relative z-10">
                        @if($product->colors)
                        <div>
                            <label class="text-sm font-bold text-gray-900 block mb-3">Select Color</label>
                            <div class="flex flex-wrap gap-3">
                                @php $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors; @endphp
                                @foreach($colors as $color)
                                <button @click="selectedColor = '{{ $color }}'"
                                        class="px-4 py-2 rounded-xl border-2 text-sm font-bold transition-all flex items-center gap-2 transform active:scale-95"
                                        :class="selectedColor === '{{ $color }}' ? 'border-primary bg-primary/5 text-primary ring-2 ring-primary/20' : 'border-gray-200 hover:border-gray-300 text-gray-600'">
                                    <span class="w-3 h-3 rounded-full border border-gray-300 shadow-sm" style="background-color: {{ strtolower($color) }}"></span>
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
                                        class="min-w-[3rem] h-12 px-3 rounded-xl border-2 text-sm font-bold flex items-center justify-center transition-all transform active:scale-95"
                                        :class="selectedSize === '{{ $size }}' ? 'border-primary bg-primary text-white shadow-lg shadow-primary/30' : 'border-gray-200 hover:border-gray-300 text-gray-600 hover:bg-gray-50'">
                                    {{ $size }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-8 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        @if($product->brand)
                        <div>
                            <span class="text-gray-400 text-xs uppercase font-bold tracking-wider">Brand</span>
                            <p class="font-semibold text-gray-800">{{ $product->brand }}</p>
                        </div>
                        @endif
                        @if($product->sku)
                        <div>
                            <span class="text-gray-400 text-xs uppercase font-bold tracking-wider">SKU</span>
                            <p class="font-mono text-gray-800 text-sm bg-white inline-block px-2 rounded border border-gray-200">{{ $product->sku }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="prose-custom text-gray-600 text-sm leading-relaxed mb-8">
                        <h3 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <i class="fas fa-align-left text-primary"></i> Product Details
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            {!! $product->description ?? $product->short_description !!}
                        </div>
                    </div>

                    <div class="hidden md:flex gap-4 mt-auto">
                        <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('Hi, I want to buy: {{ $product->name }} (Code: {{ $product->sku }})' + (selectedColor ? ' Color: '+selectedColor : '') + (selectedSize ? ' Size: '+selectedSize : ''))" 
                           target="_blank"
                           class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold text-lg hover:shadow-xl hover:shadow-blue-500/30 transition transform hover:-translate-y-1 text-center flex items-center justify-center gap-3 active:scale-95">
                            <i class="fab fa-facebook-messenger text-2xl"></i>
                            Order on Messenger
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-3 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] pb-safe safe-area-pb">
        <div class="flex gap-3">
            <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('Hi, I have a question about: {{ $product->name }}')" 
               target="_blank"
               class="flex-1 bg-gray-100 text-gray-700 py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm active:bg-gray-200">
                <i class="fas fa-comment-dots text-lg"></i> Chat
            </a>
            <a :href="'https://m.me/{{ $client->fb_page_id }}?text=' + encodeURIComponent('I want to buy: {{ $product->name }} (Code: {{ $product->sku }})' + (selectedColor ? ' Color: '+selectedColor : '') + (selectedSize ? ' Size: '+selectedSize : ''))" 
               target="_blank"
               class="flex-[2] bg-blue-600 text-white py-3.5 rounded-xl font-bold text-center flex items-center justify-center gap-2 text-sm shadow-lg shadow-blue-500/30 active:scale-95 transition">
                <i class="fab fa-facebook-messenger text-lg"></i> Buy Now
            </a>
        </div>
    </div>

    <div x-show="showVideoModal" x-cloak 
         class="fixed inset-0 z-[70] flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative w-full max-w-4xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-gray-800" @click.away="closeVideo()">
            <button @click="closeVideo()" class="absolute top-4 right-4 z-50 bg-white/10 hover:bg-white/20 text-white p-2 rounded-full transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="video-container">
                <iframe :src="activeVideoUrl" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <div x-show="showZoomModal" x-cloak 
         class="fixed inset-0 z-[80] flex items-center justify-center bg-white/95 p-4"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="relative w-full h-full flex items-center justify-center" @click.away="showZoomModal = false">
            <button @click="showZoomModal = false" class="absolute top-4 right-4 z-50 bg-gray-100 hover:bg-gray-200 text-gray-800 p-3 rounded-full shadow-lg transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <img :src="mainImage" class="max-w-full max-h-full object-contain cursor-zoom-out" @click="showZoomModal = false">
        </div>
    </div>

    <footer class="bg-white border-t border-gray-200 py-8 mt-auto md:mb-0">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} {{ $client->shop_name }}. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>