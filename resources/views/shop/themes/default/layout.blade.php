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
    
    <!-- Fonts: Inter (The ultimate modern standard) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary: '{{$client->primary_color ?? "#0f172a"}}', // Deep slate
                    },
                    fontFamily:{
                        sans:['Inter','sans-serif']
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
            --tw-color-primary: {{$client->primary_color ?? "#6366f1"}};
            --mob-primary: {{$client->primary_color ?? "#6366f1"}};
        }
        [x-cloak]{display:none!important}
        
        /* Modern Mesh/Aurora Background */
        body {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(var(--tw-color-primary), 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(var(--tw-color-primary), 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(var(--tw-color-primary), 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(var(--tw-color-primary), 0.08) 0px, transparent 50%);
            background-attachment: fixed;
        }
        
        /* Glassmorphism Utilities */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 24px -1px rgba(0, 0, 0, 0.03);
        }
        
        .hide-scroll::-webkit-scrollbar{display:none}
        .premium-transition { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .hover-lift { transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease; }
        .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01); }
        
        @media(max-width:767px){
            .shop-name-text{font-size:1.1rem!important;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-sans selection:bg-primary/20 selection:text-primary relative overflow-x-hidden">

    <!-- Top Announcement Bar -->
    @if($client->announcement_text)
    <div class="bg-primary text-white text-center py-2.5 px-4 text-xs font-semibold tracking-wide flex items-center justify-center gap-2">
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-40"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
        </span>
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- ⚡ Flash Sale Banner (auto-shows when active flash sale exists) --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    <!-- Premium Dynamic Header -->
    <header class="sticky top-0 z-50 transition-all duration-300 transform" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)" :class="{'py-2': scrolled, 'py-4': !scrolled}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="glass-panel rounded-2xl md:rounded-3xl h-16 md:h-20 flex justify-between items-center gap-3 px-4 md:px-6 transition-all duration-300" :class="{'shadow-md border-white/80 bg-white/85': scrolled}">
                
                <a href="{{$baseUrl}}" class="flex items-center gap-2 hover-lift min-w-0">
                    @if($client->logo)
                        <img src="{{asset('storage/'.$client->logo)}}" class="h-8 md:h-12 object-contain flex-shrink-0 drop-shadow-sm">
                    @else
                        <div class="w-10 h-10 bg-gradient-to-br from-primary/20 to-primary/5 rounded-xl border border-primary/20 flex items-center justify-center text-primary flex-shrink-0 shadow-inner">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <span class="shop-name-text text-xl md:text-2xl font-extrabold tracking-tight text-slate-900 bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-700">{{$client->shop_name}}</span>
                    @endif
                </a>

                <!-- Desktop Search Bar -->
                <form action="{{ $baseUrl }}" method="GET" class="hidden md:flex flex-1 max-w-md mx-8 relative group/search">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-400 group-focus-within/search:text-primary premium-transition"></i>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." 
                        class="w-full bg-white/60 hover:bg-white border border-slate-200/60 focus:border-primary/30 focus:bg-white pl-11 pr-5 py-2.5 rounded-full text-slate-700 text-sm font-medium focus:ring-4 focus:ring-primary/10 premium-transition shadow-sm outline-none">
                </form>
                
                <!-- Desktop Actions -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-slate-700 hover:text-primary hover:bg-primary/5 premium-transition hover-lift bg-white/50 border border-slate-100 shadow-sm">
                        <i class="fas fa-truck-fast text-primary"></i> <span>Track Order</span>
                    </a>
                    @if($client->fb_page_id)
                    <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-11 h-11 rounded-full bg-gradient-to-tr from-blue-600 to-blue-400 hover:shadow-lg hover:shadow-blue-500/30 flex items-center justify-center text-white premium-transition hover-lift shadow-md border-2 border-white">
                        <i class="fab fa-facebook-messenger text-xl"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-24">
        @yield('content')
    </main>

    <footer class="mt-auto relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 mb-8 mt-12">
            <div class="glass-panel rounded-3xl pt-16 pb-8 px-8 md:px-12 relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 relative z-10">
                    <!-- Brand Column -->
                    <div class="space-y-6">
                        <a href="{{$baseUrl}}" class="inline-block">
                            <span class="text-3xl font-extrabold tracking-tight text-slate-900 bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-700">{{$client->shop_name}}</span>
                        </a>
                        <p class="text-slate-500 font-medium text-sm leading-relaxed">Providing high-quality products and excellent customer service. Your satisfaction is our priority.</p>
                        @include('shop.partials.footer-links', ['client' => $client])
                        <div class="flex gap-4 items-center pt-2">
                            <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-white hover:bg-primary hover:border-primary hover-lift cursor-pointer premium-transition shadow-sm"><i class="fab fa-facebook-f"></i></div>
                            <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-white hover:bg-primary hover:border-primary hover-lift cursor-pointer premium-transition shadow-sm"><i class="fab fa-instagram"></i></div>
                            <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-white hover:bg-primary hover:border-primary hover-lift cursor-pointer premium-transition shadow-sm"><i class="fab fa-twitter"></i></div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div>
                        <h4 class="font-extrabold text-slate-900 mb-6 text-sm uppercase tracking-widest relative inline-block">
                            Shop
                            <span class="absolute -bottom-2 left-0 w-1/2 h-1 bg-gradient-to-r from-primary to-transparent rounded-full"></span>
                        </h4>
                        <div class="flex flex-col space-y-4 font-semibold text-sm text-slate-500">
                            <a href="?category=all" class="hover:text-primary premium-transition hover:translate-x-1 inline-block w-fit">All Products</a>
                            <a href="#" class="hover:text-primary premium-transition hover:translate-x-1 inline-block w-fit">New Arrivals</a>
                            <a href="#" class="hover:text-primary premium-transition hover:translate-x-1 inline-block w-fit">Best Sellers</a>
                        </div>
                    </div>

                    <!-- Support -->
                    <div>
                         <h4 class="font-extrabold text-slate-900 mb-6 text-sm uppercase tracking-widest relative inline-block">
                            Support
                            <span class="absolute -bottom-2 left-0 w-1/2 h-1 bg-gradient-to-r from-primary to-transparent rounded-full"></span>
                        </h4>
                        <div class="flex flex-col space-y-4 font-semibold text-sm text-slate-500">
                            <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary premium-transition hover:translate-x-1 inline-block w-fit">Track Your Order</a>
                            <a href="#" class="hover:text-primary premium-transition hover:translate-x-1 inline-block w-fit">Shipping Policy</a>
                            <a href="#" class="hover:text-primary premium-transition hover:translate-x-1 inline-block w-fit">Returns & Refunds</a>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div>
                         <h4 class="font-extrabold text-slate-900 mb-6 text-sm uppercase tracking-widest relative inline-block">
                            Contact Us
                            <span class="absolute -bottom-2 left-0 w-1/2 h-1 bg-gradient-to-r from-primary to-transparent rounded-full"></span>
                        </h4>
                        <div class="flex flex-col space-y-5">
                            @if($client->phone) 
                                <a href="tel:{{$client->phone}}" class="flex items-start gap-4 group">
                                    <div class="w-10 h-10 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center justify-center text-slate-400 group-hover:bg-primary group-hover:text-white group-hover:border-primary group-hover:-translate-y-1 premium-transition shrink-0">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-widest mb-0.5">Call Us</span>
                                        <span class="text-sm font-bold text-slate-700 group-hover:text-primary premium-transition">{{$client->phone}}</span>
                                    </div>
                                </a>
                            @endif
                            
                            @if($client->email)
                            <a href="mailto:{{$client->email}}" class="flex items-start gap-4 group">
                                <div class="w-10 h-10 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-center justify-center text-slate-400 group-hover:bg-primary group-hover:text-white group-hover:border-primary group-hover:-translate-y-1 premium-transition shrink-0">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-widest mb-0.5">Email Us</span>
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-primary premium-transition">{{$client->email}}</span>
                                </div>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="mt-16 pt-8 border-t border-slate-200/50 flex flex-col md:flex-row justify-between items-center gap-4 relative z-10">
                    <p class="text-sm font-bold text-slate-400">&copy; {{date('Y')}} {{$client->shop_name}}. All Rights Reserved.</p>
                    <div class="flex gap-3">
                        <i class="fab fa-cc-visa text-3xl text-slate-300 hover:text-slate-500 premium-transition hover:-translate-y-1 cursor-pointer"></i>
                        <i class="fab fa-cc-mastercard text-3xl text-slate-300 hover:text-slate-500 premium-transition hover:-translate-y-1 cursor-pointer"></i>
                        <i class="fab fa-cc-amex text-3xl text-slate-300 hover:text-slate-500 premium-transition hover:-translate-y-1 cursor-pointer"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
