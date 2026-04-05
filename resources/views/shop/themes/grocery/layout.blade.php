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
    <meta name="theme-color" content="{{ $client->primary_color ?? '#10b981' }}">
    <link rel="icon" type="image/x-icon" href="{{ $client->logo ? asset('storage/'.$client->logo) : asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ $client->logo ? asset('storage/'.$client->logo) : asset('favicon.ico') }}">
    <meta property="og:title" content="@yield('title', $client->shop_name)">
    <meta property="og:description" content="@yield('meta_description', $client->meta_description ?? $client->about_us)">
    <meta property="og:image" content="@yield('meta_image', $client->logo ? asset('storage/'.$client->logo) : asset('images/logo.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    @include('shop.partials.tracking', ['client' => $client])

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary:   '{{$client->primary_color ?? "#10b981"}}',
                        secondary: '{{$client->secondary_color ?? $client->primary_color ?? "#facc15"}}',
                        dark:      '#1a1a2e',
                        light:     '#f0fdf4',
                    },
                    fontFamily: {
                        sans: ['Nunito', 'sans-serif']
                    }
                }
            }
        }
    </script>

    @include('shop.partials.dynamic-colors', ['client' => $client])

    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#10b981"}};
            --mob-primary: {{$client->primary_color ?? "#10b981"}};
        }
        [x-cloak] { display: none !important; }
        body { background-color: #f0fdf4; color: #1f2937; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .shop-name-text { font-size: 1.5rem; font-weight: 900; letter-spacing: -0.02em; }
        @media (max-width: 767px) {
            .shop-name-text { font-size: 1rem; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen font-sans" style="{{ $client->bg_color ? 'background-color: '.$client->bg_color.' !important;' : '' }}">

    {{-- Flash Sale Bar --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Announcement Bar --}}
    @if($client->announcement_text)
    <div class="text-white text-center py-1.5 px-4 text-xs font-bold" style="background-color: var(--tw-color-primary);">
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Header --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 md:h-20 flex items-center justify-between gap-4">

            {{-- Logo --}}
            <a href="{{$baseUrl}}" class="flex items-center gap-2 shrink-0 group cursor-pointer">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client->logo)}}" class="h-10 md:h-12 object-contain rounded-full" alt="{{$client->shop_name}}">
                @else
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-lg flex-shrink-0" style="background-color: color-mix(in srgb, var(--tw-color-primary) 20%, transparent);">
                        <i class="fas fa-shopping-basket" style="color: var(--tw-color-primary);"></i>
                    </div>
                @endif
                <span class="shop-name-text text-slate-800 group-hover:text-primary transition">{{$client->shop_name}}</span>
            </a>

            {{-- Desktop Search --}}
            <div class="hidden md:flex flex-1 max-w-xl mx-8 relative">
                <form action="{{$baseUrl}}" method="GET" class="w-full">
                    <input type="text" name="search" value="{{request('search')}}" placeholder="Search for fresh vegetables, fruits, meat..."
                        class="w-full bg-slate-100 border-none px-6 py-3 rounded-full text-slate-700 font-semibold focus:outline-none focus:bg-white transition shadow-inner">
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 text-white rounded-full flex items-center justify-center transition shadow-sm" style="background-color: var(--tw-color-primary);">
                        <i class="fas fa-search text-xs"></i>
                    </button>
                </form>
            </div>

            {{-- Actions --}}
            <div class="hidden md:flex items-center gap-4">
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}"
                    class="text-sm font-bold text-slate-600 hover:text-primary transition flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-full border border-slate-200">
                    <i class="fas fa-truck-fast text-primary"></i> <span>Track Status</span>
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank"
                    class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-primary hover:text-white transition shadow-sm"
                    style="background-color: #f8fafc;" onmouseover="this.style.backgroundColor='var(--tw-color-primary)'; this.style.color='white';" onmouseout="this.style.backgroundColor='#f8fafc'; this.style.color='var(--tw-color-primary)';">
                    <i class="fab fa-facebook-messenger text-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 pt-16 pb-8 mt-auto relative overflow-hidden">
        <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(var(--tw-color-primary) 2px, transparent 2px); background-size: 30px 30px;"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 relative z-10">
            <div>
                <a href="{{$baseUrl}}" class="flex items-center gap-2 mb-6 cursor-pointer">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: color-mix(in srgb, var(--tw-color-primary) 15%, transparent); color: var(--tw-color-primary);">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <span class="text-2xl font-black text-slate-800 tracking-tight">{{$client->shop_name}}</span>
                </a>
                <p class="text-slate-500 font-semibold text-sm leading-relaxed mb-6">{{ $client->description ?? 'Freshness delivered right to your doorstep. We ensure quality and hygiene in every product we pack.' }}</p>
                <div class="flex gap-3 text-2xl text-slate-400">
                    @if($client->facebook_url ?? false)<a href="{{$client->facebook_url}}" target="_blank" class="hover:text-blue-600 transition"><i class="fab fa-facebook-f text-lg"></i></a>@endif
                    @if($client->instagram_url ?? false)<a href="{{$client->instagram_url}}" target="_blank" class="hover:text-pink-500 transition"><i class="fab fa-instagram text-lg"></i></a>@endif
                    @if($client->youtube_url ?? false)<a href="{{$client->youtube_url}}" target="_blank" class="hover:text-red-600 transition"><i class="fab fa-youtube text-lg"></i></a>@endif
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-carrot" style="color: var(--tw-color-primary);"></i>
                    {{ $client->widgets['footer']['menu1_title'] ?? 'Categories' }}
                </h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    @if(isset($footerMenu1) && $footerMenu1->items->count() > 0)
                        @foreach($footerMenu1->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">{{ $item->label }}</a>
                        @endforeach
                    @else
                        <a href="{{$baseUrl}}?category=all" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">All Products</a>
                        <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Track Order</a>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-heart text-red-400"></i>
                    {{ $client->widgets['footer']['menu2_title'] ?? 'Customer Care' }}
                </h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    @if(isset($footerMenu2) && $footerMenu2->items->count() > 0)
                        @foreach($footerMenu2->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">{{ $item->label }}</a>
                        @endforeach
                    @else
                        <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Track Your Order</a>
                        <a href="#" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Return Policy</a>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-headset text-blue-500"></i>
                    {{ $client->widgets['footer']['menu3_title'] ?? 'Contact Us' }}
                </h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    @if($client->phone)
                    <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm" style="color: var(--tw-color-primary);">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 uppercase tracking-widest mb-0.5">Hotline 24/7</span>
                            <span class="text-base text-slate-800">{{$client->phone}}</span>
                        </div>
                    </div>
                    @endif
                    @if($client->email)
                    <div class="flex items-center gap-3">
                        <i class="fas fa-envelope text-slate-400"></i>
                        <span>{{$client->email}}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 pt-8 border-t border-slate-100 text-center">
            <p class="text-sm font-bold text-slate-400">
                {{ $client->footer_text ?? '&copy; '.date('Y').' '.$client->shop_name.'. All Rights Reserved.' }}
                <i class="fas fa-heart text-red-500 mx-1"></i>
            </p>
        </div>

        {{-- Dynamic Social + Payment + Copyright from admin panel --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pb-6">
            @include('shop.partials.dynamic-footer-extras', ['client' => $client, 'baseUrl' => $baseUrl ?? '', 'clean' => $clean ?? ''])
        </div>
    </footer>

    @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
