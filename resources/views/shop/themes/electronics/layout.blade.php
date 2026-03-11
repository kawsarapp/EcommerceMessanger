<!DOCTYPE html>
@php
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
@endphp
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="{{ $client->meta_description ?? 'Best electronics and gadgets store.' }}">
    <meta property="og:title" content="@yield('title', $client->shop_name)">
    <meta property="og:description" content="{{ $client->meta_description }}">
    <meta property="og:image" content="{{ $client->logo ? asset('storage/' . $client->logo) : '' }}">
    
    <title>@yield('title', $client->shop_name)</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --primary-color: {{ $client->primary_color ?? '#0ea5e9' }};
            --primary-dark: color-mix(in srgb, var(--primary-color), black 15%);
        }
        
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        .tech-gradient { background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%); }
        .safe-area-pb { padding-bottom: calc(env(safe-area-inset-bottom) + 60px) !important; }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: 'var(--primary-color)', primaryDark: 'var(--primary-dark)' },
                    fontFamily: { 
                        sans: ['Inter', 'sans-serif'], 
                        heading: ['Space Grotesk', 'sans-serif'] // 🔥 Techy Font
                    }
                }
            }
        }
    </script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    {{-- 🔥 Google Fonts for Electronics Theme --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @if($client->pixel_id)
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $client->pixel_id }}'); fbq('track', 'PageView');
    </script>
    @endif
</head>

