<!DOCTYPE html>
@php
    $clean = preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/'));
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show',$client->slug);
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
    <!-- Tailwind CSS & Alpine JS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Google Fonts: Plus Jakarta Sans for a very premium feel -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '{{$client->primary_color ?? "#6366f1"}}',
                        secondary: '{{$client->secondary_color ?? $client->primary_color ?? "#facc15"}}',
                        accent: '#f43f5e'
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif']
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.07)',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#6366f1"}};
            --mob-primary: {{$client->primary_color ?? "#6366f1"}};
        }
        [x-cloak] { display: none !important; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        .hover-lift { transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease; }
        .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1); }
        .btn-premium { background: linear-gradient(135deg, var(--tw-color-primary) 0%, #818cf8 100%); transition: all 0.3s ease; }
        .btn-premium:hover { background: linear-gradient(135deg, #4f46e5 0%, var(--tw-color-primary) 100%); transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4); }
        @media(max-width:767px){
            .shop-name-text{font-size:1rem!important;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        }
    </style>
    @include('shop.partials.dynamic-colors', ['client' => $client])
</head>
<body class="bg-gray-50 text-gray-900 antialiased flex flex-col min-h-screen" style="{{ $client->bg_color ? 'background-color: '.$client->bg_color.' !important;' : '' }}">
    
    {{-- ? Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    @if($client->announcement_text)
        <div class="bg-primary text-white text-center py-2.5 text-sm font-medium tracking-wide shadow-sm relative z-50">
            {!! $client->announcement_text !!}
        </div>
    @endif

    <header class="glass-nav sticky top-0 z-50 w-full transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 md:h-20 flex justify-between items-center gap-3">
            <a href="{{$baseUrl}}" class="flex items-center gap-2 group min-w-0">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client->logo)}}" alt="{{$client->shop_name}}" class="h-8 md:h-10 w-auto object-contain flex-shrink-0 transition group-hover:scale-105">
                @endif
                <span class="shop-name-text text-lg md:text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 tracking-tight">
                    {{$client->shop_name}}
                </span>
            </a>
            <div class="hidden md:flex items-center gap-5">
                <a href="{{$clean ? $baseUrl.'/track' : route('shop.track',$client->slug)}}" class="text-sm font-semibold text-gray-600 hover:text-primary transition-colors flex items-center gap-2 bg-gray-100/80 px-4 py-2 rounded-full hover:bg-white hover:shadow-sm">
                    <i class="fas fa-truck-fast"></i> Track Order
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm hover:shadow-md">
                    <i class="fab fa-facebook-messenger text-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 mt-auto pt-16 pb-8">
        <div class="max-w-5xl mx-auto px-4 text-center">
            <h3 class="text-2xl font-bold tracking-widest uppercase mb-4">{{$client->shop_name}}</h3>
            <p class="text-gray-400 text-sm leading-relaxed mb-8 max-w-xl mx-auto">{{ $client->description ?? ($client->tagline ?? '????? ?????????? ???? ???????? ???? ????? ????, ???? ?????') }}</p>

            <div class="flex justify-center gap-6 mb-6 flex-wrap">
                @if($client->phone)<a href="tel:{{$client->phone}}" class="text-gray-400 hover:text-primary transition flex items-center gap-2 text-sm"><i class="fas fa-phone"></i> {{$client->phone}}</a>@endif
                @if($client->email)<a href="mailto:{{$client->email}}" class="text-gray-400 hover:text-primary transition flex items-center gap-2 text-sm"><i class="fas fa-envelope"></i> {{$client->email}}</a>@endif
            </div>

            <div class="flex justify-center gap-5 mb-8">
                @if($client->fb_page_id)<a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="text-gray-500 hover:text-primary transition"><i class="fab fa-facebook-messenger"></i></a>@endif
                @if($client->facebook_url ?? false)<a href="{{$client->facebook_url}}" target="_blank" class="text-gray-500 hover:text-primary transition"><i class="fab fa-facebook-f"></i></a>@endif
                @if($client->instagram_url ?? false)<a href="{{$client->instagram_url}}" target="_blank" class="text-gray-500 hover:text-primary transition"><i class="fab fa-instagram"></i></a>@endif
                @if($client->youtube_url ?? false)<a href="{{$client->youtube_url}}" target="_blank" class="text-gray-500 hover:text-primary transition"><i class="fab fa-youtube"></i></a>@endif
            </div>

            <div class="flex flex-wrap justify-center gap-8 text-xs font-medium tracking-widest text-gray-500 uppercase mb-10">
                <a href="{{$baseUrl}}" class="hover:text-primary transition">Home</a>
                <a href="{{$baseUrl}}?category=all" class="hover:text-primary transition">Products</a>
                <a href="{{$clean ? $baseUrl.'/track' : route('shop.track', $client->slug)}}" class="hover:text-primary transition">Track Order</a>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <p class="text-xs text-gray-400 font-medium tracking-wide">{{ $client->footer_text ?? '&copy; '.date('Y').' '.$client->shop_name.'. All Rights Reserved.' }}</p>
            </div>
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



