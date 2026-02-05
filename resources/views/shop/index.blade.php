<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $client->shop_name }} - Premium Shop</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#1e40af',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        [x-cloak] { display: none !important; }
        
        .prose-custom::-webkit-scrollbar { width: 6px; }
        .prose-custom::-webkit-scrollbar-track { background: #f1f1f1; }
        .prose-custom::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000; }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800" 
      x-data="{ 
          showModal: false,
          showVideoModal: false, 
          activeProduct: {}, 
          activeVideoUrl: '',
          mainImage: '',
          
          openProduct(product) {
              this.activeProduct = product;
              this.mainImage = product.thumbnail_url;
              this.showModal = true;
              document.body.style.overflow = 'hidden';
          },
          closeModal() {
              this.showModal = false;
              document.body.style.overflow = 'auto';
              setTimeout(() => { this.activeProduct = {} }, 300);
          },
          playVideo(url) {
              let videoId = '';
              if (url.includes('youtube.com') || url.includes('youtu.be')) {
                  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                  const match = url.match(regExp);
                  if (match && match[2].length === 11) {
                      videoId = match[2];
                      this.activeVideoUrl = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
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

    <header class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-40 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                    <i class="fas fa-store text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold text-gray-900 leading-tight">{{ $client->shop_name }}</h1>
                    <p class="text-xs text-gray-500 font-medium">Verified Seller <i class="fas fa-check-circle text-blue-500 ml-1"></i></p>
                </div>
            </div>
            <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" 
               class="bg-primary text-white px-5 py-2.5 rounded-full text-sm font-semibold shadow-lg shadow-blue-500/30 hover:bg-secondary hover:shadow-blue-500/40 transition-all active:scale-95 flex items-center gap-2">
                <i class="fab fa-facebook-messenger text-lg"></i>
                <span class="hidden sm:inline">Message Us</span>
            </a>
        </div>
    </header>

    <div class="bg-white border-b border-gray-100 sticky top-[64px] z-30">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-1">
                <a href="{{ request()->url() }}" 
                   class="whitespace-nowrap px-5 py-2 rounded-full text-sm font-medium transition-all duration-200 border 
                   {{ !request('category') ? 'bg-gray-900 text-white border-gray-900 shadow-md' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400 hover:text-gray-900' }}">
                   <i class="fas fa-th-large mr-1.5"></i> All Items
                </a>
                @foreach($categories as $category)
                <a href="?category={{ $category->slug }}" 
                   class="whitespace-nowrap px-5 py-2 rounded-full text-sm font-medium transition-all duration-200 border 
                   {{ request('category') == $category->slug ? 'bg-gray-900 text-white border-gray-900 shadow-md' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400 hover:text-gray-900' }}">
                   {{ $category->name }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-3 md:px-4 py-6 md:py-8">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-6">
                @foreach($products as $product)
                @php
                    $gallery = $product->gallery ? collect($product->gallery)->map(fn($img) => asset('storage/' . $img)) : [];
                    $productData = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => number_format($product->sale_price ?? $product->regular_price),
                        'regular_price_fmt' => number_format($product->regular_price),
                        'description_html' => $product->description,
                        'thumbnail_url' => asset('storage/' . $product->thumbnail),
                        'gallery' => $gallery,
                        'video' => $product->video_url,
                        'fb_page' => $client->fb_page_id,
                        'has_discount' => ($product->sale_price && $product->regular_price > $product->sale_price),
                        'discount_amount' => number_format($product->regular_price - $product->sale_price),
                        // নতুন অ্যাট্রিবিউটগুলো অ্যাড করা হলো
                        'colors' => $product->colors ?? [],
                        'sizes' => $product->sizes ?? [],
                        'material' => $product->material,
                        'brand' => $product->brand,
                        'sku' => $product->sku
                    ];
                @endphp
                
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group cursor-pointer"
                     @click="openProduct({{ json_encode($productData) }})">
                    <div class="relative aspect-[4/5] overflow-hidden rounded-t-2xl bg-gray-100">
                        <img src="{{ asset('storage/' . $product->thumbnail) }}" 
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute top-2 left-2 flex flex-col gap-1">
                            @if($product->sale_price && $product->regular_price > $product->sale_price)
                            <span class="bg-red-500 text-white text-[10px] md:text-xs font-bold px-2.5 py-1 rounded-full shadow-sm backdrop-blur-sm">
                                -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}%
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 md:p-4">
                        <h3 class="text-sm md:text-base font-semibold text-gray-800 line-clamp-2 min-h-[2.5em] leading-tight group-hover:text-primary transition-colors">
                            {{ $product->name }}
                        </h3>
                        <div class="mt-3 flex items-end justify-between">
                            <div class="flex flex-col">
                                <span class="text-lg md:text-xl font-bold text-gray-900">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                                @if($product->sale_price && $product->regular_price > $product->sale_price)
                                    <span class="text-xs text-gray-400 line-through font-medium">৳{{ number_format($product->regular_price) }}</span>
                                @endif
                            </div>
                            <button class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors shadow-sm">
                                <i class="fas fa-shopping-bag text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <i class="fas fa-box-open text-4xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-700">No products found</h2>
                <a href="{{ request()->url() }}" class="mt-6 px-6 py-2 bg-gray-800 text-white rounded-full text-sm font-medium hover:bg-gray-900 transition">View All Products</a>
            </div>
        @endif
    </main>

    <div x-show="showModal" x-cloak 
         class="fixed inset-0 z-[60] flex items-center justify-center px-0 md:px-4 py-0 md:py-6"
         role="dialog" aria-modal="true">
        
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" 
             x-show="showModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="closeModal()"></div>

        <div class="relative bg-white w-full h-full md:h-auto md:max-h-[90vh] md:max-w-5xl md:rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden transform transition-all"
             x-show="showModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

            <button @click="closeModal()" class="absolute top-4 right-4 z-50 bg-white/80 hover:bg-white p-2.5 rounded-full text-gray-500 hover:text-red-500 transition shadow-md backdrop-blur-md">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="w-full md:w-[55%] bg-gray-50 flex flex-col h-[40vh] md:h-auto border-r border-gray-100">
                <div class="relative flex-1 flex items-center justify-center p-6 md:p-8 overflow-hidden group">
                    <img :src="mainImage" class="max-w-full max-h-full object-contain drop-shadow-xl transition-transform duration-300 group-hover:scale-105">
                </div>
                <div class="px-6 pb-6 pt-2" x-show="activeProduct.gallery && activeProduct.gallery.length > 0">
                    <div class="flex gap-3 overflow-x-auto scrollbar-hide py-1 justify-center md:justify-start">
                        <div @click="mainImage = activeProduct.thumbnail_url" 
                             class="w-16 h-16 rounded-xl border-2 cursor-pointer overflow-hidden transition-all duration-200 bg-white"
                             :class="mainImage === activeProduct.thumbnail_url ? 'border-primary ring-2 ring-primary/20 scale-105' : 'border-gray-200 hover:border-gray-300'">
                            <img :src="activeProduct.thumbnail_url" class="w-full h-full object-cover">
                        </div>
                        <template x-for="(img, index) in activeProduct.gallery" :key="index">
                            <div @click="mainImage = img" 
                                 class="w-16 h-16 rounded-xl border-2 cursor-pointer overflow-hidden transition-all duration-200 bg-white"
                                 :class="mainImage === img ? 'border-primary ring-2 ring-primary/20 scale-105' : 'border-gray-200 hover:border-gray-300'">
                                <img :src="img" class="w-full h-full object-cover">
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-[45%] flex flex-col h-[60vh] md:h-auto bg-white">
                <div class="p-6 md:p-8 overflow-y-auto custom-scrollbar flex-1">
                    
                    <div class="border-b border-gray-100 pb-5 mb-5">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight mb-3" x-text="activeProduct.name"></h2>
                        <div class="flex items-center gap-4">
                            <div class="text-3xl font-bold text-primary" x-text="'৳' + activeProduct.price"></div>
                            <template x-if="activeProduct.has_discount">
                                <div class="flex flex-col leading-none">
                                    <span class="text-sm text-gray-400 line-through" x-text="'৳' + activeProduct.regular_price_fmt"></span>
                                    <span class="text-xs text-red-500 font-bold" x-text="'Save ৳' + activeProduct.discount_amount"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-5 mb-6">
                        
                        <template x-if="activeProduct.colors && activeProduct.colors.length > 0">
                            <div>
                                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Available Colors</h3>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="color in activeProduct.colors" :key="color">
                                        <div class="flex items-center gap-2 px-3 py-1.5 border border-gray-200 rounded-lg shadow-sm bg-gray-50">
                                            <div class="w-4 h-4 rounded-full border border-gray-300 shadow-inner"
                                                 :style="'background-color: ' + color.toLowerCase()"></div>
                                            <span class="text-sm font-medium text-gray-700 capitalize" x-text="color"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="activeProduct.sizes && activeProduct.sizes.length > 0">
                            <div>
                                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Available Sizes</h3>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="size in activeProduct.sizes" :key="size">
                                        <span class="px-4 py-1.5 border border-gray-200 rounded-md text-sm font-semibold text-gray-700 bg-white shadow-sm" x-text="size"></span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <template x-if="activeProduct.material">
                                <div>
                                    <span class="block text-xs text-gray-400 uppercase font-bold">Material</span>
                                    <span class="text-sm font-medium text-gray-800" x-text="activeProduct.material"></span>
                                </div>
                            </template>
                            <template x-if="activeProduct.brand">
                                <div>
                                    <span class="block text-xs text-gray-400 uppercase font-bold">Brand</span>
                                    <span class="text-sm font-medium text-gray-800" x-text="activeProduct.brand"></span>
                                </div>
                            </template>
                            <template x-if="activeProduct.sku">
                                <div class="col-span-2">
                                    <span class="block text-xs text-gray-400 uppercase font-bold">Product Code (SKU)</span>
                                    <span class="text-sm font-mono text-gray-600 bg-white px-2 py-0.5 rounded border border-gray-200 inline-block mt-1" x-text="activeProduct.sku"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="prose-custom text-gray-600 text-sm md:text-base mb-8">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3 border-b pb-2">Description</h3>
                        <div x-html="activeProduct.description_html"></div>
                    </div>

                    <template x-if="activeProduct.video">
                        <button @click="playVideo(activeProduct.video)" 
                           class="inline-flex items-center gap-3 text-red-600 font-semibold hover:bg-red-50 px-4 py-3 rounded-xl transition w-full border border-red-100 mb-6 group">
                            <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-play text-sm"></i>
                            </div>
                            <span>Click to Watch Video Review</span>
                        </button>
                    </template>
                </div>

                <div class="p-4 md:p-6 border-t border-gray-100 bg-white md:relative z-20">
                    <a :href="'https://m.me/' + activeProduct.fb_page + '?text=I want to buy: ' + activeProduct.name + ' (Code: ' + activeProduct.sku + ')'" 
                       target="_blank"
                       class="w-full bg-primary text-white py-4 rounded-xl font-bold text-lg hover:bg-secondary hover:shadow-xl hover:shadow-blue-500/20 transition-all active:scale-[0.98] flex items-center justify-center gap-3">
                        <i class="fab fa-facebook-messenger text-xl"></i>
                        <span>Order Now via Messenger</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showVideoModal" x-cloak 
         class="fixed inset-0 z-[70] flex items-center justify-center bg-black/90 backdrop-blur-md p-4"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative w-full max-w-4xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-gray-800" @click.away="closeVideo()">
            <button @click="closeVideo()" class="absolute -top-12 right-0 md:top-4 md:right-4 z-50 text-white hover:text-red-500 transition p-2">
                <i class="fas fa-times text-2xl"></i> <span class="text-sm font-medium ml-1">Close</span>
            </button>
            <div class="video-container">
                <iframe :src="activeVideoUrl" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <footer class="bg-white border-t border-gray-100 py-10 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-600 font-medium mb-2">&copy; {{ date('Y') }} {{ $client->shop_name }}</p>
            <p class="text-xs text-gray-400">Powered by <span class="text-primary font-semibold">AI Commerce Bot</span></p>
        </div>
    </footer>
</body>
</html>