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

    {{-- Fonts: Playfair Display for Fashion + Hind Siliguri for Bangla --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Hind+Siliguri:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#8b5cf6" }}',
                        nude: '#FAECEB',
                        rose: '#be185d',
                        gold: '#d4a574',
                    },
                    fontFamily: {
                        heading: ['Playfair Display', 'serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                        sans: ['Jost', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#8b5cf6" }};
            --mob-primary: {{ $client->primary_color ?? "#8b5cf6" }};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #faf9f7; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        {{-- Fashion Theme Specific --}}
        .fashion-border { border: 1px solid rgba(0,0,0,0.05); }
        .fashion-shadow { box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .fashion-card { border-radius: 2px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .fashion-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }

        {{-- Elegant Button --}}
        .btn-elegant {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-elegant::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-elegant:hover::before { left: 100%; }

        {{-- Gradient Text --}}
        .gradient-text {
            background: linear-gradient(135deg, #8b5cf6 0%, #be185d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (max-width: 767px) {
            .shop-name-text { font-size: 1.2rem !important; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .fashion-card:hover { transform: none; }
        }
    </style>
</head>
<body class="text-gray-900 antialiased flex flex-col min-h-screen font-bangla">

    {{-- ⚡ Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Announcement --}}
    @if($client->announcement_text)
    <div class="bg-gradient-to-r from-primary via-rose to-primary text-white py-2.5 px-4 text-center text-xs font-semibold tracking-wider">
        <i class="fas fa-sparkles mr-2"></i>{{ $client->announcement_text }}
    </div>
    @endif

    {{-- Main Header --}}
    <header class="bg-white sticky top-0 z-50 transition-all border-b border-gray-100 fashion-shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 h-16 md:h-20 flex justify-between items-center">
            {{-- Left: Track (desktop only) --}}
            <div class="w-1/3 hidden md:flex items-center">
                <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400 hover:text-primary transition flex items-center gap-2">
                    <i class="fas fa-truck-fast"></i> ট্র্যাক অর্ডার
                </a>
            </div>

            {{-- Center: Logo --}}
            <div class="flex-1 md:w-1/3 flex justify-start md:justify-center items-center">
                <a href="{{ $baseUrl }}" class="flex items-center gap-3">
                    @if($client->logo)
                        <img src="{{ asset('storage/' . $client->logo) }}" class="h-9 md:h-12 object-contain" alt="{{ $client->shop_name }}">
                    @else
                        <span class="shop-name-text text-2xl md:text-3xl font-heading font-bold tracking-tight gradient-text">{{ $client->shop_name }}</span>
                    @endif
                </a>
            </div>

            {{-- Right: Actions --}}
            <div class="w-auto md:w-1/3 flex justify-end items-center gap-4">
                @if($client->widget('show_search_bar'))
                <button class="text-gray-400 hover:text-primary transition" @click="$dispatch('open-search')">
                    <i class="fas fa-search text-lg"></i>
                </button>
                @endif
                @if($client->fb_page_id)
                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="text-gray-400 hover:text-primary transition">
                    <i class="fab fa-facebook-messenger text-lg"></i>
                </a>
                @endif
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="hidden md:flex text-gray-400 hover:text-primary transition">
                    <i class="fas fa-phone-alt text-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Search Modal --}}
    @if($client->widget('show_search_bar'))
    <div x-data="{ open: false }" x-on:open-search.window="open = true" x-show="open" x-cloak
        class="fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm flex items-start justify-center pt-20"
        @click.self="open = false" @keydown.escape.window="open = false">
        <div class="bg-white w-full max-w-2xl mx-4 rounded-2xl shadow-2xl overflow-hidden" @click.stop>
            <form action="{{ $baseUrl }}" method="GET" class="flex items-center">
                <input type="text" name="search" placeholder="পণ্য খুঁজুন..." autofocus
                    class="flex-1 px-6 py-5 text-lg focus:outline-none font-bangla">
                <button type="submit" class="px-6 py-5 bg-primary text-white font-semibold">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Main Content --}}
    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-100 pt-16 pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-8">
            {{-- Brand Section --}}
            <div class="text-center mb-12">
                <h3 class="font-heading font-bold text-3xl md:text-4xl mb-3 gradient-text">{{ $client->shop_name }}</h3>
                <p class="text-gray-400 text-sm font-medium max-w-md mx-auto">এক্সক্লুসিভ ফ্যাশন আইটেম, প্রিমিয়াম কোয়ালিটি। আপনার স্টাইল আমাদের প্যাশন।</p>
            </div>

            {{-- Quick Links --}}
            <div class="flex flex-wrap justify-center gap-6 md:gap-10 text-sm font-medium text-gray-400 mb-12">
                <a href="{{ $baseUrl }}" class="hover:text-primary transition">সকল পণ্য</a>
                <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="hover:text-primary transition">অর্ডার ট্র্যাক</a>
                <a href="#" class="hover:text-primary transition">রিটার্ন পলিসি</a>
                <a href="#" class="hover:text-primary transition">আমাদের সম্পর্কে</a>
            </div>

            {{-- Social Links --}}
            <div class="flex justify-center gap-4 mb-12">
                @if($client->facebook_url ?? false)
                <a href="{{ $client->facebook_url }}" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-white hover:bg-primary hover:border-primary transition">
                    <i class="fab fa-facebook-f"></i>
                </a>
                @endif
                @if($client->instagram_url ?? false)
                <a href="{{ $client->instagram_url }}" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-white hover:bg-primary hover:border-primary transition">
                    <i class="fab fa-instagram"></i>
                </a>
                @endif
                @if($client->fb_page_id ?? false)
                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-white hover:bg-primary hover:border-primary transition">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
                @endif
            </div>

            {{-- Contact Info --}}
            <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-500 mb-8">
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="flex items-center gap-2 hover:text-primary transition">
                    <i class="fas fa-phone-alt text-primary"></i> {{ $client->phone }}
                </a>
                @endif
                @if($client->email)
                <a href="mailto:{{ $client->email }}" class="flex items-center gap-2 hover:text-primary transition">
                    <i class="fas fa-envelope text-primary"></i> {{ $client->email }}
                </a>
                @endif
            </div>

            {{-- Copyright --}}
            <div class="border-t border-gray-100 pt-6 text-center">
                <p class="text-xs text-gray-400">&copy; {{ date('Y') }} {{ $client->shop_name }}। সর্বস্বত্ব সংরক্ষিত।</p>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
