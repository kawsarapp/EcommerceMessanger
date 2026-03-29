<!DOCTYPE html>
@php 
$clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
$baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    @include('shop.partials.tracking', ['client' => $client])
    
    <!-- AlpineJS & TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Fonts: Cormorant Garamond for Luxury, Montserrat for clean structured sans -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&family=Montserrat:wght@200;300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#d4af37"}}', /* Classic Gold Default */
                        dark: '#0a0a0a',
                        surface: '#121212'
                    },
                    fontFamily:{
                        serif:['"Cormorant Garamond"','serif'],
                        sans:['Montserrat','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#d4af37"}};
            --mob-primary: {{$client->primary_color ?? "#d4af37"}};
        }
        [x-cloak]{display:none!important} 
        .hide-scroll::-webkit-scrollbar{display:none}
        .luxury-gradient { background: linear-gradient(135deg, rgba(20,20,20,1) 0%, rgba(10,10,10,1) 100%); }
        .gold-text { background: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7, #aa771c); -webkit-background-clip: text; background-clip: text; color: transparent; }
        @media(max-width:767px){
            .shop-name-text{font-size:1.1rem!important;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;letter-spacing:.1em}
        }
    </style>
</head>
<body class="bg-dark text-gray-200 antialiased flex flex-col min-h-screen font-sans selection:bg-primary/30 selection:text-white luxury-gradient">

    {{-- ⚡ Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    <header class="bg-dark/80 backdrop-blur-md sticky top-0 z-50 border-b border-white/5 transition-all">
        <div class="max-w-[100rem] mx-auto px-4 sm:px-8 h-16 md:h-24 flex justify-between items-center">
            <!-- Left -->
            <div class="w-1/3 flex items-center gap-4">
                <button type="button" class="text-gray-300 hover:text-primary transition flex flex-col gap-1.5 w-5">
                    <span class="block w-full h-[1px] bg-current"></span>
                    <span class="block w-4/5 h-[1px] bg-current"></span>
                    <span class="block w-full h-[1px] bg-current"></span>
                </button>
            </div>
            <!-- Center logo -->
            <div class="w-1/3 flex justify-center">
                <a href="{{$baseUrl}}" class="flex items-center gap-2">
                    @if($client->logo)
                        <img src="{{asset('storage/'.$client- loading="lazy">logo)}}" class="h-9 md:h-14 object-contain brightness-0 invert">
                    @else
                        <span class="shop-name-text text-xl md:text-4xl font-serif font-medium tracking-widest text-white uppercase">{{$client->shop_name}}</span>
                    @endif
                </a>
            </div>
            <!-- Right -->
            <div class="w-1/3 flex justify-end items-center gap-4 md:gap-6">
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hidden md:block text-[10px] font-medium uppercase tracking-[0.2em] text-gray-400 hover:text-primary transition">Track</a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="text-gray-400 hover:text-primary transition">
                    <i class="fab fa-facebook-messenger text-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-surface border-t border-white/5 pt-24 pb-12 mt-auto">
        <div class="max-w-[100rem] mx-auto px-4 sm:px-12 grid grid-cols-1 md:grid-cols-12 gap-16">
            
            <div class="md:col-span-5">
                <h3 class="font-serif text-3xl mb-6 text-white tracking-widest uppercase">{{$client->shop_name}}</h3>
                <p class="text-gray-500 text-xs leading-loose font-light max-w-sm mb-8">Exquisite craftsmanship and timeless elegance. Curating the world's most desired luxury items.</p>
                @if($client->phone) 
                <p class="text-[10px] font-medium tracking-[0.2em] text-gray-400 uppercase mb-2">Concierge</p>
                <p class="text-white text-sm font-light tracking-widest mb-6">{{$client->phone}}</p> 
                @endif
            </div>
            
            <div class="md:col-span-3">
                <h4 class="font-sans text-[10px] uppercase tracking-[0.3em] font-semibold text-gray-300 mb-8">Information</h4>
                <div class="flex flex-col space-y-4 text-xs font-light tracking-wide text-gray-500">
                    <a href="{{$baseUrl}}" class="hover:text-primary transition">Homepage</a>
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition">Track Order</a>
                    <a href="#" class="hover:text-primary transition">Client Services</a>
                </div>
            </div>

            <div class="md:col-span-4 flex flex-col items-start md:items-end">
                <h4 class="font-sans text-[10px] uppercase tracking-[0.3em] font-semibold text-gray-300 mb-8">Exclusive Newsletter</h4>
                <p class="text-gray-500 text-xs leading-loose font-light md:text-right mb-6">Subscribe to receive updates on high jewelry creations, exclusive timepieces, and artistic collaborations.</p>
                <div class="w-full max-w-xs border-b border-gray-600 flex items-center pb-2">
                    <input type="email" placeholder="Email Address" class="bg-transparent border-none w-full text-xs text-white placeholder-gray-600 focus:ring-0 px-0">
                    <button class="text-xs font-serif italic text-primary hover:text-white transition">Subscribe</button>
                </div>
            </div>

        </div>
        
        <div class="max-w-[100rem] mx-auto px-4 sm:px-12 mt-20 text-center">
            <p class="text-[9px] font-light text-gray-600 uppercase tracking-[0.4em]">&copy; {{date('Y')}} {{$client->shop_name}}. All Rights Reserved.</p>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
