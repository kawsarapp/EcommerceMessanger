{{--
  📱 Reusable Mobile Bottom Navigation Bar
  Use: @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl])
  Works with all themes via CSS variable --mob-primary (defaults to primary color)
--}}
<style>
  .mob-nav{display:none}
  @media(max-width:767px){
    .mob-nav{display:flex;position:fixed;bottom:0;left:0;right:0;z-index:9999;background:rgba(255, 255, 255, 0.95);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-top:1px solid #f1f5f9;padding:6px 0 env(safe-area-inset-bottom,6px);box-shadow:0 -10px 40px rgba(0,0,0,.05)}
    .mob-nav a{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;font-size:10px;font-weight:700;color:#94a3b8;text-decoration:none;padding:4px 2px;transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);position:relative}
    .mob-nav a::after{content:'';position:absolute;bottom:-4px;left:50%;transform:translateX(-50%) scaleX(0);width:4px;height:4px;border-radius:50%;background:var(--mob-primary,#6366f1);transition:transform .3s cubic-bezier(0.4, 0, 0.2, 1)}
    .mob-nav a.active{color:var(--mob-primary,#6366f1)}
    .mob-nav a.active::after{transform:translateX(-50%) scaleX(1)}
    .mob-nav a i{font-size:20px;transition:transform .3s cubic-bezier(0.34, 1.56, 0.64, 1)}
    .mob-nav a.active i,.mob-nav a:hover i{transform:scale(1.15) translateY(-2px);color:var(--mob-primary,#6366f1)}
    .mob-search-bar{display:none;position:fixed;top:0;left:0;right:0;z-index:10000;background:#fff;padding:12px 16px;box-shadow:0 4px 20px rgba(0,0,0,.1);border-bottom:1px solid #e5e7eb}
    .mob-search-bar.open{display:flex;gap:10px;align-items:center;animation:slideDown 0.3s ease forwards}
    @keyframes slideDown{from{transform:translateY(-100%)}to{transform:translateY(0)}}
    .mob-search-bar input{flex:1;border:2px solid var(--mob-primary,#6366f1);border-radius:999px;padding:10px 16px;font-size:14px;outline:none;background:#f8fafc}
    .mob-search-bar button{background:var(--mob-primary,#6366f1);color:#fff;border:none;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:transform 0.2s}
    .mob-search-bar button:active{transform:scale(0.9)}
    main,footer{padding-bottom:calc(60px + env(safe-area-inset-bottom,0px))!important}
  }
</style>

<div class="mob-nav" x-data>
    <a href="{{$baseUrl}}" title="Home" class="{{ !request('category') && request()->route()->getName() !== 'shop.checkout' ? 'active' : '' }}">
        <i class="fas fa-home"></i>Home
    </a>

    {{-- Category Modal Trigger --}}
    <a href="#" title="Categories" @click.prevent="$dispatch('toggle-mobile-categories')" class="{{ request('category') ? 'active' : '' }}">
        <i class="fas fa-th-large"></i>Categories
    </a>
    
    @php $mobCartCount = session()->has('cart') ? count(session()->get('cart')) : 0; @endphp
    <a href="{{$clean??false ? $baseUrl.'/cart' : route('shop.cart', $client->slug)}}" title="Cart" class="relative {{ request()->route()->getName() === 'shop.cart' || request()->is('*/cart') ? 'active' : '' }}">
        <i class="fas fa-shopping-cart"></i>Cart
        @if($mobCartCount > 0)
            <span class="absolute top-0 right-3 bg-red-500 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center transform translate-x-1 -translate-y-1">{{ $mobCartCount }}</span>
        @endif
    </a>

    {{-- Track Order --}}
    <a href="{{$clean??false ? $baseUrl.'/track' : route('shop.track', $client->slug)}}" title="Track">
        <i class="fas fa-truck-fast"></i>Track
    </a>

    {{-- Messenger / Call --}}
    @if($client->fb_page_id)
    <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" title="Chat">
        <i class="fab fa-facebook-messenger"></i>Chat
    </a>
    @elseif($client->phone)
    <a href="tel:{{$client->phone}}" title="Call">
        <i class="fas fa-phone-alt"></i>Call
    </a>
    @else
    <a href="#" title="Search" @click.prevent="$dispatch('mob-search-open')">
        <i class="fas fa-search"></i>Search
    </a>
    @endif
</div>

{{-- Mobile Search Overlay --}}
<div class="mob-search-bar" x-data="{ open: false }"
    x-on:mob-search-open.window="open = true; $nextTick(() => $refs.minput.focus())"
    :class="open ? 'open' : ''"
    @keydown.escape.window="open = false">
    <button @click="open = false" style="background:#e5e7eb;color:#374151">
        <i class="fas fa-arrow-left text-sm"></i>
    </button>
    <form action="{{$baseUrl}}" method="GET" class="flex flex-1 gap-2">
        <input x-ref="minput" type="text" name="search" value="{{ request('search') }}"
            placeholder="Search products..." autocomplete="off">
        <button type="submit"><i class="fas fa-search text-sm"></i></button>
    </form>
</div>

{{-- Mobile Categories Modal (Slide Up) --}}
@php
    $navCats = isset($categories) && !empty($categories) ? $categories : app(\App\Services\Shop\ShopProductService::class)->getSidebarCategories($client->id);
@endphp
<div x-data="{ open: false }" 
     x-on:toggle-mobile-categories.window="open = !open"
     x-cloak>
     
    {{-- Backdrop --}}
    <div x-show="open" 
         x-transition.opacity 
         @click="open = false"
         class="fixed inset-0 bg-black/50 z-[9990] md:hidden"></div>

    {{-- Slide Up Drawer --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 w-full bg-white z-[9995] rounded-t-2xl shadow-2xl flex flex-col md:hidden"
         style="max-height: 80vh; padding-bottom: calc(60px + env(safe-area-inset-bottom, 0px));">
        
        {{-- Header --}}
        <div class="flex justify-between items-center p-4 border-b border-gray-100 shrink-0">
            <h3 class="text-lg font-bold text-slate-800">Shop by Category</h3>
            <button @click="open = false" class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full text-gray-500 hover:bg-red-50 hover:text-red-500 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Categories List --}}
        <div class="overflow-y-auto hide-scroll flex-1 p-4">
            <a href="{{$baseUrl}}?category=all" class="block w-full text-left py-3 px-4 mb-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg transition">
                <i class="fas fa-th-large mr-2"></i> All Products
            </a>
            
            <div class="space-y-2 mt-4">
                @foreach($navCats as $c)
                    <div x-data="{ expanded: false }" class="border border-gray-100 rounded-lg overflow-hidden bg-white shadow-sm">
                        
                        <div class="flex items-center justify-between p-1">
                            <a href="{{$baseUrl}}?category={{$c->slug}}" class="flex-1 px-3 py-2 flex items-center gap-3 text-sm font-semibold text-slate-700 hover:text-indigo-600">
                                @if($c->image)
                                    <img src="{{asset('storage/'.$c->image)}}" class="w-6 h-6 rounded-md object-cover bg-gray-50">
                                @else
                                    <div class="w-6 h-6 rounded-md bg-gray-50 text-indigo-500 flex items-center justify-center"><i class="fas fa-box"></i></div>
                                @endif
                                <span>{{ $c->name }}</span>
                            </a>
                            
                            @if($c->children->count() > 0)
                                <button @click="expanded = !expanded" class="w-10 h-10 flex items-center justify-center text-gray-400 border-l border-gray-100">
                                    <i class="fas fa-chevron-down transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                                </button>
                            @endif
                        </div>

                        {{-- Sub categories --}}
                        @if($c->children->count() > 0)
                            <div x-show="expanded" x-collapse x-cloak>
                                <div class="bg-gray-50 px-2 py-2 border-t border-gray-100">
                                    @foreach($c->children as $sub)
                                        <a href="{{$baseUrl}}?category={{$sub->slug}}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-white hover:text-indigo-600 rounded mb-1 transition">
                                            <i class="fas fa-long-arrow-alt-right text-gray-400 mr-2 text-xs"></i> {{ $sub->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
