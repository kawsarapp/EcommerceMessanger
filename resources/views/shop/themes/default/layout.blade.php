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

    @if($client->fb_pixel_id)
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $client->fb_pixel_id }}');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id={{ $client->fb_pixel_id }}&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    @endif

    {{-- AlpineJS & TailwindCSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Fonts: Inter + Hind Siliguri --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#6366f1" }}',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        bangla: ['Hind Siliguri', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 4px 40px -4px rgba(0,0,0,0.04)',
                        'float': '0 20px 40px -10px rgba(0,0,0,0.08)',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{ $client->primary_color ?? "#6366f1" }};
            --mob-primary: {{ $client->primary_color ?? "#6366f1" }};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #fafafa; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
        .premium-transition { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }

        {{-- Card Styles --}}
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); }

        @media(max-width:767px) {
            .shop-name-text { font-size: 1rem !important; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .card-hover:hover { transform: none; }
        }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-bangla selection:bg-primary/20 selection:text-primary">

    {{-- Top Announcement Bar --}}
    @if($client->announcement_text)
    <div class="bg-primary text-white text-center py-2.5 px-4 text-xs font-semibold tracking-wide flex items-center justify-center gap-2">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-40"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
        </span>
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Modern Glass Header --}}
    <header class="bg-white/90 backdrop-blur-xl sticky top-0 z-50 border-b border-slate-200/60 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 md:h-20 flex justify-between items-center gap-3">

            <a href="{{ $baseUrl }}" class="flex items-center gap-2 premium-transition hover:opacity-80 min-w-0">
                @if($client->logo)
                    <img src="{{ asset('storage/' . $client->logo) }}" class="h-8 md:h-12 object-contain flex-shrink-0" alt="{{ $client->shop_name }}">
                @else
                    <div class="w-10 h-10 bg-primary/10 rounded-xl border border-primary/20 flex items-center justify-center text-primary flex-shrink-0">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <span class="shop-name-text text-xl md:text-2xl font-bold tracking-tight text-slate-900">{{ $client->shop_name }}</span>
                @endif
            </a>

            {{-- Desktop Search Bar --}}
            @if($client->widget('show_search_bar'))
            <form action="{{ $baseUrl }}" method="GET" class="hidden md:flex flex-1 max-w-md mx-8 relative group/search">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within/search:text-primary premium-transition"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="পণ্য খুঁজুন..."
                    class="w-full bg-slate-100/50 hover:bg-slate-100 border-2 border-transparent focus:border-primary/20 focus:bg-white pl-11 pr-5 py-2.5 rounded-2xl text-slate-700 text-sm font-medium focus:ring-4 focus:ring-primary/5 premium-transition placeholder-slate-400 outline-none">
            </form>
            @endif

            {{-- Desktop Actions --}}
            <div class="hidden md:flex items-center gap-3">
                <a href="{{ $clean ? $baseUrl . '/track' : route('shop.track', $client->slug) }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:text-primary hover:bg-primary/5 premium-transition">
                    <i class="fas fa-truck-fast"></i> <span>ট্র্যাক অর্ডার</span>
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-primary hover:text-white flex items-center justify-center text-slate-600 premium-transition shadow-sm">
                    <i class="fab fa-facebook-messenger text-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 w-full pb-24">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-slate-200 pt-16 pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">

            {{-- Brand Column --}}
            <div class="space-y-6">
                <a href="{{ $baseUrl }}" class="inline-block">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ $client->shop_name }}</span>
                </a>
                <p class="text-slate-500 font-medium text-sm leading-relaxed">বিশ্বস্ত মানের পণ্য, সেরা সেবা। আপনার সন্তুষ্টিই আমাদের লক্ষ্য।</p>
                @include('shop.partials.footer-links', ['client' => $client])
                <div class="flex gap-3 items-center">
                    @if($client->facebook_url ?? false)
                    <a href="{{ $client->facebook_url }}" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary cursor-pointer premium-transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    @endif
                    @if($client->instagram_url ?? false)
                    <a href="{{ $client->instagram_url }}" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary cursor-pointer premium-transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                    @endif
                    @if($client->fb_page_id ?? false)
                    <a href="https://m.me/{{ $client->fb_page_id }}" target="_blank" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary cursor-pointer premium-transition">
                        <i class="fab fa-facebook-messenger"></i>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">দোকান</h4>
                <div class="flex flex-col space-y-4 font-medium text-sm text-slate-500">
                    <a href="?category=all" class="hover:text-primary premium-transition w-fit">সকল পণ্য</a>
                    <a href="#" class="hover:text-primary premium-transition w-fit">নতুন আসা</a>
                    <a href="#" class="hover:text-primary premium-transition w-fit">বেস্ট সেলার</a>
                </div>
            </div>

            {{-- Support --}}
            <div>
                <h4 class="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">সাহায্য</h4>
                <div class="flex flex-col space-y-4 font-medium text-sm text-slate-500">
                    <a href="{{ $clean ? $baseUrl . '/track' : route('shop.track', $client->slug) }}" class="hover:text-primary premium-transition w-fit">অর্ডার ট্র্যাক</a>
                    <a href="#" class="hover:text-primary premium-transition w-fit">শিপিং পলিসি</a>
                    <a href="#" class="hover:text-primary premium-transition w-fit">রিটার্ন ও রিফান্ড</a>
                </div>
            </div>

            {{-- Contact --}}
            <div>
                <h4 class="font-bold text-slate-900 mb-6 text-sm uppercase tracking-wider">যোগাযোগ</h4>
                <div class="flex flex-col space-y-5">
                    @if($client->phone)
                    <a href="tel:{{ $client->phone }}" class="flex items-start gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-primary/5 group-hover:text-primary premium-transition shrink-0">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <span class="block text-[11px] font-bold uppercase text-slate-400 tracking-wider mb-1">কল করুন</span>
                            <span class="text-sm font-bold text-slate-700 group-hover:text-primary premium-transition">{{ $client->phone }}</span>
                        </div>
                    </a>
                    @endif

                    @if($client->email)
                    <a href="mailto:{{ $client->email }}" class="flex items-start gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-primary/5 group-hover:text-primary premium-transition shrink-0">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <span class="block text-[11px] font-bold uppercase text-slate-400 tracking-wider mb-1">ইমেইল</span>
                            <span class="text-sm font-bold text-slate-700 group-hover:text-primary premium-transition">{{ $client->email }}</span>
                        </div>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm font-medium text-slate-500">&copy; {{ date('Y') }} {{ $client->shop_name }}। সর্বস্বত্ব সংরক্ষিত।</p>
            <div class="flex gap-2">
                <i class="fab fa-cc-visa text-2xl text-slate-300 hover:text-slate-500 premium-transition"></i>
                <i class="fab fa-cc-mastercard text-2xl text-slate-300 hover:text-slate-500 premium-transition"></i>
                <i class="fab fa-cc-amex text-2xl text-slate-300 hover:text-slate-500 premium-transition"></i>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
