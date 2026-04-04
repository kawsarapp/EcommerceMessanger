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
                        primary:'{{$client->primary_color ?? "#e11d48"}}',
                        secondary: '{{$client->secondary_color ?? $client->primary_color ?? "#facc15"}}',
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
            --color-primary: {{$client->primary_color ?? "#e11d48"}};
        }
        [x-cloak]{display:none!important} 
        .hide-scroll::-webkit-scrollbar{display:none}
        
        /* Dynamic primary shadow utilities */
        .shadow-primary-xs  { box-shadow: 2px 2px 0px var(--color-primary); }
        .shadow-primary-sm  { box-shadow: 4px 4px 0px var(--color-primary); }
        .shadow-primary-md  { box-shadow: 6px 6px 0px var(--color-primary); }
        .shadow-primary-lg  { box-shadow: 8px 8px 0px var(--color-primary); }
        .shadow-primary-xl  { box-shadow: 12px 12px 0px var(--color-primary); }
        .shadow-dark-sm     { box-shadow: 4px 4px 0px #111111; }
        .shadow-dark-md     { box-shadow: 6px 6px 0px #111111; }
        .shadow-dark-lg     { box-shadow: 8px 8px 0px #111111; }
        .shadow-dark-xl     { box-shadow: 12px 12px 0px #111111; }

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
            transform: skewX(8deg);
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-weight: 700;
        }
        .btn-speed:hover {
            background: var(--color-primary);
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

        /* Diagonal brutal card borders */
        .card-brutal {
            border: 3px solid #111111;
            transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
            position: relative;
        }
        .card-brutal::after {
            content:''; position: absolute;
            top: 6px; left: 6px;
            width: 100%; height: 100%;
            background: var(--color-primary);
            z-index: -1;
            transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .card-brutal:hover {
            transform: translate(3px, 3px);
            border-color: var(--color-primary);
        }
        .card-brutal:hover::after {
            top: 0; left: 0;
            background: #111111;
        }
        
        /* Primary border color */
        .border-primary-dynamic { border-color: var(--color-primary); }
        .text-primary-dynamic   { color: var(--color-primary); }
        .bg-primary-dynamic     { background-color: var(--color-primary); }

        @media(max-width:767px){
            .shop-name-text{font-size:1.5rem!important;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        }
    </style>
</head>
<body class="bg-white text-dark antialiased flex flex-col min-h-screen selection:bg-primary selection:text-white border-t-4 border-primary" style="{\{ $client->bg_color ? 'background-color: '.$client->bg_color.' !important;' : '' \}}">

    {{-- Flash Sale / Global Highlight Banner --}}
    @include('shop.partials.flash-sale-bar', ['client' => $client])

    {{-- Dynamic Announcement Strip --}}
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
                    <img src="{{asset('storage/'.$client->logo)}}" class="h-10 md:h-14 object-contain" alt="{{$client->shop_name}}">
                @endif
                <span class="shop-name-text text-3xl md:text-5xl font-display font-bold uppercase tracking-tight text-dark">{{$client->shop_name}}</span>
            </a>

            {{-- Right Nav --}}
            <div class="hidden md:flex gap-8 items-center h-full">
                <a href="{{$baseUrl}}" class="h-full flex items-center font-display text-xl uppercase tracking-wider font-bold hover:text-primary transition-colors border-b-4 border-transparent hover:border-primary">SHOP</a>
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="h-full flex items-center font-display text-xl uppercase tracking-wider font-bold hover:text-primary transition-colors border-b-4 border-transparent hover:border-primary">TRACK ORDER</a>
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
                <p class="text-gray-400 text-sm font-sans font-semibold leading-relaxed max-w-sm">
                    {{$client->description ?? ($client->tagline ?? 'আমাদের দোকানে স্বাগতম। সেরা পণ্য, সেরা দামে।')}}
                </p>
            </div>
            
            <div>
                <h4 class="font-display text-2xl uppercase tracking-widest text-primary mb-4">QUICK LINKS</h4>
                <div class="flex flex-col space-y-4 text-sm font-sans font-bold text-gray-300">
                    <a href="{{$baseUrl}}" class="hover:text-white transition-colors uppercase w-fit">সব পণ্য দেখুন</a>
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-white transition-colors uppercase w-fit">অর্ডার ট্র্যাক করুন</a>
                </div>
            </div>

            <div>
                <h4 class="font-display text-2xl uppercase tracking-widest text-primary mb-4">POLICIES</h4>
                <div class="flex flex-col space-y-4 text-sm font-sans font-bold text-gray-300">
                    @if(isset($pages) && count($pages) > 0)
                        @foreach($pages as $page)
                            <a href="{{ $clean ? $baseUrl.'/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-white transition-colors uppercase w-fit">{{ $page->title }}</a>
                        @endforeach
                    @else
                        <span class="opacity-50 italic">No policies added yet.</span>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-display text-2xl uppercase tracking-widest text-primary mb-4">CONTACT US</h4>
                <div class="flex flex-col space-y-4 text-sm font-sans font-bold text-gray-300">
                    @if($client->phone) 
                    <a href="tel:{{$client->phone}}" class="uppercase hover:text-white transition-colors">
                        <i class="fas fa-phone text-primary mr-2"></i> {{$client->phone}}
                    </a> 
                    @endif
                    @if($client->email) 
                    <a href="mailto:{{$client->email}}" class="uppercase hover:text-white transition-colors">
                        <i class="fas fa-envelope text-primary mr-2"></i> {{$client->email}}
                    </a> 
                    @endif
                    @if($client->address ?? false)
                    <p class="uppercase"><i class="fas fa-map-marker-alt text-primary mr-2"></i> {{$client->address}}</p>
                    @endif
                    @if($client->fb_page_id)
                    <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="uppercase hover:text-white transition-colors">
                        <i class="fab fa-facebook-messenger text-primary mr-2"></i> Messenger-এ কথা বলুন
                    </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="max-w-[100rem] mx-auto px-6 mt-16 flex flex-col md:flex-row justify-between items-center text-xs font-display text-gray-500 uppercase tracking-[0.2em]">
            <p>&copy; {{date('Y')}} {{$client->shop_name}}. All Rights Reserved.</p>
        </div>
    </footer>

        @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
@include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>



