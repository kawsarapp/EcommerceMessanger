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
    
    <!-- Fonts: Oswald for heavy performance headers, Manrope for readable body -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#e11d48"}}', // Default crimson red
                        dark: '#111111'
                    },
                    fontFamily:{
                        display:['Oswald','sans-serif'],
                        sans:['Manrope','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#e11d48"}};
            --mob-primary: {{$client->primary_color ?? "#e11d48"}};
        }
        [x-cloak]{display:none!important} 
        .hide-scroll::-webkit-scrollbar{display:none}
        
        /* Athletic brutally sharp and skewed components */
        .btn-speed { 
            position: relative;
            background: #111111;
            color: white;
            transform: skewX(-8deg);
            transition: all 0.2s;
            overflow: hidden;
            display: inline-block;
        }
        .btn-speed span {
            display: inline-block;
            transform: skewX(8deg); /* Unskew text */
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-weight: 700;
        }
        .btn-speed:hover {
            background: var(--tw-color-primary);
        }
        .btn-speed::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: rgba(255,255,255,0.2);
            transform: skewX(-15deg);
            transition: left 0.4s ease-in-out;
        }
        .btn-speed:hover::before {
            left: 150%;
        }

        /* Diagonal brutal borders */
        .card-brutal {
            border: 3px solid #111111;
            transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
            position: relative;
        }
        .card-brutal::after {
            content:''; position: absolute;
            top: 6px; left: 6px;
            width: 100%; height: 100%;
            background: var(--tw-color-primary);
            z-index: -1;
            transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .card-brutal:hover {
            transform: translate(3px, 3px);
            border-color: var(--tw-color-primary);
        }
        .card-brutal:hover::after {
            top: 0; left: 0;
            background: #111111;
        }

        @media(max-width:767px){
            .shop-name-text{font-size:1.5rem!important;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        }
    </style>
</head>
<body class="bg-white text-dark antialiased flex flex-col min-h-screen selection:bg-primary selection:text-white border-t-4 border-primary">

    {{-- Flash Sale / Global Highlight Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Strict Announcement Strip --}}
    @if($client->announcement_text)
    <div class="bg-primary text-white text-center py-2 text-sm font-display tracking-widest uppercase shadow-sm">
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Brutalist Header --}}
    <header class="bg-white sticky top-0 z-40 border-b-4 border-dark">
        <div class="max-w-[100rem] mx-auto px-4 sm:px-8 h-20 md:h-24 flex justify-between items-center gap-4">
            {{-- Branding --}}
            <a href="{{$baseUrl}}" class="flex items-center gap-4 shrink-0 transition-transform active:scale-95">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client- loading="lazy">logo)}}" class="h-10 md:h-14 object-contain">
                @endif
                <span class="shop-name-text text-3xl md:text-5xl font-display font-bold uppercase tracking-tight text-dark">{{$client->shop_name}}</span>
            </a>

            {{-- Right Nav --}}
            <div class="hidden md:flex gap-8 items-center h-full">
                <a href="{{$baseUrl}}" class="h-full flex items-center font-display text-xl uppercase tracking-wider font-bold hover:text-primary transition-colors border-b-4 border-transparent hover:border-primary">GEAR</a>
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="h-full flex items-center font-display text-xl uppercase tracking-wider font-bold hover:text-primary transition-colors border-b-4 border-transparent hover:border-primary">TRACK</a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="h-full flex items-center font-display text-xl uppercase tracking-wider font-bold hover:text-blue-600 transition-colors border-b-4 border-transparent hover:border-blue-600">SUPPORT</a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-24">
        @yield('content')
    </main>

    <footer class="bg-dark text-white border-t-[10px] border-primary pb-20 pt-16 mt-auto">
        <div class="max-w-[100rem] mx-auto px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <div>
                <h3 class="font-display font-bold text-4xl uppercase tracking-wider mb-4">{{$client->shop_name}}</h3>
                <p class="text-gray-400 text-sm font-sans font-semibold leading-relaxed max-w-sm">BUILT FOR PERFORMANCE. DESIGNED FOR SPEED. GEAR UP AND PUSH YOUR LIMITS TO THE MAXIMUM.</p>
            </div>
            
            <div>
                <h4 class="font-display text-2xl uppercase tracking-widest text-primary mb-4">HEADQUARTERS</h4>
                <div class="flex flex-col space-y-4 text-sm font-sans font-bold text-gray-300">
                    <a href="{{$baseUrl}}" class="hover:text-white transition-colors uppercase w-fit">Browse Gear</a>
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-white transition-colors uppercase w-fit">Locate Package</a>
                </div>
            </div>

            <div>
                <h4 class="font-display text-2xl uppercase tracking-widest text-primary mb-4">POLICIES</h4>
                <div class="flex flex-col space-y-4 text-sm font-sans font-bold text-gray-300">
                    <a href="#" class="hover:text-white transition-colors uppercase w-fit">Delivery Protocol</a>
                    <a href="#" class="hover:text-white transition-colors uppercase w-fit">Refunds</a>
                    <a href="#" class="hover:text-white transition-colors uppercase w-fit">Terms</a>
                </div>
            </div>

            <div>
                <h4 class="font-display text-2xl uppercase tracking-widest text-primary mb-4">COMMUNICATIONS</h4>
                <div class="flex flex-col space-y-4 text-sm font-sans font-bold text-gray-300">
                    @if($client->phone) <p class="uppercase"><i class="fas fa-bolt text-primary mr-2"></i> {{$client->phone}}</p> @endif
                    @if($client->email) <p class="uppercase"><i class="fas fa-paper-plane text-primary mr-2"></i> {{$client->email}}</p> @endif
                </div>
            </div>
        </div>
        <div class="max-w-[100rem] mx-auto px-6 mt-16 flex flex-col md:flex-row justify-between items-center text-xs font-display text-gray-500 uppercase tracking-[0.2em]">
            <p>&copy; {{date('Y')}} {{$client->shop_name}}. POWERED BY VELOCITY.</p>
        </div>
    </footer>

    @include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
