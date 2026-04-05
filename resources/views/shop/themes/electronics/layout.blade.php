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
    
    <!-- Fonts: Inter for UI, Roboto Mono for numbers/specs -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script>
        tailwind.config={
            darkMode: 'class',
            theme:{
                extend:{
                    colors:{
                        primary:'{{$client->primary_color ?? "#0ea5e9"}}',
                        secondary: '{{$client->secondary_color ?? $client->primary_color ?? "#facc15"}}', // Default Sky Blue / Tech Blue
                        dark: '#030712',
                        panel: '#111827'
                    },
                    fontFamily:{
                        sans:['Inter','sans-serif'],
                        mono:['Roboto Mono','monospace']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --tw-color-primary: {{$client->primary_color ?? "#0ea5e9"}};
            --mob-primary: {{$client->primary_color ?? "#0ea5e9"}};
        }
        [x-cloak]{display:none!important} 
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #030712; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--tw-color-primary); }
        .hide-scroll::-webkit-scrollbar{display:none}
        .tech-border { border: 1px solid rgba(255,255,255,0.08); }
        .tech-glow:hover { box-shadow: 0 0 20px -5px var(--tw-color-primary); border-color: var(--tw-color-primary); }
        .tech-gradient { background: radial-gradient(circle at top right, rgba(14, 165, 233, 0.1), transparent 50%); }
        
        /* Cyberpunk Additions */
        .neon-border { border: 1px solid var(--tw-color-primary); box-shadow: inset 0 0 10px rgba(14, 165, 233, 0.2), 0 0 10px rgba(14, 165, 233, 0.2); }
        .neon-text { text-shadow: 0 0 8px var(--tw-color-primary); }
        .cyber-grid { background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px); background-size: 20px 20px; }
        .hud-panel { background: rgba(17, 24, 39, 0.9); border: 1px solid rgba(14, 165, 233, 0.3); position: relative; }
        .hud-panel::before { content: ''; position: absolute; top: -1px; left: -1px; width: 10px; height: 10px; border-top: 2px solid var(--tw-color-primary); border-left: 2px solid var(--tw-color-primary); }
        .hud-panel::after { content: ''; position: absolute; bottom: -1px; right: -1px; width: 10px; height: 10px; border-bottom: 2px solid var(--tw-color-primary); border-right: 2px solid var(--tw-color-primary); }

        @media(max-width:767px){
            .mob-nav{--mob-primary:{{$client->primary_color ?? "#0ea5e9"}};background:#111827!important;border-top-color:rgba(255,255,255,0.08)!important}
            .mob-nav a{color:#9ca3af!important}
            .mob-nav a:hover,.mob-nav a.active{color:{{$client->primary_color ?? "#0ea5e9"}}!important; text-shadow: 0 0 8px var(--tw-color-primary);}
            .mob-search-bar{background:#111827!important;border-bottom-color:rgba(255,255,255,0.08)!important}
                @endif
                <span class="text-base md:text-2xl font-black tracking-tight text-white truncate max-w-[160px] md:max-w-none">{{$client->shop_name}}</span>
                <span class="bg-primary text-white text-[9px] font-bold px-1.5 py-0.5 rounded-sm uppercase tracking-wider ml-1 hidden sm:inline-block flex-shrink-0">Tech</span>
            </a>
            <!-- Desktop Search -->
            <div class="hidden lg:flex w-full max-w-xl mx-8 relative">
                <input type="text" placeholder="Search devices, accessories, models..." class="w-full bg-dark tech-border text-sm text-white px-4 py-2.5 rounded-md focus:ring-1 focus:ring-primary focus:border-primary transition placeholder-gray-600">
                <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
            </div>
            <div class="hidden md:flex items-center gap-4">
                <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="text-xs font-semibold text-gray-400 hover:text-white transition flex items-center gap-2 bg-dark tech-border px-3 py-1.5 rounded-md hover:border-gray-500">
                    <i class="fas fa-crosshairs text-primary"></i> Track Status
                </a>
                @if($client->fb_page_id)
                <a href="https://m.me/{{$client->fb_page_id}}" target="_blank" class="w-8 h-8 rounded-md bg-dark tech-border flex items-center justify-center text-gray-400 hover:text-white hover:border-primary transition">
                    <i class="fab fa-facebook-messenger"></i>
                </a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full pb-20 tech-gradient">
        @yield('content')
    </main>

    <footer class="bg-panel border-t border-gray-800 pt-16 pb-8 mt-auto">
        <div class="max-w-[100rem] mx-auto px-4 md:px-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <div>
                <h3 class="font-black text-2xl text-white tracking-tight mb-4 flex items-center gap-2">
                    <i class="fas fa-microchip text-primary"></i> {{$client->shop_name}}
                </h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-4 font-medium">{{ $client->description ?? ($client->tagline ?? 'Your ultimate hub for next-generation tech, gadgets, and components.') }}</p>
                @if($client->email)<a href="mailto:{{$client->email}}" class="text-gray-400 hover:text-primary transition text-xs flex items-center gap-2 mb-4"><i class="fas fa-envelope"></i> {{$client->email}}</a>@endif
                <div class="flex gap-3">
                    @if($client->facebook_url ?? false)<a href="{{$client->facebook_url}}" target="_blank" class="text-gray-500 hover:text-primary transition"><i class="fab fa-facebook-f text-sm"></i></a>@endif
                    @if($client->instagram_url ?? false)<a href="{{$client->instagram_url}}" target="_blank" class="text-gray-500 hover:text-primary transition"><i class="fab fa-instagram text-sm"></i></a>@endif
                    @if($client->youtube_url ?? false)<a href="{{$client->youtube_url}}" target="_blank" class="text-gray-500 hover:text-primary transition"><i class="fab fa-youtube text-sm"></i></a>@endif
                </div>
            </div>
            
            <div>
                <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-xs">{{ $client->widgets['footer']['menu1_title'] ?? 'Categories' }}</h4>
                <div class="flex flex-col space-y-3 text-sm font-medium text-gray-400">
                    @if(isset($categories) && $categories->count() > 0)
                        @foreach($categories->take(5) as $cat)
                        <a href="{{$baseUrl}}?category={{$cat->slug}}" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> {{$cat->name}}</a>
                        @endforeach
                    @else
                        <a href="{{$baseUrl}}?category=all" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> All Products</a>
                        <a href="{{$baseUrl}}?category=new" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> New Arrivals</a>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-xs">{{ $client->widgets['footer']['menu2_title'] ?? 'Support' }}</h4>
                <div class="flex flex-col space-y-3 text-sm font-medium text-gray-400">
                    <a href="{{$clean?$baseUrl.'/track':route('shop.track',$client->slug)}}" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> Live Tracking</a>
                    <a href="#" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> Return Policy</a>
                    <a href="#" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-angle-right text-[10px] text-gray-600"></i> Technical Support</a>
                </div>
            </div>

            <div>
                <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-xs">{{ $client->widgets['footer']['menu3_title'] ?? 'Stay Connected' }}</h4>
                <div class="flex flex-col space-y-4 text-sm font-medium text-gray-400">
                    @if($client->phone) 
                        <div class="flex items-center gap-3 bg-dark tech-border p-3 rounded-lg text-white font-mono text-xs">
                            <i class="fas fa-headset text-primary text-base"></i> {{$client->phone}}
                        </div>
                    @endif
                    <p class="text-xs text-gray-600">System architecture online. Systems operating at optimal parameters.</p>
                </div>
            </div>
        </div>
        
        <div class="max-w-[100rem] mx-auto px-4 md:px-8 mt-16 flex flex-col md:flex-row justify-between items-center border-t border-gray-800 pt-6">
            <p class="text-xs font-medium text-gray-600 font-mono tracking-wider">{{ $client->footer_text ?? '&copy; '.date('Y').' '.$client->shop_name }}</p>
            <div class="flex gap-4 mt-4 md:mt-0 text-[10px] font-bold uppercase tracking-widest text-gray-600">
                <span>Secure SSL</span> <span class="opacity-50">|</span> <span>Fast Shipping</span>
            </div>
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



