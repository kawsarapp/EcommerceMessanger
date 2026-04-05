<!DOCTYPE html>
@php
    $clean = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'));
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);
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
    
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Alpine Plugins MUST load before Alpine core --}}
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '{{$client->primary_color ?? "#f6a52a"}}',
                        secondary: '{{$client->secondary_color ?? $client->primary_color ?? "#facc15"}}',
                        dark: '#222222',
                        lightgreen: '#f5f7f0',
                        vgtext: '#777777',
                    },
                    fontFamily: {
                        sans: ['"Poppins"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#f6a52a"}};
            --mob-primary: {{$client->primary_color ?? "#f6a52a"}};
        }
        body { font-family: 'Poppins', sans-serif; background-color: #ffffff; color: #777777; }
        [x-cloak] { display: none !important; }
        .hide-scroll::-webkit-scrollbar { display: none; }

        /* Custom Styles */
        .vg-nav-link { font-size: 13px; font-weight: 500; color: #222222; text-transform: uppercase; transition: color 0.3s ease; display: inline-flex; align-items: center; gap: 4px; }
        .vg-nav-link:hover { color: var(--tw-color-primary); }
        .vg-heading { color: #222222; font-weight: 600; }
        .btn-primary { background-color: var(--tw-color-primary); color: #fff; padding: 10px 24px; border-radius: 4px; font-weight: 600; font-size: 14px; transition: background 0.3s ease, transform 0.2s; }
        .btn-primary:hover { background-color: var(--tw-color-primary); filter: brightness(0.9); transform: translateY(-1px); }
        .btn-dark { background-color: #222222; color: #fff; padding: 10px 24px; border-radius: 4px; font-weight: 600; font-size: 14px; transition: background 0.3s ease; }
        .btn-dark:hover { background-color: var(--tw-color-primary); }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen selection:bg-primary selection:text-white pb-20 md:pb-0" style="{{ $client->bg_color ? 'background-color: '.$client->bg_color.' !important;' : '' }}">

    {{-- Flash Sale Bar --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Announcement Bar --}}
    @if($client->announcement_text)
    <div class="bg-primary/10 text-primary text-center py-1.5 px-4 text-xs font-semibold">
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Top Bar (Dark) --}}
    <div class="bg-[#1f1f1f] text-[#cfcfcf] text-[11px] py-2.5 hidden lg:block">
        <div class="max-w-[1400px] mx-auto px-4 xl:px-8 flex justify-between items-center tracking-wide">
            <div>{{ $client->topbar_text ?? '?? ????? ???? ????? ?????? | ???????? ????????' }}</div>
            <div class="flex items-center gap-6">
                <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="hover:text-white transition">My order</a>
                <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="hover:text-white transition">Track order</a>
                <a href="{{ $client->email ? 'mailto:'.$client->email : '#' }}" class="hover:text-white transition">Contact us</a>
                @if($client->widgets['language_switcher']['active'] ?? false)
                <span class="pl-6 border-l border-gray-700 cursor-pointer hover:text-white"><i class="fas fa-globe mr-1"></i> BD</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Main Header --}}
    <header class="bg-white sticky sm:relative top-0 z-40 border-b border-gray-100 shadow-sm md:shadow-none">
        <div class="max-w-[1400px] mx-auto px-4 xl:px-8">
            <div class="flex justify-between items-center h-20">
                
                {{-- Mobile Menu Toggle --}}
                <div class="lg:hidden flex items-center">
                    <button class="text-dark text-2xl cursor-pointer" @click="$dispatch('toggle-mobile-categories')"><i class="fas fa-bars"></i></button>
                </div>

                {{-- Logo --}}
                <a href="{{ $baseUrl }}" class="flex items-center gap-2 shrink-0">
                    @if($client->logo)
                        <img src="{{ asset('storage/'.$client->logo) }}" class="h-10 md:h-12 object-contain hidden md:block" alt="{{ $client->shop_name }}">
                        <img src="{{ asset('storage/'.$client->logo) }}" class="h-8 object-contain md:hidden" alt="{{ $client->shop_name }}">
                    @else
                        <span class="text-2xl md:text-3xl font-black text-primary uppercase tracking-tight flex items-center gap-1">
                            <i class="fas fa-leaf text-xl text-green-500"></i> {{ $client->shop_name }}
                        </span>
                    @endif
                </a>

                {{-- Desktop Navigation Center --}}
                <nav class="hidden lg:flex items-center gap-8 translate-x-8">
                    @if(isset($primaryMenu) && $primaryMenu->items->count() > 0)
                        @foreach($primaryMenu->items as $item)
                        <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="vg-nav-link {!! request()->is(ltrim($item->resolved_url, '/')) ? '!text-primary' : '' !!}">
                            {{ $item->label }}
                        </a>
                        @endforeach
                    @else
                        <a href="{{ $baseUrl }}" class="vg-nav-link">Home</a>
                        <a href="{{ $baseUrl }}?category=all" class="vg-nav-link">Shop</a>
                        @if(isset($categories) && $categories->count() > 0)
                            @foreach($categories->take(2) as $cat)
                            <a href="{{ $baseUrl }}?category={{ $cat->slug }}" class="vg-nav-link">{{ $cat->name }}</a>
                            @endforeach
                        @endif
                        <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="vg-nav-link">Track Order</a>
                    @endif
                </nav>

                {{-- Right Icons --}}
                <div class="flex items-center gap-5 xl:gap-7 shrink-0 text-dark">
                    <button class="hover:text-primary transition text-lg" onclick="document.getElementById('mobile-search').classList.toggle('hidden')"><i class="fas fa-search"></i></button>
                    <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="hover:text-primary transition text-lg hidden md:block"><i class="far fa-user"></i></a>
                    
                    @php
                        $cartKey   = 'cart_' . $client->id;
                        $cartItems = session($cartKey, []);
                        $cartCount = count($cartItems);
                        $cartUrl   = $clean ? 'https://'.$clean.'/cart' : route('shop.cart', $client->slug);
                    @endphp
                    <a href="{{ $cartUrl }}" class="hover:text-primary transition text-lg relative group">
                        <i class="fas fa-shopping-bag"></i>
                        <span data-cart-badge class="absolute -top-2 -right-2 bg-primary text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center shadow-sm group-hover:scale-110 transition">{{ $cartCount }}</span>
                    </a>
                </div>

            </div>
        </div>
        
        {{-- Expandable Search --}}
        <div id="mobile-search" class="hidden absolute top-full left-0 w-full bg-white p-4 shadow-lg border-t border-gray-100 z-50">
            <form action="{{ $baseUrl }}" method="GET" class="w-full relative flex items-center max-w-2xl mx-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." 
                    class="w-full bg-gray-50 px-4 py-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none border border-gray-200 rounded-sm">
                <button class="absolute right-0 text-dark h-full px-5 hover:text-primary transition border-l border-gray-200">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1 w-full bg-white">
        @yield('content')
    </main>

    {{-- ===== FOOTER ===== --}}
    @php
        $footerDesc  = $client->widgets['footer']['brand_description'] ?? $client->description ?? '';
        $showSocial  = $client->widgets['footer']['show_social'] ?? true;
        $showPayment = $client->widgets['footer']['show_payment'] ?? true;
        $footerCopy  = $client->footer_text ?? ('? ' . date('Y') . ' ' . $client->shop_name . '. All Rights Reserved.');
        $fbUrl = $client->social_facebook ?? $client->facebook_url ?? null;
        $ytUrl = $client->social_youtube  ?? $client->youtube_url  ?? null;
        $igUrl = $client->social_instagram ?? $client->instagram_url ?? null;
        $tkUrl = $client->tiktok_url ?? null;
        $col1Title = $client->widgets['footer']['menu1_title'] ?? ($footerMenu1->name ?? 'Services');
        $col2Title = $client->widgets['footer']['menu2_title'] ?? ($footerMenu2->name ?? 'Privacy & Terms');
        $col3Title = $client->widgets['footer']['menu3_title'] ?? ($footerMenu3->name ?? 'My Account');
    @endphp

    <footer class="bg-white border-t border-gray-100 mt-20 pt-16 pb-12">
        <div class="max-w-[1400px] mx-auto px-4 xl:px-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 xl:gap-12">
            
            {{-- Column 1: Brand Info --}}
            <div class="lg:col-span-2">
                <a href="{{ $baseUrl }}" class="inline-block mb-6">
                    @if($client->logo)
                        <img src="{{ asset('storage/'.$client->logo) }}" class="h-10 object-contain" alt="{{ $client->shop_name }}">
                    @else
                        <span class="text-3xl font-black text-primary uppercase flex items-center gap-1">
                            <i class="fas fa-leaf text-xl text-green-500"></i> {{ $client->shop_name }}
                        </span>
                    @endif
                </a>
                <p class="text-[13px] text-gray-500 leading-relaxed mb-8 pr-4">
                    {{ $footerDesc ?: ($client->tagline ?? $client->shop_name . ' ? ????? ????????, ??? ????, ?????????') }}
                </p>
                <div class="flex flex-wrap items-center gap-8">
                    @if($client->address)
                    <div class="flex items-start gap-4">
                        <i class="fas fa-map-marker-alt text-primary text-xl mt-1"></i>
                        <div>
                            <h4 class="text-sm font-bold text-dark mb-1">{{ $client->widgets['footer']['contact_title'] ?? 'Contact us' }}</h4>
                            <p class="text-[12px] text-gray-500">{{ $client->address }}</p>
                        </div>
                    </div>
                    @endif
                    @if($client->phone || $client->email)
                    <div class="flex items-start gap-4">
                        <i class="fas fa-phone-alt text-primary text-xl mt-1"></i>
                        <div>
                            @if($client->phone)<a href="tel:{{ $client->phone }}" class="block text-[12px] text-gray-500 hover:text-primary font-medium">{{ $client->phone }}</a>@endif
                            @if($client->email)<a href="mailto:{{ $client->email }}" class="block text-[12px] text-gray-500 hover:text-primary mt-1">{{ $client->email }}</a>@endif
                        </div>
                    </div>
                    @endif
                </div>
                @if($showSocial && ($fbUrl || $ytUrl || $igUrl || $tkUrl))
                <div class="flex gap-3 mt-6">
                    @if($fbUrl)<a href="{{ $fbUrl }}" target="_blank" class="text-gray-400 hover:text-primary transition"><i class="fab fa-facebook-f"></i></a>@endif
                    @if($ytUrl)<a href="{{ $ytUrl }}" target="_blank" class="text-gray-400 hover:text-primary transition"><i class="fab fa-youtube"></i></a>@endif
                    @if($igUrl)<a href="{{ $igUrl }}" target="_blank" class="text-gray-400 hover:text-primary transition"><i class="fab fa-instagram"></i></a>@endif
                    @if($tkUrl)<a href="{{ $tkUrl }}" target="_blank" class="text-gray-400 hover:text-primary transition"><i class="fab fa-tiktok"></i></a>@endif
                </div>
                @endif
            </div>

            {{-- Column 2 --}}
            <div class="lg:col-span-1">
                <h4 class="vg-heading text-lg mb-6">{{ $col1Title }}</h4>
                <div class="flex flex-col space-y-3">
                    @if(isset($footerMenu1) && $footerMenu1->items->count() > 0)
                        @foreach($footerMenu1->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="text-[14px] text-gray-500 hover:text-primary transition capitalize">{{ $item->label }}</a>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Column 3 --}}
            <div class="lg:col-span-1">
                <h4 class="vg-heading text-lg mb-6">{{ $col2Title }}</h4>
                <div class="flex flex-col space-y-3">
                    @if(isset($footerMenu2) && $footerMenu2->items->count() > 0)
                        @foreach($footerMenu2->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="text-[14px] text-gray-500 hover:text-primary transition capitalize">{{ $item->label }}</a>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Column 4 --}}
            <div class="lg:col-span-1">
                <h4 class="vg-heading text-lg mb-6">{{ $col3Title }}</h4>
                <div class="flex flex-col space-y-3">
                    @if(isset($footerMenu3) && $footerMenu3->items->count() > 0)
                        @foreach($footerMenu3->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="text-[14px] text-gray-500 hover:text-primary transition capitalize">{{ $item->label }}</a>
                        @endforeach
                    @else
                        <a href="{{ $clean ? $baseUrl.'/cart' : route('shop.cart', $client->slug) }}" class="text-[14px] text-gray-500 hover:text-primary transition capitalize">My cart</a>
                        <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="text-[14px] text-gray-500 hover:text-primary transition capitalize">Track order</a>
                    @endif
                </div>
            </div>

        </div>

    {{-- Dynamic Social + Payment + Copyright --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pb-6">
        @include('shop.partials.dynamic-footer-extras', ['client' => $client, 'baseUrl' => $baseUrl ?? '', 'clean' => $clean ?? ''])
    </div>
    </footer>

    {{-- Bottom Copyright Strip --}}
    <div class="bg-[#1f1f1f] text-gray-400 text-xs py-4">
        <div class="max-w-[1400px] mx-auto px-4 xl:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p>{!! nl2br(e($footerCopy)) !!}</p>
            <div class="flex gap-4 flex-wrap">
                @if(!empty($client->footer_links))
                    @foreach((array)$client->footer_links as $fl)
                    <a href="{{ $fl['url'] ?? '#' }}" class="hover:text-white transition">{{ $fl['title'] ?? '' }}</a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

        @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
@include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
    
</body>
</html>



