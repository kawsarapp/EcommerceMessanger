@extends('shop.themes.shoppers.layout')
@section('title', $client->shop_name . ' | Cosmetics & Beauty')

@section('content')

@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .sh-sidebar { border: 1px solid #e5e7eb; border-top: none; }
    .sh-sidebar-item { padding: 12px 16px; border-bottom: 1px solid #f3f4f6; font-size: 12px; color: #4b5563; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
    .sh-sidebar-item:hover { color: #eb484e; padding-left: 20px; background-color: #fafa-fa; }
    
    .feature-box { border: 1px solid #f3f4f6; padding: 16px; display: flex; align-items: center; gap: 12px; transition: box-shadow 0.3s; }
    .feature-box:hover { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    
    .section-title-wrap { border: 1px solid #e5e7eb; padding: 12px 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; background: #fff; }
    .section-title { font-size: 14px; color: #4b5563; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .sh-card { border: 1px solid #f3f4f6; padding: 16px; transition: all 0.3s; position: relative; display: flex; flex-col; justify-content: center; height: 100%; box-shadow: 0 1px 2px rgba(0,0,0,0.01); background:#fff; }
    .sh-card:hover { border-color: #eb484e; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
    
    /* Category Tab headers */
    .sh-tab-header { display: flex; border-bottom: 2px solid #fbbf24; margin-bottom: 16px; overflow-x: auto;}
    .sh-tab-active { background: #fbbf24; color: white; padding: 10px 24px; font-weight: 700; font-size: 14px; text-transform: uppercase; position: relative; }
    .sh-tab-active::after { content: ''; position: absolute; top: 0; right: -12px; border-width: 22px 0 0 12px; border-style: solid; border-color: transparent transparent transparent #fbbf24; }
    .sh-tab-item { padding: 10px 24px; font-weight: 600; font-size: 12px; color: #6b7280; text-transform: uppercase; cursor: pointer; transition: color 0.2s; white-space: nowrap; }
    .sh-tab-item:hover { color: #eb484e; }
</style>

<div class="max-w-[1240px] mx-auto px-4 mt-0">
    
    {{-- Top Section: Sidebar + Banners --}}
    @if(!request('category') || request('category') == 'all')
    <div class="flex flex-col lg:flex-row gap-0 lg:gap-6 mb-8 mt-4">
        
        {{-- Left Sidebar Category Menu --}}
        <div class="hidden lg:block w-[256px] shrink-0 sh-sidebar self-start bg-white">
            <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Hot Offers!</span></a>
            <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Top Brands</span> <i class="fas fa-chevron-right text-[8px] text-gray-300"></i></a>
            
            @if(isset($categories))
                @foreach($categories->take(8) as $c)
                <a href="{{$baseUrl}}?category={{$c->slug}}" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> {{$c->name}}</span> <i class="fas fa-chevron-right text-[8px] text-gray-300"></i></a>
                @endforeach
            @else
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Makeup Shop</span> <i class="fas fa-chevron-right text-[8px] text-gray-300"></i></a>
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Health & Beauty Shop</span> <i class="fas fa-chevron-right text-[8px] text-gray-300"></i></a>
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Bath & Body Shop</span> <i class="fas fa-chevron-right text-[8px] text-gray-300"></i></a>
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Hair Care Shop</span> <i class="fas fa-chevron-right text-[8px] text-gray-300"></i></a>
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Kids & Baby Shop</span></a>
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Mens Products</span></a>
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Perfume Shop</span> <i class="fas fa-chevron-right text-[8px] text-gray-300"></i></a>
                <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Grooming Shop</span></a>
            @endif
            
            <a href="#" class="sh-sidebar-item"><span class="flex items-center gap-2"><i class="fas fa-circle text-[4px] text-gray-400"></i> Home & Lifestyle</span></a>
        </div>

        {{-- Right Banners Area --}}
        <div class="flex-1 flex flex-col md:flex-row gap-4">
            {{-- Big Banner --}}
            <div class="flex-1 bg-yellow-50 relative overflow-hidden group cursor-pointer border border-gray-100 flex items-center justify-center p-8 min-h-[300px] md:min-h-[400px]">
                <img src="https://images.unsplash.com/photo-1596462502278-27bf85033e5a?auto=format&fit=crop&w=800&q=80" class="absolute inset-0 w-full h-full object-cover opacity-30 group-hover:scale-105 transition duration-700" loading="lazy">
                <div class="relative z-10 text-center">
                    <h2 class="text-4xl md:text-5xl font-black text-shred mb-2 tracking-tighter drop-shadow-sm">SPECIAL OFFER</h2>
                    <div class="inline-block bg-white px-4 py-2 text-shdark font-bold text-2xl mb-4 border border-shred border-2 shadow-sm">SALE <span class="text-shred font-black text-3xl">UP TO 70% OFF</span></div>
                </div>
            </div>
            
            {{-- Side Small Banners --}}
            <div class="w-full md:w-[30%] flex flex-col gap-4">
                <div class="relative h-32 md:h-[31.5%] overflow-hidden border border-gray-100 bg-pink-50">
                    <img src="https://images.unsplash.com/photo-1512496015851-a1fb8fddfc2a?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover hover:scale-110 transition duration-500" loading="lazy">
                </div>
                <div class="relative h-32 md:h-[31.5%] overflow-hidden border border-gray-100 bg-green-50">
                    <img src="https://images.unsplash.com/photo-1556228578-0d85b1a4d571?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover hover:scale-110 transition duration-500" loading="lazy">
                </div>
                <div class="relative h-32 md:h-[31.5%] overflow-hidden border border-gray-100 bg-blue-50">
                    <img src="https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover hover:scale-110 transition duration-500" loading="lazy">
                </div>
            </div>
        </div>
    </div>
    
    {{-- Feature Boxes --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0 border border-gray-100 mb-12 bg-white">
        <div class="feature-box border-r border-b lg:border-b-0">
            <div class="w-12 h-12 bg-red-100 rounded flex items-center justify-center shrink-0"><i class="fas fa-rocket text-shred text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">FREE SHIPPING!</h4>
                <p class="text-[10px] text-gray-500">On Orders Over 3000 Taka.</p>
            </div>
        </div>
        <div class="feature-box border-r border-b lg:border-b-0">
            <div class="w-12 h-12 bg-green-100 rounded flex items-center justify-center shrink-0"><i class="fas fa-sync-alt text-green-500 text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">EXCHANGE POLICY</h4>
                <p class="text-[10px] text-gray-500">Fast & Hassle Free</p>
            </div>
        </div>
        <div class="feature-box border-r border-b sm:border-b-0">
            <div class="w-12 h-12 bg-purple-100 rounded flex items-center justify-center shrink-0"><i class="fas fa-headset text-purple-500 text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">ONLINE SUPPORT</h4>
                <p class="text-[10px] text-gray-500">24/7 Everyday</p>
            </div>
        </div>
        <div class="feature-box lg:border-none">
            <div class="w-12 h-12 bg-yellow-100 rounded flex items-center justify-center shrink-0"><i class="fas fa-gift text-yellow-500 text-xl"></i></div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-700 leading-tight mb-1">REWARD POINTS</h4>
                <p class="text-[10px] text-gray-500">Earn 1% Cashback</p>
            </div>
        </div>
    </div>

    {{-- Banner Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
        <a href="#" class="block overflow-hidden"><img src="https://images.unsplash.com/photo-1629198688000-71f23e745b6e?auto=format&fit=crop&w=600&h=250&q=80" class="w-full h-full object-cover hover:opacity-90 transition" loading="lazy"></a>
        <a href="#" class="block overflow-hidden"><img src="https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=600&h=250&q=80" class="w-full h-full object-cover hover:opacity-90 transition" loading="lazy"></a>
        <a href="#" class="block overflow-hidden"><img src="https://images.unsplash.com/photo-1599305090598-fe179d501227?auto=format&fit=crop&w=600&h=250&q=80" class="w-full h-full object-cover hover:opacity-90 transition" loading="lazy"></a>
    </div>

    {{-- FEATURED BRAND Placeholder --}}
    <div class="mb-10">
        <div class="section-title-wrap">
            <h3 class="section-title">FEATURED BRAND</h3>
            <div class="flex gap-1">
                <button class="w-6 h-6 border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-400"><i class="fas fa-chevron-left text-[10px]"></i></button>
                <button class="w-6 h-6 border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-400"><i class="fas fa-chevron-right text-[10px]"></i></button>
            </div>
        </div>
        <div class="flex items-center justify-around py-8 border border-gray-100 bg-white shadow-sm overflow-x-auto gap-8 px-4 font-black text-2xl text-gray-300 tracking-widest opacity-80">
            <span>BRAND A</span>
            <span>BEAUTY</span>
            <span><i class="fas fa-leaf text-green-300"></i> NATURALS</span>
            <span>GLAMOUR</span>
            <span>LUXE CO.</span>
            <span>GWP</span>
        </div>
    </div>

    {{-- POPULAR CATEGORIES (Placeholder with FontAwesome) --}}
    <div class="mb-14 border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <h3 class="section-title">POPULAR CATEGORIES</h3>
            <div class="flex gap-1">
                <button class="w-6 h-6 border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-400"><i class="fas fa-chevron-left text-[10px]"></i></button>
                <button class="w-6 h-6 border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-400"><i class="fas fa-chevron-right text-[10px]"></i></button>
            </div>
        </div>
        
        <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-y-10 gap-x-4">
            {{-- Icons mimicking popular layout --}}
            @php $cats = [
                ['icon'=>'fa-store', 'color'=>'text-red-400', 'name'=>'Makeup Shop'],
                ['icon'=>'fa-leaf', 'color'=>'text-green-500', 'name'=>'Health & Beauty'],
                ['icon'=>'fa-spray-can', 'color'=>'text-purple-400', 'name'=>'Perfume Shop'],
                ['icon'=>'fa-glasses', 'color'=>'text-blue-500', 'name'=>'Fashion Accesories'],
                ['icon'=>'fa-eye', 'color'=>'text-gray-600', 'name'=>'Eye Makeup'],
                ['icon'=>'fa-kiss-wink-heart', 'color'=>'text-pink-500', 'name'=>'Lip Makeup'],
                ['icon'=>'fa-pump-medical', 'color'=>'text-teal-400', 'name'=>'Makeup Remover'],
                ['icon'=>'fa-shower', 'color'=>'text-blue-400', 'name'=>'Shampoo'],
            ]; @endphp
            @foreach($cats as $c)
                <div class="flex flex-col items-center cursor-pointer group">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3 group-hover:shadow-md transition">
                        <i class="fas {{$c['icon']}} {{$c['color']}} text-3xl"></i>
                    </div>
                    <span class="text-[10px] text-gray-600 text-center font-medium group-hover:text-shred">{{$c['name']}}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- DYNAMIC GRID SECTION (Combos) --}}
    <div class="mb-14">
        <div class="section-title-wrap">
            <h3 class="section-title">{{$client->shop_name}} FATAFATI COMBOS / DEALS</h3>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-0 border-l border-t border-gray-100 bg-white">
            @forelse($products->take(4) as $p)
                <div class="sh-card border-r border-b group">
                    @if($p->sale_price)<span class="absolute top-4 left-4 bg-shred text-white text-[9px] font-bold px-1.5 py-0.5 z-10">-{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}%</span>@endif
                    @if($loop->iteration % 2 == 0)<span class="absolute top-4 right-4 bg-[#00bfa5] text-white text-[9px] font-bold px-1.5 py-0.5 z-10">New</span>@endif
                    
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block flex items-center justify-center h-48 mb-6 p-4">
                        <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain group-hover:-translate-y-1 transition duration-300">
                    </a>
                    
                    <div class="text-left px-2">
                        <a href="{{$baseUrl.'/product/'.$p->slug}}">
                            <h4 class="text-xs font-semibold text-gray-600 line-clamp-2 h-8 mb-2 group-hover:text-shred transition">{{$p->name}}</h4>
                        </a>
                        <div class="flex text-[#fbbf24] text-[8px] mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                        
                        <div class="flex items-end gap-2 mb-3">
                            <span class="font-bold text-shred text-sm">TK {{number_format($p->sale_price ?? $p->regular_price, 2)}}</span>
                            @if($p->sale_price)<del class="text-[10px] text-gray-400 font-medium">TK {{number_format($p->regular_price, 2)}}</del>@endif
                        </div>
                        
                        {{-- Static Fake Timer --}}
                        <div class="bg-shred text-white text-[10px] font-bold text-center py-1 mt-auto w-fit px-3 rounded-sm shadow-sm inline-block tracking-wider">
                            1005 days : {{rand(0,23)}} : {{rand(10,59)}} : {{rand(10,59)}}
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center text-gray-400">No products available at the moment.</div>
            @endforelse
        </div>
    </div>

    {{-- Main Products Tabbed Grid --}}
    <div class="mb-16">
        <div class="sh-tab-header">
            @if(request('category') && request('category') != 'all')
                <div class="sh-tab-active">{{ $categories->where('slug', request('category'))->first()?->name ?? 'Category Products' }}</div>
            @else
                <div class="sh-tab-active">MAKEUP SHOP</div>
                <div class="sh-tab-item">TRENDING NOW</div>
                <div class="sh-tab-item">EYE MAKEUP</div>
                <div class="sh-tab-item">FACE MAKEUP</div>
                <div class="sh-tab-item">LIP MAKEUP</div>
                <div class="sh-tab-item">TOOLS & ACCESSORIES</div>
            @endif
        </div>

        <div class="flex flex-col lg:flex-row border-l border-t border-gray-100 bg-white">
            
            {{-- Big Vertical Promo Banner Left (mimicking screenshot) --}}
            <div class="hidden lg:block w-[280px] shrink-0 border-r border-b border-gray-100 relative group overflow-hidden">
                <img src="https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover" loading="lazy">
                <div class="absolute bottom-10 left-0 w-full text-center px-4">
                    <span class="text-white text-xs font-bold block mb-1">EARN EXTRA CASHBACK</span>
                    <span class="text-white text-4xl font-black block leading-none">UP TO</span>
                    <span class="text-white text-6xl font-black block leading-none drop-shadow-lg mb-4">50% <span class="text-2xl">OFF</span></span>
                    <button class="bg-shred text-white px-6 py-2 text-xs font-bold w-fit mx-auto border border-shred hover:bg-white hover:text-shred transition">SHOP NOW</button>
                </div>
            </div>

            {{-- Product Grid Right --}}
            <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 border-b border-gray-100">
                @forelse($products as $p)
                    <div class="sh-card border-r border-t lg:border-t-0 group {{ $loop->iteration > 4 ? 'border-t' : '' }}">
                        @if($p->sale_price)<span class="absolute top-4 left-4 bg-shred text-white text-[9px] font-bold px-1.5 py-0.5 z-10">-{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}%</span>@endif
                        
                        <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block flex items-center justify-center h-48 mb-6 p-4">
                            <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain group-hover:-translate-y-1 transition duration-300">
                        </a>
                        
                        <div class="text-left px-2 mt-auto">
                            <a href="{{$baseUrl.'/product/'.$p->slug}}">
                                <h4 class="text-[11px] font-medium text-gray-700 line-clamp-2 h-8 mb-1.5 group-hover:text-shred transition leading-snug">{{$p->name}}</h4>
                            </a>
                            <div class="flex text-[#fbbf24] text-[8px] mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                            
                            <div class="flex flex-col mb-1 pb-2">
                                <span class="font-bold text-shred text-xs">TK {{number_format($p->sale_price ?? $p->regular_price, 2)}}</span>
                                @if($p->sale_price)<del class="text-[9px] text-gray-400 font-medium">TK {{number_format($p->regular_price, 2)}}</del>@endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center text-gray-500 border border-dashed border-gray-200 m-4">No products found.</div>
                @endforelse
            </div>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="mt-8">
            <style>
                .pg nav { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; }
                .pg nav a, .pg nav span { min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; background: white; color: #64748b; border: 1px solid #e2e8f0; transition: all 0.2s; }
                .pg nav a:hover { border-color: var(--shred); color: var(--shred); }
                .pg nav span[aria-current="page"] { background: var(--shred); color: white !important; border-color: var(--shred); }
            </style>
            <div class="pg">{{ $products->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </div>

</div>

@endsection
