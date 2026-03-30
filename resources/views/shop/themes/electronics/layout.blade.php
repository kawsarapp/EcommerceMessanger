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
    
    <!-- Fonts: Inter for UI, Roboto Mono for numbers/specs -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#0ea5e9"}}', // Default Sky Blue / Tech Blue
                        dark: '#030712',
                        panel: '#111827'
                    },
                    fontFamily:{
                        sans:['Inter','sans-serif'],
                        mono:['Roboto Mono','monospace']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#0ea5e9"}};
            --mob-primary: {{$client->primary_color ?? "#0ea5e9"}};
        }
        [x-cloak]{display:none!important} 
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #030712; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--tw-color-primary); }
        .hide-scroll::-webkit-scrollbar{display:none}
        .tech-border { border: 1px solid rgba(255,255,255,0.08); }
        .tech-glow:hover { box-shadow: 0 0 20px -5px var(--tw-color-primary); border-color: var(--tw-color-primary); }
        .tech-gradient { background: radial-gradient(circle at top right, rgba(14, 165, 233, 0.1), transparent 50%); }
        
        /* Cyberpunk Additions */
        .neon-border { border: 1px solid var(--tw-color-primary); box-shadow: inset 0 0 10px rgba(14, 165, 233, 0.2), 0 0 10px rgba(14, 165, 233, 0.2); }
        .neon-text { text-shadow: 0 0 8px var(--tw-color-primary); }
        .cyber-grid { background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px); background-size: 20px 20px; }
        .hud-panel { background: rgba(17, 24, 39, 0.9); border: 1px solid rgba(14, 165, 233, 0.3); position: relative; }
        .hud-panel::before { content: ''; position: absolute; top: -1px; left: -1px; width: 10px; height: 10px; border-top: 2px solid var(--tw-color-primary); border-left: 2px solid var(--tw-color-primary); }
        .hud-panel::after { content: ''; position: absolute; bottom: -1px; right: -1px; width: 10px; height: 10px; border-bottom: 2px solid var(--tw-color-primary); border-right: 2px solid var(--tw-color-primary); }

        @media(max-width:767px){
            .mob-nav{--mob-primary:{{$client->primary_color ?? "#0ea5e9"}};background:#111827!important;border-top-color:rgba(255,255,255,0.08)!important}
            .mob-nav a{color:#9ca3af!important}
            .mob-nav a:hover,.mob-nav a.active{color:{{$client->primary_color ?? "#0ea5e9"}}!important; text-shadow: 0 0 8px var(--tw-color-primary);}
            .mob-search-bar{background:#111827!important;border-bottom-color:rgba(255,255,255,0.08)!important}
            .mob-search-bar input{background:#030712!important;color:#fff!important;border-color:{{$client->primary_color ?? "#0ea5e9"}}!important}
        }
    </style>
</head>
<body class="bg-[#030712] text-gray-200 antialiased flex flex-col min-h-screen cyber-grid selection:bg-primary/30 selection:text-white">

    {{-- ⚡ Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    <header class="bg-panel/80 backdrop-blur-lg sticky top-0 z-50 border-b border-primary/20 shadow-[0_4px_30px_rgba(14,165,233,0.1)] transition-all">
        <div class="max-w-[100rem] mx-auto px-4 md:px-8 h-14 md:h-20 flex justify-between items-center gap-3">
            <a href="{{$baseUrl}}" class="flex items-center gap-2 min-w-0">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client->logo)}}" class="h-7 md:h-10 object-contain flex-shrink-0">
                @endif
                <span class="text-base md:text-2xl font-black tracking-tight text-white truncate max-w-[160px] md:max-w-none">{{$client->shop_name}}</span>
                <span class="bg-primary text-white text-[9px] font-bold px-1.5 py-0.5 rounded-sm uppercase tracking-wider ml-1 hidden sm:inline-block flex-shrink-0">Tech</span>
            </a>
            <!-- Desktop Search -->
            <div class="hidden lg:flex w-full max-w-xl mx-8 relative">
                <input type="text" placeholder="Search devices, accessories, models..." class="w-full bg-dark tech-border text-sm text-white px-4 py-2.5 rounded-md focus:ring-1 focus:ring-primary focus:border-primary transition placeholder-gray-600">
                <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
            </div>
            <div class="hidden md:flex items-center gap-4">
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="text-xs font-semibold text-gray-400 hover:text-white transition flex items-center gap-2 bg-dark tech-border px-3 py-1.5 rounded-md hover:border-gray-500">
                    <i class="fas fa-crosshairs text-primary"></i> Track Status
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-8 h-8 rounded-md bg-dark tech-border flex items-center justify-center text-gray-400 hover:text-white hover:border-primary transition">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20 tech-gradient">
        @yield('content')
    </main>

    <footer class="bg-panel border-t border-gray-800 pt-16 pb-8 mt-auto">
        <div class="max-w-[100rem] mx-auto px-4 md:px-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <div>
                <h3 class="font-black text-2xl text-white tracking-tight mb-4 flex items-center gap-2">
                    <i class="fas fa-microchip text-primary"></i> {{$client->shop_name}}
                </h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-6 font-medium">Your ultimate hub for next-generation tech, gadgets, and components.</p>
            </div>
            
            <div>
                <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-xs">Categories</h4>
                <div class="flex flex-col space-y-3 text-sm font-medium text-gray-400">
                    <a href="?category=all" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> All Hardware</a>
                    <a href="#" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> New Arrivals</a>
                    <a href="#" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> Best Sellers</a>
                </div>
            </div>

            <div>
                <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-xs">Support</h4>
                <div class="flex flex-col space-y-3 text-sm font-medium text-gray-400">
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> Live Tracking</a>
                    <a href="#" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> Return Policy</a>
                    <a href="#" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> Technical Support</a>
                </div>
            </div>

            <div>
                <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-xs">Stay Connected</h4>
                <div class="flex flex-col space-y-4 text-sm font-medium text-gray-400">
                    @if($client->phone) 
                        <div class="flex items-center gap-3 bg-dark tech-border p-3 rounded-lg text-white font-mono text-xs">
                            <i class="fas fa-headset text-primary text-base"></i> {{$client->phone}}
                        </div>
                    @endif
                    <p class="text-xs text-gray-600">System architecture online. Systems operating at optimal parameters.</p>
                </div>
            </div>
        </div>
        
        <div class="max-w-[100rem] mx-auto px-4 md:px-8 mt-16 flex flex-col md:flex-row justify-between items-center border-t border-gray-800 pt-6">
            <p class="text-xs font-medium text-gray-600 font-mono tracking-wider">&copy; {{date('Y')}} {{$client->shop_name}} <span class="text-primary ml-2">v2.0.4</span></p>
            <div class="flex gap-4 mt-4 md:mt-0 text-[10px] font-bold uppercase tracking-widest text-gray-600">
                <span>Secure SSL</span> <span class="opacity-50">|</span> <span>Fast Shipping</span>
            </div>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
