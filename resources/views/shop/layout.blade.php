<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', $client->shop_name)</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb', 
                        primaryDark: '#1d4ed8',
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

    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        /* Responsive Video */
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; background: #000; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <header class="glass sticky top-0 z-40 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                <a href="{{ route('shop.show', $client->slug) }}" class="flex items-center gap-2 group text-gray-600 hover:text-primary transition">
                    @if(request()->routeIs('shop.show'))
                        {{-- হোমপেজ হলে লোগো দেখাবে --}}
                        @if($client->logo)
                            <img src="{{ asset('storage/' . $client->logo) }}" alt="Logo" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 bg-primary text-white rounded-xl flex items-center justify-center"><i class="fas fa-store"></i></div>
                        @endif
                    @else
                        {{-- অন্য পেজ হলে ব্যাক বাটন দেখাবে --}}
                        <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl flex items-center justify-center shadow-sm group-hover:border-primary group-hover:text-primary transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </div>
                    @endif
                    
                    <span class="font-bold hidden sm:block text-lg">{{ $client->shop_name }}</span>
                </a>
                
                <h1 class="text-lg font-bold font-heading truncate max-w-[200px] sm:max-w-md text-gray-900 sm:hidden">{{ $client->shop_name }}</h1>

                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center hover:bg-primaryDark shadow-lg hover:shadow-blue-500/30 transition transform hover:scale-110">
                    <i class="fab fa-facebook-messenger text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    @yield('content')

    <footer class="bg-white border-t border-gray-200 py-8 mt-auto mb-20 md:mb-0">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} {{ $client->shop_name }}. All rights reserved.</p>
            
            {{-- Dynamic Pages Links --}}
            @if(isset($pages) && $pages->count() > 0)
            <div class="mt-4 flex justify-center gap-4 text-sm text-gray-400">
                @foreach($pages as $page)
                    <a href="{{ route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary">{{ $page->title }}</a>
                @endforeach
            </div>
            @endif
        </div>
    </footer>

</body>
</html>