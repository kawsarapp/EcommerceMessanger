@extends('shop.layout')

@section('title', $client->shop_name . ' | Online Store')

@section('content')

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

    @if($client->banner)
        <div class="w-full h-48 md:h-64 lg:h-80 bg-cover bg-center relative" style="background-image: url('{{ asset('storage/' . $client->banner) }}');">
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <h2 class="text-3xl md:text-5xl font-bold text-white font-heading shadow-sm px-4 text-center">
                    {{ $client->meta_title ?? 'Welcome to Our Store' }}
                </h2>
            </div>
        </div>
    @else
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-12 md:py-16 text-center text-white mb-8">
            <h1 class="text-3xl md:text-4xl font-bold font-heading mb-2">{{ $client->shop_name }}</h1>
            <p class="text-blue-100 max-w-xl mx-auto px-4">{{ $client->meta_description ?? 'Best quality products at affordable prices.' }}</p>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        
        <div class="md:hidden flex gap-3 mb-6">
            <form action="" method="GET" class="flex-1 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="w-full bg-white border border-gray-200 rounded-lg py-2.5 pl-10 pr-4 text-sm focus:ring-primary focus:border-primary shadow-sm">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
            </form>
            <button @click="filterOpen = true" class="bg-white border border-gray-200 px-4 rounded-lg text-gray-700 shadow-sm hover:bg-gray-50 transition">
                <i class="fas fa-sliders-h"></i>
            </button>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            
            <aside class="hidden lg:block w-64 flex-shrink-0">
                <div class="sticky top-24 space-y-8">
                    <form action="" method="GET">
                        @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif

                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider border-b pb-2">Categories</h3>
                            <div class="space-y-1 max-h-[300px] overflow-y-auto scrollbar-hide">
                                <label class="cursor-pointer block">
                                    <input type="radio" name="category" value="all" class="peer hidden" onchange="this.form.submit()" {{ !request('category') || request('category') == 'all' ? 'checked' : '' }}>
                                    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 peer-checked:bg-blue-50 peer-checked:text-primary transition border border-transparent peer-checked:border-blue-100">
                                        <span class="text-sm font-medium">All Products</span>
                                        <i class="fas fa-check text-xs opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                    </div>
                                </label>
                                @foreach($categories as $category)
                                <label class="cursor-pointer block">
                                    <input type="radio" name="category" value="{{ $category->slug }}" class="peer hidden" onchange="this.form.submit()" {{ request('category') == $category->slug ? 'checked' : '' }}>
                                    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 peer-checked:bg-blue-50 peer-checked:text-primary transition border border-transparent peer-checked:border-blue-100">
                                        <span class="text-sm font-medium">{{ $category->name }}</span>
                                        <span class="text-xs text-gray-500 peer-checked:text-primary bg-gray-100 peer-checked:bg-white px-2 py-0.5 rounded-full">{{ $category->products_count }}</span>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mt-6">
                            <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider border-b pb-2">Price Range</h3>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="relative w-1/2">
                                    <span class="absolute left-3 top-2.5 text-gray-400 text-xs">৳</span>
                                    <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}" class="w-full pl-6 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none">
                                </div>
                                <div class="relative w-1/2">
                                    <span class="absolute left-3 top-2.5 text-gray-400 text-xs">৳</span>
                                    <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}" class="w-full pl-6 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:border-primary outline-none">
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-gray-900 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-black transition shadow-lg shadow-gray-500/20">Apply Filter</button>
                        </div>

                        @if(request()->anyFilled(['category', 'search', 'min_price', 'max_price', 'sort']))
                        <a href="{{ request()->url() }}" class="block text-center text-sm text-red-500 hover:text-red-700 mt-4 font-medium transition">
                            <i class="fas fa-trash-alt mr-1"></i> Clear Filters
                        </a>
                        @endif
                    </form>
                </div>
            </aside>

            <main class="flex-1">
                
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold font-heading text-gray-900">
                        @if(request('category') && request('category') !== 'all') 
                            {{ $categories->firstWhere('slug', request('category'))->name ?? 'Products' }}
                        @else 
                            Latest Collections 
                        @endif
                        <span class="text-sm font-normal text-gray-500 ml-2">({{ $products->total() }} items)</span>
                    </h2>
                    
                    <div class="hidden md:block">
                        <form action="" method="GET">
                            @foreach(request()->except('sort') as $key => $val) <input type="hidden" name="{{ $key }}" value="{{ $val }}"> @endforeach
                            <select name="sort" onchange="this.form.submit()" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-primary focus:border-primary py-2 px-3 shadow-sm cursor-pointer hover:border-gray-300 outline-none">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Sort: Newest</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if($products->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                        @foreach($products as $product)
                        @php
                            $productData = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => number_format($product->sale_price ?? $product->regular_price),
                                'regular_price_fmt' => number_format($product->regular_price),
                                'description_html' => $product->description,
                                'thumbnail_url' => asset('storage/' . $product->thumbnail),
                                'gallery' => $product->gallery ? collect($product->gallery)->map(fn($img) => asset('storage/' . $img)) : [],
                                'fb_page' => $client->fb_page_id,
                                'has_discount' => ($product->sale_price && $product->regular_price > $product->sale_price),
                                'colors' => $product->colors ?? [],
                                'sizes' => $product->sizes ?? [],
                                'brand' => $product->brand,
                                'sku' => $product->sku
                            ];
                        @endphp
                        
                        <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden relative">
                            @if($product->sale_price && $product->regular_price > $product->sale_price)
                                <div class="absolute top-3 left-3 z-10 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-md">
                                    -{{ round((($product->regular_price - $product->sale_price)/$product->regular_price)*100) }}%
                                </div>
                            @endif

                            <div class="relative aspect-[4/5] bg-gray-50 overflow-hidden cursor-pointer" @click="openProduct({{ json_encode($productData) }})">
                                <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="{{ $product->name }}" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                                
                                <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-4 group-hover:translate-y-0">
                                    <button class="bg-white text-gray-800 w-10 h-10 rounded-full flex items-center justify-center hover:bg-primary hover:text-white shadow-lg transition tooltip" title="Quick View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="{{ route('shop.product.details', [$client->slug, $product->slug]) }}" class="bg-white text-gray-800 w-10 h-10 rounded-full flex items-center justify-center hover:bg-primary hover:text-white shadow-lg transition" title="View Details">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="p-4 flex flex-col flex-1">
                                <div class="text-xs text-gray-400 mb-1 font-medium">{{ $product->category->name ?? 'General' }}</div>
                                <h3 class="font-bold text-gray-800 text-sm md:text-base leading-snug line-clamp-2 mb-2 group-hover:text-primary transition-colors">
                                    <a href="{{ route('shop.product.details', [$client->slug, $product->slug]) }}">{{ $product->name }}</a>
                                </h3>
                                
                                <div class="mt-auto flex items-end justify-between">
                                    <div>
                                        <span class="font-bold text-lg text-gray-900 block">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                                        @if($product->sale_price)
                                            <span class="text-xs text-gray-400 line-through">৳{{ number_format($product->regular_price) }}</span>
                                        @endif
                                    </div>
                                    <button @click="openProduct({{ json_encode($productData) }})" class="w-8 h-8 rounded-full bg-blue-50 text-primary flex items-center justify-center hover:bg-primary hover:text-white transition">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-12">
                        {{ $products->links('pagination::tailwind') }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-24 text-center bg-white rounded-3xl shadow-sm border border-gray-100">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-300">
                            <i class="fas fa-box-open text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">No Products Found</h3>
                        <p class="text-gray-500 max-w-sm mx-auto mb-6">Try adjusting your search or filters to find what you're looking for.</p>
                        <a href="{{ request()->url() }}" class="px-6 py-2.5 bg-gray-900 text-white rounded-lg font-medium shadow-lg hover:bg-black transition">Clear All Filters</a>
                    </div>
                @endif
            </main>
        </div>
    </div>

    <div x-show="filterOpen" class="fixed inset-0 z-[60] lg:hidden" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="filterOpen = false" x-transition.opacity></div>
        <div class="absolute inset-y-0 right-0 max-w-xs w-full bg-white shadow-2xl flex flex-col transform transition-transform" 
             x-transition:enter="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="translate-x-0" x-transition:leave-end="translate-x-full">
            
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="text-lg font-bold text-gray-800">Filter & Sort</h2>
                <button @click="filterOpen = false" class="w-8 h-8 flex items-center justify-center bg-white rounded-full text-gray-500 shadow-sm"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6">
                <form action="" method="GET">
                    @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                    
                    <div class="mb-8">
                        <h4 class="font-bold mb-3 text-sm uppercase text-gray-500">Sort By</h4>
                        <select name="sort" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-primary outline-none">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        </select>
                    </div>

                    <div class="mb-8">
                        <h4 class="font-bold mb-3 text-sm uppercase text-gray-500">Category</h4>
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

                    <button type="submit" class="w-full bg-primary text-white py-3.5 rounded-xl font-bold shadow-lg shadow-blue-500/30">View Results</button>
                </form>
            </div>
        </div>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="closeModal()"></div>
        <div class="relative bg-white w-full max-w-4xl max-h-[90vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row transform transition-all"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            
            <button @click="closeModal()" class="absolute top-4 right-4 z-50 bg-white p-2 rounded-full hover:text-red-500 shadow-md transition"><i class="fas fa-times text-lg"></i></button>

            <div class="w-full md:w-1/2 bg-gray-100 flex flex-col">
                <div class="flex-1 flex items-center justify-center p-6">
                    <img :src="mainImage" class="max-h-[300px] md:max-h-[400px] object-contain drop-shadow-xl">
                </div>
                <div class="p-4 flex gap-2 overflow-x-auto bg-white" x-show="activeProduct.gallery && activeProduct.gallery.length > 0">
                    <img :src="activeProduct.thumbnail_url" @click="mainImage = activeProduct.thumbnail_url" class="w-16 h-16 object-cover rounded-lg border-2 cursor-pointer transition" :class="mainImage == activeProduct.thumbnail_url ? 'border-primary' : 'border-transparent opacity-60'">
                    <template x-for="img in activeProduct.gallery">
                        <img :src="img" @click="mainImage = img" class="w-16 h-16 object-cover rounded-lg border-2 cursor-pointer transition" :class="mainImage == img ? 'border-primary' : 'border-transparent opacity-60'">
                    </template>
                </div>
            </div>

            <div class="w-full md:w-1/2 flex flex-col bg-white overflow-y-auto">
                <div class="p-8 flex-1">
                    <h2 class="text-2xl font-bold font-heading text-gray-900 mb-2" x-text="activeProduct.name"></h2>
                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-3xl font-bold text-primary" x-text="'৳' + activeProduct.price"></span>
                        <span x-show="activeProduct.has_discount" class="text-gray-400 line-through text-lg" x-text="'৳' + activeProduct.regular_price_fmt"></span>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div x-show="activeProduct.colors && activeProduct.colors.length">
                            <span class="text-sm font-bold text-gray-900 block mb-2">Available Colors:</span>
                            <div class="flex gap-2">
                                <template x-for="color in activeProduct.colors">
                                    <span class="px-3 py-1 bg-gray-100 rounded-md text-sm capitalize border border-gray-200" x-text="color"></span>
                                </template>
                            </div>
                        </div>
                        <div x-show="activeProduct.sizes && activeProduct.sizes.length">
                            <span class="text-sm font-bold text-gray-900 block mb-2">Sizes:</span>
                            <div class="flex gap-2">
                                <template x-for="size in activeProduct.sizes">
                                    <span class="w-10 h-10 flex items-center justify-center border border-gray-200 rounded-lg text-sm font-bold" x-text="size"></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="prose prose-sm text-gray-600 mb-6" x-html="activeProduct.description_html"></div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50 sticky bottom-0">
                    <a :href="'https://m.me/' + activeProduct.fb_page + '?text=I want to buy: ' + activeProduct.name + ' (Code: ' + activeProduct.sku + ')'" 
                       target="_blank"
                       class="w-full bg-primary hover:bg-primaryDark text-white py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition shadow-lg shadow-blue-500/30 transform hover:-translate-y-1">
                        <i class="fab fa-facebook-messenger text-2xl"></i>
                        Confirm Order
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection