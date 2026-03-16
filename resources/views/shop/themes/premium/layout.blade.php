<!DOCTYPE html>
@php
    $clean = preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/'));
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show',$client->slug);
@endphp
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <!-- Tailwind CSS & Alpine JS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Google Fonts: Plus Jakarta Sans for a very premium feel -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{$client->primary_color ?? "#6366f1"}}',
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
</head>
<body class="bg-gray-50 text-gray-900 antialiased flex flex-col min-h-screen">
    
    @if($client->announcement_text)
        <div class="bg-primary text-white text-center py-2.5 text-sm font-medium tracking-wide shadow-sm">
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
                <a href="{{$clean ? $baseUrl.'/track-order' : route('shop.track',$client->slug)}}" class="text-sm font-semibold text-gray-600 hover:text-primary transition-colors flex items-center gap-2 bg-gray-100/80 px-4 py-2 rounded-full hover:bg-white hover:shadow-sm">
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
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12 text-center md:text-left">
            <div>
                <h3 class="font-bold text-2xl text-gray-900 mb-4">{{$client->shop_name}}</h3>
                <p class="text-gray-500 text-sm leading-relaxed max-w-sm mx-auto md:mx-0">
                    Your premium shopping destination. We provide the best quality products with top-notch customer support.
                </p>
            </div>
            <div class="flex flex-col items-center md:items-start space-y-3">
                <h4 class="font-bold text-gray-900">Quick Links</h4>
                <a href="{{$baseUrl}}" class="text-gray-500 hover:text-primary text-sm transition">Home</a>
                <a href="{{$clean ? $baseUrl.'/track-order' : route('shop.track', $client->slug)}}" class="text-gray-500 hover:text-primary text-sm transition">Track Order</a>
            </div>
            <div class="flex flex-col items-center md:items-end space-y-4">
                <h4 class="font-bold text-gray-900">Contact Us</h4>
                @if($client->phone) <p class="text-gray-500 text-sm"><i class="fas fa-phone mr-2"></i> {{$client->phone}}</p> @endif
                <div class="flex gap-3 mt-2">
                    @if($client->fb_page_id)
                        <a href="https://facebook.com/{{$client->fb_page_id}}" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-blue-600 hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                    @endif
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-6 mt-16 pt-8 border-t border-gray-100 flex justify-center items-center">
            <p class="text-xs text-gray-400 font-medium tracking-wide">&copy; {{date('Y')}} {{$client->shop_name}}. All Rights Reserved.</p>
        </div>
    </footer>
    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
