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
    
    <!-- Fonts: Playfair Display for High Fashion Vogue Look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#111111"}}',
                        nude: '#FAECEB'
                    },
                    fontFamily:{
                        heading:['Playfair Display','serif'],
                        sans:['Jost','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#111111"}};
            --mob-primary: {{$client->primary_color ?? "#111111"}};
        }
        [x-cloak]{display:none!important} 
        .hide-scroll::-webkit-scrollbar{display:none}
        .fashion-border { border: 1px solid rgba(0,0,0,0.05); }
        @media(max-width:767px){
            .shop-name-text{font-size:1.4rem!important;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        }
    </style>
</head>
 $args[0].Groups[1].Value + "`n    {{-- ⚡ Flash Sale Banner --}}`n    @include('shop.partials.flash-sale-bar', ['client' => `$client])`n`n    " + $args[0].Groups[3].Value  class="bg-white sticky top-0 z-50 transition-all border-b border-gray-100">
        <div class="max-w-[100rem] mx-auto px-4 sm:px-8 h-16 md:h-24 flex justify-between items-center">
            <!-- Left: Track (desktop only) -->
            <div class="w-1/3 hidden md:flex items-center">
                <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400 hover:text-black transition">Track</a>
            </div>
            <!-- Center: Logo -->
            <div class="flex-1 md:w-1/3 flex justify-start md:justify-center items-center">
                <a href="{{$baseUrl}}" class="flex items-center gap-3">
                    @if($client->logo)
                        <img src="{{asset('storage/'.$client->logo)}}" class="h-9 md:h-12 object-contain">
                    @else
                        <span class="shop-name-text text-2xl md:text-4xl font-heading font-black tracking-tight text-primary">{{$client->shop_name}}</span>
                    @endif
                </a>
            </div>
            <!-- Right: Messenger -->
            <div class="w-auto md:w-1/3 flex justify-end items-center gap-4">
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="text-gray-400 hover:text-black transition">
                    <i class="fab fa-facebook-messenger text-lg md:text-xl"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-100 pt-24 pb-12 mt-auto">
        <div class="max-w-[100rem] mx-auto px-4 sm:px-8 flex flex-col items-center">
            <h3 class="font-heading font-black text-4xl mb-4 text-center">{{$client->shop_name}}</h3>
            <p class="text-gray-400 text-sm font-medium leading-relaxed max-w-lg text-center mx-auto mb-12">Redefining elegance. Your ultimate destination for exclusive fashion.</p>
            
            <div class="flex gap-10 text-xs font-semibold tracking-widest uppercase text-gray-400 mb-16">
                <a href="{{$baseUrl}}" class="hover:text-black transition">Shop</a>
                <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="hover:text-black transition">Track Order</a>
                <a href="#" class="hover:text-black transition">Terms</a>
            </div>

            <p class="text-[10px] font-medium text-gray-300 uppercase tracking-widest text-center">&copy; {{date('Y')}} {{$client->shop_name}}. Exclusive Collection.</p>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>