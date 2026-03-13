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
    
    <!-- Fonts: Outfit for Modern Minimal Look -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#000000"}}'
                    },
                    fontFamily:{
                        sans:['Outfit','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak]{display:none!important} 
        .hide-scroll::-webkit-scrollbar{display:none}
        .modern-hover { transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .modern-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="bg-[#fafafa] text-gray-900 antialiased flex flex-col min-h-screen selection:bg-primary selection:text-white">

    @if($client->announcement_text)
    <div class="bg-black text-white text-center py-2.5 text-xs font-bold tracking-[0.2em] uppercase w-full z-50">
        {!! $client->announcement_text !!}
    </div>
    @endif

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-40 border-b border-gray-100 transition-all">
        <div class="max-w-[90rem] mx-auto px-6 h-20 flex justify-between items-center">
            
            <a href="{{$baseUrl}}" class="flex items-center gap-3">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client->logo)}}" class="h-8 md:h-10 object-contain">
                @endif
                <span class="text-2xl font-black tracking-tighter uppercase">{{$client->shop_name}}</span>
            </a>
            
            <div class="flex gap-6 items-center">
                <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-xs font-black uppercase tracking-[0.15em] text-gray-500 hover:text-black transition-colors relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 after:bg-black hover:after:w-full after:transition-all after:duration-300">TRACK ORDER</a>
                
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-10 h-10 border border-gray-200 rounded-full flex items-center justify-center hover:bg-black hover:text-white hover:border-black transition-all">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
                @endif
            </div>
            
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-100 py-24 mt-auto">
        <div class="max-w-[90rem] mx-auto px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <div>
                <h3 class="font-black text-2xl uppercase tracking-tighter mb-6">{{$client->shop_name}}</h3>
                <p class="text-gray-500 text-sm leading-relaxed max-w-xs font-medium">Modern minimal design curated for the perfect aesthetic shopping experience.</p>
            </div>
            
            <div>
                <h4 class="font-bold uppercase tracking-widest text-xs mb-6 text-gray-900">Explore</h4>
                <div class="flex flex-col space-y-4 text-sm font-medium text-gray-500">
                    <a href="{{$baseUrl}}" class="hover:text-primary transition-colors inline-block w-fit">Shop All</a>
                    <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="hover:text-primary transition-colors inline-block w-fit">Track My Order</a>
                </div>
            </div>

            <div>
                <h4 class="font-bold uppercase tracking-widest text-xs mb-6 text-gray-900">Policies</h4>
                <div class="flex flex-col space-y-4 text-sm font-medium text-gray-500">
                    <a href="#" class="hover:text-primary transition-colors inline-block w-fit">Shipping Details</a>
                    <a href="#" class="hover:text-primary transition-colors inline-block w-fit">Refund Policy</a>
                    <a href="#" class="hover:text-primary transition-colors inline-block w-fit">Terms of Service</a>
                </div>
            </div>

            <div>
                <h4 class="font-bold uppercase tracking-widest text-xs mb-6 text-gray-900">Contact</h4>
                <div class="flex flex-col space-y-4 text-sm font-medium text-gray-500">
                    @if($client->phone) <p><i class="fas fa-phone mr-2 text-gray-300"></i> {{$client->phone}}</p> @endif
                    @if($client->email) <p><i class="fas fa-envelope mr-2 text-gray-300"></i> {{$client->email}}</p> @endif
                </div>
            </div>
        </div>
        <div class="max-w-[90rem] mx-auto px-6 mt-20 pt-8 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center text-xs font-bold text-gray-400 uppercase tracking-widest">
            <p>&copy; {{date('Y')}} {{$client->shop_name}}.</p>
            <p class="mt-4 md:mt-0">Crafted with Precision.</p>
        </div>
    </footer>

</body>
</html>