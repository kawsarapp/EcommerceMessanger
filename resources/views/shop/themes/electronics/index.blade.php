@extends('shop.themes.electronics.layout')

@section('title', $client->shop_name . ' | Premium Gadgets & Electronics')

@section('content')

@php
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
@endphp

{{-- Alpine Data for Index Page (Filter & Modal) --}}
<div x-data="{ 
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

    {{-- 🔥 Tech Hero Section --}}
    @if($client->banner)
        <div class="w-full h-56 md:h-80 lg:h-[400px] bg-cover bg-center relative group overflow-hidden" style="background-image: url('{{ asset('storage/' . $client->banner) }}');">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900/90 via-slate-900/50 to-transparent flex items-center">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full">
                    <h2 class="text-3xl md:text-5xl lg:text-6xl font-bold text-white font-heading tracking-tight max-w-2xl transform translate-y-4 group-hover:translate-y-0 transition-all duration-700">
                        {{ $client->meta_title ?? 'Next-Gen Gadgets & Tech Accessories' }}
                    </h2>
                    <p class="text-slate-300 mt-4 max-w-xl text-sm md:text-base opacity-0 group-hover:opacity-100 transition-opacity duration-1000 delay-100">
                        {{ $client->meta_description ?? 'Upgrade your lifestyle with our premium collection of electronics.' }}
                    </p>
                </div>
            </div>
            <div class="absolute bottom-0 w-full h-16 bg-gradient-to-t from-[#f8fafc] to-transparent"></div>
        </div>
    @else
        <div class="tech-gradient py-16 md:py-24 text-center text-white relative overflow-hidden">
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>
            <h1 class="text-4xl md:text-5xl font-bold font-heading mb-4 tracking-tight relative z-10">{{ $client->shop_name }}</h1>
            <p class="text-slate-400 max-w-xl mx-auto px-4 relative z-10">{{ $client->meta_description ?? 'Discover the latest technology and top-quality gadgets.' }}</p>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full -mt-8 relative z-20">
        
        {{-- Mobile Search & Filter Bar --}}
        <div class="md:hidden flex gap-3 mb-6">
            <form action="" method="GET" class="flex-1 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search gadgets..." class="w-full bg-white border border-slate-200 rounded-lg py-3 pl-10 pr-4 text-sm focus:ring-1 focus:ring-primary focus:border-primary shadow-sm outline-none transition">
                <i class="fas fa-search absolute left-4 top-3.5 text-slate-400 text-sm"></i>
            </form>
            <button @click="filterOpen = true" class="bg-slate-800 text-white px-4 rounded-lg shadow-sm hover:bg-slate-700 transition flex items-center justify-center">
                <i class="fas fa-sliders-h text-lg"></i>
            </button>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- 🔥 Desktop Tech Sidebar --}}
            <aside class="hidden lg:block w-64 flex-shrink-0">
                <div class="sticky top-28 space-y-6">
                    <form action="" method="GET">
                        @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif

                        <div class="bg-white rounded-xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)] border border-slate-200">
                            <h3 class="font-bold text-slate-800 mb-4 text-xs uppercase tracking-widest border-b border-slate-100 pb-3 flex items-center gap-2">
                                <i class="fas fa-microchip text-primary"></i> Categories
                            </h3>
                            <div class="space-y-1 max-h-[400px] overflow-y-auto scrollbar-hide pr-2">
                                <label class="cursor-pointer block">
                                    <input type="radio" name="category" value="all" class="peer hidden" onchange="this.form.submit()" {{ !request('category') || request('category') == 'all' ? 'checked' : '' }}>
                                    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 peer-checked:bg-slate-800 peer-checked:text-white transition-colors border border-transparent">
                                        <span class="text-sm font-medium">All Gadgets</span>
                                    </div>
                                </label>
                                @foreach($categories as $category)
                                <label class="cursor-pointer block">
                                    <input type="radio" name="category" value="{{ $category->slug }}" class="peer hidden" onchange="this.form.submit()" {{ request('category') == $category->slug ? 'checked' : '' }}>
                                    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 peer-checked:bg-slate-800 peer-checked:text-white transition-colors border border-transparent group">
                                        <span class="text-sm font-medium">{{ $category->name }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 group-hover:text-slate-600 peer-checked:text-slate-300">{{ $category->products_count }}</span>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)] border border-slate-200 mt-6">
                            <h3 class="font-bold text-slate-800 mb-4 text-xs uppercase tracking-widest border-b border-slate-100 pb-3 flex items-center gap-2">
                                <i class="fas fa-filter text-primary"></i> Price Range
                            </h3>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="relative w-1/2">
                                    <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}" class="w-full py-2 px-2 text-center bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition font-mono">
                                </div>
                                <span class="text-slate-300">-</span>
                                <div class="relative w-1/2">
                                    <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}" class="w-full py-2 px-2 text-center bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition font-mono">
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg text-sm font-bold hover:bg-primaryDark transition shadow-md active:scale-95">Apply Filter</button>
                        </div>

                        @if(request()->anyFilled(['category', 'search', 'min_price', 'max_price', 'sort']))
                        <a href="{{ request()->url() }}" class="block text-center text-xs text-red-500 hover:text-white mt-4 font-bold transition flex items-center justify-center gap-1.5 border border-red-200 py-2.5 rounded-lg hover:bg-red-500">
                            <i class="fas fa-undo"></i> Reset Filters
                        </a>
                        @endif
                    </form>
                </div>
            </aside>

            <main class="flex-1">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                    <h2 class="text-xl font-bold font-heading text-slate-800 flex items-center gap-2">
                        @if(request('category') && request('category') !== 'all') 
                            {{ $categories->firstWhere('slug', request('category'))->name ?? 'Gadgets' }}
                        @else 
                            Latest Tech 
                        @endif
                        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ $products->total() }}</span>
                    </h2>
                    
                    <div class="hidden md:block w-full sm:w-auto">
                        <form action="" method="GET">
                            @foreach(request()->except('sort') as $key => $val) <input type="hidden" name="{{ $key }}" value="{{ $val }}"> @endforeach
                            <select name="sort" onchange="this.form.submit()" class="w-full sm:w-48 bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-1 focus:ring-primary focus:border-primary py-2 px-3 cursor-pointer outline-none transition font-medium">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if($products->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-5">
                        @foreach($products as $product)
                        @php
                            $hasGallery = !empty($product->gallery) && count($product->gallery) > 0;
                            $productData = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'category' => $product->category->name ?? 'Gadget',
                                'sku' => $product->sku,
                                'stock_status' => $product->stock_status ?? 'in_stock',
                                'price' => number_format($product->sale_price ?? $product->regular_price),
                                'regular_price_fmt' => number_format($product->regular_price),
                                'description_html' => $product->description,
                                'thumbnail_url' => asset('storage/' . $product->thumbnail),
                                'gallery' => $hasGallery ? collect($product->gallery)->map(fn($img) => asset('storage/' . $img)) : [],
                                'fb_page' => $client->fb_page_id,
                                'has_discount' => ($product->sale_price && $product->regular_price > $product->sale_price),
                                'colors' => $product->colors ?? [],
                                'sizes' => $product->sizes ?? [],
                                'brand' => $product->brand,
                                'checkout_url' => $client->custom_domain ? route('shop.checkout.custom', $product->slug) : route('shop.checkout', [$client->slug, $product->slug])
                            ];
                        @endphp
                        
                        {{-- 🔥 Sharp Electronics Product Card --}}
                        <div class="group bg-white rounded-xl border border-slate-200 hover:border-primary/50 shadow-sm hover:shadow-[0_8px_30px_rgba(14,165,233,0.15)] transition-all duration-300 flex flex-col overflow-hidden relative">
                            
                            <div class="absolute top-2 left-2 z-20 flex flex-col gap-1.5">
                                @if($product->sale_price && $product->regular_price > $product->sale_price)
                                    <div class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">
                                        -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}%
                                    </div>
                                @endif
                                @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                                    <div class="bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm uppercase tracking-wider">
                                        Stock Out
                                    </div>
                                @endif
                            </div>

                            <div class="relative aspect-square bg-slate-50 overflow-hidden cursor-pointer p-4 flex items-center justify-center" @click="openProduct({{ json_encode($productData) }})">
                                <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="{{ $product->name }}" class="w-full h-full object-contain mix-blend-multiply transform group-hover:scale-110 transition-transform duration-500 z-10 {{ $hasGallery ? 'group-hover:opacity-0' : '' }}">
                                
                                @if($hasGallery)
                                    <img src="{{ asset('storage/' . $product->gallery[0]) }}" class="absolute inset-0 w-full h-full object-contain mix-blend-multiply p-4 transform scale-90 group-hover:scale-105 transition-transform duration-500 z-0 opacity-0 group-hover:opacity-100">
                                @endif
                                
                                <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                                    <button class="bg-slate-900 text-white w-8 h-8 rounded-md flex items-center justify-center hover:bg-primary shadow-lg transition transform hover:-translate-y-1" title="Quick View">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="p-4 flex flex-col flex-1 border-t border-slate-100 bg-white">
                                <div class="text-[10px] text-slate-400 mb-1 font-bold uppercase tracking-widest font-mono">{{ $product->category->name ?? 'Tech' }}</div>
                                <h3 class="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 mb-3 group-hover:text-primary transition-colors">
                                    <a href="{{ $cleanDomain ? $baseUrl.'/product/'.$product->slug : route('shop.product.details', [$client->slug, $product->slug]) }}">{{ $product->name }}</a>
                                </h3>
                                
                                <div class="mt-auto flex items-end justify-between">
                                    <div>
                                        <span class="font-extrabold text-lg text-slate-900 block font-mono tracking-tight">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                                        @if($product->sale_price)
                                            <span class="text-[11px] text-slate-400 line-through font-medium">৳{{ number_format($product->regular_price) }}</span>
                                        @endif
                                    </div>
                                    <button @click="openProduct({{ json_encode($productData) }})" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center hover:bg-primary hover:text-white transition shadow-sm border border-slate-200 hover:border-primary">
                                        <i class="fas fa-cart-plus text-sm"></i>
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
                    <div class="flex flex-col items-center justify-center py-20 px-4 text-center bg-white rounded-xl shadow-sm border border-slate-200">
                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-400">
                            <i class="fas fa-robot text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-1">No Tech Found</h3>
                        <p class="text-sm text-slate-500 max-w-sm mx-auto mb-6">We couldn't find any gadgets matching your criteria.</p>
                        <a href="{{ request()->url() }}" class="px-6 py-2.5 bg-slate-900 text-white rounded-lg font-bold shadow-md hover:bg-primary transition text-sm">Clear Filters</a>
                    </div>
                @endif
            </main>
        </div>
    </div>

    {{-- Mobile Filter Sidebar (Dark Mode for Electronics) --}}
    <div x-show="filterOpen" class="fixed inset-0 z-[60] lg:hidden" x-cloak>
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="filterOpen = false" x-transition.opacity></div>
        <div class="absolute inset-y-0 right-0 max-w-[280px] w-full bg-slate-900 shadow-2xl flex flex-col transform transition-transform border-l border-slate-800" 
             x-transition:enter="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="translate-x-0" x-transition:leave-end="translate-x-full">
            
            <div class="p-5 border-b border-slate-800 flex justify-between items-center">
                <h2 class="text-lg font-bold text-white font-heading"><i class="fas fa-sliders-h text-primary mr-2"></i> Tech Filters</h2>
                <button @click="filterOpen = false" class="w-8 h-8 flex items-center justify-center bg-slate-800 border border-slate-700 rounded-lg text-slate-300 hover:text-red-400 transition"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-5 text-slate-300">
                <form action="" method="GET">
                    @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                    
                    <div class="mb-6">
                        <h4 class="font-bold mb-3 text-xs uppercase tracking-widest text-slate-500">Sort By</h4>
                        <select name="sort" class="w-full p-3 bg-slate-800 border border-slate-700 text-white rounded-lg focus:border-primary outline-none transition text-sm">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Latest Tech</option>
                            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        </select>
                    </div>

                    <div class="mb-8">
                        <h4 class="font-bold mb-3 text-xs uppercase tracking-widest text-slate-500">Categories</h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                                <input type="radio" name="category" value="all" class="accent-primary" {{ !request('category') ? 'checked' : '' }}>
                                <span class="text-sm">All Gadgets</span>
                            </label>
                            @foreach($categories as $category)
                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-800 transition cursor-pointer">
                                <input type="radio" name="category" value="{{ $category->slug }}" class="accent-primary" {{ request('category') == $category->slug ? 'checked' : '' }}>
                                <span class="text-sm">{{ $category->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primaryDark text-white py-3 rounded-lg font-bold shadow-lg transition">Apply</button>
                </form>
            </div>
        </div>
    </div>

    {{-- 🔥 Quick View Modal (Tech Style) --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-900/90 backdrop-blur-md transition-opacity" @click="closeModal()"></div>
        <div class="relative bg-white w-full max-w-4xl max-h-[90vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row transform transition-all"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <button @click="closeModal()" class="absolute top-3 right-3 z-50 bg-white/90 border border-slate-200 w-9 h-9 flex items-center justify-center rounded-lg hover:bg-red-50 hover:border-red-200 text-slate-600 hover:text-red-500 shadow-sm transition"><i class="fas fa-times"></i></button>

            {{-- Image Section --}}
            <div class="w-full md:w-1/2 bg-slate-50 flex flex-col relative border-r border-slate-200">
                <div class="flex-1 flex items-center justify-center p-8 relative">
                    <img :src="mainImage" class="max-h-[250px] md:max-h-[300px] object-contain drop-shadow-xl relative z-10 transition-all duration-300 mix-blend-multiply">
                </div>
                <div class="p-4 flex gap-3 overflow-x-auto bg-white border-t border-slate-200 scrollbar-hide" x-show="activeProduct.gallery && activeProduct.gallery.length > 0">
                    <img :src="activeProduct.thumbnail_url" @click="mainImage = activeProduct.thumbnail_url" class="w-14 h-14 object-cover rounded-lg border-2 cursor-pointer transition" :class="mainImage == activeProduct.thumbnail_url ? 'border-primary' : 'border-transparent opacity-60'">
                    <template x-for="img in activeProduct.gallery">
                        <img :src="img" @click="mainImage = img" class="w-14 h-14 object-cover rounded-lg border-2 cursor-pointer transition" :class="mainImage == img ? 'border-primary' : 'border-transparent opacity-60'">
                    </template>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="w-full md:w-1/2 flex flex-col bg-white overflow-y-auto">
                <div class="p-6 md:p-8 flex-1">
                    
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-primary text-[10px] font-bold tracking-widest font-mono uppercase bg-blue-50 px-2 py-1 rounded" x-text="activeProduct.category"></span>
                        <span x-show="activeProduct.stock_status == 'out_of_stock'" class="text-red-600 text-[10px] font-bold uppercase tracking-widest font-mono flex items-center gap-1"><i class="fas fa-ban"></i> Stock Out</span>
                        <span x-show="activeProduct.stock_status != 'out_of_stock'" class="text-green-600 text-[10px] font-bold uppercase tracking-widest font-mono flex items-center gap-1"><i class="fas fa-check"></i> Available</span>
                    </div>

                    <h2 class="text-2xl font-bold font-heading text-slate-900 mb-1 leading-tight" x-text="activeProduct.name"></h2>
                    <p class="text-xs text-slate-400 mb-4 font-mono font-medium bg-slate-50 inline-block px-2 py-1 rounded border border-slate-100" x-show="activeProduct.sku" x-text="'SKU: ' + activeProduct.sku"></p>

                    <div class="flex items-end gap-3 mb-6 pb-6 border-b border-slate-100">
                        <span class="text-3xl font-extrabold text-slate-900 font-mono tracking-tighter" x-text="'৳' + activeProduct.price"></span>
                        <div class="flex flex-col mb-1" x-show="activeProduct.has_discount">
                            <span class="text-xs text-slate-400 line-through font-mono" x-text="'৳' + activeProduct.regular_price_fmt"></span>
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div x-show="activeProduct.colors && activeProduct.colors.length">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-widest block mb-2">Color Options:</span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="color in activeProduct.colors">
                                    <span class="px-3 py-1.5 bg-slate-100 text-slate-700 font-bold rounded-md text-[11px] uppercase border border-slate-200" x-text="color"></span>
                                </template>
                            </div>
                        </div>
                        <div x-show="activeProduct.sizes && activeProduct.sizes.length">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-widest block mb-2">Variants/Sizes:</span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="size in activeProduct.sizes">
                                    <span class="px-3 py-1.5 bg-slate-100 text-slate-700 font-bold rounded-md text-[11px] uppercase border border-slate-200" x-text="size"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="prose prose-sm text-slate-600 mb-6 max-w-none text-sm leading-relaxed" x-html="activeProduct.description_html"></div>
                </div>

                {{-- Action Buttons --}}
                <div class="p-5 border-t border-slate-100 bg-slate-50 sticky bottom-0 z-20 flex gap-3">
                    @if($client->is_whatsapp_active && $client->phone)
                        <a :href="'https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text=Hi, I want to know about ' + activeProduct.name" target="_blank"
                           class="flex-1 bg-white text-[#25D366] border border-slate-200 hover:border-[#25D366] py-3 rounded-lg font-bold text-center flex items-center justify-center gap-2 transition shadow-sm">
                            <i class="fab fa-whatsapp text-lg"></i> <span class="hidden sm:inline text-xs uppercase tracking-wide">WhatsApp</span>
                        </a>
                    @endif

                    <a :href="activeProduct.checkout_url" 
                       class="flex-[2] bg-slate-900 hover:bg-black text-white py-3 rounded-lg font-bold text-sm uppercase tracking-widest flex items-center justify-center gap-2 transition shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-bolt"></i> Buy Now
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection