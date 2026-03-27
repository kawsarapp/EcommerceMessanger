<!DOCTYPE html>
@php
$clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
$baseUrl = $clean ? 'https://' . $clean : route('shop.show', $client->slug);
@endphp
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    {{-- TailwindCSS & AlpineJS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Fonts: Inter for UI, Hind Siliguri for Bangla, Roboto Mono for numbers --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#06b6d4" }}',
                        dark: '#030712',
                        panel: '#111827',
                        accent: '#22d3ee',
                        neon: '#00ffcc',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                        mono: ['Roboto Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#06b6d4" }};
            --mob-primary: {{ $client->primary_color ?? "#06b6d4" }};
        }
        [x-cloak] { display: none !important; }

        {{-- Dark Scrollbar --}}
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #030712; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--tw-color-primary); }

        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        {{-- Tech Theme Specific --}}
        .tech-border { border: 1px solid rgba(255,255,255,0.08); }
        .tech-glow { transition: all 0.3s ease; }
        .tech-glow:hover { box-shadow: 0 0 30px -5px var(--tw-color-primary); border-color: var(--tw-color-primary); }
        .tech-gradient { background: radial-gradient(ellipse at top right, rgba(6, 182, 212, 0.15), transparent 50%); }

        {{-- Card Hover Effect --}}
        .tech-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .tech-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.3), 0 0 20px rgba(6, 182, 212, 0.2); }

        {{-- Neon Glow Button --}}
        .btn-neon {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-neon:hover { box-shadow: 0 0 20px rgba(6, 182, 212, 0.5); }

        {{-- Animated Background --}}
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }
        .animate-glow { animation: pulse-glow 3s ease-in-out infinite; }

        @media (max-width: 767px) {
            .shop-name-text { font-size: 1rem !important; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .tech-card:hover { transform: none; }
            .mob-nav { --mob-primary: {{ $client->primary_color ?? "#06b6d4" }}; background: #111827 !important; border-top-color: rgba(255,255,255,0.08) !important; }
            .mob-nav a { color: #9ca3af !important; }
            .mob-nav a:hover, .mob-nav a.active { color: {{ $client->primary_color ?? "#06b6d4" }} !important; }
            .mob-search-bar { background: #111827 !important; border-bottom-color: rgba(255,255,255,0.08) !important; }
            .mob-search-bar input { background: #030712 !important; color: #fff !important; border-color: {{ $client->primary_color ?? "#06b6d4" }} !important; }
        }
    </style>
</head>
<body class="bg-dark text-white antialiased flex flex-col min-h-screen font-bangla tech-gradient">

    {{-- ⚡ Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Announcement --}}
    @if($client->announcement_text)
    <div class="bg-gradient-to-r from-primary/20 via-primary/30 to-primary/20 border-b border-primary/30 py-2.5 px-4 text-center">
        <span class="text-primary text-xs font-bold tracking-wider uppercase">
            <i class="fas fa-bolt mr-2"></i>{{ $client->announcement_text }}
        </span>
    </div>
    @endif

    {{-- Main Header --}}
    <header class="bg-panel/90 backdrop-blur-md sticky top-0 z-50 tech-border transition-all">
        <div class="max-w-7xl mx-auto px-4 md:px-6 h-14 md:h-18 flex justify-between items-center gap-3">
            {{-- Logo --}}
            <a href="{{ $baseUrl }}" class="flex items-center gap-2 min-w-0">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" class="h-7 md:h-10 object-contain flex-shrink-0" alt="{{ $client->shop_name }}">
                @endif
                <span class="text-base md:text-xl font-bold tracking-tight text-white truncate max-w-[140px] md:max-w-none">{{ $client->shop_name }}</span>
                <span class="bg-primary text-white text-[8px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider ml-1 hidden sm:inline-block flex-shrink-0">Tech</span>
            </a>

            {{-- Desktop Search --}}
            @if($client->widget('show_search_bar'))
            <div class="hidden lg:flex w-full max-w-lg mx-6 relative">
                <form action="{{ $baseUrl }}" method="GET" class="w-full">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ডিভাইস, মডেল, ব্র্যান্ড খুঁজুন..."
                        class="w-full bg-dark tech-border text-sm text-white px-4 py-2.5 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary transition placeholder-gray-500 font-bangla">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-primary transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            @endif

            {{-- Actions --}}
            <div class="hidden md:flex items-center gap-3">
                <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}"
                    class="text-xs font-semibold text-gray-400 hover:text-white transition flex items-center gap-2 bg-dark tech-border px-3 py-2 rounded-lg hover:border-gray-500">
                    <i class="fas fa-crosshairs text-primary"></i> ট্র্যাক অর্ডার
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-9 h-9 rounded-lg bg-dark tech-border flex items-center justify-center text-gray-400 hover:text-white hover:border-primary transition">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
                @endif
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="w-9 h-9 rounded-lg bg-dark tech-border flex items-center justify-center text-gray-400 hover:text-white hover:border-primary transition">
                    <i class="fas fa-phone-alt"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-panel border-t border-gray-800 pt-12 pb-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 md:px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                {{-- Brand --}}
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-microchip text-primary text-2xl"></i>
                        <h3 class="font-bold text-xl text-white">{{ $client->shop_name }}</h3>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4">নেক্সট-জেন টেক, গ্যাজেট এবং কম্পোনেন্টের আলটিমেট হাব।</p>
                    <div class="flex gap-3">
                        @if($client->facebook_url ?? false)
                        <a href="{{ $client->facebook_url }}" class="w-8 h-8 rounded-lg bg-dark tech-border flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition">
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        @endif
                        @if($client->fb_page_id ?? false)
                        <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-8 h-8 rounded-lg bg-dark tech-border flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary transition">
                            <i class="fab fa-facebook-messenger text-sm"></i>
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Categories --}}
                <div>
                    <h4 class="font-bold text-white mb-4 uppercase tracking-wider text-xs">ক্যাটাগরি</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-gray-400">
                        <a href="?category=all" class="hover:text-primary transition flex items-center gap-2">
                            <i class="fas fa-chevron-right text-[8px] text-gray-600"></i> সকল পণ্য
                        </a>
                        <a href="#" class="hover:text-primary transition flex items-center gap-2">
                            <i class="fas fa-chevron-right text-[8px] text-gray-600"></i> নতুন আসা
                        </a>
                        <a href="#" class="hover:text-primary transition flex items-center gap-2">
                            <i class="fas fa-chevron-right text-[8px] text-gray-600"></i> বেস্ট সেলার
                        </a>
                    </div>
                </div>

                {{-- Support --}}
                <div>
                    <h4 class="font-bold text-white mb-4 uppercase tracking-wider text-xs">সাপোর্ট</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-gray-400">
                        <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="hover:text-primary transition flex items-center gap-2">
                            <i class="fas fa-chevron-right text-[8px] text-gray-600"></i> লাইভ ট্র্যাকিং
                        </a>
                        <a href="#" class="hover:text-primary transition flex items-center gap-2">
                            <i class="fas fa-chevron-right text-[8px] text-gray-600"></i> রিটার্ন পলিসি
                        </a>
                        <a href="#" class="hover:text-primary transition flex items-center gap-2">
                            <i class="fas fa-chevron-right text-[8px] text-gray-600"></i> টেক সাপোর্ট
                        </a>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-white mb-4 uppercase tracking-wider text-xs">যোগাযোগ</h4>
                    <div class="flex flex-col space-y-3 text-sm">
                        @if($client->phone)
                        <div class="flex items-center gap-3 bg-dark tech-border p-3 rounded-lg">
                            <i class="fas fa-headset text-primary"></i>
                            <a href="tel:{{ $client->phone }}" class="text-white font-mono text-xs">{{ $client->phone }}</a>
                        </div>
                        @endif
                        @if($client->email)
                        <a href="mailto:{{ $client->email }}" class="text-gray-400 hover:text-primary transition text-xs">
                            <i class="fas fa-envelope mr-2"></i>{{ $client->email }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bottom Bar --}}
            <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-gray-600 font-mono">&copy; {{ date('Y') }} {{ $client->shop_name }}</p>
                <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest text-gray-600">
                    <span class="flex items-center gap-1"><i class="fas fa-lock text-primary"></i> Secure SSL</span>
                    <span class="opacity-50">|</span>
                    <span class="flex items-center gap-1"><i class="fas fa-shipping-fast text-primary"></i> Fast Shipping</span>
                </div>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
