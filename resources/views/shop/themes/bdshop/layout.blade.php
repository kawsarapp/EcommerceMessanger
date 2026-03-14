<!DOCTYPE html>
@php 
$clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
$baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <meta name="description" content="{{ $client->meta_description ?? $client->shop_name . ' - অনলাইন শপিং করুন সেরা দামে' }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            theme:{
                extend:{
                    colors:{
                        primary: '{{$client->primary_color ?? "#f85606"}}',
                        secondary: '#fef0eb',
                        accent: '#1a9cb7',
                        dark: '#212121',
                    },
                    fontFamily:{
                        sans:['Inter','system-ui','sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root { --tw-color-primary: {{$client->primary_color ?? "#f85606"}}; }
        [x-cloak]{display:none!important}
        body { background-color: #f5f5f5; }
        .hide-scroll::-webkit-scrollbar{display:none}
        .smooth-transition { transition: all 0.25s ease; }
    </style>
</head>
<body class="text-dark antialiased font-sans min-h-screen flex flex-col">

    {{-- Top Bar --}}
    @if($client->widget('show_announcement_bar') && ($client->announcement_text ?? false))
    <div class="bg-primary text-white text-center py-2 px-4 text-xs font-semibold tracking-wide">
        <i class="fas fa-bolt mr-1"></i> {!! $client->announcement_text !!}
    </div>
    @endif

    {{-- Header --}}
    <header class="bg-primary sticky top-0 z-50 shadow-md">
        <div class="max-w-[1280px] mx-auto px-4">
            {{-- Main Row --}}
            <div class="flex items-center gap-4 h-14 md:h-16">
                {{-- Logo / Shop Name --}}
                <a href="{{$baseUrl}}" class="flex items-center gap-2 shrink-0">
                    @if($client->logo)
                        <img src="{{asset('storage/'.$client->logo)}}" class="h-8 md:h-10 object-contain rounded" alt="{{$client->shop_name}}">
                    @endif
                    <span class="text-white font-extrabold text-lg md:text-xl tracking-tight hidden sm:block">{{$client->shop_name}}</span>
                </a>

                {{-- Search Bar --}}
                @if($client->widget('show_search_bar'))
                <div class="flex-1 max-w-2xl mx-2 md:mx-6">
                    <div class="relative">
                        <input type="text" placeholder="আপনার পণ্য খুঁজুন..." 
                            class="w-full bg-white rounded-lg pl-4 pr-12 py-2.5 text-sm text-dark font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-white/30">
                        <button class="absolute right-0 top-0 h-full px-4 bg-primary/80 hover:bg-primary/60 text-white rounded-r-lg transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" 
                        class="text-white/90 hover:text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 py-1.5 rounded-lg hover:bg-white/10">
                        <i class="fas fa-truck-fast"></i>
                        <span class="hidden md:inline">ট্র্যাক অর্ডার</span>
                    </a>
                    @if($client->phone)
                    <a href="tel:{{$client->phone}}" class="text-white/90 hover:text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition px-2 py-1.5 rounded-lg hover:bg-white/10">
                        <i class="fas fa-phone-alt"></i>
                        <span class="hidden lg:inline">{{$client->phone}}</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    {{-- Category Bar --}}
    @if($client->widget('show_category_filter') && isset($categories) && count($categories) > 0)
    <nav class="bg-white border-b border-slate-200 shadow-sm sticky top-14 md:top-16 z-40">
        <div class="max-w-[1280px] mx-auto px-4">
            <div class="flex gap-1 overflow-x-auto hide-scroll py-2">
                <a href="?category=all" class="px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all {{!request('category')||request('category')=='all' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}}">
                    <i class="fas fa-th-large mr-1"></i> সকল পণ্য
                </a>
                @foreach($categories as $c)
                    <a href="?category={{$c->slug}}" class="px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all {{request('category')==$c->slug ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}}">
                        {{$c->name}}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>
    @endif

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-dark text-white mt-auto">
        <div class="max-w-[1280px] mx-auto px-4 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                {{-- Brand --}}
                <div class="col-span-2 md:col-span-1">
                    <span class="text-xl font-extrabold block mb-4">{{$client->shop_name}}</span>
                    <p class="text-slate-400 text-sm leading-relaxed mb-4">বিশ্বস্ত মানের পণ্য, সেরা দামে। সারাদেশে হোম ডেলিভারি।</p>
                    <div class="flex gap-3">
                        @if($client->facebook_url ?? false)<a href="{{$client->facebook_url}}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition"><i class="fab fa-facebook-f text-sm"></i></a>@endif
                        @if($client->instagram_url ?? false)<a href="{{$client->instagram_url}}" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-primary flex items-center justify-center transition"><i class="fab fa-instagram text-sm"></i></a>@endif
                    </div>
                </div>
                
                {{-- Quick Links --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">দোকান</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-400">
                        <a href="{{$baseUrl}}" class="hover:text-white transition w-fit">সকল পণ্য</a>
                        <a href="{{$baseUrl}}?category=all" class="hover:text-white transition w-fit">নতুন আসা</a>
                    </div>
                </div>

                {{-- Support --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">সাহায্য</h4>
                    <div class="flex flex-col space-y-2.5 text-sm text-slate-400">
                        <a href="{{$clean?$baseUrl.'/track-order':route('shop.track',$client->slug)}}" class="hover:text-white transition w-fit">অর্ডার ট্র্যাক</a>
                        <a href="#" class="hover:text-white transition w-fit">রিটার্ন পলিসি</a>
                        <a href="#" class="hover:text-white transition w-fit">ডেলিভারি তথ্য</a>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-sm mb-4 text-white/80 uppercase tracking-wider">যোগাযোগ</h4>
                    <div class="flex flex-col space-y-3 text-sm text-slate-400">
                        @if($client->phone)<a href="tel:{{$client->phone}}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-phone-alt text-primary"></i> {{$client->phone}}</a>@endif
                        @if($client->email)<a href="mailto:{{$client->email}}" class="hover:text-white transition flex items-center gap-2"><i class="fas fa-envelope text-primary"></i> {{$client->email}}</a>@endif
                        @if($client->address)<span class="flex items-start gap-2"><i class="fas fa-map-marker-alt text-primary mt-1"></i> {{$client->address}}</span>@endif
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 py-5">
            <div class="max-w-[1280px] mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-3">
                <p class="text-slate-500 text-xs">&copy; {{date('Y')}} {{$client->shop_name}}। সর্বস্বত্ব সংরক্ষিত।</p>
                <div class="flex items-center gap-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Visa_Logo.png/120px-Visa_Logo.png" class="h-5 opacity-40">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/120px-MasterCard_Logo.svg.png" class="h-5 opacity-40">
                    <span class="text-[10px] text-slate-500 font-bold border border-slate-600 px-2 py-0.5 rounded">ক্যাশ অন ডেলিভারি</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Floating Chat --}}
    @include('shop.partials.floating-chat', ['client' => $client])

</body>
</html>
