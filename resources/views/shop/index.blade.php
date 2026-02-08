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
                        accent: '#8b5cf6',
                        dark: '#1e293b',
                        light: '#f8fafc',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif']
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            -webkit-tap-highlight-color: transparent; 
            overflow-x: hidden;
        }
        [x-cloak] { display: none !important; }
        
        .prose-custom::-webkit-scrollbar { width: 6px; }
        .prose-custom::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .prose-custom::-webkit-scrollbar-thumb { background: #4f46e5; border-radius: 10px; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        .video-container { 
            position: relative; 
            padding-bottom: 56.25%; 
            height: 0; 
            overflow: hidden; 
            background: #000; 
            border-radius: 16px;
        }
        .video-container iframe { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            border: 0; 
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(37, 99, 235, 0.3);
        }
        
        .badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
            animation: pulse-slow 3s infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 200% 200%;
            animation: gradient 3s ease infinite;
        }
        
        .product-image {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .product-image:hover {
            transform: scale(1.05);
        }
        
        .category-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .category-item {
            transition: all 0.3s ease;
        }
        .category-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-btn {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            .category-nav {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800 min-h-screen flex flex-col" 
      x-data="{ 
          showModal: false,
          showVideoModal: false, 
          activeProduct: {}, 
          activeVideoUrl: '',
          mainImage: '',
          cartCount: 0,
          mobileMenuOpen: false,
          
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
          },
          toggleMobileMenu() {
              this.mobileMenuOpen = !this.mobileMenuOpen;
          }
      }">

    <!-- Header with Enhanced Design -->
    <header class="bg-white/95 backdrop-blur-md shadow-lg sticky top-0 z-50 transition-all duration-300 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 py-4 md:py-5">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4 group cursor-pointer" @click="window.location.href='/shop/{{ $client->slug }}'">
                    <div class="w-14 h-14 bg-gradient-to-br from-primary to-accent rounded-2xl flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-store text-2xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight gradient-text group-hover:scale-105 transition-transform">
                            {{ $client->shop_name }}
                        </h1>
                        <p class="text-xs md:text-sm text-gray-500 font-medium flex items-center gap-1">
                            <i class="fas fa-badge-check text-blue-500"></i>
                            <span>Verified Premium Seller</span>
                        </p>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                    <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-100 px-4 py-2 rounded-full">
                        <i class="fas fa-clock text-primary"></i>
                        <span>Open: 9AM - 10PM</span>
                    </div>
                    
                    <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" 
                       class="bg-gradient-to-r from-primary to-secondary text-white px-6 py-3 rounded-full text-sm font-semibold shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 transition-all duration-300 active:scale-95 flex items-center gap-2 group w-full md:w-auto justify-center">
                        <i class="fab fa-facebook-messenger text-lg group-hover:scale-110 transition-transform"></i>
                        <span class="hidden sm:inline">Message Us Now</span>
                        <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Enhanced Category Navigation -->
    <div class="bg-white border-b border-gray-100 sticky top-[72px] z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex gap-2 md:gap-4 overflow-x-auto scrollbar-hide pb-1 category-nav">
                <a href="{{ request()->url() }}" 
                   class="category-item whitespace-nowrap px-4 py-2.5 md:px-6 md:py-3 rounded-xl text-sm md:text-base font-semibold transition-all duration-300 border-2 
                   {{ !request('category') ? 'category-active border-primary shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:border-primary hover:text-primary' }}">
                   <i class="fas fa-th-large mr-1.5"></i> All Products
                </a>
                @foreach($categories as $category)
                <a href="?category={{ $category->slug }}" 
                   class="category-item whitespace-nowrap px-4 py-2.5 md:px-6 md:py-3 rounded-xl text-sm md:text-base font-semibold transition-all duration-300 border-2 
                   {{ request('category') == $category->slug ? 'category-active border-primary shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:border-primary hover:text-primary' }}">
                   <i class="fas fa-tags mr-1.5"></i> {{ $category->name }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 md:px-6 py-8 md:py-12 flex-1">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
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
                        'colors' => $product->colors ?? [],
                        'sizes' => $product->sizes ?? [],
                        'material' => $product->material,
                        'brand' => $product->brand,
                        'sku' => $product->sku
                    ];
                @endphp
                
                <div class="card-hover bg-white rounded-2xl border border-gray-100 shadow-md group cursor-pointer overflow-hidden transform transition-all duration-300"
                     @click="openProduct({{ json_encode($productData) }})">
                    <!-- Product Image Section -->
                    <div class="relative aspect-[4/5] overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100">
                        <img src="{{ asset('storage/' . $product->thumbnail) }}" 
                             class="w-full h-full object-cover product-image"
                             loading="lazy">
                        
                        <!-- Discount Badge -->
                        @if($product->sale_price && $product->regular_price > $product->sale_price)
                        <span class="badge bg-red-500 text-white">
                            -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}%
                        </span>
                        @endif
                        
                        <!-- Quick View Button -->
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <div class="bg-white text-primary px-6 py-3 rounded-full font-semibold shadow-lg flex items-center gap-2">
                                <i class="fas fa-eye"></i>
                                <span>Quick View</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="p-4">
                        <h3 class="text-sm md:text-base font-bold text-gray-900 line-clamp-2 min-h-[2.5em] leading-tight group-hover:text-primary transition-colors mb-2">
                            {{ $product->name }}
                        </h3>
                        
                        <!-- Price Section -->
                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex flex-col gap-1">
                                <span class="text-lg md:text-xl font-bold text-gray-900">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                                @if($product->sale_price && $product->regular_price > $product->sale_price)
                                    <span class="text-xs text-gray-400 line-through font-medium">৳{{ number_format($product->regular_price) }}</span>
                                @endif
                            </div>
                            
                            <!-- Add to Cart Button -->
                            <button class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white group-hover:scale-110 transition-transform duration-200 shadow-lg hover:shadow-xl">
                                <i class="fas fa-shopping-bag text-sm"></i>
                            </button>
                        </div>
                        
                        <!-- Product Meta -->
                        @if($product->colors || $product->sizes)
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                @if($product->colors)
                                    <div class="flex -space-x-1">
                                        @php $colors = is_string($product->colors) ? json_decode($product->colors, true) : $product->colors; @endphp
                                        @foreach(collect($colors)->take(3) as $color)
                                            <div class="w-4 h-4 rounded-full border-2 border-white shadow-sm" 
                                                 style="background-color: {{ $color }};"></div>
                                        @endforeach
                                        @if(collect($colors)->count() > 3)
                                            <div class="w-4 h-4 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center text-xs text-white">
                                                +{{ collect($colors)->count() - 3 }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @if($product->sizes)
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-ruler-combined mr-1"></i>
                                        {{ is_string($product->sizes) ? json_decode($product->sizes, true)[0] : $product->sizes[0] }}+
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Load More Button -->
            @if($products->hasMorePages())
            <div class="mt-12 text-center">
                <button class="bg-white text-primary px-8 py-3 rounded-full text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2 mx-auto">
                    <i class="fas fa-sync-alt"></i>
                    <span>Load More Products</span>
                </button>
            </div>
            @endif
            
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-24 text-center bg-white rounded-2xl shadow-sm">
                <div class="w-28 h-28 bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl flex items-center justify-center mb-6 text-gray-400 shadow-lg">
                    <i class="fas fa-box-open text-5xl"></i>
                </div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">No Products Found</h2>
                <p class="text-gray-500 text-base mb-6 max-w-md">We're currently updating our inventory. Please check back soon or browse other categories.</p>
                <a href="{{ request()->url() }}" class="mt-4 px-8 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-full text-sm font-semibold hover:shadow-xl transition shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i>
                    View All Products
                </a>
            </div>
        @endif
    </main>

    <!-- Enhanced Product Modal -->
    <div x-show="showModal" x-cloak 
         class="fixed inset-0 z-[60] flex items-center justify-center px-2 md:px-4 py-4 md:py-8"
         role="dialog" aria-modal="true">
        
        <div class="absolute inset-0 bg-gradient-to-br from-black/80 to-black/90 backdrop-blur-sm transition-opacity" 
             x-show="showModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="closeModal()"></div>

        <div class="relative bg-white w-full h-full md:h-auto md:max-h-[90vh] md:max-w-6xl md:rounded-3xl shadow-2xl flex flex-col md:flex-row overflow-hidden transform transition-all"
             x-show="showModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

            <button @click="closeModal()" class="absolute top-6 right-6 z-50 bg-white/90 hover:bg-white p-3 rounded-full text-gray-600 hover:text-red-500 transition shadow-xl backdrop-blur-md hover:scale-110">
                <i class="fas fa-times text-2xl"></i>
            </button>

            <!-- Image Gallery Section -->
            <div class="w-full md:w-[50%] bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col h-[45vh] md:h-auto border-r border-gray-100">
                <div class="relative flex-1 flex items-center justify-center p-8 md:p-10 overflow-hidden group">
                    <img :src="mainImage" class="max-w-full max-h-full object-contain drop-shadow-2xl transition-transform duration-500 group-hover:scale-105">
                </div>
                
                <!-- Thumbnail Gallery -->
                <div class="px-6 pb-6 pt-3" x-show="activeProduct.gallery && activeProduct.gallery.length > 0">
                    <div class="flex gap-3 overflow-x-auto scrollbar-hide py-2 justify-center md:justify-start">
                        <div @click="mainImage = activeProduct.thumbnail_url" 
                             class="w-18 h-18 rounded-xl border-3 cursor-pointer overflow-hidden transition-all duration-300 bg-white hover:scale-110"
                             :class="mainImage === activeProduct.thumbnail_url ? 'border-primary ring-2 ring-primary/30 scale-105 shadow-lg' : 'border-gray-200 hover:border-primary'">
                            <img :src="activeProduct.thumbnail_url" class="w-full h-full object-cover">
                        </div>
                        <template x-for="(img, index) in activeProduct.gallery" :key="index">
                            <div @click="mainImage = img" 
                                 class="w-18 h-18 rounded-xl border-3 cursor-pointer overflow-hidden transition-all duration-300 bg-white hover:scale-110"
                                 :class="mainImage === img ? 'border-primary ring-2 ring-primary/30 scale-105 shadow-lg' : 'border-gray-200 hover:border-primary'">
                                <img :src="img" class="w-full h-full object-cover">
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Product Details Section -->
            <div class="w-full md:w-[50%] flex flex-col h-[55vh] md:h-auto bg-white">
                <div class="p-6 md:p-8 overflow-y-auto custom-scrollbar flex-1">
                    
                    <!-- Product Header -->
                    <div class="border-b border-gray-100 pb-6 mb-6">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight mb-3" x-text="activeProduct.name"></h2>
                        
                        <div class="flex items-center gap-4 mb-4">
                            <div class="text-3xl md:text-4xl font-bold gradient-text" x-text="'৳' + activeProduct.price"></div>
                            <template x-if="activeProduct.has_discount">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-400 line-through font-medium" x-text="'৳' + activeProduct.regular_price_fmt"></span>
                                    <span class="text-xs text-red-500 font-bold flex items-center gap-1">
                                        <i class="fas fa-tag"></i>
                                        <span x-text="'Save ৳' + activeProduct.discount_amount"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Rating -->
                        <div class="flex items-center gap-2 text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="text-sm text-gray-600 ml-2">(4.8/5 - 128 reviews)</span>
                        </div>
                    </div>

                    <!-- Product Variations -->
                    <div class="space-y-6 mb-8">
                        
                        <template x-if="activeProduct.colors && activeProduct.colors.length > 0">
                            <div>
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-palette"></i>
                                    <span>Available Colors</span>
                                </h3>
                                <div class="flex flex-wrap gap-3">
                                    <template x-for="color in activeProduct.colors" :key="color">
                                        <div class="flex items-center gap-2 px-4 py-2 border-2 border-gray-200 rounded-lg shadow-sm bg-white hover:border-primary hover:shadow-md transition-all cursor-pointer group">
                                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 shadow-inner"
                                                 :style="'background-color: ' + color.toLowerCase()"></div>
                                            <span class="text-sm font-semibold text-gray-800 capitalize group-hover:text-primary transition-colors" x-text="color"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="activeProduct.sizes && activeProduct.sizes.length > 0">
                            <div>
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-ruler-combined"></i>
                                    <span>Available Sizes</span>
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="size in activeProduct.sizes" :key="size">
                                        <span class="px-4 py-2 border-2 border-gray-200 rounded-md text-sm font-semibold text-gray-700 bg-white shadow-sm hover:border-primary hover:text-primary cursor-pointer transition-all" x-text="size"></span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Product Specifications -->
                        <div class="grid grid-cols-2 gap-4 bg-gradient-to-br from-gray-50 to-gray-100 p-5 rounded-xl border border-gray-200">
                            <template x-if="activeProduct.material">
                                <div class="flex flex-col">
                                    <span class="block text-xs text-gray-500 uppercase font-bold">Material</span>
                                    <span class="text-sm font-semibold text-gray-800" x-text="activeProduct.material"></span>
                                </div>
                            </template>
                            <template x-if="activeProduct.brand">
                                <div class="flex flex-col">
                                    <span class="block text-xs text-gray-500 uppercase font-bold">Brand</span>
                                    <span class="text-sm font-semibold text-gray-800" x-text="activeProduct.brand"></span>
                                </div>
                            </template>
                            <template x-if="activeProduct.sku">
                                <div class="col-span-2">
                                    <span class="block text-xs text-gray-500 uppercase font-bold">Product Code (SKU)</span>
                                    <span class="text-sm font-mono font-bold text-primary bg-white px-3 py-1.5 rounded-lg border border-gray-200 inline-block mt-1 shadow-sm" x-text="activeProduct.sku"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Product Description -->
                    <div class="prose-custom text-gray-600 text-sm md:text-base mb-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-primary"></i>
                            <span>Product Description</span>
                        </h3>
                        <div x-html="activeProduct.description_html"></div>
                    </div>

                    <!-- Video Button -->
                    <template x-if="activeProduct.video">
                        <button @click="playVideo(activeProduct.video)" 
                           class="inline-flex items-center gap-3 text-red-600 font-bold hover:bg-red-50 px-5 py-3.5 rounded-xl transition w-full border-2 border-red-100 mb-6 group hover:border-red-300 hover:shadow-lg">
                            <div class="w-11 h-11 rounded-full bg-red-100 text-red-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-md">
                                <i class="fas fa-play text-lg"></i>
                            </div>
                            <span class="text-lg">Watch Product Video Review</span>
                        </button>
                    </template>
                </div>

                <!-- Order Button -->
                <div class="p-5 md:p-6 border-t border-gray-100 bg-gradient-to-r from-white to-gray-50 md:relative z-20">
                    <a :href="'https://m.me/' + activeProduct.fb_page + '?text=I want to buy: ' + activeProduct.name + ' (Code: ' + activeProduct.sku + ')'" 
                       target="_blank"
                       class="w-full bg-gradient-to-r from-primary to-secondary text-white py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:shadow-blue-500/30 transition-all duration-300 active:scale-[0.98] flex items-center justify-center gap-3 group shadow-lg">
                        <i class="fab fa-facebook-messenger text-2xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-lg">Order Now via Messenger</span>
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                    </a>
                    
                    <p class="text-center text-xs text-gray-500 mt-3">
                        <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                        100% Secure & Verified Purchase
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div x-show="showVideoModal" x-cloak 
         class="fixed inset-0 z-[70] flex items-center justify-center bg-gradient-to-br from-black/90 to-black/95 backdrop-blur-md p-4"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative w-full max-w-5xl bg-black rounded-2xl overflow-hidden shadow-2xl border-2 border-gray-800" @click.away="closeVideo()">
            <button @click="closeVideo()" class="absolute -top-16 right-0 md:top-6 md:right-6 z-50 text-white hover:text-red-500 transition p-3 hover:scale-110">
                <i class="fas fa-times text-3xl"></i> 
                <span class="text-base font-semibold ml-2 hidden md:inline">Close Video</span>
            </button>
            <div class="video-container">
                <iframe :src="activeVideoUrl" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <!-- Enhanced Footer -->
    <footer class="bg-gradient-to-br from-dark to-gray-900 text-white py-12 mt-auto border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div class="flex flex-col items-center md:items-start">
                    <div class="w-16 h-16 bg-gradient-to-br from-primary to-accent rounded-2xl flex items-center justify-center text-white mb-4">
                        <i class="fas fa-store text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2">{{ $client->shop_name }}</h3>
                    <p class="text-gray-300 text-center md:text-left text-sm">
                        Premium quality products delivered to your doorstep with exceptional customer service.
                    </p>
                </div>
                
                <div class="flex flex-col items-center">
                    <h4 class="text-lg font-bold mb-4 text-center">Quick Links</h4>
                    <div class="flex flex-col gap-2 text-gray-300 text-sm">
                        <a href="/" class="hover:text-white transition">Home</a>
                        <a href="/shop/{{ $client->slug }}" class="hover:text-white transition">Products</a>
                        <a href="#" class="hover:text-white transition">Categories</a>
                        <a href="#" class="hover:text-white transition">Contact</a>
                    </div>
                </div>
                
                <div class="flex flex-col items-center">
                    <h4 class="text-lg font-bold mb-4 text-center">Contact Info</h4>
                    <div class="flex flex-col gap-3 text-gray-300 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-clock"></i>
                            <span>9AM - 10PM Daily</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fab fa-facebook-messenger"></i>
                            <a href="https://m.me/{{ $client->fb_page_id }}" class="hover:text-white transition">Message Us</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-phone"></i>
                            <span>+8801XXXXXXXXX</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-6 text-center">
                <p class="text-gray-400 text-sm mb-2">
                    &copy; {{ date('Y') }} {{ $client->shop_name }}. All Rights Reserved.
                </p>
                <p class="text-xs text-gray-500">
                    Powered by <span class="text-primary font-bold">AI Commerce Bot</span> | Premium E-commerce Solution
                </p>
            </div>
        </div>
    </footer>
</body>
</html>