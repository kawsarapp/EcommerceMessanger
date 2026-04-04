@extends('shop.themes.shoppers.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    /* Shoppers Product specific CSS */
    .sh-breadcrumb { font-size: 11px; color: #6b7280; font-weight: 500; padding: 12px 16px; border-bottom: 1px solid #f3f4f6; margin-bottom: 24px; }
    .sh-breadcrumb a { color: #4b5563; transition: color 0.2s; }
    .sh-breadcrumb a:hover { color: #eb484e; }
    
    .sh-price { color: #ef4444; font-size: 24px; font-weight: 700; }
    .sh-qty-btn { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: 1px solid #d1d5db; background: #fff; cursor: pointer; color: #4b5563; font-weight: bold; transition: background 0.2s; }
    .sh-qty-btn:hover { background: #f3f4f6; }
    .sh-qty-input { width: 40px; height: 32px; text-align: center; border-top: 1px solid #d1d5db; border-bottom: 1px solid #d1d5db; border-left: none; border-right: none; font-weight: bold; color: #333; outline: none; }
    
    .sh-btn-red { background: #eb484e; color: #fff; border: 1px solid #eb484e; font-weight: 700; text-transform: uppercase; font-size: 12px; transition: background 0.2s; display: inline-flex; align-items: center; justify-content: center; }
    .sh-btn-red:hover { background: #d63d42; }
    .sh-btn-dark { background: #24263f; color: #fff; border: 1px solid #24263f; display: inline-flex; align-items: center; justify-content: center; transition: background 0.2s; }
    .sh-btn-dark:hover { background: #1a1b2d; }
    
    .sh-tab-container { border: 1px solid #e5e7eb; margin-top: 40px; }
    .sh-tab-header { display: flex; border-bottom: 1px solid #e5e7eb; background: #f9fafb; flex-wrap: wrap; }
    .sh-tab-btn { padding: 14px 24px; font-size: 13px; font-weight: 600; text-transform: uppercase; color: #4b5563; border-right: 1px solid #e5e7eb; transition: all 0.2s; }
    .sh-tab-btn:hover { color: #eb484e; }
    .sh-tab-active { background: #eb484e; color: #fff !important; }
    
    .sh-share-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; transition: opacity 0.2s; cursor: pointer; }
    .sh-share-icon:hover { opacity: 0.8; }
    
    .text-justify { text-align: justify; }
</style>

<div class="max-w-[1240px] mx-auto bg-white" x-data="{ 
    mainImg: '{{asset('storage/'.$product->thumbnail)}}', 
    qty: 1, 
    color: '', 
    size: '',
    tab: 'description',
    hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
    variants: {{ $product->has_variants ? $product->variants->toJson() : '[]' }},
    basePrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    currentPrice: {{ $product->sale_price ?? $product->regular_price ?? 0 }},
    updatePrice() {
        if(this.hasVariants) {
            let matched = this.variants.find(v => 
                (v.color === this.color || (!v.color && !this.color)) && 
                (v.size === this.size || (!v.size && !this.size))
            );
            if(matched && matched.price) {
                this.currentPrice = parseInt(matched.price);
            } else {
                this.currentPrice = this.basePrice;
            }
        }
    }
}" x-init="$watch('color', () => updatePrice()); $watch('size', () => updatePrice());">
    
    {{-- Breadcrumb --}}
    <div class="sh-breadcrumb flex items-center gap-2">
        <a href="{{$baseUrl}}">Home</a>
        <i class="fas fa-angle-double-right text-[8px] text-gray-400 mt-[1px]"></i>
        <a href="{{$baseUrl}}?category={{$product->category->slug ?? 'all'}}">{{$product->category->name ?? 'Category'}}</a>
        <i class="fas fa-angle-double-right text-[8px] text-gray-400 mt-[1px]"></i>
        <span class="text-gray-400 font-normal truncate max-w-sm">{{$product->name}}</span>
    </div>

    <div class="px-4">
        {{-- Top Layout: Product Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 section-main">
            
            {{-- Product Main Image --}}
            <div class="md:col-span-5 lg:col-span-4 flex flex-col items-center">
                <div class="w-full aspect-[4/5] bg-white border border-gray-100 p-2 mb-2 flex items-center justify-center">
                    <img :src="mainImg" class="max-w-full max-h-full object-contain" loading="lazy">
                </div>
                <div class="bg-gray-500 text-white text-[11px] font-bold py-1.5 px-6 rounded-full flex items-center gap-2 opacity-80 mb-4 cursor-zoom-in hover:opacity-100">
                    <i class="fas fa-search-plus"></i> Hover to zoom
                </div>

                {{-- Image Thumbnails (if any) --}}
                @if($product->gallery && count($product->gallery) > 0)
                <div class="w-full flex gap-2 overflow-x-auto hide-scroll justify-center">
                    <div @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                         :class="{ 'border-shred': mainImg === '{{asset('storage/'.$product->thumbnail)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$product->thumbnail)}}' }"
                         class="w-16 h-16 bg-white border cursor-pointer p-1">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-contain">
                    </div>
                    @foreach($product->gallery as $img)
                    <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                         :class="{ 'border-shred': mainImg === '{{asset('storage/'.$img)}}', 'border-gray-200': mainImg !== '{{asset('storage/'.$img)}}' }"
                         class="w-16 h-16 bg-white border cursor-pointer p-1">
                        <img src="{{asset('storage/'.$img)}}" class="w-full h-full object-contain" loading="lazy">
                    </div>
                    @endforeach
                </div>
                @endif

                @if($product->video_url)
                <a href="{{$product->video_url}}" target="_blank" class="w-full mt-4 bg-red-50 hover:bg-red-100 text-shred font-bold text-xs py-2.5 rounded border border-red-200 flex items-center justify-center transition">
                    <i class="fab fa-youtube text-lg mr-2"></i> WATCH PRODUCT VIDEO
                </a>
                @endif
            </div>

            {{-- Product Info (Middle) --}}
            <div class="md:col-span-7 lg:col-span-5 flex flex-col">
                @if($product->brand)
                <div class="text-[10px] font-bold text-gray-400 tracking-wider uppercase mb-1 flex items-center gap-1.5">
                    <i class="fas fa-tag"></i> {{$product->brand}}
                </div>
                @endif
                <h1 class="text-[22px] text-gray-800 font-medium leading-tight mb-3">{{$product->name}}</h1>
                
                {{-- Review Stars --}}
                <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-4">
                    <div class="flex text-gray-300 text-xs">
                        <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                    </div>
                    <a href="#" @click.prevent="tab='reviews'" class="text-[11px] text-gray-500 hover:text-shred font-medium">Be the first to review this product</a>
                </div>

                {{-- Price Block --}}
                <div class="flex items-end gap-3 mb-2">
                    <span class="sh-price" x-text="'TK ' + new Intl.NumberFormat('en-IN').format(currentPrice)">TK {{number_format($product->sale_price ?? $product->regular_price, 2)}}</span>
                    @if($product->sale_price)
                        <del class="text-gray-500 font-medium text-sm mb-1">TK {{number_format($product->regular_price, 2)}}</del>
                    @endif
                    <span class="bg-shred text-white text-[10px] font-bold px-2 py-0.5 ml-auto self-center uppercase">Live Stock</span>
                </div>

                {{-- Stock & SKU --}}
                <div class="text-xs mb-1">
                    <span class="font-medium text-gray-500">Stock:</span> 
                    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                        <span class="text-red-500 font-bold ml-1">Out of stock</span>
                    @else
                        <span class="text-shred font-medium ml-1">More than 10 available</span>
                    @endif
                </div>
                <div class="text-xs text-gray-500 mb-6">
                    <span class="font-medium">SKU</span> <span class="ml-1 text-gray-800 font-bold">{{$product->id}}{{$product->client_id*137}}</span>
                </div>

                {{-- Action Form --}}
                <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="GET" class="border-t border-b border-gray-100 py-6 mb-6">
                    
                    {{-- Attributes --}}
                    @if($product->colors)
                    <div class="mb-4">
                        <span class="text-gray-800 text-xs font-bold block mb-2 uppercase">Color</span>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($product->colors as $c)
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="{{$c}}" x-model="color" class="peer hidden">
                                <span class="px-3 py-1.5 border border-gray-300 peer-checked:border-shred peer-checked:bg-red-50 peer-checked:text-shred text-xs text-gray-600 bg-white block transition">{{$c}}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($product->sizes)
                    <div class="mb-4">
                        <span class="text-gray-800 text-xs font-bold block mb-2 uppercase">Size</span>
                        <div class="flex gap-2 flex-wrap">
                            @foreach($product->sizes as $s)
                            <label class="cursor-pointer">
                                <input type="radio" name="size" value="{{$s}}" x-model="size" class="peer hidden">
                                <span class="min-w-[40px] text-center px-2 py-1.5 border border-gray-300 peer-checked:border-shred peer-checked:bg-red-50 peer-checked:text-shred text-xs text-gray-600 bg-white block transition">{{$s}}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <div class="flex items-center inline-flex">
                            <button type="button" @click="if(qty>1)qty--" class="sh-qty-btn"><i class="fas fa-minus text-[10px]"></i></button>
                            <input type="number" name="qty" x-model="qty" class="sh-qty-input" readonly>
                            
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])
<button type="button" @click="qty++" class="sh-qty-btn"><i class="fas fa-plus text-[10px]"></i></button>
                        </div>

                        @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
                            <button type="button" disabled class="sh-btn-red h-8 px-6 opacity-50 cursor-not-allowed">OUT OF STOCK</button>
                        @else
                            @if($client->show_order_button ?? true)
                                <button type="submit" class="sh-btn-red h-8 px-8 hover:bg-red-700">ADD TO CART</button>
                            @endif
                            @if($client->show_chat_button ?? true)
                                @include('shop.partials.chat-button', ['client' => $client, 'product' => $product])
                            @endif
                        @endif
                    </div>
                </form>

                {{-- Details Short --}}
                <div class="text-[11px] text-gray-500 leading-relaxed font-medium mb-6">
                    <strong class="text-gray-700 font-bold block mb-1">Ingredients:</strong>
                    Aqua, Parfum, Sodium Hydroxide, Cocamidopropyl Betaine, Benzyl Alcohol, Geraniol, Hexyl Cinnamal, 
                    Carbomer, Guar Hydroxypropyltrimonium Chloride, Sodium Benzoate, Glycol Distearate, Citric Acid.
                </div>

                {{-- Share --}}
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-gray-800">Share It</span>
                    <div class="flex gap-1.5">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="sh-share-icon bg-[#3b5998]"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode(`$product->name) }}" target="_blank" class="sh-share-icon bg-[#1da1f2]"><i class="fab fa-twitter"></i></a>
                        <a href="https://pinterest.com/pin/create/button/?url={{ urlencode(url()->current()) }}&media={{ urlencode(asset(`"storage/`".`$product->thumbnail)) }}&description={{ urlencode(`$product->name) }}" target="_blank" class="sh-share-icon bg-[#bd081c]"><i class="fab fa-pinterest-p"></i></a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}" target="_blank" class="sh-share-icon bg-[#0077b5]"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

            </div>

            {{-- Right Promo side banners (matching screenshot) --}}
            <div class="hidden lg:flex flex-col gap-4 lg:col-span-3">
                <a href="#" class="block hover:opacity-90 transition border border-gray-100 shadow-sm relative overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1522337660859-02fbefca4702?auto=format&fit=crop&w=300&h=200&q=80" class="w-full h-auto object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                    <div class="absolute top-0 right-0 bg-black text-white px-3 py-2 text-right">
                        <span class="text-[8px] uppercase tracking-widest block leading-tight">Discount<br>UP TO</span>
                        <span class="text-xl font-bold font-serif italic">20%</span>
                    </div>
                </a>
                <a href="#" class="block hover:opacity-90 transition border border-gray-100 shadow-sm relative overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1526947425960-945c6e72858f?auto=format&fit=crop&w=300&h=200&q=80" class="w-full h-auto object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                    <div class="absolute top-0 right-0 bg-white border border-gray-200 text-dark px-3 py-2 text-right">
                        <span class="text-[8px] uppercase tracking-widest block leading-tight text-gray-500">GET UP TO</span>
                        <span class="text-xl font-bold font-serif italic">26%</span>
                    </div>
                </a>
            </div>

        </div>

        {{-- Product Details Tabs --}}
        <div class="sh-tab-container mb-12">
            <div class="sh-tab-header">
                <button @click="tab = 'description'" :class="{'sh-tab-active': tab === 'description'}" class="sh-tab-btn">DESCRIPTION</button>
                <button @click="tab = 'information'" :class="{'sh-tab-active': tab === 'information'}" class="sh-tab-btn">INFORMATION</button>
                <button @click="tab = 'reviews'" :class="{'sh-tab-active': tab === 'reviews'}" class="sh-tab-btn">REVIEWS</button>
                <button @click="tab = 'bengali'" :class="{'sh-tab-active': tab === 'bengali'}" class="sh-tab-btn">BENGALI</button>
                <button @click="tab = 'disclaimer'" :class="{'sh-tab-active': tab === 'disclaimer'}" class="sh-tab-btn">DISCLAIMER</button>
            </div>
            
            <div class="p-6 md:p-8 text-sm text-gray-500 leading-relaxed bg-white">
                
                <div x-show="tab === 'description'" class="animate-fade-in space-y-6">
                    <div class="text-justify font-medium">
                        {!! clean($product->description ?? $product->long_description) !!}
                    </div>

                    {{-- Fake structure resembling the screenshot Features --}}
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 mb-4 pb-2 border-b border-gray-100">Features</h3>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 list-disc pl-5">
                            <li>Delivers up to 72 hours of frizz control</li>
                            <li>Formulated with Keratin and Marula Oil</li>
                            <li>Five smoothing benefits into one system</li>
                            <li>You can enjoy 5 benefits in 1 system</li>
                            <li>Specifically formulated to leave your hair gorgeously sleek and manageable</li>
                        </ul>
                    </div>
                </div>
                
                <div x-show="tab === 'information'" class="animate-fade-in hidden">
                    @if($product->material || ($client->show_return_warranty ?? true))
                        <div class="border border-gray-100 p-6 rounded bg-gray-50 max-w-2xl">
                            <h3 class="font-bold text-lg text-gray-800 mb-4 pb-2 border-b border-gray-200">Additional Information</h3>
                            <div class="space-y-4 text-sm">
                                @if($product->material)
                                <div class="grid grid-cols-3">
                                    <div class="text-gray-500 font-medium">Material</div>
                                    <div class="col-span-2 text-gray-800 font-bold">{{$product->material}}</div>
                                </div>
                                <div class="border-b border-gray-200 w-full"></div>
                                @endif

                                @if($client->show_return_warranty ?? true)
                                <div class="grid grid-cols-3">
                                    <div class="text-gray-500 font-medium">Warranty</div>
                                    <div class="col-span-2 text-gray-800 font-bold">{{$product->warranty ?? 'N/A'}}</div>
                                </div>
                                <div class="border-b border-gray-200 w-full"></div>
                                <div class="grid grid-cols-3">
                                    <div class="text-gray-500 font-medium">Return Policy</div>
                                    <div class="col-span-2 text-gray-800 font-bold">{{$product->return_policy ?? '7 Days Easy Return'}}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-center p-12 text-gray-400">
                            No additional information provided.
                        </div>
                    @endif
                </div>
                
                <div x-show="tab === 'reviews'" class="animate-fade-in hidden">
                    @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
                </div>

                <div x-show="tab === 'bengali'" class="animate-fade-in hidden">
                    <p class="text-justify font-medium text-gray-600">
                        এই পণ্যটির সম্পর্কে বিস্তারিত বাংলা বিবরণ এখনো যোগ করা হয়নি।
                    </p>
                </div>
                
                <div x-show="tab === 'disclaimer'" class="animate-fade-in hidden">
                    <h3 class="font-bold text-gray-800 mb-2">Platform Disclaimer</h3>
                    <p class="text-justify text-xs leading-relaxed text-gray-500">
                        We strive to ensure that product information is correct, but on occasion manufacturers may alter their ingredient lists. Actual product packaging and materials may contain more and/or different information than that shown on our Web site. We recommend that you do not solely rely on the information presented and that you always read labels, warnings, and directions before using or consuming a product.
                    </p>
                </div>
                
            </div>
        </div>

        {{-- RELATED PRODUCTS --}}
        @if($client->show_related_products ?? true)
        <div class="mb-12">
            <div class="flex justify-between items-center mb-0 bg-white border border-gray-200 border-b-0 px-4 py-3">
                <h3 class="text-xs text-gray-600 font-medium">RELATED PRODUCTS</h3>
                <div class="flex gap-1">
                    <button class="w-6 h-6 border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-400"><i class="fas fa-chevron-left text-[10px]"></i></button>
                    <button class="w-6 h-6 border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-400"><i class="fas fa-chevron-right text-[10px]"></i></button>
                </div>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 border border-gray-200 border-r-0 border-b-0">
                @php $related = App\Models\Product::where('client_id', $client->id)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->inRandomOrder()->limit(5)->get(); @endphp
                @foreach($related as $p)
                <div class="border-r border-b border-gray-200 p-4 relative group bg-white">
                    @if($p->sale_price)<span class="absolute top-4 left-4 bg-shred text-white text-[9px] font-bold px-1.5 py-0.5 z-10">-{{ round((($p->regular_price - $p->sale_price) / $p->regular_price) * 100) }}%</span>@endif
                    
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block flex items-center justify-center h-40 mb-4">
                        <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain group-hover:-translate-y-1 transition duration-300">
                    </a>
                    
                    <a href="{{$baseUrl.'/product/'.$p->slug}}">
                        <h4 class="text-[11px] font-medium text-gray-700 line-clamp-2 h-8 mb-2 group-hover:text-shred transition leading-snug">{{$p->name}}</h4>
                    </a>
                    
                    <div class="flex items-end gap-1 mb-1">
                        <span class="font-bold text-shred text-xs">TK {{number_format($p->sale_price ?? $p->regular_price, 2)}}</span>
                        @if($p->sale_price)<del class="text-[9px] text-gray-400 font-medium line-through">TK {{number_format($p->regular_price, 2)}}</del>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
