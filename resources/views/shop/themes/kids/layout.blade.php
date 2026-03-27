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

    {{-- Fonts: Nunito for playful look + Hind Siliguri --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#f472b6" }}',
                        candy: '#fb7185',
                        sky: '#38bdf8',
                        mint: '#34d399',
                        sunny: '#fbbf24',
                        purple: '#a78bfa',
                    },
                    fontFamily: {
                        sans: ['Nunito', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#f472b6" }};
            --mob-primary: {{ $client->primary_color ?? "#f472b6" }};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #fff5f7; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        {{-- Kids Theme Specific --}}
        .kids-card { transition: all 0.3s ease; border-radius: 20px; }
        .kids-card:hover { transform: translateY(-6px) rotate(1deg); box-shadow: 0 16px 32px rgba(244, 114, 182, 0.2); }
        .rainbow-gradient { background: linear-gradient(135deg, #f472b6, #fb7185, #fbbf24, #34d399, #38bdf8, #a78bfa); }
        .candy-bg { background: linear-gradient(135deg, #fdf2f8 0%, #fff1f2 50%, #fef3c7 100%); }

        {{-- Playful Animations --}}
        @keyframes bounce-soft {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .bounce-animation { animation: bounce-soft 2s ease-in-out infinite; }

        {{-- Fun Badges --}}
        .fun-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 767px) {
            .shop-name-text { font-size: 1rem !important; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .kids-card:hover { transform: none; }
        }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-bangla candy-bg">

    {{-- Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Announcement --}}
    @if($client->announcement_text)
    <div class="rainbow-gradient text-white py-2.5 px-4 text-center text-xs font-bold flex items-center justify-center gap-2">
        <i class="fas fa-star bounce-animation"></i>
        {!! $client->announcement_text !!}
        <i class="fas fa-star bounce-animation"></i>
    </div>
    @endif

    {{-- Fun Header --}}
    <header class="bg-white sticky top-0 z-50 shadow-md" style="border-bottom: 4px solid; border-image: linear-gradient(90deg, #f472b6, #fb7185, #fbbf24, #34d399, #38bdf8, #a78bfa) 1;">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 h-14 md:h-18 flex justify-between items-center gap-3">
            {{-- Logo --}}
            <a href="{{ $baseUrl }}" class="flex items-center gap-2 min-w-0">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" class="h-8 md:h-12 object-contain flex-shrink-0" alt="{{ $client->shop_name }}">
                @else
                    <div class="w-10 h-10 bg-primary rounded-2xl flex items-center justify-center text-white flex-shrink-0 bounce-animation">
                        <i class="fas fa-child text-lg"></i>
                    </div>
                @endif
                <span class="shop-name-text text-lg md:text-2xl font-extrabold text-slate-800">{{ $client->shop_name }}</span>
            </a>

            {{-- Search Bar --}}
            @if($client->widget('show_search_bar'))
            <div class="flex-1 max-w-lg mx-4 hidden md:block">
                <form action="{{ $baseUrl }}" method="GET" class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="玩具, 游戏, 衣服..."
                        class="w-full bg-pink-50 border-2 border-pink-100 rounded-full pl-5 pr-12 py-2.5 text-sm font-bold focus:border-primary focus:bg-white transition placeholder-slate-400">
                    <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 w-9 h-9 bg-primary text-white rounded-full flex items-center justify-center">
                        <i class="fas fa-search text-sm"></i>
                    </button>
                </form>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="bg-sky text-white text-xs font-bold px-3 py-2 rounded-full flex items-center gap-1.5 hover:opacity-90 transition">
                    <i class="fas fa-truck-fast"></i>
                    <span class="hidden sm:inline">ট্র্যাক</span>
                </a>
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="w-10 h-10 bg-candy text-white rounded-full flex items-center justify-center text-sm hover:opacity-90 transition">
                    <i class="fas fa-phone-alt"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Category Bar with Icons --}}
    @if($client->widget('show_category_filter') && isset($categories) && count($categories) > 0)
    <nav class="bg-white/80 backdrop-blur border-b border-pink-100 sticky top-14 md:top-[4.5rem] z-40">
        <div class="max-w-7xl mx-auto px-3 sm:px-4">
            <div class="flex gap-3 overflow-x-auto hide-scroll py-3">
                <a href="?category=all" class="flex flex-col items-center gap-1.5 px-4 py-2 rounded-2xl transition {{ !request('category') || request('category') == 'all' ? 'bg-primary text-white shadow-lg' : 'bg-pink-50 text-slate-600 hover:bg-pink-100' }}">
                    <span class="text-xl">🧸</span>
                    <span class="text-[10px] font-bold">সব</span>
                </a>
                @foreach($categories as $c)
                    <a href="?category={{ $c->slug }}" class="flex flex-col items-center gap-1.5 px-4 py-2 rounded-2xl transition {{ request('category') == $c->slug ? 'bg-primary text-white shadow-lg' : 'bg-pink-50 text-slate-600 hover:bg-pink-100' }}">
                        <span class="text-xl">⭐</span>
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

    {{-- Fun Footer --}}
    <footer class="bg-white border-t-4 border-primary pt-12 pb-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
                {{-- Brand --}}
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-12 h-12 rainbow-gradient rounded-2xl flex items-center justify-center text-white text-xl">
                            🎈
                        </div>
                        <span class="text-xl font-extrabold text-slate-800">{{ $client->shop_name }}</span>
                    </div>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">বাচ্চাদের জন্য সেরা পণ্য, মজার দাম!</p>
                    <div class="flex gap-2">
                        @if($client->facebook_url ?? false)
                        <a href="{{ $client->facebook_url }}" class="w-10 h-10 rounded-full bg-pink-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition text-lg">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        @endif
                        @if($client->fb_page_id ?? false)
                        <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-10 h-10 rounded-full bg-pink-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition text-lg">
                            <i class="fab fa-facebook-messenger"></i>
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h4 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wider flex items-center gap-2">
                        🛍️ কেনাকাটা
                    </h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-500">
                        <a href="{{ $baseUrl }}" class="hover:text-primary transition">সকল পণ্য</a>
                        <a href="#" class="hover:text-primary transition">নতুন আসা</a>
                        <a href="#" class="hover:text-primary transition">বেস্ট সেলার</a>
                    </div>
                </div>

                {{-- Support --}}
                <div>
                    <h4 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wider flex items-center gap-2">
                        💝 সাহায্য
                    </h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-500">
                        <a href="{{ $clean ? $baseUrl . '/track-order' : route('shop.track', $client->slug) }}" class="hover:text-primary transition">অর্ডার ট্র্যাক</a>
                        <a href="#" class="hover:text-primary transition">ডেলিভারি</a>
                        <a href="#" class="hover:text-primary transition">রিটার্ন</a>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-slate-800 mb-4 text-sm uppercase tracking-wider flex items-center gap-2">
                        📞 যোগাযোগ
                    </h4>
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

            {{-- Fun Info Bar --}}
            <div class="bg-gradient-to-r from-pink-50 via-yellow-50 to-green-50 rounded-2xl p-4 mb-6">
                <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-bold text-slate-600">
                    <span class="flex items-center gap-2">🚚 সারাদেশে ডেলিভারি</span>
                    <span class="flex items-center gap-2">✨ ১০০% অরিজিনাল</span>
                    <span class="flex items-center gap-2">🎁 সুন্দর প্যাকেজিং</span>
                </div>
            </div>

            <div class="border-t border-pink-100 pt-6 text-center">
                <p class="text-xs text-slate-400">Made with 💖 &copy; {{ date('Y') }} {{ $client->shop_name }}</p>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
