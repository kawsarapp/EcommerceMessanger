<!DOCTYPE html>
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
        <div class="bg-primary text-white text-center py-2 text-sm font-medium px-4 relative z-50">
            {{ $client->announcement_text }}
        </div>
    @endif

    <header class="glass sticky top-0 z-40 transition-all duration-300 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                
                <a href="{{ route('shop.show', $client->slug) }}" class="flex items-center gap-3 group text-gray-600 hover:text-primary transition">
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
                <h3 class="text-lg font-bold text-gray-900">{{ $client->shop_name }}</h3>
                @if($client->address)
                    <p class="text-sm text-gray-500 mt-1">{{ $client->address }}</p>
                @endif
                @if($client->phone)
                    <p class="text-sm text-gray-500 mt-1"><i class="fas fa-phone mr-1"></i> {{ $client->phone }}</p>
                @endif
            </div>

            @if(isset($pages) && $pages->count() > 0)
            <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-500 mb-6">
                @foreach($pages as $page)
                    <a href="{{ route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary transition hover:underline decoration-primary underline-offset-4">
                        {{ $page->title }}
                    </a>
                @endforeach
                <a href="{{ route('shop.track', $client->slug) }}" class="hover:text-primary transition hover:underline decoration-primary underline-offset-4">Track Order</a>
            </div>
            @endif

            <div class="border-t border-gray-100 pt-6">
                <p class="text-xs text-gray-400">&copy; {{ date('Y') }} {{ $client->shop_name }}. All rights reserved.</p>
                <p class="text-[10px] text-gray-300 mt-1 uppercase tracking-wider">Powered by Smart Commerce</p>
            </div>
        </div>
    </footer>

    <button x-show="showScrollTop" 
            @click="window.scrollTo({top: 0, behavior: 'smooth'})"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-24 right-4 z-40 bg-gray-900 text-white p-3 rounded-full shadow-xl hover:bg-primary transition-colors md:bottom-8"
            style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>
</html>