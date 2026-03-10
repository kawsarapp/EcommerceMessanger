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
    
    <title>@yield('title', $client->shop_name)</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --primary-color: {{ $client->primary_color ?? '#4f46e5' }};
            /* Auto Darken Primary Color for Hover State */
            --primary-dark: color-mix(in srgb, var(--primary-color), black 10%);
            /* Light Background Color */
            --primary-light: color-mix(in srgb, var(--primary-color), white 90%);
        }
        
        /* Custom Utilities */
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        
        /* Video Aspect Ratio */
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; background: #000; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }

        /* Loading Spinner */
        .loader { border-top-color: var(--primary-color); -webkit-animation: spinner 1.5s linear infinite; animation: spinner 1.5s linear infinite; }
        @keyframes spinner { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Floating Button Animation */
        .animate-bounce-slow { animation: bounce 3s infinite; }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--primary-color)',
                        primaryDark: 'var(--primary-dark)',
                        secondary: '#475569',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Plus Jakarta Sans', 'sans-serif'],
                    }
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
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id={{ $client->pixel_id }}&ev=PageView&noscript=1"
    /></noscript>
    @endif
</head>

<body class="bg-gray-50 text-slate-800 antialiased min-h-screen flex flex-col relative" x-data="{ showScrollTop: false }" @scroll.window="showScrollTop = (window.pageYOffset > 300)">

    @if($client->announcement_text)
        <div class="bg-primary text-white text-center py-2.5 text-sm font-medium px-4 relative z-50 shadow-sm tracking-wide">
            {{ $client->announcement_text }}
        </div>
    @endif

    <header class="glass sticky top-0 z-40 transition-all duration-300 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                
                {{-- 🔥 Custom Domain Clean URL Update --}}
                <a href="{{ $baseUrl }}" class="flex items-center gap-3 group text-gray-600 hover:text-primary transition">
                    @if(request()->routeIs('shop.show'))
                        {{-- হোমপেজ হলে লোগো দেখাবে --}}
                        @if($client->logo)
                            <img src="{{ asset('storage/' . $client->logo) }}" alt="Logo" class="w-10 h-10 rounded-full object-cover border border-gray-100 shadow-sm">
                        @else
                            <div class="w-10 h-10 bg-primary text-white rounded-xl flex items-center justify-center shadow-lg shadow-primary/20 transform group-hover:rotate-3 transition"><i class="fas fa-store"></i></div>
                        @endif
                    @else
                        {{-- অন্য পেজ হলে ব্যাক বাটন দেখাবে --}}
                        <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl flex items-center justify-center shadow-sm group-hover:border-primary group-hover:text-primary transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </div>
                    @endif
                    
                    <span class="font-bold hidden sm:block text-lg font-heading text-gray-800 group-hover:text-primary transition">{{ $client->shop_name }}</span>
                </a>
                
                <h1 class="text-lg font-bold font-heading truncate max-w-[200px] sm:max-w-md text-gray-900 sm:hidden">{{ $client->shop_name }}</h1>

                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" 
                   class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center hover:bg-primaryDark shadow-lg hover:shadow-primary/40 transition transform hover:scale-110 active:scale-95"
                   title="Chat on Messenger">
                    <i class="fab fa-facebook-messenger text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="flex-1 w-full">
        @yield('content')
    </div>

    <footer class="bg-white border-t border-gray-200 pt-10 pb-8 mt-auto mb-20 md:mb-0">
        <div class="max-w-7xl mx-auto px-4 text-center">
            
            <div class="mb-6">
                <h3 class="text-xl font-bold font-heading text-gray-900">{{ $client->shop_name }}</h3>
                @if($client->address)
                    <p class="text-sm text-gray-500 mt-2 max-w-sm mx-auto"><i class="fas fa-map-marker-alt text-primary mr-1"></i> {{ $client->address }}</p>
                @endif
                @if($client->phone)
                    <p class="text-sm font-medium text-gray-600 mt-2 hover:text-primary transition"><i class="fas fa-phone mr-1"></i> {{ $client->phone }}</p>
                @endif
            </div>

            {{-- 🔥 NEW: Social Media Links --}}
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
            <div class="flex flex-wrap justify-center gap-6 text-sm font-medium text-gray-500 mb-8">
                {{-- 🔥 Custom Domain Clean URL Update --}}
                @foreach($pages as $page)
                    <a href="{{ $cleanDomain ? $baseUrl.'/page/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary transition hover:underline decoration-primary underline-offset-4">
                        {{ $page->title }}
                    </a>
                @endforeach
                <a href="{{ $cleanDomain ? $baseUrl.'/track-order' : route('shop.track', $client->slug) }}" class="hover:text-primary transition hover:underline decoration-primary underline-offset-4">Track Order</a>
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

    {{-- 🔥 NEW: Floating WhatsApp Button --}}
    @if($client->is_whatsapp_active && $client->phone)
    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text=Hello" 
       target="_blank"
       class="fixed bottom-24 left-4 md:bottom-8 md:left-8 z-40 bg-green-500 text-white w-14 h-14 flex items-center justify-center rounded-full shadow-[0_8px_30px_rgba(34,197,94,0.4)] hover:bg-green-600 hover:scale-110 transition-transform duration-300 animate-bounce-slow"
       title="Chat with us on WhatsApp">
        <i class="fab fa-whatsapp text-3xl"></i>
    </a>
    @endif

    {{-- Scroll to Top Button --}}
    <button x-show="showScrollTop" 
            @click="window.scrollTo({top: 0, behavior: 'smooth'})"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-24 right-4 z-40 bg-gray-900 text-white w-12 h-12 flex items-center justify-center rounded-full shadow-xl hover:bg-primary transition-colors md:bottom-8 md:right-8"
            style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>
</html>