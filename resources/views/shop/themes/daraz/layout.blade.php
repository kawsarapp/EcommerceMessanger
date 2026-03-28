<!DOCTYPE html>
@php
    $clean = preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/'));
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show',$client->slug);
@endphp
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    @include('shop.partials.tracking', ['client' => $client])
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Daraz typically uses clean system fonts or Roboto/Open Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{$client->primary_color ?? "#F85606"}}',
                        dark: '#0f172a',
                    },
                    fontFamily: {
                        sans: ['"Roboto"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#F85606"}};
            --mob-primary: {{$client->primary_color ?? "#F85606"}};
        }
        body { background-color: #F5F5F5; }
        [x-cloak] { display: none !important; }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hero-gradient { background: linear-gradient(90deg, var(--tw-color-primary) 0%, #ff8a00 100%); }
        .btn-primary { background: var(--tw-color-primary); transition: background 0.3s; }
        .btn-primary:hover { background: #d04000; }
        @media(max-width:767px){
            .shop-name-text{font-size:1rem!important;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        }
    </style>
</head>
<body class="text-gray-900 antialiased flex flex-col min-h-screen selection:bg-primary selection:text-white">

    {{-- Top Announcement Bar --}}
    @if($client->announcement_text)
    <div class="bg-gray-100/80 text-gray-700 text-center py-1 text-xs font-semibold">
        {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Main Header --}}
    <header class="bg-white sticky top-0 z-40 border-b border-gray-200/60 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 md:h-20 flex justify-between items-center gap-4">
            {{-- Logo --}}
            <a href="{{$baseUrl}}" class="flex items-center gap-2 shrink-0">
                @if($client->logo)
                    <img src="{{asset('storage/'.$client->logo)}}" class="h-8 md:h-12 object-contain">
                @endif
                <span class="shop-name-text text-xl md:text-2xl font-black text-primary uppercase">{{$client->shop_name}}</span>
            </a>

            {{-- Search Bar --}}
            <form action="{{$baseUrl}}" method="GET" class="hidden md:flex flex-1 max-w-2xl mx-8 relative">
                <input type="text" name="search" value="{{request('search')}}" placeholder="পণ্য খুঁজুন..." class="w-full bg-gray-100 border-none rounded-lg px-6 py-3 text-sm focus:ring-2 focus:ring-primary/50 transition outline-none">
                <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primary text-white w-8 h-8 rounded-md flex items-center justify-center hover:bg-[#d04000] transition">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            {{-- Right Actions --}}
            <div class="hidden md:flex items-center gap-6 shrink-0">
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="flex flex-col items-center text-gray-600 hover:text-primary transition group">
                    <i class="fas fa-truck-fast text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">অর্ডার ট্র্যাক</span>
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="flex flex-col items-center text-gray-600 hover:text-blue-600 transition group">
                    <i class="fab fa-facebook-messenger text-xl mb-1 group-hover:scale-110 transition"></i>
                    <span class="text-[10px] font-bold uppercase">মেসেজ</span>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 py-16 mt-auto">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="md:col-span-2">
                <h3 class="font-black text-2xl text-primary mb-4 uppercase">{{$client->shop_name}}</h3>
                <p class="text-gray-500 text-sm leading-relaxed max-w-md">১০০% আসল পণ্য। সারা দেশে ডেলিভারি। ২৪/৭ কাস্টমার সাপোর্ট। সহজ রিটার্ন ও রিফান্ড।</p>
                
                <div class="flex gap-4 mt-6">
                    @if($client->fb_page_id)
                        <a href="https://facebook.com/{{$client->fb_page_id}}" class="w-10 h-10 rounded-full bg-gray-100 flex flex-col items-center justify-center text-gray-600 hover:bg-blue-600 hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                    @endif
                </div>
            </div>
            
            <div>
                <h4 class="font-bold text-gray-900 mb-6 uppercase tracking-wider text-sm">{{ $footerMenu1->name ?? 'কাস্টমার কেয়ার' }}</h4>
                <div class="flex flex-col space-y-3 text-sm text-gray-500 font-medium">
                    @if(isset($footerMenu1) && $footerMenu1->items->count() > 0)
                        @foreach($footerMenu1->items as $item)
                            <a href="{{ $item->resolved_url }}" target="{{ $item->target }}" class="hover:text-primary transition">{{ $item->label }}</a>
                        @endforeach
                    @else
                        <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition">অর্ডার ট্র্যাক করুন</a>
                        <a href="#" class="hover:text-primary transition">শিপিং পলিসি</a>
                        <a href="#" class="hover:text-primary transition">রিটার্ন পলিসি</a>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-bold text-gray-900 mb-6 uppercase tracking-wider text-sm">যোগাযোগ</h4>
                <div class="flex flex-col space-y-3 text-sm text-gray-500 font-medium">
                    @if($client->phone) <p><i class="fas fa-phone mr-2 text-primary"></i> {{$client->phone}}</p> @endif
                    @if($client->email) <p><i class="fas fa-envelope mr-2 text-primary"></i> {{$client->email}}</p> @endif
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 mt-12 pt-8 border-t border-gray-100 text-center text-xs font-bold text-gray-400">
            <p>&copy; {{date('Y')}} {{$client->shop_name}}. All rights reserved.</p>
        </div>
    </footer>

    @include('shop.themes.daraz.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.themes.daraz.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>
