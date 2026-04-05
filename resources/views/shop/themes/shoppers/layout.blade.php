<!DOCTYPE html>
@php 
$clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
$baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
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
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Using Roboto for a cleaner, structural look popular in 2010s retail sites like BanglaShoppers -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            darkMode: 'class',
            theme:{
                extend:{
                    colors:{
                        primary: '{{$client->primary_color ?? "#ef4444"}}',
                        secondary: '{{$client->secondary_color ?? $client->primary_color ?? "#facc15"}}',
                        shred: '#eb484e', /* Shoppers Red */
                        shdark: '#24263f', /* Shoppers Dark Blue Head/Foot */
                        shbg: '#f8f8f8',
                    },
                    fontFamily:{
                        sans:['Roboto','system-ui','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root { --tw-color-primary: {{$client->primary_color ?? "#ef4444"}}; --mob-primary: #eb484e; }
        [x-cloak]{display:none!important}
        body { background-color: #fff; color: #333; }
        .hide-scroll::-webkit-scrollbar{display:none}
        
        /* Category Menu Box styling */
        .cat-menu-box { border: 1px solid #e5e7eb; border-top: none; }
        .cat-item { padding: 12px 16px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #4b5563; transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; }
        .cat-item:hover { color: #eb484e; padding-left: 20px; background-color: #fcfcfc; }
        
        .nav-link { font-size: 12px; font-weight: 700; color: #4b5563; text-transform: uppercase; padding: 16px; transition: color 0.2s; position: relative; }
        .nav-link:hover { color: #eb484e; }
        .nav-badge { position: absolute; top: 0; left: 50%; transform: translateX(-50%); font-size: 9px; padding: 2px 6px; border-radius: 2px; color: white; display: inline-block; white-space: nowrap; font-weight: bold; }
        .badge-hot { background-color: #3b82f6; } /* Blue */
        .badge-sale { background-color: #ef4444; } /* Red */
        .badge-hot::after, .badge-sale::after { content: ''; position: absolute; bottom: -3px; left: 50%; transform: translateX(-50%); border-width: 4px 4px 0; border-style: solid; border-color: inherit; }
        .badge-hot::after { border-color: #3b82f6 transparent transparent; }
        .badge-sale::after { border-color: #ef4444 transparent transparent; }

        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .footer-heading { font-weight: 700; color: #333; text-transform: uppercase; font-size: 13px; margin-bottom: 16px; }
        .footer-link { color: #6b7280; font-size: 12px; display: block; margin-bottom: 10px; transition: color 0.2s; }
        .footer-link:hover { color: #eb484e; }
    </style>
    @include('shop.partials.dynamic-colors', ['client' => $client])
</head>
<body class="antialiased flex flex-col min-h-screen font-sans selection:bg-shred/20 selection:text-shred" style="{{ $client->bg_color ? 'background-color: '.$client->bg_color.' !important;' : '' }}">
    
    {{-- Flash Sale Bar --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Bar (Dark Blue) --}}
    <div class="bg-shdark text-gray-300 text-[11px] py-2 hidden md:block border-b border-gray-700">
        <div class="max-w-[1240px] mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center gap-6">
                @if($client->email)<a href="mailto:{{$client->email}}" class="hover:text-white transition flex items-center gap-2"><i class="far fa-envelope text-gray-400"></i> Email: {{$client->email}}</a>@endif
                @if($client->phone)<a href="tel:{{$client->phone}}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-phone-alt text-gray-400"></i> Hotline: {{$client->phone}}</a>@endif
            </div>
            <div class="flex items-center gap-5">
                <a href="{{ $client->email ? 'mailto:'.$client->email : '#' }}" class="hover:text-shred transition font-medium">Contact Us</a>
                <div class="flex items-center gap-4 text-gray-400">
                    @if($client->social_facebook ?? $client->facebook_url)<a href="{{$client->social_facebook ?? $client->facebook_url}}" target="_blank" class="hover:text-white transition"><i class="fab fa-facebook-f"></i></a>@endif
                    @if($client->social_youtube ?? $client->youtube_url)<a href="{{$client->social_youtube ?? $client->youtube_url}}" target="_blank" class="hover:text-white transition"><i class="fab fa-youtube"></i></a>@endif
                    @if($client->social_instagram ?? $client->instagram_url)<a href="{{$client->social_instagram ?? $client->instagram_url}}" target="_blank" class="hover:text-white transition"><i class="fab fa-instagram"></i></a>@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Header (Dark Blue BG matching the screenshot) --}}
    <header class="bg-shdark py-5 sticky sm:relative top-0 z-50">
        <div class="max-w-[1240px] mx-auto px-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 md:gap-8">
                
                {{-- Logo --}}
                <a href="{{$baseUrl}}" class="flex items-center shrink-0">
                    @if($client->logo)
                        <img src="{{asset('storage/'.$client->logo)}}" class="h-10 md:h-12 object-contain bg-white rounded p-1" alt="{{$client->shop_name}}">
                    @else
                        <!-- Text Logo if no image -->
                        <div class="text-white font-black text-2xl flex items-center gap-2">
                            <i class="fas fa-shopping-bag text-shred"></i>
                            <span class="tracking-tight">{{$client->shop_name}}<span class="text-sm font-normal text-gray-400 block -mt-1 tracking-widest uppercase" style="font-size:8px;">Proudly Bangladesh</span></span>
                        </div>
                    @endif
                </a>

                {{-- Search Bar --}}
                <div class="flex-1 w-full max-w-2xl lg:ml-8">
                    <form action="{{$baseUrl}}" method="GET" class="w-full relative flex items-center bg-white rounded shadow-sm overflow-hidden h-10 md:h-11 border border-white focus-within:ring-2 focus-within:ring-shred/50">
                        <input type="text" name="search" value="{{request('search')}}" placeholder="Search entire store here..." 
                            class="w-full bg-transparent px-4 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none border-none h-full">
                        <button class="bg-shred hover:bg-red-600 text-white w-12 md:w-16 h-full flex items-center justify-center transition">
                            <i class="fas fa-search text-sm"></i>
                        </button>
                    </form>
                </div>

                {{-- User / Cart Icons --}}
                <div class="hidden md:flex items-center gap-8 shrink-0 text-white">
                    <a href="#" class="flex items-center gap-3 hover:text-shred transition group cursor-pointer">
                        <div class="relative">
                            <i class="fas fa-shopping-cart text-2xl text-gray-300 group-hover:text-white transition"></i>
                            <span class="absolute -top-2 -right-2 bg-shred text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-shdark">0</span>
                        </div>
                        <div class="flex flex-col pt-1">
                            <span class="text-[10px] text-gray-400 font-bold uppercase leading-none">0</span>
                            <span class="text-sm font-bold leading-none mt-1">My Cart</span>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        <i class="far fa-user text-2xl text-gray-300"></i>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-gray-300 uppercase leading-tight">Hello Guest!</span>
                            <span class="text-xs font-bold leading-tight mt-0.5"><a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="hover:text-shred transition">Track Order</a></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Navigation Bar --}}
    <nav class="bg-white border-b border-gray-200 hidden md:block shadow-sm relative z-40">
        <div class="max-w-[1240px] mx-auto px-4 flex items-center h-12">
            
            {{-- Category Button --}}
            <div class="w-64 h-full relative group">
                <a href="{{$baseUrl}}?category=all" class="h-full flex items-center justify-between px-5 bg-shred hover:bg-red-600 transition text-white text-sm font-bold cursor-pointer">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="tracking-wide">ALL CATEGORIES</span>
                    </div>
                    <i class="fas fa-bars opacity-80"></i>
                </a>
            </div>

            <div class="flex items-center pl-6 flex-1 gap-1">
                @if(isset($primaryMenu) && $primaryMenu->items->count() > 0)
                    @foreach($primaryMenu->items as $item)
                        <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="nav-link {!! request()->is(ltrim($item->resolved_url, '/')) ? '!text-shred border-b-2 border-shred' : '' !!}">{{ mb_strtoupper($item->label) }}</a>
                    @endforeach
                @else
                    <a href="{{$baseUrl}}" class="nav-link !text-shred {!! request()->is('/') ? 'border-b-2 border-shred' : '' !!}">HOME</a>
                    @if(isset($categories) && $categories->count() > 0)
                        @foreach($categories->take(4) as $cat)
                        <a href="{{$baseUrl}}?category={{$cat->slug}}" class="nav-link">{{ mb_strtoupper($cat->name) }}</a>
                        @endforeach
                    @else
                        <a href="{{$baseUrl}}?category=all" class="nav-link">ALL PRODUCTS</a>
                        <a href="{{$baseUrl}}?category=hot-deals" class="nav-link flex items-center gap-1">
                            <span class="nav-badge badge-sale">Sale</span> HOT DEALS
                        </a>
                    @endif
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="nav-link">TRACK ORDER <i class="fas fa-truck-fast ml-1 text-[10px]"></i></a>
                @endif
            </div>
            
        </div>
    </nav>

    <main class="flex-1 w-full bg-white pb-16">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-auto">
        {{-- Newsletter Bar --}}
        <div class="bg-shdark pt-8 pb-6 border-b border-gray-700">
            <div class="max-w-[1240px] mx-auto px-4">
                <div class="flex flex-col md:flex-row items-center gap-6 justify-between lg:px-12 relative h-16">
                    <div class="flex items-center gap-4 hidden md:flex">
                        <i class="far fa-envelope-open text-4xl text-white"></i>
                        <h3 class="text-white font-bold text-[15px] tracking-widest">SIGN UP FOR NEWSLETTER FOR OFFER AND UPDATES</h3>
                    </div>
                    
                    <form class="flex w-full md:w-1/2 max-w-lg h-10 shadow-sm">
                        <input type="email" placeholder="Your email address" required class="flex-1 px-4 py-2 text-sm text-dark placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-shred border-none h-full bg-white">
                        <button type="submit" class="bg-shred hover:bg-red-600 text-white font-bold text-xs px-6 uppercase tracking-wider transition h-full text-center">SUBSCRIBE</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Main Footer --}}
        <div class="bg-white pt-10 pb-8 border-b border-gray-200">
            <div class="max-w-[1240px] mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    
                    {{-- Column 1: Contact --}}
                    <div>
                        <h4 class="footer-heading pb-2 border-b border-gray-200 inline-block">{{ $client->widgets['footer']['contact_title'] ?? 'CONTACT & INFO' }}</h4>
                        <div class="space-y-4 mt-4">
                            @if($client->address)
                            <div class="flex items-start gap-3">
                                <i class="fas fa-map-marker-alt text-shred mt-1 text-sm bg-red-50 p-1.5 rounded-full w-6 h-6 flex items-center justify-center"></i>
                                <span class="text-xs text-gray-500 leading-relaxed">{{ $client->address }}</span>
                            </div>
                            @endif
                            @if($client->phone)
                            <div class="flex items-start gap-3">
                                <i class="fas fa-phone-alt text-shred mt-0.5 text-sm bg-red-50 p-1.5 rounded-full w-6 h-6 flex items-center justify-center"></i>
                                <span class="text-xs text-gray-500 leading-relaxed">Hotline: {{ $client->phone }}</span>
                            </div>
                            @endif
                            @if($client->email)
                            <div class="flex items-start gap-3">
                                <i class="far fa-envelope text-shred mt-0.5 text-sm bg-red-50 p-1.5 rounded-full w-6 h-6 flex items-center justify-center"></i>
                                <span class="text-xs text-gray-500 leading-relaxed">Email: {{ $client->email }}</span>
                            </div>
                            @endif
                            @if($client->widgets['office_hours']['text'] ?? false)
                            <div class="flex items-start gap-3">
                                <i class="far fa-clock text-shred mt-0.5 text-sm bg-red-50 p-1.5 rounded-full w-6 h-6 flex items-center justify-center"></i>
                                <span class="text-xs text-gray-500 leading-relaxed">Open Time: {{ $client->widgets['office_hours']['text'] }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Social Square Icons --}}
                        @if($client->widgets['footer']['show_social'] ?? true)
                        <div class="flex gap-2 mt-6">
                            @php $fbUrl = $client->social_facebook ?? $client->facebook_url ?? null; $ytUrl = $client->social_youtube ?? $client->youtube_url ?? null; $igUrl = $client->social_instagram ?? $client->instagram_url ?? null; @endphp
                            @if($fbUrl)<a href="{{$fbUrl}}" target="_blank" class="w-8 h-8 flex items-center justify-center bg-[#3b5998] hover:bg-[#2d4373] text-white rounded transition"><i class="fab fa-facebook-f text-sm"></i></a>@endif
                            @if($ytUrl)<a href="{{$ytUrl}}" target="_blank" class="w-8 h-8 flex items-center justify-center bg-[#cd201f] hover:bg-[#a31918] text-white rounded transition"><i class="fab fa-youtube text-sm"></i></a>@endif
                            @if($igUrl)<a href="{{$igUrl}}" target="_blank" class="w-8 h-8 flex items-center justify-center bg-[#c13584] hover:bg-[#9c2b6b] text-white rounded transition"><i class="fab fa-instagram text-sm"></i></a>@endif
                        </div>
                        @endif
                    </div>

                    {{-- Column 2 --}}
                    <div>
                        <h4 class="footer-heading pb-2 border-b border-gray-200 inline-block">{{ mb_strtoupper($client->widgets['footer']['menu1_title'] ?? ($footerMenu1->name ?? 'CUSTOMER SERVICE')) }}</h4>
                        <div class="mt-4">
                            @if(isset($footerMenu1) && $footerMenu1->items->count() > 0)
                                @foreach($footerMenu1->items as $item)
                                    <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-link">{{ $item->label }}</a>
                                @endforeach
                            @else
                                <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="footer-link">Track Your Order</a>
                            @endif
                        </div>
                    </div>

                    {{-- Column 3 --}}
                    <div>
                        <h4 class="footer-heading pb-2 border-b border-gray-200 inline-block">{{ mb_strtoupper($client->widgets['footer']['menu2_title'] ?? ($footerMenu2->name ?? 'USEFUL LINKS')) }}</h4>
                        <div class="mt-4">
                            @if(isset($footerMenu2) && $footerMenu2->items->count() > 0)
                                @foreach($footerMenu2->items as $item)
                                    <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-link">{{ $item->label }}</a>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Column 4 --}}
                    <div>
                        <h4 class="footer-heading pb-2 border-b border-gray-200 inline-block">{{ mb_strtoupper($client->widgets['footer']['menu3_title'] ?? ($footerMenu3->name ?? 'TERMS & POLICY '.date('Y'))) }}</h4>
                        <div class="mt-4">
                            @if(isset($footerMenu3) && $footerMenu3->items->count() > 0)
                                @foreach($footerMenu3->items as $item)
                                    <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="footer-link">{{ $item->label }}</a>
                                @endforeach
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

            {{-- Bottom Bar --}}
            <div class="bg-white py-6 border-b-[6px] border-shred">
                <div class="max-w-[1240px] mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                    @if($client->widgets['app_links']['active'] ?? false)
                    <div class="flex items-center gap-4">
                        <span class="text-[13px] font-bold text-dark tracking-tighter">CHECK OUT OUR APP!</span>
                        @if($client->widgets['app_links']['ios'] ?? false)
                        <a href="{{ $client->widgets['app_links']['ios'] }}" target="_blank" class="bg-black text-white hover:bg-gray-800 transition rounded flex items-center gap-2 px-3 py-1.5 h-8">
                            <i class="fab fa-apple text-xl mb-1"></i>
                            <span class="flex flex-col"><span class="text-[7px]">Download on the</span><span class="text-[11px] font-bold leading-none">App Store</span></span>
                        </a>
                        @endif
                        @if($client->widgets['app_links']['android'] ?? false)
                        <a href="{{ $client->widgets['app_links']['android'] }}" target="_blank" class="bg-black text-white hover:bg-gray-800 transition rounded flex items-center gap-2 px-3 py-1.5 h-8">
                            <i class="fab fa-google-play text-[15px]"></i>
                            <span class="flex flex-col justify-center"><span class="text-[7px]">GET IT ON</span><span class="text-[11px] font-bold leading-none mt-0.5">Google Play</span></span>
                        </a>
                        @endif
                    </div>
                    @endif

                    <div class="flex flex-col items-end">
                        <div class="flex gap-2 mb-2">
                            @if($client->cod_active)
                            <span class="text-[10px] font-bold text-gray-600 border border-gray-200 px-2 py-1">COD</span>
                            @endif
                            @if($client->partial_payment_active || ($client->full_payment_active ?? false))
                            <span class="text-[10px] font-bold text-pink-600 border border-pink-200 px-2 py-1">Mobile Pay</span>
                            @endif
                        </div>
                        <div class="text-[10px] text-gray-400 flex gap-4">
                            <span>{!! nl2br(e($client->footer_text ?? ('&copy; '.date('Y').' '.$client->shop_name.'. All Rights Reserved.'))) !!}</span>
                            @if(!empty($client->footer_links))
                                @foreach((array)$client->footer_links as $fl)
                                <a href="{{ $fl['url'] ?? '#' }}" class="hover:text-shred transition">{{ $fl['title'] ?? '' }}</a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
@include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
@include('shop.partials.popup-banner', ['client' => $client])
</body>
</html>



