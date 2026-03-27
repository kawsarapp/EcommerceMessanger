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

    {{-- Fonts: Cormorant Garamond for luxury + Hind Siliguri --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Hind+Siliguri:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#b8860b" }}',
                        gold: '#d4a574',
                        champagne: '#f7e7ce',
                        dark: '#1a1a1a',
                    },
                    fontFamily: {
                        heading: ['Cormorant Garamond', 'serif'],
                        sans: ['Montserrat', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#b8860b" }};
            --mob-primary: {{ $client->primary_color ?? "#b8860b" }};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #faf8f5; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        {{-- Luxury Theme Specific --}}
        .luxury-card { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        .luxury-card:hover { transform: translateY(-8px); box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1); }
        .gold-gradient { background: linear-gradient(135deg, #b8860b 0%, #d4a574 50%, #f7e7ce 100%); }
        .luxury-border { border: 1px solid rgba(184, 134, 11, 0.2); }

        {{-- Elegant Divider --}}
        .gold-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .gold-divider::before, .gold-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(184, 134, 11, 0.3), transparent);
        }

        {{-- Shimmer Effect --}}
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        .shimmer-text {
            background: linear-gradient(90deg, #b8860b, #d4a574, #b8860b);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s linear infinite;
        }

        @media (max-width: 767px) {
            .shop-name-text { font-size: 1.1rem !important; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .luxury-card:hover { transform: none; }
        }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-bangla">

    {{-- Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Announcement --}}
    @if($client->announcement_text)
    <div class="bg-dark text-champagne py-2.5 px-4 text-center text-xs font-medium tracking-[0.2em] uppercase">
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Elegant Header --}}
    <header class="bg-white/95 backdrop-blur-md sticky top-0 z-50 border-b luxury-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 h-16 md:h-20 flex justify-between items-center">
            {{-- Left: Navigation --}}
            <div class="w-1/3 hidden md:flex items-center gap-6">
                <a href="{{ $baseUrl }}" class="text-xs font-medium uppercase tracking-[0.15em] text-slate-400 hover:text-primary transition">হোম</a>
                <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="text-xs font-medium uppercase tracking-[0.15em] text-slate-400 hover:text-primary transition">ট্র্যাক</a>
            </div>

            {{-- Center: Logo --}}
            <div class="flex-1 md:w-1/3 flex justify-center items-center">
                <a href="{{ $baseUrl }}" class="flex flex-col items-center">
                    @if($client->logo)
                        <img src="{{ asset('storage/' . $client->logo) }}" class="h-10 md:h-14 object-contain" alt="{{ $client->shop_name }}">
                    @else
                        <span class="shop-name-text text-2xl md:text-3xl font-heading font-semibold tracking-wide text-dark">{{ $client->shop_name }}</span>
                    @endif
                </a>
            </div>

            {{-- Right: Actions --}}
            <div class="w-1/3 flex justify-end items-center gap-4">
                @if($client->widget('show_search_bar'))
                <button class="text-slate-400 hover:text-primary transition" @click="$dispatch('open-luxury-search')">
                    <i class="fas fa-search"></i>
                </button>
                @endif
                @if($client->fb_page_id)
                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="text-slate-400 hover:text-primary transition">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
                @endif
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="hidden md:block text-slate-400 hover:text-primary transition">
                    <i class="fas fa-phone-alt"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Search Modal --}}
    @if($client->widget('show_search_bar'))
    <div x-data="{ open: false }" x-on:open-luxury-search.window="open = true" x-show="open" x-cloak
        class="fixed inset-0 z-[60] bg-black/60 backdrop-blur-sm flex items-start justify-center pt-32"
        @click.self="open = false" @keydown.escape.window="open = false">
        <div class="bg-white w-full max-w-xl mx-4 shadow-2xl overflow-hidden" @click.stop>
            <form action="{{ $baseUrl }}" method="GET" class="flex items-center border-b luxury-border">
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

    {{-- Elegant Footer --}}
    <footer class="bg-dark text-white pt-16 pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-8">
            {{-- Brand Section --}}
            <div class="text-center mb-12">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" class="h-16 mx-auto mb-4 brightness-0 invert" alt="{{ $client->shop_name }}">
                @else
                    <h3 class="font-heading text-3xl md:text-4xl mb-2 shimmer-text">{{ $client->shop_name }}</h3>
                @endif
                <p class="text-slate-400 text-sm tracking-wider max-w-md mx-auto">এক্সক্লুসিভ লাক্সারি কালেকশন। প্রিমিয়াম কোয়ালিটি, অপূর্ব ডিজাইন।</p>
            </div>

            <div class="gold-divider mb-12">
                <i class="fas fa-gem text-primary text-sm"></i>
            </div>

            {{-- Links Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
                <div>
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-primary mb-6">কালেকশন</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        <a href="{{ $baseUrl }}" class="hover:text-white transition">সকল পণ্য</a>
                        <a href="#" class="hover:text-white transition">নতুন আসা</a>
                        <a href="#" class="hover:text-white transition">এক্সক্লুসিভ</a>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-primary mb-6">সাহায্য</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="hover:text-white transition">অর্ডার ট্র্যাক</a>
                        <a href="#" class="hover:text-white transition">ডেলিভারি</a>
                        <a href="#" class="hover:text-white transition">রিটার্ন</a>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-primary mb-6">সোশ্যাল</h4>
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
                    <h4 class="text-xs font-medium uppercase tracking-[0.15em] text-primary mb-6">যোগাযোগ</h4>
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

            <div class="gold-divider mb-8"></div>

            {{-- Bottom Bar --}}
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} {{ $client->shop_name }}। সর্বস্বত্ব সংরক্ষিত।</p>
                <div class="flex items-center gap-4 tracking-wider">
                    <span>প্রিমিয়াম কোয়ালিটি</span>
                    <span class="text-primary">|</span>
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
