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
    <meta name="description" content="{{ $client->meta_description ?? $client->shop_name . ' - অনলাইন শপিং করুন সেরা দামে' }}">

    {{-- TailwindCSS & AlpineJS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Fonts: Inter + Hind Siliguri for Bangla --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#f85606" }}',
                        secondary: '#fef0eb',
                        accent: '#1a9cb7',
                        dark: '#212121',
                        bdOrange: '#f85606',
                        bdPink: '#ff6b6b',
                    },
                    fontFamily: {
                        bangla: ['Hind Siliguri', 'sans-serif'],
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#f85606" }};
            --mob-primary: {{ $client->primary_color ?? "#f85606" }};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #f5f5f5; font-family: 'Hind Siliguri', sans-serif; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
        .smooth-transition { transition: all 0.25s ease; }

        {{-- Custom BD E-commerce Styles --}}
        .bd-gradient { background: linear-gradient(135deg, #f85606 0%, #ff6b6b 100%); }
        .bd-card { border-radius: 8px; overflow: hidden; }
        .bd-badge { font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }

        {{-- Product Card Hover Effect --}}
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); }
        .product-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

        {{-- Flash Sale Animation --}}
        @keyframes flash-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .flash-animate { animation: flash-pulse 1.5s ease-in-out infinite; }

        {{-- Mobile Optimization --}}
        @media (max-width: 767px) {
            .shop-name-text { font-size: 1rem !important; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .product-card:hover { transform: none; }
        }

        {{-- Desktop Search Bar Styling --}}
        .search-input:focus { box-shadow: 0 0 0 3px rgba(248, 86, 6, 0.1); }

        {{-- Category Pills --}}
        .category-pill { white-space: nowrap; transition: all 0.2s ease; }
        .category-pill:hover { transform: scale(1.02); }
        .category-pill.active { background: var(--tw-color-primary); color: white; }

        {{-- Footer Styles --}}
        .footer-link { transition: all 0.2s ease; }
        .footer-link:hover { color: var(--tw-color-primary); padding-left: 4px; }
    </style>
</head>
<body class="text-dark antialiased flex flex-col min-h-screen font-bangla">

    {{-- ⚡ Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Header --}}
    @if($client->announcement_text)
    <div class="bg-gradient-to-r from-primary to-bdPink text-white py-2 px-4 text-center text-xs font-semibold">
        <div class="flex items-center justify-center gap-2">
            <i class="fas fa-bullhorn flash-animate"></i>
            <span>{{ $client->announcement_text }}</span>
        </div>
    </div>
    @endif

    {{-- Main Header --}}
    <header class="bg-primary sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-3 sm:px-4">
            {{-- Main Row --}}
            <div class="flex items-center gap-2 sm:gap-4 h-14 md:h-16">
                {{-- Logo / Shop Name --}}
                <a href="{{ $baseUrl }}" class="flex items-center gap-2 shrink-0">
                    @if($client->logo)
                        <img src="{{ asset('storage/' . $client->logo) }}" class="h-8 md:h-10 object-contain rounded" alt="{{ $client->shop_name }}">
                    @else
                        <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center">
                            <i class="fas fa-store text-primary text-lg"></i>
                        </div>
                    @endif
                    <span class="text-white font-bold text-sm sm:text-lg md:text-xl tracking-tight hidden sm:block">{{ $client->shop_name }}</span>
                </a>

                {{-- Search Bar --}}
                @if($client->widget('show_search_bar'))
                <div class="flex-1 max-w-2xl mx-2 md:mx-6">
                    <form action="{{ $baseUrl }}" method="GET">
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="আপনার পণ্য খুঁজুন..."
                                class="search-input w-full bg-white rounded-lg pl-4 pr-12 py-2.5 text-sm text-dark font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-white/30 border-0">
                            <button type="submit" class="absolute right-0 top-0 h-full px-4 bg-primary/80 hover:bg-primary/60 text-white rounded-r-lg transition">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-1 sm:gap-3 shrink-0">
                    <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}"
                        class="text-white/90 hover:text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 py-1.5 rounded-lg hover:bg-white/10">
                        <i class="fas fa-truck-fast"></i>
                        <span class="hidden md:inline">ট্র্যাক অর্ডার</span>
                    </a>
                    @if($client->phone)
                    <a href="tel:{{ $client->phone }}" class="text-white/90 hover:text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 py-1.5 rounded-lg hover:bg-white/10">
                        <i class="fas fa-phone-alt"></i>
                        <span class="hidden lg:inline">{{ $client->phone }}</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    {{-- Category Bar --}}
    @if($client->widget('show_category_filter') && isset($categories) && count($categories) > 0)
    <nav class="bg-white border-b border-slate-200 shadow-sm sticky top-14 md:top-16 z-40">
        <div class="max-w-7xl mx-auto px-3 sm:px-4">
            <div class="flex gap-2 overflow-x-auto hide-scroll py-2.5">
                <a href="?category=all" class="category-pill px-4 py-2 rounded-full text-xs font-bold {{ !request('category') || request('category') == 'all' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <i class="fas fa-th-large mr-1"></i> সকল পণ্য
                </a>
                @foreach($categories as $c)
                    <a href="?category={{ $c->slug }}" class="category-pill px-4 py-2 rounded-full text-xs font-bold {{ request('category') == $c->slug ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $c->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>
    @endif

    {{-- Main Content --}}
    <main class="flex-1 w-full">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-dark text-white mt-auto">
        {{-- Trust Badges --}}
        <div class="bg-slate-800 py-6 border-b border-slate-700">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-truck text-primary text-lg"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-300">সারাদেশে ডেলিভারি</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-shield-check text-primary text-lg"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-300">১০০% অরিজিনাল</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-undo text-primary text-lg"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-300">সহজ রিটার্ন</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-primary text-lg"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-300">ক্যাশ অন ডেলিভারি</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Footer --}}
        <div class="max-w-7xl mx-auto px-4 py-10">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                {{-- Brand --}}
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        @if($client->logo)
                            <img src="{{ asset('storage/' . $client->logo) }}" class="h-10 object-contain rounded" alt="{{ $client->shop_name }}">
                        @endif
                        <span class="text-xl font-bold">{{ $client->shop_name }}</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-4">বিশ্বস্ত মানের পণ্য, সেরা দামে। সারাদেশে হোম ডেলিভারি।</p>
                    <div class="flex gap-3">
                        @if($client->facebook_url ?? false)
                            <a href="{{ $client->facebook_url }}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </a>
                        @endif
                        @if($client->instagram_url ?? false)
                            <a href="{{ $client->instagram_url }}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition">
                                <i class="fab fa-instagram text-sm"></i>
                            </a>
                        @endif
                        @if($client->fb_page_id ?? false)
                            <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition">
                                <i class="fab fa-facebook-messenger text-sm"></i>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">দোকান</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-400">
                        <a href="{{ $baseUrl }}" class="footer-link w-fit">সকল পণ্য</a>
                        <a href="{{ $baseUrl }}?category=all" class="footer-link w-fit">নতুন আসা</a>
                        <a href="#" class="footer-link w-fit">বেস্ট সেলার</a>
                    </div>
                </div>

                {{-- Support --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">সাহায্য</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-400">
                        <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="footer-link w-fit">অর্ডার ট্র্যাক</a>
                        <a href="#" class="footer-link w-fit">রিটার্ন পলিসি</a>
                        <a href="#" class="footer-link w-fit">ডেলিভারি তথ্য</a>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">যোগাযোগ</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        @if($client->phone)
                            <a href="tel:{{ $client->phone }}" class="hover:text-white transition flex items-center gap-2">
                                <i class="fas fa-phone-alt text-primary"></i> {{ $client->phone }}
                            </a>
                        @endif
                        @if($client->email)
                            <a href="mailto:{{ $client->email }}" class="hover:text-white transition flex items-center gap-2">
                                <i class="fas fa-envelope text-primary"></i> {{ $client->email }}
                            </a>
                        @endif
                        @if($client->address)
                            <span class="flex items-start gap-2">
                                <i class="fas fa-map-marker-alt text-primary mt-1"></i> {{ $client->address }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Footer --}}
        <div class="border-t border-white/10 py-5">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-3">
                <p class="text-slate-500 text-xs">&copy; {{ date('Y') }} {{ $client->shop_name }}। সর্বস্বত্ব সংরক্ষিত।</p>
                <div class="flex items-center gap-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Visa_Logo.png/120px-Visa_Logo.png" class="h-5 opacity-40" alt="Visa">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/120px-MasterCard_Logo.svg.png" class="h-5 opacity-40" alt="MasterCard">
                    <span class="text-[10px] text-slate-500 font-bold border border-slate-600 px-2 py-0.5 rounded">ক্যাশ অন ডেলিভারি</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Floating Chat --}}
    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
