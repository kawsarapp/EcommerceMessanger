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
    <meta name="description" content="{{ $client->meta_description ?? $client->shop_name . ' - Groceries & More' }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Using a clean sans-serif font for the grocery UI -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary: '{{$client->primary_color ?? "#e31e24"}}',
                        swred: '#e31e24', /* Shwapno Red */
                        swyellow: '#ffd100', /* Shwapno Yellow */
                        swdark: '#222222',
                        swbg: '#f8f9fa',
                    },
                    fontFamily:{
                        sans:['Inter','system-ui','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root { --tw-color-primary: {{$client->primary_color ?? "#e31e24"}}; --mob-primary: #e31e24; }
        [x-cloak]{display:none!important}
        body { background-color: #f7f8f9; color: #333; }
        .hide-scroll::-webkit-scrollbar{display:none}
        
        /* Shwapno UI Elements */
        .sw-nav-link { font-size: 11px; font-weight: 700; color: #4b5563; text-transform: uppercase; padding: 12px 10px; transition: color 0.1s; display: block; border-bottom: 2px solid transparent; }
        .sw-nav-link:hover { color: #e31e24; border-bottom-color: #e31e24; }
        
        .sw-sidebar { border-right: 1px solid #e5e7eb; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .sw-sidebar-item { padding: 12px 16px; font-size: 13px; color: #4b5563; transition: background 0.2s, color 0.2s; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f9fafb; }
        .sw-sidebar-item:hover { color: #e31e24; background-color: #fffafb; font-weight: 600; }
        
        .sw-btn-pill { border-radius: 9999px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer; }
        .sw-btn-red { background-color: #e31e24; color: #fff; border: 1px solid #e31e24; }
        .sw-btn-red:hover { background-color: #c8161c; border-color: #c8161c; }
        
        .footer-heading { font-weight: 700; color: #1f2937; font-size: 14px; margin-bottom: 16px; }
        .footer-link { color: #6b7280; font-size: 12px; display: block; margin-bottom: 12px; transition: color 0.2s; }
        .footer-link:hover { color: #e31e24; text-decoration: underline; }
        
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen font-sans selection:bg-swred/20 selection:text-swred">
    
    {{-- Main Top Header (Red) --}}
    <header class="bg-swred sticky sm:relative top-0 z-50 shadow-sm border-b border-red-700">
        <div class="max-w-[1340px] mx-auto px-4 lg:px-6">
            <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-4 py-3 md:py-0 md:h-16">
                
                {{-- Logo & Location Box --}}
                <div class="flex items-center gap-6 shrink-0 w-full md:w-auto justify-between md:justify-start">
                    <a href="{{$baseUrl}}" class="flex items-center bg-black h-16 px-4 md:-ml-6 -my-3 md:my-0">
                        @if($client->logo)
                            <img src="{{asset('storage/'.$client->logo)}}" class="h-10 object-contain" alt="{{$client->shop_name}}">
                        @else
                            <div class="text-white font-black text-xl flex items-center gap-2">
                                <span>{{$client->shop_name}}</span>
                            </div>
                        @endif
                    </a>
                    
                    <button class="hidden lg:flex items-center gap-2 border border-red-400 bg-red-600/30 hover:bg-red-600/50 transition px-3 py-1.5 rounded-sm text-white text-[11px] h-9">
                        <i class="fas fa-truck text-lg"></i>
                        <span class="font-medium whitespace-nowrap">Select your delivery location</span>
                        <i class="fas fa-chevron-down text-[10px] ml-1"></i>
                    </button>

                    {{-- Mobile Cart Icon --}}
                    <a href="#" class="md:hidden text-white flex items-center gap-2">
                        <div class="relative">
                            <i class="fas fa-shopping-bag text-2xl"></i>
                            <span class="absolute -top-1 -right-2 bg-swyellow text-swdark text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
                        </div>
                    </a>
                </div>

                {{-- Search Bar --}}
                <div class="w-full md:flex-1 max-w-2xl px-0 md:px-4 order-3 md:order-none">
                    <form action="{{$baseUrl}}" method="GET" class="w-full relative flex items-center bg-white rounded-sm overflow-hidden h-10 shadow-inner">
                        <input type="text" name="search" value="{{request('search')}}" placeholder="Search your products" 
                            class="w-full bg-transparent px-4 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none border-none h-full">
                        <button class="bg-swyellow hover:bg-yellow-500 text-swdark w-12 h-full flex items-center justify-center transition border-l border-yellow-200">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                {{-- Right User Actions (Desktop) --}}
                <div class="hidden md:flex items-center gap-4 shrink-0">
                    <a href="#" class="h-9 hidden xl:block rounded-sm overflow-hidden border border-yellow-400">
                        <img src="https://images.unsplash.com/photo-1611162617215-d2274483ae5d?auto=format&fit=crop&w=150&h=40&q=80" class="h-full object-cover" alt="App Download">
                    </a>
                    <a href="#" class="border border-red-400 text-white hover:bg-red-600 px-4 py-1.5 text-xs font-bold rounded-sm h-9 flex items-center transition">বাংলা</a>
                    
                    <a href="#" class="bg-white/10 hover:bg-white/20 border border-red-400 text-white px-4 py-1.5 text-[11px] font-bold rounded-sm h-9 flex items-center gap-2 transition">
                        <i class="far fa-user text-sm"></i> Sign in / Sign up
                    </a>

                    <a href="#" class="relative ml-2 group cursor-pointer h-16 flex items-center px-4 bg-red-700/40 hover:bg-red-700/60 transition md:-mr-6 border-l border-red-800">
                        <i class="fas fa-shopping-bag text-white text-2xl group-hover:scale-110 transition"></i>
                        <span class="absolute top-3 right-2 bg-swyellow text-swdark text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center border border-swdark shadow-sm">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Sub Navigation Bar (White, Thin) --}}
    <nav class="bg-white border-b border-gray-200 hidden md:block shadow-sm">
        <div class="max-w-[1340px] mx-auto px-4 lg:px-6 flex items-center justify-between">
            
            <div class="flex items-center gap-6 xl:gap-10">
                {{-- Category Hamburger Toggle --}}
                <div class="w-64 border-r border-gray-100 flex items-center gap-3 py-3 cursor-pointer group">
                    <i class="fas fa-bars text-gray-500 group-hover:text-swred transition"></i>
                    <span class="text-xs font-black text-gray-800 uppercase tracking-tight group-hover:text-swred transition">SHOP BY CATEGORY</span>
                </div>

                {{-- Horizontal Links --}}
                <div class="flex items-center gap-1 xl:gap-3">
                    <a href="{{$baseUrl}}" class="sw-nav-link {!! request()->is('/') ? '!text-swred border-b-swred' : '' !!}">GREAT DEALS</a>
                    <a href="{{$baseUrl}}?category=unilever" class="sw-nav-link">UNILEVER-STOCK & SAVE</a>
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="sw-nav-link">BUY & SAVE MORE</a>
                    <a href="#" class="sw-nav-link">OUR BRANDS</a>
                    <a href="#" class="sw-nav-link">WOMEN'S CORNER</a>
                </div>
            </div>

            {{-- Right Help Links --}}
            <div class="flex items-center gap-6">
                <a href="#" class="text-[11px] text-gray-500 hover:text-swred flex items-center gap-1.5 font-medium transition"><i class="fas fa-store text-gray-400"></i> Our outlets</a>
                <a href="#" class="text-[11px] text-gray-500 hover:text-swred flex items-center gap-1.5 font-medium transition"><i class="far fa-question-circle text-gray-400"></i> Help line</a>
            </div>
            
        </div>
    </nav>

    <main class="flex-1 w-full bg-[#f7f8f9] pb-16">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 mt-auto pt-10">
        <div class="max-w-[1340px] mx-auto px-4 lg:px-6">
            
            {{-- Top Pre-Footer Banner Array (Placeholder) --}}
            <div class="w-full bg-blue-50 border border-blue-100 rounded mb-10 overflow-hidden relative group hidden md:block">
                <img src="https://images.unsplash.com/photo-1604719312566-8912e9227c6a?auto=format&fit=crop&w=1200&h=200&q=80" class="w-full h-40 object-cover opacity-90 group-hover:scale-105 transition duration-700">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900/80 to-transparent flex items-center px-12">
                    <div>
                        <h2 class="text-4xl font-black text-white leading-tight mb-2">Affordable<br>Monthly Grocery<br>Deals!</h2>
                    </div>
                </div>
            </div>

            {{-- Main Footer Columns --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 pb-10 border-b border-gray-100">
                
                {{-- Column 1: Brand Info --}}
                <div class="lg:col-span-1">
                    <div class="bg-black inline-block p-2 mb-4">
                        @if($client->logo)
                            <img src="{{asset('storage/'.$client->logo)}}" class="h-8 object-contain" alt="{{$client->shop_name}}">
                        @else
                            <h3 class="text-white font-black text-lg">{{$client->shop_name}}</h3>
                        @endif
                    </div>
                    <p class="text-sm font-bold text-gray-800 mb-2">Always Here for You</p>
                    <p class="text-[11px] text-gray-500 leading-relaxed mb-4">
                        Call Us: {{$client->phone ?? '16469 (8am-10pm, Everyday)'}}<br>
                        Email Us: {{$client->email ?? 'queries@'.$client->slug.'.com'}}<br>
                        <span class="font-bold text-gray-600 mt-2 block">{{strtoupper($client->shop_name)}} E-COMMERCE LIMITED</span>
                    </p>
                </div>

                {{-- Column 2: Information --}}
                <div class="lg:col-span-1">
                    <h4 class="footer-heading">Information</h4>
                    <div>
                        <a href="#" class="footer-link">Office Address</a>
                        <a href="#" class="footer-link">Shipping & returns</a>
                        <a href="#" class="footer-link">About us</a>
                        <a href="#" class="footer-link">Terms & Condition</a>
                    </div>
                </div>

                {{-- Column 3: Customer Service --}}
                <div class="lg:col-span-1">
                    <h4 class="footer-heading">Customer Service</h4>
                    <div>
                        <a href="#" class="footer-link">Contact Us</a>
                        <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="footer-link">Track Order</a>
                    </div>
                </div>

                {{-- Column 4: My Account --}}
                <div class="lg:col-span-1">
                    <h4 class="footer-heading">My Account</h4>
                    <div>
                        <a href="#" class="footer-link">Sign In</a>
                        <a href="#" class="footer-link">View Cart</a>
                    </div>
                </div>

                {{-- Column 5: Payments & Social --}}
                <div class="lg:col-span-1">
                    <h4 class="footer-heading">Pay With</h4>
                    <div class="flex flex-wrap gap-2 mb-6">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/1024px-MasterCard_Logo.svg.png" class="h-4 object-contain opacity-70">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/16/Former_Visa_%28company%29_logo.svg/1024px-Former_Visa_%28company%29_logo.svg.png" class="h-4 object-contain opacity-70">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/American_Express_logo.svg/1200px-American_Express_logo.svg.png" class="h-4 object-contain opacity-70">
                    </div>

                    <h4 class="footer-heading">Follow Us</h4>
                    <div class="flex gap-2">
                        @if($client->facebook_url)<a href="{{$client->facebook_url}}" class="w-7 h-7 bg-[#3b5998] text-white flex items-center justify-center rounded-sm hover:opacity-80 transition"><i class="fab fa-facebook-f text-xs"></i></a>@else
                        <a href="#" class="w-7 h-7 bg-[#3b5998] text-white flex items-center justify-center rounded-sm hover:opacity-80 transition"><i class="fab fa-facebook-f text-xs"></i></a>
                        @endif
                        <a href="#" class="w-7 h-7 bg-[#ff0000] text-white flex items-center justify-center rounded-sm hover:opacity-80 transition"><i class="fab fa-youtube text-xs"></i></a>
                        @if($client->instagram_url)<a href="{{$client->instagram_url}}" class="w-7 h-7 bg-[#c32aa3] text-white flex items-center justify-center rounded-sm hover:opacity-80 transition"><i class="fab fa-instagram text-xs"></i></a>@endif
                    </div>
                </div>

            </div>
            
            {{-- Copyright Area --}}
            <div class="py-6 flex flex-col items-center justify-center">
                <i class="fas fa-shopping-basket text-gray-200 text-3xl mb-2 opacity-30"></i>
                <p class="text-[10px] text-gray-400 font-medium tracking-wide">© {{date('Y')}} {{$client->shop_name}}. All Rights Reserved.</p>
            </div>
            
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
