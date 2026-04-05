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
    
    <!-- Fonts: Nunito for friendly grocery look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
    <meta property="og:url" content="{{ url()->current() }}">
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
            darkMode: 'class',
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#10b981"}}',
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
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="fas fa-carrot text-primary"></i>{{ $client->widgets['footer']['menu1_title'] ?? 'Categories' }}</h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    <a href="?category=all" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Fresh Produce</a>
                                        @if(isset($pages) && count($pages) > 0)
                        @foreach($pages as $page)
                            <a href="{{ $clean ? $baseUrl.'/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary transition-colors inline-block w-fit">{{ $page->title }}</a>
                        @endforeach
                    @else
                        <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="hover:text-primary transition-colors inline-block w-fit">Track Order</a>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="fas fa-heart text-red-400"></i>{{ $client->widgets['footer']['menu2_title'] ?? 'Customer Care' }}</h4>
                <div class="flex flex-col space-y-4 font-bold text-sm text-slate-500">
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition hover:translate-x-1 w-fit transform duration-200">Track Your Order</a>
                                        @if(isset($pages) && count($pages) > 0)
                        @foreach($pages as $page)
                            <a href="{{ $clean ? $baseUrl.'/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary transition-colors inline-block w-fit">{{ $page->title }}</a>
                        @endforeach
                    @else
                        <a href="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" class="hover:text-primary transition-colors inline-block w-fit">Track Order</a>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-slate-800 text-lg mb-6 flex items-center gap-2"><i class="fas fa-headset text-blue-500"></i>{{ $client->widgets['footer']['menu3_title'] ?? 'Contact Us' }}</h4>
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
            <p class="text-sm font-bold text-slate-400">{{ $client->footer_text ?? '&copy; '.date('Y').' '.$client->shop_name.'. All Rights Reserved.' }} <i class="fas fa-heart text-red-500 mx-1"></i></p>
        </div>

    {{-- Dynamic Social + Payment + Copyright from admin panel --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pb-6">
        @include('shop.partials.dynamic-footer-extras', ['client' => $client, 'baseUrl' => $baseUrl ?? '', 'clean' => $clean ?? ''])
    </div>
    </footer>

        @include('shop.partials.compare-bar', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
@include('shop.partials.floating-chat', ['client' => $client])
    @include('shop.partials.popup-banner', ['client' => $client])
    @include('shop.partials.mobile-nav', ['client' => $client, 'baseUrl' => $baseUrl, 'clean' => $clean])
</body>
</html>



