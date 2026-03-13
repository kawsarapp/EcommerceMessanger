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
    
    <!-- Fonts: Inter for classic, highly readable, premium default -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#0f172a"}}', // Deep slate / classic dark blue
                    },
                    fontFamily:{
                        sans:['Inter','sans-serif']
                    },
                    boxShadow: {
                        'subtle': '0 4px 20px -5px rgba(0,0,0,0.05)',
                        'hover': '0 10px 30px -5px rgba(0,0,0,0.08)',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak]{display:none!important} 
        body { background-color: #fcfcfc; }
        .hide-scroll::-webkit-scrollbar{display:none}
        .classic-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>
<body class="text-gray-800 antialiased flex flex-col min-h-screen font-sans">

    @if($client->announcement_text)
    <div class="bg-gray-100 border-b border-gray-200 text-gray-600 text-center py-2 text-xs font-medium tracking-wide">
        {!! $client->announcement_text !!}
    </div>
    @endif

    <header class="bg-white sticky top-0 z-50 border-b border-gray-200 shadow-sm transition-all relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-primary"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex justify-between items-center">
            
            <a href="{{$baseUrl}}" class="flex items-center gap-3">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client->logo)}}" class="h-10 md:h-12 object-contain">
                @else
                    <div class="w-10 h-10 bg-primary rounded shadow-sm flex items-center justify-center text-white">
                        <i class="fas fa-store"></i>
                    </div>
                @endif
                <span class="text-xl md:text-3xl font-bold tracking-tight text-gray-900 ml-1">{{$client->shop_name}}</span>
            </a>

            <!-- Search Mock -->
            <div class="hidden md:flex flex-1 max-w-lg mx-8 relative">
                <input type="text" placeholder="Search products..." class="w-full bg-gray-50 border border-gray-300 px-5 py-2.5 rounded text-gray-700 text-sm focus:ring-1 focus:ring-primary focus:border-primary transition placeholder-gray-400">
                <button class="absolute right-0 top-0 h-full px-4 text-gray-500 hover:text-primary transition bg-gray-100 rounded-r border-l border-gray-300">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-sm font-semibold text-gray-600 hover:text-primary transition flex items-center gap-2">
                    <i class="fas fa-box"></i> <span class="hidden sm:inline-block">Track Order</span>
                </a>
                
                @if($client->fb_page_id)
                <span class="w-px h-6 bg-gray-300 mx-2 hidden sm:block"></span>
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-10 h-10 rounded bg-gray-50 border border-gray-200 flex items-center justify-center text-primary hover:bg-primary hover:text-white transition shadow-sm">
                    <i class="fab fa-facebook-messenger text-lg"></i>
                </a>
                @endif
            </div>
            
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-gray-900 border-t border-gray-800 pt-16 pb-8 mt-auto text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12">
            <div>
                <a href="{{$baseUrl}}" class="flex items-center gap-2 mb-6 cursor-pointer">
                    <span class="text-2xl font-bold text-white tracking-tight">{{$client->shop_name}}</span>
                </a>
                <p class="text-gray-400 font-normal text-sm leading-relaxed mb-6">Discover premium quality products at unbeatable prices, backed by our commitment to exceptional service.</p>
                <div class="flex gap-4 text-xl text-gray-500">
                    <i class="fab fa-cc-visa hover:text-white transition cursor-pointer"></i>
                    <i class="fab fa-cc-mastercard hover:text-white transition cursor-pointer"></i>
                    <i class="fab fa-cc-amex hover:text-white transition cursor-pointer"></i>
                </div>
            </div>
            
            <div>
                <h4 class="font-bold text-white text-base mb-6 tracking-wide uppercase">Shop Departments</h4>
                <div class="flex flex-col space-y-3 font-medium text-sm text-gray-400">
                    <a href="?category=all" class="hover:text-white transition w-fit">All Categories</a>
                    <a href="#" class="hover:text-white transition w-fit">New Arrivals</a>
                    <a href="#" class="hover:text-white transition w-fit">Featured Items</a>
                    <a href="#" class="hover:text-white transition w-fit">Special Offers</a>
                </div>
            </div>

            <div>
                 <h4 class="font-bold text-white text-base mb-6 tracking-wide uppercase">Customer Support</h4>
                <div class="flex flex-col space-y-3 font-medium text-sm text-gray-400">
                    <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="hover:text-white transition w-fit">Order Status</a>
                    <a href="#" class="hover:text-white transition w-fit">Shipping Policy</a>
                    <a href="#" class="hover:text-white transition w-fit">Returns & Exchanges</a>
                    <a href="#" class="hover:text-white transition w-fit">Secure Payment</a>
                </div>
            </div>

            <div>
                 <h4 class="font-bold text-white text-base mb-6 tracking-wide uppercase">Contact Us</h4>
                <div class="flex flex-col space-y-4 font-medium text-sm text-gray-400">
                    @if($client->phone) 
                        <div class="flex items-center gap-3">
                            <i class="fas fa-headset text-xl text-gray-500"></i>
                            <div>
                                <span class="block text-xs uppercase text-gray-500 tracking-wider">Call Support</span>
                                <span class="text-base text-white font-medium">{{$client->phone}}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">Contact information unavailable.</p>
                    @endif
                    
                    @if($client->email)
                    <div class="flex items-center gap-3 mt-4">
                        <i class="fas fa-envelope text-gray-500"></i>
                        <span>{{$client->email}}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 pt-8 border-t border-gray-800 text-center flex flex-col md:flex-row justify-between items-center text-sm font-medium text-gray-500">
            <p>&copy; {{date('Y')}} {{$client->shop_name}}. All Rights Reserved.</p>
            <div class="mt-4 md:mt-0 space-x-4">
                <a href="#" class="hover:text-white transition">Privacy Policy</a>
                <a href="#" class="hover:text-white transition">Terms of Service</a>
            </div>
        </div>
    </footer>

</body>
</html>