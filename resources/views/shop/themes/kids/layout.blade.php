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
    
    <!-- AlpineJS & TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Fonts: Fredoka One (chubby heading) & Quicksand (fun rounded body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#f43f5e"}}', // Default vibrant pink
                        funblue: '#0ea5e9',
                        funyellow: '#facc15'
                    },
                    fontFamily:{
                        heading:['"Fredoka One"','cursive'],
                        sans:['Quicksand','sans-serif']
                    },
                    boxShadow: {
                        'cloud': '0 10px 30px rgba(0,0,0,0.05), inset 0 2px 10px rgba(255,255,255,0.5)',
                        'float': '0 20px 40px -10px var(--tw-color-primary)',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#f43f5e"}};
        }
        [x-cloak]{display:none!important} 
        body { background-color: #fce7f3; background-image: radial-gradient(#f9a8d4 2px, transparent 2px); background-size: 40px 40px; }
        .hide-scroll::-webkit-scrollbar{display:none}
        .cloud-border { border-radius: 60px 20px 50px 30px / 30px 40px 60px 40px; }
        .bouncy:hover { animation: bounce-fun 0.5s ease; }
        @keyframes bounce-fun {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen font-sans selection:bg-funyellow selection:text-black">

    @if($client->announcement_text)
    <div class="bg-funyellow text-slate-800 text-center py-2.5 text-sm font-bold tracking-widest uppercase shadow-sm flex items-center justify-center gap-2 border-b-4 border-yellow-500">
        <i class="fas fa-star text-yellow-600 animate-spin-slow"></i> 
        {!! $client->announcement_text !!} 
        <i class="fas fa-star text-yellow-600 animate-spin-slow"></i>
    </div>
    @endif

    <header class="bg-white/90 backdrop-blur-md sticky top-0 z-50 border-b-4 border-slate-200 transition-all shadow-cloud">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-12 h-20 md:h-24 flex justify-between items-center group">
            
            <a href="{{$baseUrl}}" class="flex items-center gap-3 bouncy relative">
                <!-- Decorative hidden cloud element -->
                <div class="absolute -top-6 -left-6 w-20 h-20 bg-primary/10 rounded-full blur-xl z-0 transition-opacity opacity-0 group-hover:opacity-100"></div>
                
                @if($client->logo)
                    <img src="{{asset('storage/'.$client->logo)}}" class="h-12 md:h-16 object-contain z-10 relative">
                @else
                    <div class="w-12 h-12 md:w-16 md:h-16 bg-primary text-white rounded-[2rem] flex items-center justify-center shadow-lg border-4 border-pink-200 z-10 relative transform -rotate-6 group-hover:rotate-0 transition-transform">
                        <i class="fas fa-puzzle-piece text-2xl md:text-3xl"></i>
                    </div>
                @endif
                <span class="text-2xl md:text-4xl font-heading tracking-wide text-slate-800 group-hover:text-primary transition z-10 relative ml-2 drop-shadow-sm">{{$client->shop_name}}</span>
            </a>

            <!-- Search Mock -->
            <div class="hidden lg:flex w-full max-w-lg mx-6 relative">
                <input type="text" placeholder="Find toys, clothes, games..." class="w-full bg-slate-100 border-4 border-white shadow-inner px-6 py-3.5 rounded-full text-slate-700 font-bold focus:ring-4 focus:ring-funblue/30 focus:border-funblue transition">
                <button class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-funblue text-white rounded-full flex items-center justify-center hover:bg-blue-600 hover:scale-110 transition shadow-md">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="flex items-center gap-3 md:gap-5">
                <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-sm font-bold text-slate-700 hover:text-white transition flex items-center gap-2 bg-white px-5 py-2.5 rounded-full border-4 border-slate-200 hover:border-funblue hover:bg-funblue shadow-sm bouncy transform-gpu whitespace-nowrap">
                    <i class="fas fa-map-marker-alt text-funblue group-hover:text-white"></i> <span class="hidden sm:inline-block">Track Magic</span>
                </a>
                
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-12 h-12 rounded-full bg-white border-4 border-slate-200 flex items-center justify-center text-funblue hover:bg-funblue hover:text-white hover:border-funblue transition shadow-sm bouncy">
                    <i class="fab fa-facebook-messenger text-xl"></i>
                </a>
                @endif
            </div>
            
        </div>
    </header>

    <main class="flex-1 w-full pb-24">
        @yield('content')
    </main>

    <footer class="bg-white border-t-8 border-primary pt-20 pb-12 mt-auto relative overflow-hidden">
        <!-- Fun decoration -->
        <i class="fas fa-rocket absolute -right-20 -top-20 text-[200px] text-slate-50 opacity-50 transform rotate-45"></i>
        
        <div class="max-w-7xl mx-auto px-6 md:px-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 relative z-10 text-center sm:text-left">
            <div>
                <a href="{{$baseUrl}}" class="flex items-center justify-center sm:justify-start gap-3 mb-6 bouncy inline-block">
                    <div class="w-10 h-10 bg-funblue text-white rounded-2xl flex items-center justify-center transform rotate-12 shadow-md border-2 border-white">
                        <i class="fas fa-puzzle-piece text-xl"></i>
                    </div>
                    <span class="text-3xl font-heading text-slate-800 tracking-wide">{{$client->shop_name}}</span>
                </a>
                <p class="text-slate-500 font-bold text-base leading-relaxed mb-6">Making playtime the best time! Discover a universe of fun, learning, and endless smiles.</p>
            </div>
            
            <div>
                <h4 class="font-heading text-slate-800 text-xl mb-6 relative inline-block">
                    <span class="relative z-10">Fun Zones</span>
                    <span class="absolute bottom-0 left-0 w-full h-3 bg-funyellow/50 -rotate-2 -z-0"></span>
                </h4>
                <div class="flex flex-col space-y-4 font-bold text-base text-slate-600">
                    <a href="?category=all" class="hover:text-primary transition hover:translate-x-2 w-fit transform duration-200">All Toys & Goodies</a>
                    <a href="#" class="hover:text-funblue transition hover:translate-x-2 w-fit transform duration-200">Action Figures</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-2 w-fit transform duration-200">Creative Learning</a>
                    <a href="#" class="hover:text-funblue transition hover:translate-x-2 w-fit transform duration-200">Tiny Clothing</a>
                </div>
            </div>

            <div>
                 <h4 class="font-heading text-slate-800 text-xl mb-6 relative inline-block">
                    <span class="relative z-10">Parent Help</span>
                    <span class="absolute bottom-0 left-0 w-full h-3 bg-primary/30 rotate-2 -z-0"></span>
                </h4>
                <div class="flex flex-col space-y-4 font-bold text-base text-slate-600">
                    <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="hover:text-primary transition hover:translate-x-2 w-fit transform duration-200">Where's my order?</a>
                    <a href="#" class="hover:text-funblue transition hover:translate-x-2 w-fit transform duration-200">Returns are easy</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-2 w-fit transform duration-200">Safety Information</a>
                </div>
            </div>

            <div>
                 <h4 class="font-heading text-slate-800 text-xl mb-6 relative inline-block">
                    <span class="relative z-10">Say Hello!</span>
                    <span class="absolute bottom-0 left-0 w-full h-3 bg-emerald-400/30 -rotate-1 -z-0"></span>
                </h4>
                <div class="flex flex-col items-center sm:items-start space-y-5 font-bold text-base text-slate-600">
                    @if($client->phone) 
                        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-[2rem] border-4 border-slate-100 shadow-sm w-full bouncy cursor-pointer">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-md text-funblue border-2 border-slate-100 shrink-0">
                                <i class="fas fa-phone-alt text-xl"></i>
                            </div>
                            <span class="text-xl font-heading text-slate-800 tracking-wider">{{$client->phone}}</span>
                        </div>
                    @else
                        <p class="text-slate-500 border-2 border-dashed border-slate-300 rounded-xl p-4">No phone yet!</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="max-w-7xl mx-auto px-6 mt-16 text-center">
            <p class="text-base font-bold text-slate-400">&copy; {{date('Y')}} {{$client->shop_name}}. Bringing joy every day! <i class="fas fa-smile text-funyellow mx-1"></i></p>
        </div>
    </footer>

</body>
</html>