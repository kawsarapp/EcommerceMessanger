<!DOCTYPE html>
@php
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
@endphp
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="{{ $client->meta_description ?? 'Best online shop powered by Smart Commerce.' }}">
    <meta property="og:title" content="@yield('title', $client->shop_name)">
    <meta property="og:description" content="{{ $client->meta_description }}">
    <meta property="og:image" content="{{ $client->logo ? asset('storage/' . $client->logo) : '' }}">

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    
    <title>@yield('title', $client->shop_name)</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --primary-color: {{ $client->primary_color ?? '#4f46e5' }};
            --primary-dark: color-mix(in srgb, var(--primary-color), black 10%);
            --primary-light: color-mix(in srgb, var(--primary-color), white 90%);
        }
        
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; background: #000; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
        .animate-bounce-slow { animation: bounce 3s infinite; }
        
        /* 🔥 NEW: Hide default mobile sticky nav if app-nav is active */
        .safe-area-pb { padding-bottom: calc(env(safe-area-inset-bottom) + 60px) !important; }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                colors: { 
                    primary: 'var(--primary-color)', 
                    primaryDark: 'var(--primary-dark)' 
                },

                fontFamily: { 
                    sans: ['Roboto', 'SolaimanLipi', 'Inter', 'sans-serif'], 
                    heading: ['Plus Jakarta Sans', 'Roboto', 'SolaimanLipi', 'sans-serif']
                }
            }
        }
    </script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    @if($client->pixel_id)
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $client->pixel_id }}');
    fbq('track', 'PageView');
    </script>
    @endif
</head>