<body class="bg-[#f8fafc] text-slate-800 antialiased min-h-screen flex flex-col relative" x-data="{ showScrollTop: false }" @scroll.window="showScrollTop = (window.pageYOffset > 300)">

    @if($client->announcement_text)
        <div class="bg-primary text-white text-center py-2 text-xs font-bold px-4 relative z-50 uppercase tracking-widest">
            {{ $client->announcement_text }}
        </div>
    @endif

    {{-- 🔥 Dark Tech Header --}}
    <header class="tech-gradient sticky top-0 z-40 transition-all duration-300 shadow-lg border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20 gap-4">
                
                {{-- Logo & Brand --}}
                <a href="{{ $baseUrl }}" class="flex items-center gap-3 group text-white hover:text-primary transition flex-shrink-0">
                    @if(request()->routeIs('shop.show'))
                        @if($client->logo)
                            <img src="{{ asset('storage/' . $client->logo) }}" alt="Logo" class="w-10 h-10 md:w-12 md:h-12 rounded-lg object-cover border border-slate-700 shadow-sm">
                        @else
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-primary text-white rounded-lg flex items-center justify-center shadow-lg"><i class="fas fa-microchip text-xl"></i></div>
                        @endif
                    @else
                        <div class="w-10 h-10 bg-slate-800 border border-slate-700 rounded-lg flex items-center justify-center text-slate-300 group-hover:border-primary group-hover:text-primary transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </div>
                    @endif
                    <span class="font-bold text-lg md:text-xl font-heading tracking-wide truncate max-w-[150px] sm:max-w-[200px]">{{ $client->shop_name }}</span>
                </a>

                {{-- Desktop Search Bar --}}
                @if(request()->routeIs('shop.show'))
                <div class="hidden lg:flex flex-1 max-w-2xl mx-8 relative">
                    <form action="" method="GET" class="w-full relative flex">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search for gadgets, laptops, accessories..." class="w-full bg-slate-800/80 border border-slate-700 rounded-l-lg py-2.5 pl-12 pr-4 text-sm text-white placeholder-slate-400 focus:bg-slate-800 focus:ring-1 focus:ring-primary focus:border-primary outline-none transition">
                        <i class="fas fa-search absolute left-4 top-3 text-slate-400"></i>
                        <button type="submit" class="bg-primary hover:bg-primaryDark text-white px-6 rounded-r-lg font-bold text-sm transition">Search</button>
                    </form>
                </div>
                @endif

                {{-- Navigation Links & Action Buttons --}}
                <div class="flex items-center gap-4 flex-shrink-0">
                    @if(isset($pages) && $pages->count() > 0)
                    <nav class="hidden lg:flex items-center gap-6 mr-4 text-sm font-medium text-slate-300">
                        @foreach($pages as $page)
                            <a href="{{ $cleanDomain ? $baseUrl.'/page/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-white transition">{{ $page->title }}</a>
                        @endforeach
                    </nav>
                    @endif

                    <a href="{{ $cleanDomain ? $baseUrl.'/track-order' : route('shop.track', $client->slug) }}" class="hidden md:flex items-center gap-2 text-sm font-bold text-slate-200 hover:text-white transition bg-slate-800 hover:bg-slate-700 border border-slate-700 px-4 py-2 rounded-lg">
                        <i class="fas fa-crosshairs text-primary"></i> Track Order
                    </a>

                    {{-- Messenger --}}
                    <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" 
                       class="w-10 h-10 bg-blue-600 text-white rounded-lg flex items-center justify-center shadow-lg hover:bg-blue-500 transition transform hover:scale-105 active:scale-95"
                       title="Live Support">
                        <i class="fab fa-facebook-messenger text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 w-full pb-20 md:pb-0">
        @yield('content')
    </div>

    {{-- 🔥 Dark Tech Footer --}}
    <footer class="tech-gradient border-t border-slate-800 pt-12 pb-24 md:pb-8 mt-auto text-slate-300">
        <div class="max-w-7xl mx-auto px-4 text-center">
            
            <div class="mb-8">
                <div class="inline-flex items-center justify-center gap-2 text-2xl font-bold font-heading text-white mb-4">
                    <i class="fas fa-microchip text-primary"></i> {{ $client->shop_name }}
                </div>
                @if($client->address)
                    <p class="text-sm text-slate-400 max-w-sm mx-auto"><i class="fas fa-map-marker-alt text-primary mr-1"></i> {{ $client->address }}</p>
                @endif
                @if($client->phone)
                    <p class="text-sm font-bold text-white mt-2"><i class="fas fa-headset mr-1 text-primary"></i> {{ $client->phone }}</p>
                @endif
            </div>

            @if($client->social_facebook || $client->social_instagram || $client->social_youtube)
            <div class="flex justify-center gap-4 mb-8">
                @if($client->social_facebook)
                    <a href="{{ $client->social_facebook }}" target="_blank" class="w-10 h-10 rounded-lg bg-slate-800 text-blue-500 border border-slate-700 flex items-center justify-center hover:bg-blue-600 hover:text-white hover:border-blue-600 transition"><i class="fab fa-facebook-f"></i></a>
                @endif
                @if($client->social_instagram)
                    <a href="{{ $client->social_instagram }}" target="_blank" class="w-10 h-10 rounded-lg bg-slate-800 text-pink-500 border border-slate-700 flex items-center justify-center hover:bg-pink-600 hover:text-white hover:border-pink-600 transition"><i class="fab fa-instagram text-lg"></i></a>
                @endif
                @if($client->social_youtube)
                    <a href="{{ $client->social_youtube }}" target="_blank" class="w-10 h-10 rounded-lg bg-slate-800 text-red-500 border border-slate-700 flex items-center justify-center hover:bg-red-600 hover:text-white hover:border-red-600 transition"><i class="fab fa-youtube"></i></a>
                @endif
            </div>
            @endif

            @if(isset($pages) && $pages->count() > 0)
            <div class="flex flex-wrap justify-center gap-4 md:gap-6 text-sm font-medium mb-8">
                @foreach($pages as $page)
                    <a href="{{ $cleanDomain ? $baseUrl.'/page/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-white transition">{{ $page->title }}</a>
                @endforeach
                <a href="{{ $cleanDomain ? $baseUrl.'/track-order' : route('shop.track', $client->slug) }}" class="text-primary hover:text-white transition font-bold">Track Order</a>
            </div>
            @endif

            <div class="border-t border-slate-800 pt-8 flex flex-col items-center justify-center">
                <p class="text-xs text-slate-500">&copy; {{ date('Y') }} {{ $client->shop_name }}. All rights reserved.</p>
                <div class="flex items-center gap-1 mt-2 opacity-50">
                    <i class="fas fa-bolt text-primary text-xs"></i>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Powered by Smart Commerce</p>
                </div>
            </div>
        </div>
    </footer>

    {{-- 🔥 Mobile Bottom App Navigation (Dark Mode) --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-800 shadow-[0_-5px_20px_rgba(0,0,0,0.3)] z-50 pb-safe">
        <div class="flex justify-around items-center h-16 px-2">
            
            <a href="{{ $baseUrl }}" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-primary {{ request()->routeIs('shop.show') ? 'text-primary' : '' }}">
                <i class="fas fa-home text-lg mb-1"></i>
                <span class="text-[10px] font-bold">Home</span>
            </a>

            <a href="{{ $cleanDomain ? $baseUrl.'/track-order' : route('shop.track', $client->slug) }}" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-primary {{ request()->routeIs('shop.track') ? 'text-primary' : '' }}">
                <i class="fas fa-crosshairs text-lg mb-1"></i>
                <span class="text-[10px] font-bold">Track</span>
            </a>

            @if($client->is_whatsapp_active && $client->phone)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}" target="_blank" class="flex flex-col items-center justify-center w-full h-full relative -top-4">
                <div class="w-12 h-12 bg-[#25D366] text-white rounded-xl flex items-center justify-center shadow-lg shadow-green-900/50 text-2xl transform transition hover:scale-105 active:scale-95 rotate-3">
                    <i class="fab fa-whatsapp"></i>
                </div>
            </a>
            @else
            <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="flex flex-col items-center justify-center w-full h-full relative -top-4">
                <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-900/50 text-2xl transform transition hover:scale-105 active:scale-95 rotate-3">
                    <i class="fab fa-facebook-messenger"></i>
                </div>
            </a>
            @endif

            <a href="{{ $baseUrl }}" class="flex flex-col items-center justify-center w-full h-full text-slate-400 hover:text-primary">
                <i class="fas fa-laptop text-lg mb-1"></i>
                <span class="text-[10px] font-bold">Gadgets</span>
            </a>
        </div>
    </div>

    {{-- Scroll to Top Button --}}
    <button x-show="showScrollTop" 
            @click="window.scrollTo({top: 0, behavior: 'smooth'})"
            x-transition
            class="hidden md:flex fixed bottom-8 right-8 z-40 bg-slate-800 border border-slate-700 text-white w-12 h-12 items-center justify-center rounded-lg shadow-xl hover:bg-primary hover:border-primary transition-colors"
            style="display: none;">
        <i class="fas fa-chevron-up"></i>
    </button>

</body>
</html>