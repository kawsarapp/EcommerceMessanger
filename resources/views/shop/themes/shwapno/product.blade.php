@extends('shop.themes.shwapno.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .sw-breadcrumb { font-size: 11px; color: #4b5563; padding: 16px 0; font-weight: 500; display: flex; gap: 8px; align-items: center; }
    .sw-breadcrumb a { color: #1f2937; transition: color 0.1s; }
    .sw-breadcrumb a:hover { color: #e31e24; text-decoration: underline; }
    
    .sw-btn-pill { border-radius: 9999px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer; }
    .sw-btn-red { background-color: #e31e24; color: #fff; border: 1px solid #e31e24; }
    .sw-btn-red:hover { background-color: #c8161c; border-color: #c8161c; }
    
    .sw-qty-input { width: 44px; height: 36px; text-align: center; border: 1px solid #d1d5db; border-radius: 4px; font-weight: bold; color: #333; outline: none; margin: 0 10px; }
    .sw-qty-btn { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; background: #f3f4f6; color: #4b5563; font-weight: bold; border-radius: 4px; transition: background 0.2s; cursor: pointer; }
    .sw-qty-btn:hover { background: #e5e7eb; }
    
    .sw-info-box { border: 1px solid #e5e7eb; padding: 20px; border-radius: 4px; background: white; margin-bottom: 24px; }
    
    /* Related layout from index */
    .sw-card { background: white; border: 1px solid #f3f4f6; transition: all 0.2s; position: relative; display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; height: 100%; border-radius: 2px; }
    .sw-card:hover { border-color: #fee2e2; box-shadow: 0 4px 12px rgba(227,30,36,0.06); transform: translateY(-2px); }
    .sw-section-title { font-size: 18px; font-weight: 800; color: #333; text-transform: uppercase; text-align: center; margin-bottom: 24px; letter-spacing: 0.5px; }
</style>

<div class="max-w-[1340px] mx-auto px-4 lg:px-6" x-data="{ 
    mainImg: '{{asset('storage/'.$product->thumbnail)}}', 
    qty: 1, 
    color: '', 
    size: '' 
}">
    
    {{-- Breadcrumb --}}
    <div class="sw-breadcrumb">
        <a href="{{$baseUrl}}">Home</a>
        <i class="fas fa-chevron-right text-[8px] text-gray-400"></i>
        <span>...</span>
        <i class="fas fa-chevron-right text-[8px] text-gray-400"></i>
        <span class="font-bold text-gray-800">{{$product->name}}</span>
    </div>

    {{-- Main Product Layout: 3 Columns Desktop (Image, Info, Meta) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10 pb-16 border-b border-gray-200 bg-white p-6 rounded shadow-sm">
        
        {{-- Product Image (Left) --}}
        <div class="lg:col-span-5 flex flex-col items-center relative">
            @if($product->sale_price)<span class="absolute top-0 left-4 bg-green-600 text-white text-[11px] font-bold px-2 py-0.5 z-10 rounded-sm shadow-sm opacity-90">New</span>@endif
            
            <div class="w-full aspect-square border border-gray-100 p-4 mb-4 flex items-center justify-center relative bg-white">
                <img :src="mainImg" class="max-w-full max-h-full object-contain" loading="lazy">
                <div class="absolute bottom-4 right-4 text-gray-400 opacity-60"><i class="fas fa-search"></i></div>
            </div>

            {{-- Thumbnails --}}
            @if($product->gallery && count($product->gallery) > 0)
            <div class="flex gap-3 justify-center w-full">
                <div @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                     :class="{ 'border-swred': mainImg === '{{asset('storage/'.$product->thumbnail)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$product->thumbnail)}}' }"
                     class="w-16 h-16 bg-white border cursor-pointer p-1.5 rounded-sm">
                    <img src="{{asset('storage/'.$product- loading="lazy">thumbnail)}}" class="w-full h-full object-contain">
                </div>
                @foreach($product->gallery as $img)
                <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                     :class="{ 'border-swred': mainImg === '{{asset('storage/'.$img)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$img)}}' }"
                     class="w-16 h-16 bg-white border cursor-pointer p-1.5 rounded-sm">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-contain" loading="lazy">
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Product Center Info --}}
        <div class="lg:col-span-4 flex flex-col mt-2">
            <h1 class="text-[17px] text-gray-800 font-bold leading-snug mb-3">{{$product->name}}</h1>
            
            <div class="flex items-center gap-2 mb-6">
                <span class="text-3xl font-black text-swred">৳{{number_format($product->sale_price ?? $product->regular_price, 0)}}</span>
                @if($product->sale_price)
                    <del class="text-[15px] text-gray-400 font-medium ml-1">৳{{number_format($product->regular_price, 0)}}</del>
                @endif
                <span class="text-[11px] text-gray-500 font-medium ml-1 bg-gray-100 py-1 px-2 rounded-full">Per Piece</span>
            </div>

            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="border-t border-gray-100 pt-6 mt-2">
                
                {{-- Attributes --}}
                @if($product->colors)
                <div class="mb-5 bg-gray-50 p-3 rounded">
                    <span class="text-gray-800 text-xs font-bold block mb-2 uppercase">Select Color:</span>
                    <div class="flex gap-2 flex-wrap">
                        @foreach($product->colors as $c)
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden" required>
                            <span class="px-3 py-1.5 border peer-checked:border-swred peer-checked:bg-white peer-checked:text-swred peer-checked:font-bold text-xs text-gray-600 bg-white block transition rounded-sm shadow-sm">{{$c}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($product->sizes)
                <div class="mb-5 bg-gray-50 p-3 rounded">
                    <span class="text-gray-800 text-xs font-bold block mb-2 uppercase">Select Variant:</span>
                    <div class="flex gap-2 flex-wrap">
                        @foreach($product->sizes as $s)
                        <label class="cursor-pointer">
                            <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden" required>
                            <span class="px-3 py-1.5 border peer-checked:border-swred peer-checked:bg-white peer-checked:text-swred peer-checked:font-bold text-xs text-gray-600 bg-white block transition rounded-sm shadow-sm">{{$s}}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex items-center gap-1 mb-6">
                    <div class="flex items-center">
                        <button type="button" @click="if(qty>1)qty--" class="sw-qty-btn"><i class="fas fa-minus text-xs"></i></button>
                        <input type="number" name="qty" x-model="qty" class="sw-qty-input" readonly>
                        <button type="button" @click="qty++" class="sw-qty-btn"><i class="fas fa-plus text-xs"></i></button>
                    </div>
                </div>

                @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                    <button type="button" disabled class="w-[80%] max-w-[200px] sw-btn-pill py-3 text-sm bg-gray-300 text-gray-500 cursor-not-allowed">OUT OF STOCK</button>
                @else
                    <button type="submit" class="w-[80%] max-w-[200px] sw-btn-pill sw-btn-red py-3 text-sm hover:shadow-lg">
                        <i class="fas fa-plus mr-2 text-white/80"></i> Add to Bag
                    </button>
                @endif
            </form>

            {{-- Description Details --}}
            <div class="mt-8 pt-6 border-t border-gray-100">
                <h3 class="text-[13px] font-bold text-gray-800 mb-2">Product Information</h3>
                <div class="text-[11px] text-gray-500 leading-relaxed text-justify">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
            </div>
        </div>

        {{-- Right Information Panel --}}
        <div class="lg:col-span-3">
            <div class="sw-info-box">
                <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                    <span class="text-[11px] font-bold text-gray-700">SKU: <span class="font-normal">{{$product->id}}{{$product->client_id*137}}</span></span>
                    
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <span class="text-[11px] font-bold text-red-500 flex items-center gap-1"><i class="fas fa-times-circle"></i> Out of stock</span>
                    @else
                        <span class="text-[11px] font-bold text-green-600 flex items-center gap-1"><i class="fas fa-check-circle"></i> In-stock</span>
                    @endif
                </div>

                <p class="text-[11px] text-gray-500 mb-5 leading-relaxed">
                    {{ Str::limit(strip_tags($product->description ?? $product->long_description), 100) }}
                </p>

                <div class="flex justify-between items-center mb-6">
                    <a href="#" class="text-[11px] text-gray-500 hover:text-swred flex items-center gap-1.5 transition"><i class="far fa-heart"></i> Add to Wishlist</a>
                    <div class="flex gap-2">
                        <a href="#" class="text-blue-600 hover:opacity-80 transition"><i class="fab fa-facebook bg-blue-50 p-1.5 rounded-full"></i></a>
                        <a href="#" class="text-pink-500 hover:opacity-80 transition"><i class="fab fa-instagram bg-pink-50 p-1.5 rounded-full"></i></a>
                        <a href="#" class="text-green-500 hover:opacity-80 transition"><i class="fab fa-whatsapp bg-green-50 p-1.5 rounded-full"></i></a>
                    </div>
                </div>

                <div class="text-[11px] text-gray-600 mb-2 font-medium flex gap-2">
                    <i class="fas fa-truck text-gray-400 mt-1"></i> <span><strong class="text-gray-800">Delivery:</strong> 1-2 hours</span>
                </div>
                <div class="text-[11px] text-gray-600 mb-5 font-medium flex gap-2">
                    <i class="fas fa-map-marker-alt text-gray-400 mt-1"></i> <span><strong class="text-gray-800">Location:</strong> <a href="#" class="text-blue-500 hover:underline font-normal">Select your delivery location <i class="fas fa-chevron-down text-[8px] ml-0.5"></i></a></span>
                </div>

                {{-- Payment Methods Graphic Box --}}
                <div class="rounded border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-3 py-2 text-[10px] font-bold text-gray-700 flex justify-between items-center border-b border-gray-200">
                        Other Payment Methods
                        <div class="flex gap-1">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/1024px-MasterCard_Logo.svg.png" class="h-3 object-contain opacity-50" loading="lazy">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/16/Former_Visa_%28company%29_logo.svg/1024px-Former_Visa_%28company%29_logo.svg.png" class="h-3 object-contain opacity-50" loading="lazy">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-gray-200 bg-white">
                        <div class="p-3 flex flex-col items-center justify-center gap-2 border-b border-gray-200">
                            <span class="text-[9px] font-bold text-gray-500">bKash</span>
                            <i class="fas fa-paper-plane text-[#e2136e] text-lg"></i>
                        </div>
                        <div class="p-3 flex flex-col items-center justify-center gap-2 border-b border-gray-200">
                            <span class="text-[9px] font-bold text-gray-500 text-center leading-tight">Cash on<br>Delivery</span>
                            <i class="far fa-money-bill-alt text-green-600 text-lg"></i>
                        </div>
                        <div class="p-3 flex flex-col items-center justify-center gap-2 col-span-2">
                            <span class="text-[9px] font-bold text-gray-500 flex items-center justify-between w-full">Card Payment <i class="far fa-credit-card text-gray-400 text-sm"></i></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Reviews Section (Shwapno styling) --}}
    <div class="mt-8 mb-12">
        <div class="bg-swyellow text-swdark font-bold text-xs px-6 py-2 rounded-full inline-block shadow-sm mb-4">Reviews</div>
        
        <div class="border border-gray-200 rounded-sm bg-white p-8 ext-center flex flex-col items-center justify-center">
            @if(false)
                <!-- Usually dynamic logic goes here -->
            @else
                <h3 class="text-sm font-bold text-gray-800 mb-6">No reviews yet, Be the first one to review !</h3>
                <div class="w-full max-w-2xl bg-gray-50 p-6 rounded border border-gray-100">
                    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
                </div>
            @endif
        </div>
    </div>

    {{-- SIMILAR PRODUCTS SECTION --}}
    <div class="mb-16">
        <h3 class="sw-section-title">SIMILAR PRODUCTS</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 lg:gap-4">
            @php $similar = App\Models\Product::where('category_id', $product->category_id)->where('id', '!=', $product->id)->limit(6)->get(); @endphp
            @forelse($similar as $p)
                <div class="sw-card group/card">
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block flex items-center justify-center h-32 mb-2 mt-4">
                        <img src="{{asset('storage/'.$p- loading="lazy">thumbnail)}}" class="max-w-full max-h-full object-contain group-hover/card:scale-105 transition duration-300">
                    </a>
                    <div class="text-center mt-auto flex flex-col items-center">
                        <span class="text-[9px] italic text-gray-400 mb-1">Delivery 1-2 hours</span>
                        <a href="{{$baseUrl.'/product/'.$p->slug}}" class="w-full">
                            <h4 class="text-[11px] font-bold text-gray-800 line-clamp-2 h-8 leading-snug mb-1 hover:text-swred transition">{{$p->name}}</h4>
                        </a>
                        <div class="flex items-center justify-center gap-1.5 mb-2 h-6">
                            @if($p->sale_price)<del class="text-[9px] text-gray-400 font-medium">৳{{number_format($p->regular_price, 0)}}</del>@endif
                            <span class="font-bold text-swred text-xs">৳{{number_format($p->sale_price ?? $p->regular_price, 0)}}</span>
                            <span class="text-[9px] text-gray-500 ml-0.5">Per Piece</span>
                        </div>
                        <form action="{{$baseUrl.'/checkout/'.$p->slug}}" method="GET" class="w-full mt-2">
                            <button class="w-full sw-btn-pill sw-btn-red py-1.5 text-[10px] w-full hover:shadow-md">
                                <i class="fas fa-plus mr-1 text-[8px]"></i> Add to Bag
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-gray-400 text-sm">No similar items found in this category.</div>
            @endforelse
        </div>
    </div>
    
    {{-- RELATED PRODUCTS SECTION --}}
    <div class="mb-16">
        <h3 class="sw-section-title">RELATED PRODUCTS</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 lg:gap-4">
            @php $related = App\Models\Product::where('id', '!=', $product->id)->inRandomOrder()->limit(5)->get(); @endphp
            @foreach($related as $p)
                <div class="sw-card group/card">
                    @if($p->sale_price)<span class="absolute top-0 left-0 bg-swred text-white text-[10px] font-bold px-1.5 py-1 z-10 flex flex-col items-center leading-none rounded-br-sm shadow-sm"><span class="text-[8px]">৳{{ $p->regular_price - $p->sale_price }}</span><span>OFF</span></span>@endif
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block flex items-center justify-center h-40 mb-2 mt-4">
                        <img src="{{asset('storage/'.$p- loading="lazy">thumbnail)}}" class="max-w-full max-h-full object-contain group-hover/card:scale-105 transition duration-300">
                    </a>
                    <div class="text-center mt-auto flex flex-col items-center">
                        <span class="text-[10px] italic text-gray-400 mb-1">Delivery 1-2 hours</span>
                        <a href="{{$baseUrl.'/product/'.$p->slug}}" class="w-full">
                            <h4 class="text-xs font-bold text-gray-800 line-clamp-2 h-8 leading-snug mb-2 hover:text-swred transition">{{$p->name}}</h4>
                        </a>
                        <div class="flex items-center justify-center gap-1.5 mb-2 h-6">
                            @if($p->sale_price)<del class="text-[11px] text-gray-400 font-medium">৳{{number_format($p->regular_price, 0)}}</del>@endif
                            <span class="font-bold text-swred text-sm">৳{{number_format($p->sale_price ?? $p->regular_price, 0)}}</span>
                            <span class="text-[10px] text-gray-500 ml-1 font-medium">Per Piece</span>
                        </div>
                        <form action="{{$baseUrl.'/checkout/'.$p->slug}}" method="GET" class="w-full mt-2">
                            <button class="w-full sw-btn-pill sw-btn-red py-2 text-xs w-[85%] mx-auto hover:shadow-md">
                                <i class="fas fa-plus mr-1"></i> Add to Bag
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
