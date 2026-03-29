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
    
    <!-- Fonts: Nunito for friendly grocery look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#10b981"}}', // Emerald Green by default
                        secondary: '#facc15' // Yellow accent
                    },
                    fontFamily:{
                        sans:['Nunito','sans-serif']
                    },
                    boxShadow: {
                        'soft': '0 10px 40px -10px rgba(0,0,0,0.08)',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#10b981"}};
            --mob-primary: {{$client->primary_color ?? "#10b981"}};
        }
        [x-cloak]{display:none!important} 
        body { background-color: #f8fafc; }
        .hide-scroll::-webkit-scrollbar{display:none}
        .blob-bg { background-image: radial-gradient(circle at top left, rgba(16, 185, 129, 0.05), transparent 40%), radial-gradient(circle at bottom right, rgba(250, 204, 21, 0.05), transparent 40%); }
        
        /* Fresh Grocery Additions */
        .grocer-card { background: white; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02); border: 1px solid rgba(16, 185, 129, 0.08); transition: all 0.3s ease; overflow: hidden; }
        .grocer-card:hover { box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.05), 0 10px 10px -5px rgba(16, 185, 129, 0.02); transform: translateY(-3px); border-color: rgba(16, 185, 129, 0.15); }
        .pill-btn { border-radius: 9999px; font-weight: 800; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .pill-btn:active { transform: scale(0.95); }

        @media(max-width:767px){
            .shop-name-text{font-size:1.1rem!important;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased flex flex-col min-h-screen font-sans selection:bg-primary/20 selection:text-primary blob-bg relative">

    {{-- Decorative Background Blobs --}}
    <div class="fixed inset-0 pointer-events-none z-[-1] overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary/5 rounded-full blur-[100px]"></div>
        <div class="absolute top-[40%] -right-40 w-96 h-96 bg-yellow-400/5 rounded-full blur-[100px]"></div>
    </div>

    {{-- ⚡ Flash Sale Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    <header class="bg-white/95 backdrop-blur-sm sticky top-0 z-50 border-b border-primary/10 transition-all shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 md:h-20 flex justify-between items-center gap-3">
            <a href="{{$baseUrl}}" class="flex items-center gap-2 group min-w-0">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client- loading="lazy">logo)}}" class="h-8 md:h-12 object-contain flex-shrink-0 group-hover:scale-105 transition">
                @else
                    <div class="w-9 h-9 bg-primary/10 rounded-full flex items-center justify-center text-primary text-lg flex-shrink-0">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                @endif
                <span class="shop-name-text text-xl md:text-2xl font-black tracking-tight text-slate-800 group-hover:text-primary transition">{{$client->shop_name}}</span>
            </a>
            <!-- Desktop: search + actions -->
            <div class="hidden md:flex flex-1 max-w-xl mx-8 relative">
                <input type="text" placeholder="Search for fresh vegetables, fruits, meat..." class="w-full bg-slate-100 border-none px-6 py-3 rounded-full text-slate-700 font-semibold focus:ring-2 focus:ring-primary focus:bg-white transition shadow-inner">
                <button class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center hover:bg-emerald-600 transition shadow-sm">
                    <i class="fas fa-search text-xs"></i>
                </button>
            </div>
            <div class="hidden md:flex items-center gap-4">
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="text-sm font-bold text-slate-600 hover:text-primary transition flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-full border border-slate-200 hover:border-primary/50">
                    <i class="fas fa-truck-fast text-primary"></i> <span>Track Status</span>
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-primary hover:bg-primary hover:text-white transition shadow-sm">
                    <i class="fab fa-facebook-messenger text-lg"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 pt-16 pb-8 mt-auto relative overflow-hidden">
        <!-- Decorative subtle pattern -->
        <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(var(--tw-color-primary) 2px, transparent 2px); background-size: 30px 30px;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 relative z-10">
            <div>
                <a href="{{$baseUrl}}" class="flex items-center gap-2 mb-6 cursor-pointer">
                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <span class="text-2xl font-black text-slate-800 tracking-tight">{{$client->shop_name}}</span>
                </a>
                <p class="text-slate-500 font-semibold text-sm leading-relaxed mb-6">Freshness delivered right to your doorstep. We ensure quality and hygiene in every product we pack.</p>
                <div class="flex gap-3 text-2xl text-slate-400">
                    <i class="fab fa-cc-visa hover:text-blue-600 transition cursor-pointer"></i>
                    <i class="fab fa-cc-mastercard hover:text-red-500 transition cursor-pointer"></i>
                    <i class="fab fa-cc-paypal hover:text-blue-500 transition cursor-pointer"></i>
                </div>
            </div>
            
            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="fas fa-carrot text-primary"></i> Categories</h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    <a href="?category=all" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Fresh Produce</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Dairy & Bakery</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Snacks & Beverages</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Meat & Seafood</a>
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="fas fa-heart text-red-400"></i> Customer Care</h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Track Your Order</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Delivery Information</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Returns & Refunds</a>
                    <a href="#" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Help Center</a>
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="fas fa-headset text-blue-500"></i> Contact Us</h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    @if($client->phone) 
                        <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-primary">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div>
                                <span class="block text-xs text-slate-400 uppercase tracking-widest mb-0.5">Hotline 24/7</span>
                                <span class="text-base text-slate-800">{{$client->phone}}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-slate-500">Contact details not available.</p>
                    @endif
                    
                    @if($client->email)
                    <div class="flex items-center gap-3">
                        <i class="fas fa-envelope text-slate-400"></i>
                        <span>{{$client->email}}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 pt-8 border-t border-slate-100 text-center">
            <p class="text-sm font-bold text-slate-400">&copy; {{date('Y')}} {{$client->shop_name}}. All Rights Reserved. Crafted with <i class="fas fa-heart text-red-500 mx-1"></i></p>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
