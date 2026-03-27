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

    {{-- Fonts: Poppins for fresh look + Hind Siliguri --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#22c55e" }}',
                        fresh: '#10b981',
                        lime: '#84cc16',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#22c55e" }};
            --mob-primary: {{ $client->primary_color ?? "#22c55e" }};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #f0fdf4; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        {{-- Grocery Theme Specific --}}
        .grocery-card { transition: all 0.3s ease; border-radius: 16px; }
        .grocery-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(34, 197, 94, 0.15); }
        .fresh-gradient { background: linear-gradient(135deg, #22c55e 0%, #84cc16 100%); }
        .organic-badge { background: linear-gradient(135deg, #22c55e, #10b981); }

        {{-- Category Icons --}}
        .category-icon { transition: all 0.3s ease; }
        .category-icon:hover { transform: scale(1.1); }

        @media (max-width: 767px) {
            .shop-name-text { font-size: 1rem !important; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .grocery-card:hover { transform: none; }
        }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-bangla">

    {{-- Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Announcement --}}
    @if($client->announcement_text)
    <div class="fresh-gradient text-white py-2.5 px-4 text-center text-xs font-semibold flex items-center justify-center gap-2">
        <i class="fas fa-leaf"></i>
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Main Header --}}
    <header class="bg-white sticky top-0 z-50 shadow-sm border-b border-green-100">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 h-14 md:h-16 flex justify-between items-center gap-3">
            {{-- Logo --}}
            <a href="{{ $baseUrl }}" class="flex items-center gap-2 min-w-0">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" class="h-8 md:h-10 object-contain flex-shrink-0" alt="{{ $client->shop_name }}">
                @else
                    <div class="w-9 h-9 fresh-gradient rounded-xl flex items-center justify-center text-white flex-shrink-0">
                        <i class="fas fa-carrot text-lg"></i>
                    </div>
                @endif
                <span class="shop-name-text text-lg md:text-xl font-bold text-slate-800">{{ $client->shop_name }}</span>
            </a>

            {{-- Search Bar --}}
            @if($client->widget('show_search_bar'))
            <div class="flex-1 max-w-xl mx-4 hidden md:block">
                <form action="{{ $baseUrl }}" method="GET" class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="পণ্য খুঁজুন (ফল, সবজি, মুদি...)"
                        class="w-full bg-green-50 border-2 border-green-100 rounded-full pl-5 pr-12 py-2.5 text-sm font-medium focus:border-primary focus:bg-white transition placeholder-slate-400">
                    <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 bg-primary text-white rounded-full flex items-center justify-center">
                        <i class="fas fa-search text-sm"></i>
                    </button>
                </form>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="text-slate-600 hover:text-primary text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 sm:px-3 py-1.5 rounded-lg hover:bg-green-50">
                    <i class="fas fa-truck-fast"></i>
                    <span class="hidden sm:inline">ট্র্যাক</span>
                </a>
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="w-9 h-9 bg-primary text-white rounded-full flex items-center justify-center text-sm hover:bg-primary/90 transition">
                    <i class="fas fa-phone-alt"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Category Bar --}}
    @if($client->widget('show_category_filter') && isset($categories) && count($categories) > 0)
    <nav class="bg-white border-b border-green-100 shadow-sm sticky top-14 md:top-16 z-40">
        <div class="max-w-7xl mx-auto px-3 sm:px-4">
            <div class="flex gap-2 overflow-x-auto hide-scroll py-3">
                <a href="?category=all" class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition {{ !request('category') || request('category') == 'all' ? 'bg-primary text-white' : 'bg-green-50 text-slate-600 hover:bg-green-100' }}">
                    <i class="fas fa-th-large text-lg"></i>
                    <span class="text-[10px] font-bold">সব</span>
                </a>
                @foreach($categories as $c)
                    <a href="?category={{ $c->slug }}" class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition {{ request('category') == $c->slug ? 'bg-primary text-white' : 'bg-green-50 text-slate-600 hover:bg-green-100' }}">
                        <i class="fas fa-tag text-lg"></i>
                        <span class="text-[10px] font-bold">{{ $c->name }}</span>
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
    <footer class="bg-white border-t border-green-100 pt-12 pb-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                {{-- Brand --}}
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-10 h-10 fresh-gradient rounded-xl flex items-center justify-center text-white">
                            <i class="fas fa-carrot"></i>
                        </div>
                        <span class="text-xl font-bold text-slate-800">{{ $client->shop_name }}</span>
                    </div>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">তাজা পণ্য, সেরা দাম। ঘরে বসে কেনাকাটা।</p>
                    <div class="flex gap-2">
                        @if($client->facebook_url ?? false)
                        <a href="{{ $client->facebook_url }}" class="w-9 h-9 rounded-full bg-green-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition">
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        @endif
                        @if($client->fb_page_id ?? false)
                        <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-9 h-9 rounded-full bg-green-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition">
                            <i class="fab fa-facebook-messenger text-sm"></i>
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h4 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wider">কেনাকাটা</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-500">
                        <a href="{{ $baseUrl }}" class="hover:text-primary transition">সকল পণ্য</a>
                        <a href="#" class="hover:text-primary transition">তাজা সবজি</a>
                        <a href="#" class="hover:text-primary transition">ফলমূল</a>
                    </div>
                </div>

                {{-- Support --}}
                <div>
                    <h4 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wider">সাহায্য</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-500">
                        <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="hover:text-primary transition">অর্ডার ট্র্যাক</a>
                        <a href="#" class="hover:text-primary transition">ডেলিভারি তথ্য</a>
                        <a href="#" class="hover:text-primary transition">রিটার্ন</a>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wider">যোগাযোগ</h4>
                    <div class="flex flex-col space-y-3 text-sm">
                        @if($client->phone)
                        <a href="tel:{{ $client->phone }}" class="flex items-center gap-2 text-slate-500 hover:text-primary transition">
                            <i class="fas fa-phone-alt text-primary"></i> {{ $client->phone }}
                        </a>
                        @endif
                        @if($client->email)
                        <a href="mailto:{{ $client->email }}" class="flex items-center gap-2 text-slate-500 hover:text-primary transition">
                            <i class="fas fa-envelope text-primary"></i> {{ $client->email }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Delivery Info --}}
            <div class="bg-green-50 rounded-2xl p-4 mb-6">
                <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-semibold text-slate-600">
                    <span class="flex items-center gap-2"><i class="fas fa-truck text-primary"></i> সারাদেশে ডেলিভারি</span>
                    <span class="flex items-center gap-2"><i class="fas fa-clock text-primary"></i> দ্রুত পৌঁছাই</span>
                    <span class="flex items-center gap-2"><i class="fas fa-leaf text-primary"></i> ১০০% তাজা</span>
                </div>
            </div>

            <div class="border-t border-green-100 pt-6 text-center">
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} {{ $client->shop_name }}। সর্বস্বত্ব সংরক্ষিত।</p>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
