<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $client->shop_name }} - {{ $client->shop_name }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',   // Indigo 600
                        secondary: '#4338ca', // Indigo 700
                        accent: '#ec4899',    // Pink 500
                        dark: '#0f172a',
                        light: '#f8fafc',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif']
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.5s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        }
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
        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }
        
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        .glass-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }

        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            background: #4f46e5;
            cursor: pointer;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 min-h-screen flex flex-col"
      x-data="{ 
          mobileMenuOpen: false, 
          filterOpen: false,
          showModal: false,
          activeProduct: {},
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
          }
      }">

    <header class="glass-nav sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 text-gray-600 hover:text-primary transition">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <a href="{{ request()->url() }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center text-white shadow-lg group-hover:rotate-6 transition-transform">
                        <i class="fas fa-shopping-bag text-lg md:text-xl"></i>
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-lg md:text-xl font-bold text-gray-900 leading-tight">{{ $client->shop_name }}</h1>
                        <p class="text-xs text-green-600 font-medium flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Online Store
                        </p>
                    </div>
                </a>

                <div class="hidden lg:flex flex-1 max-w-lg mx-8">
                    <form action="" method="GET" class="w-full relative group">
                        @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                        @if(request('sort')) <input type="hidden" name="sort" value="{{ request('sort') }}"> @endif
                        
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search for products..." 
                               class="w-full bg-gray-100 border-none rounded-full py-3 pl-12 pr-4 focus:ring-2 focus:ring-primary/50 focus:bg-white transition-all shadow-inner text-sm">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 group-hover:text-primary transition-colors"></i>
                    </form>
                </div>

                <div class="flex items-center gap-3 md:gap-4">
                    <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" 
                       class="hidden md:flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-full hover:bg-blue-700 transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fab fa-facebook-messenger"></i>
                        <span class="font-medium text-sm">Chat Now</span>
                    </a>
                    
                    <button class="lg:hidden p-2 text-gray-600">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="lg:hidden pb-4">
                <form action="" method="GET" class="relative">
                    @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." 
                           class="w-full bg-gray-100 border-none rounded-lg py-2.5 pl-10 pr-4 text-sm focus:ring-1 focus:ring-primary">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                </form>
            </div>
        </div>
    </header>

    <div class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <aside class="hidden lg:block w-64 flex-shrink-0">
                <div class="sticky top-24 space-y-8">
                    
                    <form action="" method="GET" id="desktopFilterForm">
                        @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="fas fa-th-large text-primary"></i> Categories
                            </h3>
                            <div class="space-y-2 max-h-60 overflow-y-auto scrollbar-hide">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="radio" name="category" value="all" onchange="this.form.submit()"
                                           class="w-4 h-4 text-primary border-gray-300 focus:ring-primary"
                                           {{ !request('category') || request('category') == 'all' ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-600 group-hover:text-primary transition">All Products</span>
                                </label>
                                @foreach($categories as $category)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="radio" name="category" value="{{ $category->slug }}" onchange="this.form.submit()"
                                           class="w-4 h-4 text-primary border-gray-300 focus:ring-primary"
                                           {{ request('category') == $category->slug ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-600 group-hover:text-primary transition">{{ $category->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mt-6">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="fas fa-tag text-primary"></i> Price Range
                            </h3>
                            <div class="flex items-center gap-2 mb-4">
                                <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}"
                                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-primary">
                                <span class="text-gray-400">-</span>
                                <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}"
                                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-primary">
                            </div>
                            <button type="submit" class="w-full bg-gray-900 text-white py-2 rounded-lg text-sm font-medium hover:bg-black transition">
                                Apply Filter
                            </button>
                        </div>
                        
                        @if(request()->anyFilled(['category', 'search', 'min_price', 'max_price', 'sort']))
                        <a href="{{ request()->url() }}" class="block text-center text-sm text-red-500 hover:text-red-700 mt-4 font-medium">
                            <i class="fas fa-times-circle"></i> Clear All Filters
                        </a>
                        @endif
                    </form>
                </div>
            </aside>

            <div x-show="filterOpen" class="fixed inset-0 z-[60] lg:hidden" x-cloak>
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="filterOpen = false"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
                
                <div class="absolute inset-y-0 right-0 max-w-xs w-full bg-white shadow-xl flex flex-col"
                     x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                    
                    <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-lg font-bold">Filters & Sort</h2>
                        <button @click="filterOpen = false" class="p-2 text-gray-500 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-5">
                        <form action="" method="GET">
                            @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                            
                            <div class="mb-6">
                                <h4 class="font-bold mb-3 text-sm uppercase text-gray-500 tracking-wider">Sort By</h4>
                                <select name="sort" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-primary">
                                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                                </select>
                            </div>

                            <div class="mb-6">
                                <h4 class="font-bold mb-3 text-sm uppercase text-gray-500 tracking-wider">Category</h4>
                                <div class="space-y-3">
                                    <label class="flex items-center gap-3">
                                        <input type="radio" name="category" value="all" class="text-primary focus:ring-primary" {{ !request('category') ? 'checked' : '' }}>
                                        <span>All Categories</span>
                                    </label>
                                    @foreach($categories as $category)
                                    <label class="flex items-center gap-3">
                                        <input type="radio" name="category" value="{{ $category->slug }}" class="text-primary focus:ring-primary" {{ request('category') == $category->slug ? 'checked' : '' }}>
                                        <span>{{ $category->name }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg shadow-primary/30">Apply Filters</button>
                        </form>
                    </div>
                </div>
            </div>

            <main class="flex-1">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">
                            @if(request('search')) Search: "{{ request('search') }}"
                            @elseif(request('category') && request('category') !== 'all') {{ $categories->firstWhere('slug', request('category'))->name ?? 'Category' }}
                            @else All Products @endif
                        </h2>
                        <p class="text-sm text-gray-500">Showing {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} results</p>
                    </div>

                    <div class="flex gap-3 w-full sm:w-auto">
                        <button @click="filterOpen = true" class="lg:hidden flex-1 flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-semibold">
                            <i class="fas fa-filter"></i> Filter
                        </button>

                        <div class="hidden lg:block">
                            <form action="" method="GET">
                                @foreach(request()->except('sort') as $key => $val)
                                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                                @endforeach
                                <select name="sort" onchange="this.form.submit()" class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5 pr-8">
                                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Sort: Newest</option>
                                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                @if($products->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
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
                        
                        <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full animate-fade-in-up">
                            <div class="relative aspect-[4/5] overflow-hidden bg-gray-100">
                                <img src="{{ asset('storage/' . $product->thumbnail) }}" 
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500"
                                     loading="lazy">
                                
                                <div class="absolute top-2 left-2 flex flex-col gap-1">
                                    @if($product->sale_price && $product->regular_price > $product->sale_price)
                                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                                        -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}%
                                    </span>
                                    @endif
                                    @if($product->created_at->diffInDays() < 7)
                                    <span class="bg-green-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">NEW</span>
                                    @endif
                                </div>

                                <div class="absolute inset-0 bg-black/10 group-hover:bg-black/20 transition-colors flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 backdrop-blur-[2px]">
                                    <button @click="openProduct({{ json_encode($productData) }})" 
                                            class="bg-white text-gray-800 w-10 h-10 rounded-full flex items-center justify-center hover:bg-primary hover:text-white transition shadow-lg transform hover:scale-110" title="Quick View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="{{ route('shop.product.details', [$client->slug, $product->slug]) }}" 
                                       class="bg-white text-gray-800 w-10 h-10 rounded-full flex items-center justify-center hover:bg-primary hover:text-white transition shadow-lg transform hover:scale-110" title="View Details">
                                        <i class="fas fa-link"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="p-4 flex flex-col flex-1">
                                <div class="text-xs text-gray-400 mb-1">{{ $product->category->name ?? 'General' }}</div>
                                <h3 class="font-semibold text-gray-800 text-sm md:text-base leading-snug line-clamp-2 mb-2 group-hover:text-primary transition-colors">
                                    <a href="{{ route('shop.product.details', [$client->slug, $product->slug]) }}">
                                        {{ $product->name }}
                                    </a>
                                </h3>
                                
                                <div class="mt-auto flex items-end justify-between">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-lg text-gray-900">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                                        @if($product->sale_price)
                                        <span class="text-xs text-gray-400 line-through">৳{{ number_format($product->regular_price) }}</span>
                                        @endif
                                    </div>
                                    <button @click="openProduct({{ json_encode($productData) }})" class="text-primary bg-primary/5 hover:bg-primary hover:text-white p-2 rounded-lg transition-colors">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $products->links('pagination::tailwind') }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" alt="No products" class="w-32 h-32 opacity-50 mb-4">
                        <h3 class="text-xl font-bold text-gray-700 mb-2">No Products Found</h3>
                        <p class="text-gray-500 max-w-sm mx-auto mb-6">We couldn't find what you're looking for. Try adjusting your search or filters.</p>
                        <a href="{{ request()->url() }}" class="px-6 py-2.5 bg-primary text-white rounded-lg font-medium shadow-lg shadow-primary/30 hover:bg-secondary transition">
                            Clear Filters
                        </a>
                    </div>
                @endif
            </main>
        </div>
    </div>

    <div x-show="showModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         role="dialog" aria-modal="true">
        
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" 
             x-show="showModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="closeModal()"></div>

        <div class="relative bg-white w-full max-w-4xl max-h-[90vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row transform transition-all"
             x-show="showModal"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            <button @click="closeModal()" class="absolute top-4 right-4 z-50 bg-white/80 p-2 rounded-full hover:bg-red-50 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="w-full md:w-1/2 bg-gray-50 flex flex-col">
                <div class="flex-1 flex items-center justify-center p-6 h-[300px] md:h-auto">
                    <img :src="mainImage" class="max-h-full max-w-full object-contain drop-shadow-md">
                </div>
                <div class="p-4 flex gap-2 overflow-x-auto bg-white border-t border-gray-100" x-show="activeProduct.gallery && activeProduct.gallery.length > 0">
                    <img :src="activeProduct.thumbnail_url" @click="mainImage = activeProduct.thumbnail_url" class="w-16 h-16 object-cover rounded-lg border-2 cursor-pointer hover:border-primary" :class="mainImage == activeProduct.thumbnail_url ? 'border-primary' : 'border-transparent'">
                    <template x-for="img in activeProduct.gallery">
                        <img :src="img" @click="mainImage = img" class="w-16 h-16 object-cover rounded-lg border-2 cursor-pointer hover:border-primary" :class="mainImage == img ? 'border-primary' : 'border-transparent'">
                    </template>
                </div>
            </div>

            <div class="w-full md:w-1/2 flex flex-col bg-white">
                <div class="p-6 md:p-8 overflow-y-auto flex-1 custom-scrollbar">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2" x-text="activeProduct.name"></h2>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-3xl font-bold text-primary" x-text="'৳' + activeProduct.price"></span>
                        <span x-show="activeProduct.has_discount" class="text-gray-400 line-through text-lg" x-text="'৳' + activeProduct.regular_price_fmt"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                        <div x-show="activeProduct.brand">
                            <span class="text-gray-500 block">Brand:</span>
                            <span class="font-semibold" x-text="activeProduct.brand"></span>
                        </div>
                        <div x-show="activeProduct.sku">
                            <span class="text-gray-500 block">Code:</span>
                            <span class="font-mono bg-gray-100 px-2 py-0.5 rounded" x-text="activeProduct.sku"></span>
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div x-show="activeProduct.colors && activeProduct.colors.length">
                            <span class="text-sm font-bold text-gray-700 block mb-2">Colors:</span>
                            <div class="flex gap-2">
                                <template x-for="color in activeProduct.colors">
                                    <span class="px-3 py-1 bg-gray-100 rounded text-sm capitalize" x-text="color"></span>
                                </template>
                            </div>
                        </div>
                        <div x-show="activeProduct.sizes && activeProduct.sizes.length">
                            <span class="text-sm font-bold text-gray-700 block mb-2">Sizes:</span>
                            <div class="flex gap-2">
                                <template x-for="size in activeProduct.sizes">
                                    <span class="px-3 py-1 border rounded text-sm font-medium" x-text="size"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="text-gray-600 text-sm mb-6 line-clamp-3" x-html="activeProduct.description_html"></div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50">
                    <a :href="'https://m.me/' + activeProduct.fb_page + '?text=I want to buy: ' + activeProduct.name + ' (Code: ' + activeProduct.sku + ')'" 
                       target="_blank"
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition shadow-lg shadow-blue-500/30">
                        <i class="fab fa-facebook-messenger text-xl"></i>
                        Order via Messenger
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-white border-t border-gray-200 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} {{ $client->shop_name }}. All rights reserved.</p>
            <p class="text-xs text-gray-400 mt-2">Powered by Smart AI Commerce</p>
        </div>
    </footer>

</body>
</html>