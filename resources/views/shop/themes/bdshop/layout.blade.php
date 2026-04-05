<!DOCTYPE html>
@php 
$clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
$baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp
<html lang="bn">
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
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            darkMode: 'class',
            theme:{
                extend:{
                    colors:{
                        primary: '{{$client->primary_color ?? "#f85606"}}',
                        secondary: '{{$client->secondary_color ?? "#fef0eb"}}',
                        accent: '#1a9cb7',
                        dark: '#212121',
                    },
                    fontFamily:{
                        sans:['Inter','system-ui','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root { --tw-color-primary: {{$client->primary_color ?? "#f85606"}}; --mob-primary: {{$client->primary_color ?? "#f85606"}}; }
        [x-cloak]{display:none!important}
        body { background-color: #f8f9fa; }
        /* Modern Material Depth */
        .mat-card { background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05),0 2px 4px -2px rgba(0,0,0,0.025); transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .mat-card:hover { box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1),0 8px 10px -6px rgba(0,0,0,0.05); transform: translateY(-4px); }
        .mat-elevated { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08),0 4px 6px -4px rgba(0,0,0,0.04); }
        .hide-scroll::-webkit-scrollbar{display:none}
        .smooth-transition { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    </style>
    @include('shop.partials.dynamic-colors', ['client' => $client])
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-sans selection:bg-primary/20 selection:text-primary" style="{{ $client->bg_color ? 'background-color: '.$client->bg_color.' !important;' : '' }}">
    
    {{-- Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Announcement Bar (Dynamic from Admin Panel) --}}
    @if(!empty($client->announcement_text))
    <div class="bg-dark text-white text-center text-xs py-2 px-4 font-medium tracking-wide">
        {{ $client->announcement_text }}
    </div>
    @endif

    <header class="bg-primary sticky top-0 z-50 shadow-lg" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)" :class="{'py-1': scrolled, 'py-2': !scrolled}">
        <div class="max-w-[1280px] mx-auto px-4">
            {{-- Main Row --}}
            <div class="flex items-center gap-4 h-14 md:h-16">
                {{-- Logo / Shop Name --}}
                <a href="{{$baseUrl}}" class="flex items-center gap-2 shrink-0">
                    @if($client->logo)
                        <img src="{{asset('storage/'.$client->logo)}}" class="h-8 md:h-10 object-contain rounded" alt="{{$client->shop_name}}">
                    @endif
                    <span class="text-white font-extrabold text-lg md:text-xl tracking-tight hidden sm:block">{{$client->shop_name}}</span>
                </a>

                {{-- Search Bar --}}
                @if($client->widget('search_bar'))
                @php $sbConfig = $client->widgetConfig('search_bar'); @endphp
                <div class="flex-1 max-w-2xl mx-2 md:mx-6">
                    <div class="relative">
                        <input type="text" placeholder="{{ $sbConfig['text'] ?? 'পণ্য খুঁজুন...' }}" 
                            class="w-full bg-white rounded-lg pl-4 pr-12 py-2.5 text-sm text-dark font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-white/30">
                        <button class="absolute right-0 top-0 h-full px-4 hover:opacity-80 text-white rounded-r-lg transition"
                            style="background-color: {{ !empty($sbConfig['color']) ? $sbConfig['color'] : 'rgba(var(--tw-color-primary), 0.8)' }}">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" 
                        class="text-white/90 hover:text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 py-1.5 rounded-lg hover:bg-white/10">
                        <i class="fas fa-truck-fast"></i>
                        <span class="hidden md:inline">অর্ডার ট্র্যাক</span>
                    </a>
                    @if($client->phone)
                    <a href="tel:{{$client->phone}}" class="text-white/90 hover:text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 py-1.5 rounded-lg hover:bg-white/10">
                        <i class="fas fa-phone-alt"></i>
                        <span class="hidden lg:inline">{{$client->phone}}</span>
                    </a>
                    @endif
                    @php $bgCartCount = session()->has('cart') ? count(session()->get('cart')) : 0; @endphp
                    <a href="{{$clean?$baseUrl.'/cart':route('shop.cart',$client->slug)}}" class="relative text-white/90 hover:text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 py-1.5 rounded-lg hover:bg-white/10">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        <span class="hidden lg:inline">কার্ট</span>
                        @if($bgCartCount > 0)
                            <span class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full transform translate-x-1 -translate-y-1">{{ $bgCartCount }}</span>
                        @else
                            <span class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full transform translate-x-1 -translate-y-1 hidden" data-cart-badge>0</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Category Bar --}}
    @if($client->widget('category_filter') && isset($categories) && count($categories) > 0)
    <nav class="bg-white border-b border-slate-100 mat-elevated sticky top-[102px] sm:top-[72px] md:top-[76px] z-40">
        <div class="max-w-[1280px] mx-auto px-4">
            <div class="flex gap-2 overflow-x-auto hide-scroll py-3">
                <a href="?category=all" class="px-5 py-2.5 rounded-full text-xs font-bold whitespace-nowrap mat-card hover:translate-y-0 shadow-sm {{!request('category')||request('category')=='all' ? 'bg-primary text-white border border-primary' : 'bg-white text-slate-600 border border-slate-200'}}">
                    <i class="fas fa-th-large mr-1.5"></i> সব পণ্য
                </a>
                @foreach($categories as $c)
                    <a href="?category={{$c->slug}}" class="px-5 py-2.5 rounded-full text-xs font-bold whitespace-nowrap mat-card hover:translate-y-0 shadow-sm {{request('category')==$c->slug ? 'bg-primary text-white border border-primary' : 'bg-white text-slate-600 border border-slate-200'}}">
                        {{$c->name}}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>
    @endif

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-dark text-white mt-auto">
        <div class="max-w-[1280px] mx-auto px-4 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                {{-- Brand --}}
                <div class="col-span-2 md:col-span-1">
                    <span class="text-xl font-extrabold block mb-4">{{$client->shop_name}}</span>
                    <p class="text-gray-400 text-sm leading-relaxed mb-6">{{ $client->widgets['footer']['brand_description'] ?? ($client->description ?? ($client->meta_description ?? 'সেরা মানের পণ্য, দ্রুত ডেলিভারি সুবিধায় আপনার দোরগোড়ায়।')) }}</p>
                    <div class="flex gap-3">
                        @if($client->facebook_url ?? false)<a href="{{$client->facebook_url}}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition"><i class="fab fa-facebook-f text-sm"></i></a>@endif
                        @if($client->instagram_url ?? false)<a href="{{$client->instagram_url}}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition"><i class="fab fa-instagram text-sm"></i></a>@endif
                        @if($client->youtube_url ?? false)<a href="{{$client->youtube_url}}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition"><i class="fab fa-youtube text-sm"></i></a>@endif
                        @if($client->tiktok_url ?? false)<a href="{{$client->tiktok_url}}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition"><i class="fab fa-tiktok text-sm"></i></a>@endif
                    </div>
                </div>
                
                {{-- Quick Links --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">{{ strtoupper($client->widgets['footer']['menu1_title'] ?? 'Quick Links') }}</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-400">
                        <a href="{{$baseUrl}}" class="hover:text-white transition w-fit">হোম পেজ</a>
                        <a href="{{$baseUrl}}?category=all" class="hover:text-white transition w-fit">সকল পণ্য</a>
                    </div>
                </div>

                {{-- Support --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">{{ strtoupper($client->widgets['footer']['menu2_title'] ?? 'Support') }}</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-400">
                        <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-white transition w-fit">অর্ডার ট্র্যাকিং</a>
                        <a href="#" class="hover:text-white transition w-fit">রিটার্ন পলিসি</a>
                        <a href="#" class="hover:text-white transition w-fit">গোপনীয়তা নীতি</a>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">{{ strtoupper($client->widgets['footer']['contact_title'] ?? $client->widgets['footer']['menu3_title'] ?? 'Contact') }}</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        @if($client->phone)<a href="tel:{{$client->phone}}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-phone-alt text-primary"></i> {{$client->phone}}</a>@endif
                        @if($client->email)<a href="mailto:{{$client->email}}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-envelope text-primary"></i> {{$client->email}}</a>@endif
                        @if($client->address)<span class="flex items-start gap-2"><i class="fas fa-map-marker-alt text-primary mt-1"></i> {{$client->address}}</span>@endif
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 py-5">
            <div class="max-w-[1280px] mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-3">
                <p class="text-slate-500 text-xs">{{ $client->footer_text ?? '&copy; '.date('Y').' '.$client->shop_name.' — সর্বস্বত্ব সংরক্ষিত।' }}</p>
                <div class="flex items-center gap-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Visa_Logo.png/120px-Visa_Logo.png" class="h-5 opacity-40" loading="lazy">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/120px-MasterCard_Logo.svg.png" class="h-5 opacity-40" loading="lazy">
                    <span class="text-[10px] text-slate-500 font-bold border border-slate-600 px-2 py-0.5 rounded">নিরাপদ পেমেন্ট</span>
                </div>
            </div>
        </div>

        {{-- Dynamic Social + Payment + Copyright from admin panel --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pb-6">
            @include('shop.partials.dynamic-footer-extras', ['client' => $client, 'baseUrl' => $baseUrl ?? '', 'clean' => $clean ?? ''])
        </div>
    </footer>

    {{-- Floating Chat --}}
    @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
