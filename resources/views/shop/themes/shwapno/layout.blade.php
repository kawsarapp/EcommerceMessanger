<!DOCTYPE html>
@php 
$clean  = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')); 
$baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug); 

// Footer settings (from widgets JSON — no migration needed)
$footerDesc        = $client->widgets['footer']['brand_description'] ?? $client->description ?? '';
$footerShowPay     = $client->widgets['footer']['show_payment'] ?? true;
$footerShowSocial  = $client->widgets['footer']['show_social'] ?? true;
$footerCopyright   = $client->footer_text ?? ('© ' . date('Y') . ' ' . $client->shop_name . '. All Rights Reserved.');
@endphp
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', $client->shop_name)</title>
    <meta name="description" content="@yield('meta_description', $client->meta_description ?? $client->about_us ?? 'Welcome to ' . $client->shop_name)">
    <meta name="theme-color" content="{{ $client->primary_color ?? '#ffffff' }}">
    <link rel="icon" type="image/x-icon" href="{{ $client->logo ? asset('storage/'.$client->logo) : asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ $client->logo ? asset('storage/'.$client->logo) : asset('favicon.ico') }}">
    <meta property="og:title" content="@yield('title', $client->shop_name)">
    <meta property="og:description" content="@yield('meta_description', $client->meta_description ?? $client->about_us)">
    <meta property="og:image" content="@yield('meta_image', $client->logo ? asset('storage/'.$client->logo) : asset('images/logo.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    @include('shop.partials.tracking', ['client' => $client])
    meta_description ?? ($client->description ?? $client->shop_name . ' - Online Shop') }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $client->primary_color ?? "#e31e24" }}',
                        secondary: '{{$client->secondary_color ?? $client->primary_color ?? "#facc15"}}',
                        swred:   '#e31e24',
                        swyellow:'#ffd100',
                        swdark:  '#222222',
                        swbg:    '#f8f9fa',
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    <style>
        :root { --swred: #e31e24; --swyellow: #ffd100; }
        [x-cloak]{ display: none !important; }
        body { background-color: #f7f8f9; color: #333; }
        
        .sw-nav-link { font-size: 11px; font-weight: 700; color: #4b5563; text-transform: uppercase; padding: 12px 10px; transition: color 0.15s; display: block; border-bottom: 2px solid transparent; }
        .sw-nav-link:hover, .sw-nav-link.active { color: #e31e24; border-bottom-color: #e31e24; }
        
        .sw-sidebar { border-right: 1px solid #e5e7eb; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .sw-sidebar-item { padding: 11px 16px; font-size: 13px; color: #4b5563; transition: background 0.15s, color 0.15s; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f9fafb; }
        .sw-sidebar-item:hover { color: #e31e24; background-color: #fffafb; font-weight: 600; }
        
        .sw-btn-pill { border-radius: 9999px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer; }
        .sw-btn-red { background-color: #e31e24; color: #fff; border: 1px solid #e31e24; }
        .sw-btn-red:hover { background-color: #c8161c; }
        
        .footer-heading { font-weight: 700; color: #f3f4f6; font-size: 13px; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .footer-link { color: #9ca3af; font-size: 12px; display: block; margin-bottom: 10px; transition: color 0.2s; }
        .footer-link:hover { color: #ffd100; }
        
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen font-sans selection:bg-swred/20 selection:text-swred" style="{\{ $client->bg_color ? 'background-color: '.$client->bg_color.' !important;' : '' \}}">
    
    @if(!empty($client->announcement_text))
    <div class="bg-swdark text-swyellow text-center py-1.5 px-4 text-[11px] md:text-xs font-bold w-full uppercase tracking-wider relative z-50 flex items-center justify-center gap-2 shadow-sm">
        <i class="fas fa-bullhorn shrink-0 text-white/50"></i>
        <span>{{ $client->announcement_text }}</span>
    </div>
    @endif

    {{-- Main Top Header --}}
    <header class="bg-swred sticky sm:relative top-0 z-50 shadow-sm border-b border-red-700">
        <div class="max-w-[1340px] mx-auto px-4 lg:px-6">
            <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-4 py-3 md:py-0 md:h-16">
                
                {{-- Logo --}}
                <div class="flex items-center gap-6 shrink-0 w-full md:w-auto justify-between md:justify-start">
                    <a href="{{ $baseUrl }}" class="flex items-center bg-black h-16 px-4 md:-ml-6 -my-3 md:my-0">
                        @if($client->logo)
                            <img src="{{ asset('storage/'.$client->logo) }}" loading="lazy" class="h-10 object-contain" alt="{{ $client->shop_name }}">
                        @else
                            <div class="text-white font-black text-xl">{{ $client->shop_name }}</div>
                        @endif
                    </a>
                    
                    @if($client->widgets['location_picker']['active'] ?? false)
                    <button class="hidden lg:flex items-center gap-2 border border-red-400 bg-red-600/30 hover:bg-red-600/50 transition px-3 py-1.5 rounded-sm text-white text-[11px] h-9">
                        <i class="fas fa-truck text-base"></i>
                        <span class="font-medium whitespace-nowrap">{{ $client->widgets['location_picker']['text'] ?? 'Select delivery location' }}</span>
                        <i class="fas fa-chevron-down text-[9px] ml-1"></i>
                    </button>
                    @endif

                    {{-- Mobile Cart --}}
                    @php $cartCount = session()->has('cart') ? count(session()->get('cart')) : 0; @endphp
                    <a href="{{ $clean ? $baseUrl.'/cart' : route('shop.cart', $client->slug) }}" class="md:hidden text-white flex items-center gap-2">
                        <div class="relative">
                            <i class="fas fa-shopping-bag text-2xl"></i>
                            <span class="absolute -top-1 -right-2 bg-swyellow text-swdark text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">{{ $cartCount }}</span>
                        </div>
                    </a>
                </div>

                {{-- Search Bar --}}
                @php
                    $searchActive = $client->widgets['search_bar']['active'] ?? true;
                    $searchText   = $client->widgets['search_bar']['text'] ?? 'Search products...';
                    $searchColor  = $client->widgets['search_bar']['color'] ?? '#ffd100';
                @endphp
                @if($searchActive)
                <div class="w-full md:flex-1 max-w-2xl px-0 md:px-4 order-3 md:order-none">
                    <form action="{{ $baseUrl }}" method="GET" class="w-full relative flex items-center bg-white rounded-sm overflow-hidden h-10 shadow-inner">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $searchText }}" 
                            class="w-full bg-transparent px-4 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none border-none h-full">
                        <button class="text-swdark w-12 h-full flex items-center justify-center transition border-l border-yellow-200" style="background-color:{{ $searchColor }};">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                @endif

                {{-- Right Actions (Desktop) --}}
                <div class="hidden md:flex items-center gap-3 shrink-0">
                    @if($client->widgets['language_switcher']['active'] ?? false)
                    <a href="#" class="border border-red-400 text-white hover:bg-red-600 px-3 py-1.5 text-xs font-bold rounded-sm h-9 flex items-center transition">?????</a>
                    @endif
                    
                    <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="bg-white/10 hover:bg-white/20 border border-red-400 text-white px-4 py-1.5 text-[11px] font-bold rounded-sm h-9 flex items-center gap-2 transition">
                        <i class="far fa-user text-sm"></i> Track Order
                    </a>

                    @php $cartCount = session()->has('cart') ? count(session()->get('cart')) : 0; @endphp
                    <a href="{{ $clean ? $baseUrl.'/cart' : route('shop.cart', $client->slug) }}" class="relative ml-1 group cursor-pointer h-16 flex items-center px-4 bg-red-700/40 hover:bg-red-700/60 transition md:-mr-6 border-l border-red-800">
                        <i class="fas fa-shopping-bag text-white text-2xl group-hover:scale-110 transition"></i>
                        <span class="absolute top-3 right-2 bg-swyellow text-swdark text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center border border-swdark shadow-sm">{{ $cartCount }}</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Sub Navigation --}}
    <nav class="bg-white border-b border-gray-200 hidden md:block shadow-sm">
        <div class="max-w-[1340px] mx-auto px-4 lg:px-6 flex items-center justify-between">
            <div class="flex items-center gap-6 xl:gap-10">
                <div class="w-64 border-r border-gray-100 flex items-center gap-3 py-3 cursor-pointer group">
                    <i class="fas fa-bars text-gray-500 group-hover:text-swred transition"></i>
                    <span class="text-xs font-black text-gray-800 uppercase tracking-tight group-hover:text-swred transition">
                        {{ $client->widgets['category_filter']['text'] ?? 'SHOP BY CATEGORY' }}
                    </span>
                </div>
                <div class="flex items-center gap-1 xl:gap-3">
                    @if(isset($primaryMenu) && $primaryMenu->items->count() > 0)
                        @foreach($primaryMenu->items as $item)
                        <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="sw-nav-link {{ request()->is(ltrim($item->resolved_url, '/')) ? 'active' : '' }}">{{ $item->label }}</a>
                        @endforeach
                    @else
                        <a href="{{ $baseUrl }}" class="sw-nav-link {{ request()->routeIs('shop.show') && !request()->has('category') ? 'active' : '' }}">GREAT DEALS</a>
                        <a href="{{ $baseUrl }}?category=all" class="sw-nav-link">ALL PRODUCTS</a>
                        <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="sw-nav-link">TRACK ORDER</a>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-5">
                @if($client->phone)
                <a href="tel:{{ $client->phone }}" class="text-[11px] text-gray-500 hover:text-swred flex items-center gap-1.5 font-medium transition"><i class="fas fa-phone-alt text-gray-400"></i> {{ $client->phone }}</a>
                @endif
                @if($client->widgets['top_help_links'] ?? true)
                <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="text-[11px] text-gray-500 hover:text-swred flex items-center gap-1.5 font-medium transition"><i class="far fa-question-circle text-gray-400"></i> Help</a>
                @endif
            </div>
        </div>
    </nav>

    <main class="flex-1 w-full bg-[#f7f8f9] pb-16">
        @yield('content')
    </main>

    {{-- ====== FOOTER ====== --}}
    <footer class="bg-gray-900 border-t border-gray-800 mt-auto pt-12">
        <div class="max-w-[1340px] mx-auto px-4 lg:px-6">

            {{-- Pre-footer promo banner --}}
            @if($client->homepage_banner_active && $client->homepage_banner_image)
            <a href="{{ $client->homepage_banner_link ?? '#' }}" class="w-full rounded mb-10 overflow-hidden relative group hidden md:block -mt-4">
                <img src="{{ asset('storage/'.$client->homepage_banner_image) }}" class="w-full h-36 object-cover opacity-80 group-hover:scale-105 transition duration-700" loading="lazy" alt="{{ $client->homepage_banner_title ?? '' }}">
                <div class="absolute inset-0 bg-gradient-to-r from-gray-900/90 to-gray-900/20 flex items-center px-10">
                    @if($client->homepage_banner_title)
                    <h2 class="text-3xl font-black text-white leading-tight">{!! nl2br(e($client->homepage_banner_title)) !!}</h2>
                    @endif
                </div>
            </a>
            @endif

            {{-- Main Footer Columns --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 pb-10 border-b border-gray-800">
                
                {{-- Column 1: Brand --}}
                <div class="col-span-2 md:col-span-1 lg:col-span-1">
                    <div class="bg-black inline-block p-2 mb-4 rounded-sm">
                        @if($client->logo)
                            <img src="{{ asset('storage/'.$client->logo) }}" loading="lazy" class="h-8 object-contain" alt="{{ $client->shop_name }}">
                        @else
                            <h3 class="text-white font-black text-lg">{{ $client->shop_name }}</h3>
                        @endif
                    </div>
                    @if($footerDesc)
                    <p class="text-gray-400 text-[12px] leading-relaxed mb-4">{{ $footerDesc }}</p>
                    @elseif($client->tagline)
                    <p class="text-gray-300 text-[13px] font-semibold mb-1">{{ $client->tagline }}</p>
                    @endif
                    
                    @if($client->phone)
                    <p class="text-gray-500 text-[11px] mb-1"><i class="fas fa-phone text-gray-600 mr-2"></i>{{ $client->phone }}</p>
                    @endif
                    @if($client->email)
                    <p class="text-gray-500 text-[11px]"><i class="fas fa-envelope text-gray-600 mr-2"></i>{{ $client->email }}</p>
                    @endif
                </div>

                {{-- Column 2: Footer Menu 1 --}}
                <div>
                    <h4 class="footer-heading">{{ $footerMenu1->name ?? ($client->widgets['footer']['menu1_title'] ?? 'Information') }}</h4>
                    @if(isset($footerMenu1) && $footerMenu1->items->count() > 0)
                        @foreach($footerMenu1->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-link">{{ $item->label }}</a>
                        @endforeach
                    @endif
                </div>

                {{-- Column 3: Footer Menu 2 --}}
                <div>
                    <h4 class="footer-heading">{{ $footerMenu2->name ?? ($client->widgets['footer']['menu2_title'] ?? 'Customer Service') }}</h4>
                    @if(isset($footerMenu2) && $footerMenu2->items->count() > 0)
                        @foreach($footerMenu2->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-link">{{ $item->label }}</a>
                        @endforeach
                    @else
                        <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="footer-link">Track Order</a>
                    @endif
                </div>

                {{-- Column 4: Footer Menu 3 --}}
                <div>
                    <h4 class="footer-heading">{{ $footerMenu3->name ?? ($client->widgets['footer']['menu3_title'] ?? 'Quick Links') }}</h4>
                    @if(isset($footerMenu3) && $footerMenu3->items->count() > 0)
                        @foreach($footerMenu3->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-link">{{ $item->label }}</a>
                        @endforeach
                    @endif
                </div>

                {{-- Column 5: Payment + Social --}}
                <div>
                    @if($footerShowPay)
                    <h4 class="footer-heading">Payment Options</h4>
                    <div class="flex flex-wrap gap-2 mb-5">
                        @if($client->cod_active)
                        <span class="bg-gray-800 px-2 py-1 text-[10px] font-bold text-gray-300 rounded-sm border border-gray-700">COD</span>
                        @endif
                        @if($client->partial_payment_active || $client->full_payment_active)
                        <span class="bg-gray-800 px-2 py-1 text-[10px] font-bold text-blue-400 rounded-sm border border-gray-700">Mobile Banking</span>
                        @endif
                        @if($client->isPaymentGatewayActive('bkash_pgw') || $client->isPaymentGatewayActive('bkash_merchant'))
                        <span class="bg-gray-800 px-2 py-1 text-[10px] font-bold text-pink-400 rounded-sm border border-gray-700">bKash</span>
                        @endif
                        @if($client->isPaymentGatewayActive('sslcommerz'))
                        <span class="bg-gray-800 px-2 py-1 text-[10px] font-bold text-green-400 rounded-sm border border-gray-700">SSL Commerz</span>
                        @endif
                    </div>
                    @endif

                    @if($footerShowSocial)
                    <h4 class="footer-heading">Follow Us</h4>
                    <div class="flex gap-2 flex-wrap">
                        @if($client->social_facebook ?? $client->facebook_url)
                        <a href="{{ $client->social_facebook ?? $client->facebook_url }}" target="_blank" class="w-8 h-8 bg-[#3b5998] text-white flex items-center justify-center rounded-sm hover:opacity-80 transition"><i class="fab fa-facebook-f text-xs"></i></a>
                        @endif
                        @if($client->social_youtube ?? $client->youtube_url)
                        <a href="{{ $client->social_youtube ?? $client->youtube_url }}" target="_blank" class="w-8 h-8 bg-[#ff0000] text-white flex items-center justify-center rounded-sm hover:opacity-80 transition"><i class="fab fa-youtube text-xs"></i></a>
                        @endif
                        @if($client->social_instagram ?? $client->instagram_url)
                        <a href="{{ $client->social_instagram ?? $client->instagram_url }}" target="_blank" class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center rounded-sm hover:opacity-80 transition"><i class="fab fa-instagram text-xs"></i></a>
                        @endif
                        @if($client->tiktok_url)
                        <a href="{{ $client->tiktok_url }}" target="_blank" class="w-8 h-8 bg-black text-white flex items-center justify-center rounded-sm hover:opacity-80 transition border border-gray-700"><i class="fab fa-tiktok text-xs"></i></a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Copyright --}}
            <div class="py-6 flex flex-col md:flex-row items-center justify-between gap-3">
                <p class="text-[11px] text-gray-500 text-center md:text-left">
                    {!! nl2br(e($footerCopyright)) !!}
                </p>
                @if(!empty($client->footer_links) && is_array($client->footer_links))
                <div class="flex gap-4 flex-wrap justify-center">
                    @foreach($client->footer_links as $link)
                    <a href="{{ $link['url'] ?? '#' }}" class="text-[11px] text-gray-500 hover:text-swyellow transition">{{ $link['title'] ?? '' }}</a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </footer>

        @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
@include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
    
    {{-- Popup Banner --}}
    @if($client->popup_active)
    @php
        $showPopup = true;
        if (!empty($client->popup_expires_at) && \Carbon\Carbon::now()->greaterThan(\Carbon\Carbon::parse($client->popup_expires_at))) { $showPopup = false; }
        if (!empty($client->popup_pages)) {
            $pages = is_array($client->popup_pages) ? $client->popup_pages : (json_decode((string)$client->popup_pages, true) ?? []);
            if (!empty($pages)) {
                $currentRoute = request()->route()->getName();
                $isHome     = str_contains($currentRoute, 'show') && !str_contains($currentRoute, 'product') && !str_contains($currentRoute, 'checkout');
                $isProduct  = str_contains($currentRoute, 'product');
                $isCheckout = str_contains($currentRoute, 'checkout');
                if (($isHome && !in_array('home', $pages)) || ($isProduct && !in_array('product', $pages)) || ($isCheckout && !in_array('checkout', $pages))) { $showPopup = false; }
            }
        }
    @endphp
    @if($showPopup)
    <div x-data="{ open: false }" x-init="setTimeout(() => { open = true }, {{ ($client->popup_delay ?? 3) * 1000 }})" x-cloak x-show="open" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div @click.outside="open = false" x-show="open" x-transition.enter="transition ease-out duration-300" x-transition.enter-start="opacity-0 scale-90" x-transition.enter-end="opacity-100 scale-100" class="bg-white rounded shadow-2xl max-w-md w-full overflow-hidden relative">
            <button @click="open = false" class="absolute top-2 right-2 w-8 h-8 bg-black/50 hover:bg-red-500 text-white rounded-full flex items-center justify-center transition z-10 shadow">
                <i class="fas fa-times text-sm"></i>
            </button>
            @if($client->popup_link)<a href="{{ $client->popup_link }}" class="block">@endif
            @if($client->popup_image)
                <img src="{{ asset('storage/'.$client->popup_image) }}" class="w-full h-auto max-h-[350px] object-cover">
            @endif
            @if($client->popup_title || $client->popup_description)
                <div class="p-6 text-center">
                    @if($client->popup_title)<h2 class="text-xl font-black text-gray-800 mb-2">{{ $client->popup_title }}</h2>@endif
                    @if($client->popup_description)<p class="text-sm text-gray-600 mb-4">{{ $client->popup_description }}</p>@endif
                    @if($client->popup_link)<span class="inline-block bg-swred text-white px-6 py-2 rounded-full font-bold text-xs uppercase shadow hover:bg-red-700 transition">Explore Now</span>@endif
                </div>
            @endif
            @if($client->popup_link)</a>@endif
        </div>
    </div>
    @endif
    @endif
</body>
</html>


