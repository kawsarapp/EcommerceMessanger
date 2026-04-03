<!DOCTYPE html>
@php 
$clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
$baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
$primary='#1a3673';
@endphp
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <meta name="description" content="{{ $client->meta_description ?? $client->shop_name . ' - অনলাইন শপিং করুন সেরা দামে' }}">
    
    @include('shop.partials.tracking', ['client' => $client])

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- BDShop uses system typography or Roboto/Arial. We use Inter to make it look clean -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary: '{{$client->primary_color ?? "#f85606"}}',
                        bdblue: '#1a3673',
                        bdlight: '#f5f7fa',
                        bdhover: '#e2e8f0',
                        bddeep: '#0a2540',
                        dark: '#1e293b',
                    },
                    fontFamily:{
                        sans:['Inter','system-ui','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root { --tw-color-primary: {{$client->primary_color ?? "#f85606"}}; --mob-primary: #1a3673; }
        [x-cloak]{display:none!important}
        body { background-color: #f8f9fa; }
        .hide-scroll::-webkit-scrollbar{display:none}
        .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .bd-card::before { content: ""; position: absolute; inset: 0; background-color: rgba(0,0,0,0.02); opacity: 0; transition: opacity 0.2s; border-radius: inherit; pointer-events: none; }
        .bd-card:hover::before { opacity: 1; }
        .nav-dropdown:hover .dropdown-menu { display: block; }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-sans selection:bg-bdblue/20 selection:text-bdblue">
    
    {{-- Flash Sale Bar --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Top Bar (Black) --}}
    <div class="bg-gray-900 text-gray-300 text-[11px] py-1.5 hidden md:block">
        <div class="max-w-[1400px] mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fas fa-heart text-red-500"></i>
                <span class="font-medium">{{ $client->topbar_text ?? ('স্বাগতম ' . $client->shop_name . '-এ') }}</span>
            </div>
            @if($client->announcement_text)
            <div class="flex items-center gap-2 text-warning font-semibold text-yellow-400">
                <i class="fas fa-exclamation-triangle"></i>
                <span>{!! strip_tags($client->announcement_text) !!}</span>
            </div>
            @endif
            <div class="flex items-center gap-4">
                @if($client->facebook_url)<a href="{{$client->facebook_url}}" target="_blank" class="hover:text-white transition"><i class="fab fa-facebook-f"></i></a>@endif
                @if($client->instagram_url)<a href="{{$client->instagram_url}}" target="_blank" class="hover:text-white transition"><i class="fab fa-instagram"></i></a>@endif
                @if($client->youtube_url ?? false)<a href="{{$client->youtube_url}}" target="_blank" class="hover:text-white transition"><i class="fab fa-youtube"></i></a>@endif
            </div>
        </div>
    </div>

    {{-- Main Header (White) --}}
    <header class="bg-white sticky sm:relative top-0 z-50 border-b border-gray-100">
        <div class="max-w-[1400px] mx-auto px-4 py-3 sm:py-4">
            <div class="flex items-center justify-between gap-4 md:gap-8">
                
                {{-- Logo --}}
                <a href="{{$baseUrl}}" class="flex items-center shrink-0">
                    @if($client->logo)
                        <img src="{{asset('storage/'.$client->logo)}}" class="h-8 md:h-12 object-contain" alt="{{$client->shop_name}}">
                    @endif
                    <span class="text-bdblue font-extrabold text-xl ml-2 hidden sm:block">{{$client->shop_name}}</span>
                </a>

                {{-- Search Bar (Large Center) --}}
                <div class="hidden lg:flex flex-1 max-w-2xl">
                    <form action="{{$baseUrl}}" method="GET" class="w-full relative flex items-center bg-gray-100/80 rounded-lg overflow-hidden border border-gray-200 focus-within:border-bdblue transition-colors">
                        <i class="fas fa-search text-gray-400 pl-4"></i>
                        <input type="text" name="search" value="{{request('search')}}" placeholder="Search for products, brands, or categories..." 
                            class="w-full bg-transparent px-4 py-3 text-sm font-medium text-dark placeholder-gray-500 focus:outline-none focus:ring-0 border-none transition h-full">
                    </form>
                </div>

                {{-- Action Buttons (Desktop only placeholders) --}}
                <div class="hidden xl:flex items-center gap-2 shrink-0">
                    <a href="#" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold px-4 py-2.5 rounded-md flex items-center gap-2 shadow-sm transition"><i class="fas fa-users"></i> Group Buy</a>
                    <a href="#" class="bg-indigo-900 hover:bg-indigo-800 text-white text-xs font-bold px-4 py-2.5 rounded-md flex items-center gap-2 shadow-sm transition"><i class="fas fa-box-open"></i> Dropshop</a>
                    <a href="#" class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-4 py-2.5 rounded-md flex items-center gap-2 shadow-sm transition"><i class="fas fa-handshake"></i> Be Partner</a>
                </div>

                {{-- Right User Actions --}}
                <div class="flex items-center gap-4 sm:gap-6 shrink-0 relative z-50">
                    <a href="#" class="hidden sm:flex flex-col items-center text-gray-600 hover:text-bdblue transition">
                        <i class="far fa-user text-xl mb-1"></i>
                        <span class="text-[10px] font-bold">Account</span>
                    </a>
                    
                    {{-- Mini Cart Icon (Placeholder UI) --}}
                    <div class="relative flex flex-col items-center text-gray-600 hover:text-bdblue transition cursor-pointer">
                        <i class="fas fa-shopping-bag text-xl mb-1"></i>
                        <span class="text-[10px] font-bold">My Cart</span>
                        <span class="absolute -top-1 right-0 sm:right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center transform translate-x-1 outline outline-2 outline-white">0</span>
                    </div>
                </div>
                
            </div>
            
            {{-- Mobile Search --}}
            <div class="lg:hidden mt-3">
                <form action="{{$baseUrl}}" method="GET" class="w-full relative flex items-center bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                    <i class="fas fa-search text-gray-400 pl-3 text-sm"></i>
                    <input type="text" name="search" value="{{request('search')}}" placeholder="Search products..." 
                        class="w-full bg-transparent px-3 py-2 text-sm text-dark placeholder-gray-500 focus:outline-none border-none">
                </form>
            </div>
        </div>
    </header>

    {{-- Blue Navigation Bar --}}
    <nav class="bg-bdblue text-white sticky top-0 sm:top-[-1px] z-40 hidden md:block border-b border-light/10 shadow-sm">
        <div class="max-w-[1400px] mx-auto px-4 flex items-center h-12">
            
            {{-- Category Dropdown Button --}}
            <div class="nav-dropdown h-full relative group">
                <button class="h-full flex items-center gap-3 px-5 bg-white/10 hover:bg-white/20 transition cursor-pointer text-sm font-bold w-60">
                    <i class="fas fa-bars"></i>
                    <span class="flex-1 text-left">Shop by Category</span>
                    <i class="fas fa-chevron-down text-[10px]"></i>
                </button>
                
                {{-- Dropdown Menu --}}
                <div class="dropdown-menu absolute top-full left-0 w-64 bg-white shadow-xl border border-gray-100 rounded-b-lg hidden z-50">
                    <ul class="py-2 text-sm text-gray-700">
                        <li><a href="{{$baseUrl}}?category=all" class="block px-5 py-2 hover:bg-gray-50 hover:text-bdblue font-medium transition"><i class="fas fa-th-large mr-2 w-4 text-center text-gray-400"></i> All Products</a></li>
                        @if(isset($categories))
                            @foreach($categories->take(10) as $c)
                            <li><a href="{{$baseUrl}}?category={{$c->slug}}" class="block px-5 py-2 hover:bg-gray-50 hover:text-bdblue font-medium transition line-clamp-1"><i class="fas fa-caret-right mr-2 w-4 text-center text-gray-400"></i> {{$c->name}}</a></li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="flex items-center ml-6 flex-1 gap-1">
                @if(isset($primaryMenu) && $primaryMenu->items->count() > 0)
                    @foreach($primaryMenu->items as $item)
                        <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="px-4 py-2 rounded-md hover:bg-white/10 transition text-sm font-bold flex items-center gap-2 {{ request()->is(ltrim($item->resolved_url, '/')) ? 'text-yellow-400' : 'text-white/90 hover:text-white' }}">{{ $item->label }}</a>
                    @endforeach
                @else
                    <a href="{{$baseUrl}}" class="px-4 py-2 rounded-md hover:bg-white/10 transition text-sm font-bold flex items-center gap-2 {{ request()->is('/') ? 'text-yellow-400' : '' }}"><i class="fas fa-home"></i> Home</a>
                    <a href="{{$baseUrl}}?category=hot-deals" class="px-4 py-2 rounded-md hover:bg-white/10 transition text-sm font-bold flex items-center gap-2 text-yellow-400"><i class="fas fa-fire"></i> Hot Deals</a>
                    <a href="{{$baseUrl}}?category=all" class="px-4 py-2 rounded-md hover:bg-white/10 transition text-sm font-bold text-white/90 hover:text-white">All Products</a>
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="px-4 py-2 rounded-md hover:bg-white/10 transition text-sm font-bold flex items-center gap-2 text-white/90 hover:text-white"><i class="fas fa-truck-fast"></i> Track Order</a>
                @endif
            </div>
        </div>
    </nav>

    <main class="flex-1 w-full pb-10">
        @yield('content')
    </main>

    {{-- Custom Footer --}}
    <footer class="mt-auto">
        {{-- White SEO text block --}}
        <div class="bg-white py-12 md:py-16 border-t border-gray-200">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <h2 class="text-2xl md:text-3xl font-extrabold text-dark mb-4">{{$client->shop_name}} — {{ $client->tagline ?? 'আপনার বিশ্বস্ত অনলাইন শপিং গন্তব্য' }}</h2>
                <p class="text-gray-500 text-sm leading-relaxed mb-8">{{ $client->description ?? ($client->meta_description ?? $client->shop_name . ' — দ্রুত ডেলিভারি, আসল পণ্য, এবং সারাদেশে সেরা কাস্টমার সার্ভিস।') }}</p>
                
                <div class="flex flex-wrap justify-center items-center gap-4 sm:gap-8 text-xs font-bold text-gray-600">
                    <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-green-500"></i> 100% Genuine Products</span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-truck text-bdblue"></i> Fast Nationwide Delivery</span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-leaf text-green-600"></i> Best Selections</span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-shield-alt text-blue-500"></i> Official Warranty</span>
                </div>
            </div>
        </div>

        {{-- Deep Blue Footer block --}}
        <div class="bg-bddeep text-white pt-16 pb-8 border-t-[8px] border-bdblue">
            <div class="max-w-[1400px] mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12 mb-12">
                    
                    {{-- Logo & Contact (Span 2) --}}
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2 mb-4 shrink-0">
                            @if($client->logo)
                                <img src="{{asset('storage/'.$client->logo)}}" class="h-8 md:h-10 object-contain rounded brightness-0 invert" alt="{{$client->shop_name}}">
                            @endif
                            <span class="text-white font-extrabold text-2xl uppercase tracking-tighter">{{$client->shop_name}}<sup class="text-[10px] font-normal">&trade;</sup></span>
                        </div>
                        <p class="text-gray-400 text-xs leading-relaxed mb-6 max-w-sm">Your premier destination for quality products. We deliver excellence in every product.</p>
                        
                        <div class="space-y-3 mb-6">
                            @if($client->phone)<a href="tel:{{$client->phone}}" class="flex items-center gap-3 text-sm hover:text-primary transition"><i class="fas fa-phone-alt text-primary min-w-[20px]"></i> {{$client->phone}}</a>@endif
                            @if($client->email)<a href="mailto:{{$client->email}}" class="flex items-center gap-3 text-sm hover:text-primary transition"><i class="fas fa-envelope text-primary min-w-[20px]"></i> {{$client->email}}</a>@endif
                            <div class="flex items-center gap-3 text-sm text-gray-300"><i class="fas fa-clock text-primary min-w-[20px]"></i> {{ $client->widgets['office_hours']['text'] ?? '\u09B8ন্ধ্যা \u09EE\u09EA\u09F7\u09EA\u09EE' }}</div>
                        </div>

                        <div class="flex gap-3 mt-4">
                            @if($client->facebook_url ?? false)<a href="{{$client->facebook_url}}" target="_blank" class="w-8 h-8 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center transition text-sm"><i class="fab fa-facebook-f"></i></a>@endif
                            @if($client->instagram_url ?? false)<a href="{{$client->instagram_url}}" target="_blank" class="w-8 h-8 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center transition text-sm"><i class="fab fa-instagram"></i></a>@endif
                            @if($client->youtube_url ?? false)<a href="{{$client->youtube_url}}" target="_blank" class="w-8 h-8 rounded-full bg-white/10 hover:bg-primary flex items-center justify-center transition text-sm"><i class="fab fa-youtube"></i></a>@endif
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-sm mb-6 pb-2 border-b border-white/10">{{ $footerMenu1->name ?? 'Quick Links' }}</h4>
                        <ul class="space-y-3 text-sm text-gray-400">
                            @if(isset($footerMenu1) && $footerMenu1->items->count() > 0)
                                @foreach($footerMenu1->items as $item)
                                    <li><a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> {{ $item->label }}</a></li>
                                @endforeach
                            @else
                                <li><a href="{{$baseUrl}}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Home</a></li>
                                <li><a href="{{$baseUrl}}?category=all" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Shop</a></li>
                                <li><a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Track Order</a></li>
                            @endif
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold text-sm mb-6 pb-2 border-b border-white/10">{{ $footerMenu2->name ?? 'Categories' }}</h4>
                        <ul class="space-y-3 text-sm text-gray-400">
                            @if(isset($footerMenu2) && $footerMenu2->items->count() > 0)
                                @foreach($footerMenu2->items as $item)
                                    <li><a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="hover:text-white transition flex items-center gap-2 line-clamp-1"><i class="fas fa-chevron-right text-[10px] text-primary"></i> {{ $item->label }}</a></li>
                                @endforeach
                            @elseif(isset($categories))
                                @foreach($categories->take(6) as $c)
                                <li><a href="{{$baseUrl}}?category={{$c->slug}}" class="hover:text-white transition flex items-center gap-2 line-clamp-1"><i class="fas fa-chevron-right text-[10px] text-primary"></i> {{$c->name}}</a></li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold text-sm mb-6 pb-2 border-b border-white/10">{{ $footerMenu3->name ?? 'Customer Service' }}</h4>
                        <ul class="space-y-3 text-sm text-gray-400 mb-6">
                            @if(isset($footerMenu3) && $footerMenu3->items->count() > 0)
                                @foreach($footerMenu3->items as $item)
                                    <li><a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> {{ $item->label }}</a></li>
                                @endforeach
                            @else
                                <li><a href="#" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Terms & Conditions</a></li>
                                <li><a href="#" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Privacy Policy</a></li>
                            @endif
                        </ul>
                        
                        <h5 class="font-bold text-xs mb-3 text-white">Newsletter</h5>
                        <p class="text-[10px] text-gray-400 mb-3 block">Subscribe for exclusive deals & updates</p>
                        <form class="flex overflow-hidden rounded border border-white/20 focus-within:border-primary transition max-w-[200px]">
                            <input type="email" placeholder="Your email" class="w-full bg-white/5 px-3 py-1.5 text-xs text-white placeholder-gray-500 focus:outline-none border-none">
                            <button type="button" class="bg-blue-500 hover:bg-blue-600 px-3 text-white"><i class="fas fa-paper-plane text-xs"></i></button>
                        </form>
                    </div>

                </div>

                {{-- Bottom Bar --}}
                <div class="border-t border-white/10 py-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-[11px] text-gray-400">
                        &copy; {{date('Y')}} <strong class="text-white">{{$client->shop_name}}</strong>. সর্বস্বত্ব সংরক্ষিত।
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400 mr-2">Secure Payment:</span>
                        <div class="bg-white rounded p-1 flex items-center justify-center w-10 h-6"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/16/Former_Visa_%28company%29_logo.svg/1024px-Former_Visa_%28company%29_logo.svg.png" class="h-3 object-contain" loading="lazy"></div>
                        <div class="bg-white rounded p-1 flex items-center justify-center w-10 h-6"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/1024px-MasterCard_Logo.svg.png" class="h-4 object-contain" loading="lazy"></div>
                        <div class="bg-white rounded p-1 flex items-center justify-center w-10 h-6"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/American_Express_logo_%282018%29.svg/1200px-American_Express_logo_%282018%29.svg.png" class="h-4 object-contain" loading="lazy"></div>
                        <div class="bg-white rounded p-1 flex items-center justify-center w-10 h-6"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/1024px-PayPal.svg.png" class="h-3 object-contain" loading="lazy"></div>
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