<body class="bg-gray-50 text-slate-800 antialiased min-h-screen flex flex-col relative" x-data="{ showScrollTop: false }" @scroll.window="showScrollTop = (window.pageYOffset > 300)">

    @if($client->announcement_text)
        <div class="bg-primary text-white text-center py-2 text-xs font-medium px-4 relative z-50 tracking-wide">
            {{ $client->announcement_text }}
        </div>
    @endif

    {{-- 🔥 UPDATED HEADER --}}
    <header class="glass sticky top-0 z-40 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20 gap-4">
                
                {{-- Logo & Brand --}}
                <a href="{{ $baseUrl }}" class="flex items-center gap-3 group text-gray-800 hover:text-primary transition flex-shrink-0">
                    @if(request()->routeIs('shop.show'))
                        @if($client->logo)
                            <img src="{{ asset('storage/' . $client->logo) }}" alt="Logo" class="w-10 h-10 md:w-12 md:h-12 rounded-full object-cover border border-gray-100 shadow-sm">
                        @else
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-primary text-white rounded-xl flex items-center justify-center shadow-lg shadow-primary/20 transform group-hover:rotate-3 transition"><i class="fas fa-store"></i></div>
                        @endif
                    @else
                        <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl flex items-center justify-center shadow-sm group-hover:border-primary group-hover:text-primary transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </div>
                    @endif
                    <span class="font-bold text-lg md:text-xl font-heading truncate max-w-[150px] sm:max-w-[200px]">{{ $client->shop_name }}</span>
                </a>

                {{-- 🔥 NEW: Desktop Search Bar (Hidden on Mobile) --}}
                @if(request()->routeIs('shop.show'))
                <div class="hidden lg:flex flex-1 max-w-xl mx-8 relative">
                    <form action="" method="GET" class="w-full relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="w-full bg-gray-100 border border-transparent rounded-full py-2.5 pl-12 pr-4 text-sm focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                        <i class="fas fa-search absolute left-4 top-3 text-gray-400"></i>
                    </form>
                </div>
                @endif

                {{-- 🔥 NEW: Desktop Navigation Links & Action Buttons --}}
                <div class="flex items-center gap-4 flex-shrink-0">
                    
                    {{-- Page Links (Desktop Only) --}}
                    @if(isset($pages) && $pages->count() > 0)
                    <nav class="hidden lg:flex items-center gap-6 mr-4 text-sm font-medium text-gray-600">
                        @foreach($pages as $page)
                            <a href="{{ $cleanDomain ? $baseUrl.'/page/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary transition">{{ $page->title }}</a>
                        @endforeach
                    </nav>
                    @endif

                    {{-- Track Order Button (Hidden on Mobile) --}}
                    <a href="{{ $cleanDomain ? $baseUrl.'/track-order' : route('shop.track', $client->slug) }}" class="hidden md:flex items-center gap-2 text-sm font-bold text-gray-700 hover:text-primary transition bg-gray-100 hover:bg-blue-50 px-4 py-2 rounded-full">
                        <i class="fas fa-truck-fast"></i> Track Order
                    </a>

                    {{-- Messenger Button (All Devices) --}}
                    <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" 
                       class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-blue-500 text-white rounded-full flex items-center justify-center shadow-lg hover:shadow-blue-500/40 transition transform hover:scale-110 active:scale-95"
                       title="Chat on Messenger">
                        <i class="fab fa-facebook-messenger text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 w-full pb-20 md:pb-0">
        @yield('content')
    </div>

    <footer class="bg-white border-t border-gray-200 pt-10 pb-24 md:pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            
            <div class="mb-6">
                <h3 class="text-xl font-bold font-heading text-gray-900">{{ $client->shop_name }}</h3>
                @if($client->address)
                    <p class="text-sm text-gray-500 mt-2 max-w-sm mx-auto"><i class="fas fa-map-marker-alt text-primary mr-1"></i> {{ $client->address }}</p>
                @endif
                @if($client->phone)
                    <p class="text-sm font-medium text-gray-600 mt-2"><i class="fas fa-phone mr-1"></i> {{ $client->phone }}</p>
                @endif
            </div>

            @if($client->social_facebook || $client->social_instagram || $client->social_youtube)
            <div class="flex justify-center gap-4 mb-8">
                @if($client->social_facebook)
                    <a href="{{ $client->social_facebook }}" target="_blank" class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition transform hover:-translate-y-1 shadow-sm"><i class="fab fa-facebook-f"></i></a>
                @endif
                @if($client->social_instagram)
                    <a href="{{ $client->social_instagram }}" target="_blank" class="w-10 h-10 rounded-full bg-pink-50 text-pink-600 flex items-center justify-center hover:bg-gradient-to-tr hover:from-yellow-400 hover:via-pink-500 hover:to-purple-500 hover:text-white transition transform hover:-translate-y-1 shadow-sm"><i class="fab fa-instagram text-lg"></i></a>
                @endif
                @if($client->social_youtube)
                    <a href="{{ $client->social_youtube }}" target="_blank" class="w-10 h-10 rounded-full bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition transform hover:-translate-y-1 shadow-sm"><i class="fab fa-youtube"></i></a>
                @endif
            </div>
            @endif

            @if(isset($pages) && $pages->count() > 0)
            <div class="flex flex-wrap justify-center gap-4 md:gap-6 text-sm font-medium text-gray-500 mb-8">
                @foreach($pages as $page)
                    <a href="{{ $cleanDomain ? $baseUrl.'/page/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary transition">{{ $page->title }}</a>
                @endforeach
                <a href="{{ $cleanDomain ? $baseUrl.'/track-order' : route('shop.track', $client->slug) }}" class="hover:text-primary transition font-bold">Track Order</a>
            </div>
            @endif

            <div class="border-t border-gray-100 pt-8 flex flex-col items-center justify-center">
                <p class="text-xs text-gray-400">&copy; {{ date('Y') }} {{ $client->shop_name }}. All rights reserved.</p>
                <div class="flex items-center gap-2 mt-2">
                    <i class="fas fa-bolt text-yellow-400 text-xs"></i>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Powered by Smart Commerce</p>
                </div>
            </div>
        </div>
    </footer>

    {{-- 🔥 NEW: Mobile Bottom App Navigation --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-5px_20px_rgba(0,0,0,0.05)] z-50 pb-safe">
        <div class="flex justify-around items-center h-16 px-2">
            
            <a href="{{ $baseUrl }}" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-primary {{ request()->routeIs('shop.show') ? 'text-primary' : '' }}">
                <i class="fas fa-home text-lg mb-1"></i>
                <span class="text-[10px] font-bold">Home</span>
            </a>

            <a href="{{ $cleanDomain ? $baseUrl.'/track-order' : route('shop.track', $client->slug) }}" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-primary {{ request()->routeIs('shop.track') ? 'text-primary' : '' }}">
                <i class="fas fa-truck-fast text-lg mb-1"></i>
                <span class="text-[10px] font-bold">Track Order</span>
            </a>

            @if($client->is_whatsapp_active && $client->phone)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}" target="_blank" class="flex flex-col items-center justify-center w-full h-full relative -top-3">
                <div class="w-12 h-12 bg-green-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-green-500/40 text-2xl transform transition hover:scale-105 active:scale-95">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <span class="text-[10px] font-bold text-gray-600 mt-1">WhatsApp</span>
            </a>
            @else
            <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="flex flex-col items-center justify-center w-full h-full relative -top-3">
                <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-blue-500/40 text-2xl transform transition hover:scale-105 active:scale-95">
                    <i class="fab fa-facebook-messenger"></i>
                </div>
                <span class="text-[10px] font-bold text-gray-600 mt-1">Chat</span>
            </a>
            @endif

            <a href="{{ $baseUrl }}" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-primary">
                <i class="fas fa-shopping-cart text-lg mb-1"></i>
                <span class="text-[10px] font-bold">Shop</span>
            </a>
        </div>
    </div>

    {{-- Scroll to Top Button (Hidden on Mobile due to Bottom Nav) --}}
    <button x-show="showScrollTop" 
            @click="window.scrollTo({top: 0, behavior: 'smooth'})"
            x-transition
            class="hidden md:flex fixed bottom-8 right-8 z-40 bg-gray-900 text-white w-12 h-12 items-center justify-center rounded-full shadow-xl hover:bg-primary transition-colors"
            style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>
</html>