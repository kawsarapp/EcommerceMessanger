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

    {{-- Fonts: DM Serif Display for premium + Hind Siliguri --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Hind+Siliguri:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#18181b" }}',
                        accent: '#c9a962',
                        cream: '#faf8f5',
                    },
                    fontFamily: {
                        heading: ['DM Serif Display', 'serif'],
                        sans: ['DM Sans', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#18181b" }};
            --mob-primary: {{ $client->primary_color ?? "#18181b" }};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #faf8f5; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        {{-- Premium Theme Specific --}}
        .premium-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .premium-card:hover { transform: translateY(-6px); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.08); }
        .premium-border { border: 1px solid rgba(24, 24, 27, 0.08); }
        .accent-line { width: 40px; height: 2px; background: #c9a962; }

        {{-- Underline Animation --}}
        .animated-underline {
            position: relative;
        }
        .animated-underline::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: #c9a962;
            transition: width 0.3s ease;
        }
        .animated-underline:hover::after {
            width: 100%;
        }

        @media (max-width: 767px) {
            .shop-name-text { font-size: 1.1rem !important; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .premium-card:hover { transform: none; }
        }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-bangla">

    {{-- Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Announcement --}}
    @if($client->announcement_text)
    <div class="bg-primary text-white py-2.5 px-4 text-center text-xs font-medium tracking-wider">
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Premium Header --}}
    <header class="bg-white sticky top-0 z-50 border-b premium-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 h-16 md:h-20 flex justify-between items-center">
            {{-- Logo --}}
            <a href="{{ $baseUrl }}" class="flex items-center gap-3">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" class="h-9 md:h-12 object-contain" alt="{{ $client->shop_name }}">
                @else
                    <span class="shop-name-text text-xl md:text-2xl font-heading">{{ $client->shop_name }}</span>
                @endif
            </a>

            {{-- Navigation --}}
            <nav class="hidden md:flex items-center gap-8">
                <a href="{{ $baseUrl }}" class="animated-underline text-sm font-medium text-slate-600 hover:text-primary transition">সকল পণ্য</a>
                <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="animated-underline text-sm font-medium text-slate-600 hover:text-primary transition">ট্র্যাক অর্ডার</a>
                <a href="#" class="animated-underline text-sm font-medium text-slate-600 hover:text-primary transition">আমাদের সম্পর্কে</a>
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-4">
                @if($client->widget('show_search_bar'))
                <button class="text-slate-400 hover:text-primary transition" @click="$dispatch('open-premium-search')">
                    <i class="fas fa-search"></i>
                </button>
                @endif
                @if($client->fb_page_id)
                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="text-slate-400 hover:text-primary transition">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
                @endif
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="hidden md:block text-sm font-medium text-slate-600 hover:text-primary transition">
                    {{ $client->phone }}
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Search Modal --}}
    @if($client->widget('show_search_bar'))
    <div x-data="{ open: false }" x-on:open-premium-search.window="open = true" x-show="open" x-cloak
        class="fixed inset-0 z-[60] bg-primary/80 backdrop-blur-sm flex items-start justify-center pt-32"
        @click.self="open = false" @keydown.escape.window="open = false">
        <div class="bg-white w-full max-w-2xl mx-4 shadow-2xl" @click.stop>
            <form action="{{ $baseUrl }}" method="GET" class="flex items-center">
                <input type="text" name="search" placeholder="পণ্য খুঁজুন..." autofocus
                    class="flex-1 px-8 py-5 text-lg focus:outline-none font-heading bg-transparent">
                <button type="submit" class="px-8 py-5 bg-primary text-white">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Main Content --}}
    <main class="flex-1 w-full">
        @yield('content')
    </main>

    {{-- Premium Footer --}}
    <footer class="bg-primary text-white pt-16 pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-8">
            {{-- Brand Section --}}
            <div class="text-center mb-12">
                <h3 class="font-heading text-3xl md:text-4xl mb-3">{{ $client->shop_name }}</h3>
                <div class="accent-line mx-auto mb-4"></div>
                <p class="text-slate-400 text-sm max-w-md mx-auto">প্রিমিয়াম কোয়ালিটি, অসাধারণ ডিজাইন। আপনার জন্য সেরাটাই।</p>
            </div>

            {{-- Links Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
                <div>
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-accent mb-6">কালেকশন</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        <a href="{{ $baseUrl }}" class="hover:text-white transition">সকল পণ্য</a>
                        <a href="#" class="hover:text-white transition">নতুন আসা</a>
                        <a href="#" class="hover:text-white transition">বেস্ট সেলার</a>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-accent mb-6">সাহায্য</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="hover:text-white transition">অর্ডার ট্র্যাক</a>
                        <a href="#" class="hover:text-white transition">ডেলিভারি</a>
                        <a href="#" class="hover:text-white transition">রিটার্ন</a>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-accent mb-6">সোশ্যাল</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        @if($client->facebook_url ?? false)
                        <a href="{{ $client->facebook_url }}" class="hover:text-white transition flex items-center gap-2">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        @endif
                        @if($client->instagram_url ?? false)
                        <a href="{{ $client->instagram_url }}" class="hover:text-white transition flex items-center gap-2">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                        @endif
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-accent mb-6">যোগাযোগ</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        @if($client->phone)
                        <a href="tel:{{ $client->phone }}" class="hover:text-white transition">{{ $client->phone }}</a>
                        @endif
                        @if($client->email)
                        <a href="mailto:{{ $client->email }}" class="hover:text-white transition">{{ $client->email }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} {{ $client->shop_name }}। সর্বস্বত্ব সংরক্ষিত।</p>
                <div class="flex items-center gap-4">
                    <span>প্রিমিয়াম কোয়ালিটি</span>
                    <span class="text-accent">|</span>
                    <span>এক্সক্লুসিভ ডিজাইন</span>
                </div>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
