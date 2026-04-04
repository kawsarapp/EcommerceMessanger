@extends('shop.themes.bdpro.layout')
@section('title', $product->name . ' | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<div class="bg-white py-4" x-data="productApp()">
<script>
function productApp() {
    return {
        mainImg: '{{asset('storage/'.$product->thumbnail)}}', 
        qty: 1, 
        color: '', 
        size: '',
        tab: 'description',
        hasVariants: {{ $product->has_variants ? 'true' : 'false' }},
        variants: {!! json_encode($product->has_variants ? $product->variants : [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        
        get currentVariant() {
            if (!this.hasVariants) return null;
            let c = this.color ? this.color.trim() : null;
            let s = this.size ? this.size.trim() : null;
            
            return this.variants.find(v => {
                let matchesColor = c ? (v.color && v.color.trim() === c) : true;
                let matchesSize = s ? (v.size && v.size.trim() === s) : true;
                return matchesColor && matchesSize;
            });
        },
        
        get displayPrice() {
            if (this.currentVariant && this.currentVariant.price > 0) {
                return parseFloat(this.currentVariant.price).toLocaleString();
            }
            return '{{ number_format($product->sale_price ?? $product->regular_price) }}';
        },

        get stockStatus() {
            if (this.hasVariants) {
                if (!this.color && !this.size) return '{{ $product->stock_status }}';
                if (this.currentVariant) {
                    return this.currentVariant.stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
                }
                return 'out_of_stock';
            }
            return '{{ $product->stock_status }}';
        },

        get availableStock() {
            if (this.hasVariants) {
                return this.currentVariant ? this.currentVariant.stock_quantity : 0;
            }
            return {{ $product->stock_quantity ?? 0 }};
        }
    };
}
</script>
    @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])

    <div class="max-w-[1400px] mx-auto px-4">
        
        {{-- Breadcrumb --}}
        <nav class="flex items-center text-[11px] text-gray-500 mb-6 bg-gray-50 py-2.5 px-4 rounded-sm border border-gray-100/50 w-fit">
            <a href="{{$baseUrl}}" class="hover:text-bdblue transition"><i class="fas fa-home"></i></a>
            <i class="fas fa-chevron-right text-[8px] mx-3 text-gray-300"></i>
            <a href="{{$baseUrl}}?category=all" class="hover:text-bdblue transition">Products</a>
            <i class="fas fa-chevron-right text-[8px] mx-3 text-gray-300"></i>
            <a href="{{$baseUrl}}?category={{$product->category->slug ?? 'all'}}" class="hover:text-bdblue transition">{{$product->category->name ?? 'General'}}</a>
            <i class="fas fa-chevron-right text-[8px] mx-3 text-gray-300"></i>
            <span class="text-bdblue font-medium">{{$product->name}}</span>
        </nav>

        {{-- Product Details Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-12 mb-10">
            
            {{-- Left Side: Image Gallery --}}
            <div class="lg:col-span-5 flex flex-col gap-4">
                <div class="aspect-square bg-gray-50 border border-gray-100 rounded-lg p-4 flex justify-center items-center relative">
                    <img :src="mainImg" class="max-w-full max-h-full object-contain cursor-zoom-in hover:scale-105 transition-transform duration-300" loading="lazy">
                </div>
                
                {{-- Thumbnails --}}
                <div class="flex gap-3 overflow-x-auto hide-scroll pb-2">
                    <div @click="mainImg = '{{asset('storage/'.$product->thumbnail)}}'" 
                         :class="{ 'border-bdblue shadow-sm ring-1 ring-bdblue': mainImg === '{{asset('storage/'.$product->thumbnail)}}', 'border-gray-200 hover:border-bdblue/50': mainImg !== '{{asset('storage/'.$product->thumbnail)}}' }"
                         class="w-20 h-20 bg-white border rounded cursor-pointer transition p-2 flex items-center justify-center shrink-0">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain">
                    </div>
                    
                    @foreach($product->gallery ?? [] as $img)
                    <div @click="mainImg = '{{asset('storage/'.$img)}}'" 
                         :class="{ 'border-bdblue shadow-sm ring-1 ring-bdblue': mainImg === '{{asset('storage/'.$img)}}', 'border-gray-200 hover:border-bdblue/50': mainImg !== '{{asset('storage/'.$img)}}' }"
                         class="w-20 h-20 bg-white border rounded cursor-pointer transition p-2 flex items-center justify-center shrink-0">
                        <img src="{{asset('storage/'.$img)}}" class="max-w-full max-h-full object-contain" loading="lazy">
                    </div>
                    @endforeach
                </div>
                
                @if($product->video_url)
                <a href="{{$product->video_url}}" target="_blank" class="mt-4 flex items-center justify-center gap-2 bg-blue-50 hover:bg-blue-100 text-bdblue border border-blue-200 font-bold py-2.5 rounded-lg transition text-xs shadow-sm w-full">
                    <i class="fab fa-youtube text-red-500 text-base"></i> Watch Product Video
                </a>
                @endif
            </div>

            {{-- Right Side: Info & Add to Cart --}}
            <div class="lg:col-span-7 flex flex-col">
                {{-- Meta Info --}}
                <div class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                    <span class="text-bdblue">{{$product->category->name ?? 'Category'}}</span> 
                    @if($product->brand)
                    <span class="text-gray-300">by</span> 
                    <span class="text-bdblue bg-blue-50 px-2 py-0.5 rounded border border-blue-100">{{$product->brand}}</span>
                    @endif
                </div>

                {{-- Title --}}
                <h1 class="text-2xl md:text-3xl font-extrabold text-dark tracking-tight leading-tight mb-4">{{$product->name}}</h1>

                {{-- ID and Review --}}
                <div class="flex flex-wrap items-center gap-6 mb-6 pb-6 border-b border-gray-100">
                    <div class="text-xs text-gray-500 bg-gray-50 px-3 py-1.5 rounded flex items-center gap-2 border border-gray-100">
                        <span class="font-bold text-gray-700">Product ID:</span> {{$product->id}}{{$product->client_id*87}} <i class="fas fa-copy text-gray-400 cursor-pointer hover:text-bdblue" onclick="navigator.clipboard.writeText('{{$product->id}}{{$product->client_id*87}}'); alert('Product ID Copied!')"></i>
                    </div>
                    
                    <div class="flex items-center gap-1.5 text-xs">
                        <div class="flex text-gray-300">
                            <i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </div>
                        <span class="text-gray-400">No reviews yet</span>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="mb-5">
                    <div class="flex items-baseline gap-4">
                        <span class="text-[40px] font-black text-[#1a85ff] leading-none tracking-tighter">?<span x-text="displayPrice"></span></span>
                        @if($product->sale_price)
                        <div class="flex flex-col">
                            <del class="text-sm font-semibold text-gray-400">?{{number_format($product->regular_price)}}</del>
                            <span class="text-xs font-black text-red-500 bg-red-50 px-2 py-0.5 rounded border border-red-100 inline-block w-fit mt-1">-{{ round((($product->regular_price - $product->sale_price) / $product->regular_price) * 100) }}% OFF</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Stock --}}
                <div class="mb-8">
                    <div x-show="stockStatus == 'out_of_stock'">
                        <div class="w-full h-1.5 bg-gray-200 rounded-full mb-2 overflow-hidden">
                            <div class="h-full bg-red-500 w-full"></div>
                        </div>
                        <span class="text-xs font-bold text-red-500 flex items-center gap-1"><i class="fas fa-circle text-[8px]"></i> Out of stock (Select another variation)</span>
                    </div>

                    <div x-show="stockStatus == 'in_stock'" style="display: none;">
                        <div class="w-full h-1.5 bg-gray-200 rounded-full mb-2 overflow-hidden">
                            <div class="h-full bg-green-500 w-[85%]"></div>
                        </div>
                        <span class="text-xs font-bold text-green-600 flex items-center gap-1">
                            <i class="fas fa-circle text-[8px]"></i> In stock 
                            <span class="text-gray-500 font-bold ml-1">(<span x-text="availableStock"></span> available)</span>
                        </span>
                    </div>
                </div>

                @include('shop.partials.product-variations')

                {{-- Shipping Options Box --}}
                <div class="mt-8 border border-gray-200 rounded-xl overflow-hidden shadow-sm bg-white">
                    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50/80 flex items-center gap-2">
                        <i class="fas fa-truck text-bdblue"></i> <span class="font-bold text-sm text-dark">Shipping Options</span>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Option --}}
                        <div class="border border-green-200 bg-green-50 rounded-lg p-3 relative">
                            <span class="absolute top-2 right-2 flex w-2 h-2 rounded-full bg-green-500"></span>
                            <div class="text-[11px] font-bold text-green-700 flex items-center gap-1.5 mb-1"><i class="fas fa-building"></i> Office Pickup</div>
                            <div class="font-black text-green-600 text-lg">Free</div>
                            <div class="text-[10px] text-green-800 mt-1">Pick up from our office</div>
                        </div>
                        {{-- Option --}}
                        <div class="border border-blue-200  rounded-lg p-3">
                            <div class="text-[11px] font-bold text-blue-700 flex items-center gap-1.5 mb-1"><i class="fas fa-map-marker-alt"></i> Inside Dhaka</div>
                            <div class="font-black text-blue-600 text-lg">?{{$client->delivery_charge_inside ?? 60}}</div>
                            <div class="text-[10px] text-gray-500 mt-1">1-3 business days</div>
                        </div>
                        {{-- Option --}}
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-[11px] font-bold text-gray-700 flex items-center gap-1.5 mb-1"><i class="fas fa-route"></i> Outside Dhaka</div>
                            <div class="font-black text-dark text-lg">?{{$client->delivery_charge_outside ?? 120}}</div>
                            <div class="text-[10px] text-gray-500 mt-1">2-5 business days</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Tabs & Description Segment --}}
        <div class="mb-12">
            {{-- Tab Headers --}}
            <div class="flex border-b border-gray-200 gap-1 overflow-x-auto hide-scroll">
                <button @click="tab = 'description'" :class="{'text-bdblue border-b-2 border-bdblue font-bold': tab === 'description', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'description'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Description</button>
                <button @click="tab = 'specifications'" :class="{'text-bdblue border-b-2 border-bdblue font-bold': tab === 'specifications', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'specifications'}" class="px-6 py-4 text-sm whitespace-nowrap transition">Specifications</button>
                <button @click="tab = 'reviews'" :class="{'text-bdblue border-b-2 border-bdblue font-bold': tab === 'reviews', 'text-gray-500 hover:text-gray-700 font-semibold border-b-2 border-transparent': tab !== 'reviews'}" class="px-6 py-4 text-sm whitespace-nowrap transition text-gray-400">Reviews (0)</button>
            </div>
            
            {{-- Tab Contents --}}
            <div class="py-8 text-sm text-gray-600 leading-relaxed font-medium">
                
                <div x-show="tab === 'description'" class="animate-fade-in text-justify">
                    {!! clean($product->description ?? $product->long_description) !!}
                </div>
                
                <div x-show="tab === 'specifications'" class="animate-fade-in hidden">
                    <div class="max-w-xl bg-gray-50 rounded-lg border border-gray-200 p-6">
                        <div class="grid grid-cols-2 gap-y-4 text-sm">
                            <div class="font-bold text-dark">Brand</div><div class="text-right">{{$product->brand ?? 'Generic'}}</div>
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">SKU</div><div class="text-right border-t border-gray-200 pt-4">{{$product->id}}{{$product->client_id*87}}</div>
                            @if($product->material)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Material</div><div class="text-right border-t border-gray-200 pt-4">{{$product->material}}</div>
                            @endif
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Warranty</div><div class="text-right border-t border-gray-200 pt-4">{{$product->warranty ?? 'N/A'}}</div>
                            @if($client->show_return_warranty ?? true)
                            <div class="font-bold text-dark border-t border-gray-200 pt-4">Return Policy</div><div class="text-right border-t border-gray-200 pt-4">{{$product->return_policy ?? '7 Days Easy Return'}}</div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div x-show="tab === 'reviews'" class="animate-fade-in hidden">
                    @include('shop.partials.related-products', ['client' => $client, 'product' => $product, 'relatedProducts' => App\Models\Product::where('client_id', $client->id)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->limit(8)->get()])

@include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
                </div>
                
            </div>
            
            {{-- Disclaimer --}}
            <div class="bg-yellow-50/80 border border-yellow-200 rounded-lg p-5 mt-6">
                <div class="flex items-center gap-2 text-yellow-700 font-bold text-sm mb-2"><i class="fas fa-info-circle"></i> Disclaimer:</div>
                <p class="text-[11px] text-yellow-800/80 text-justify leading-relaxed">
                    Product descriptions and technical specifications on this website may be generated with the assistance of AI and are provided for reference purposes only. Actual product features, performance, and specifications may vary — please verify all details directly with the official manufacturer's website before purchase. We do not manufacture; we are a seller and are not responsible for discrepancies in product features, behavior, or specifications.
                </p>
            </div>
        </div>

        {{-- You Might Also Like --}}
        @if($client->show_related_products ?? true)
        <div class="mb-12 border-t border-gray-200 pt-10">
            <h3 class="text-xl font-extrabold text-dark mb-6">You might also like</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @php $related = App\Models\Product::where('client_id', $client->id)->where('category_id', $product->category_id)->where('id', '!=', $product->id)->inRandomOrder()->limit(5)->get(); @endphp
                @foreach($related as $p)
                <div class="border border-gray-100 rounded-lg p-3 hover:border-bdblue hover:shadow-md transition group bg-white relative">
                    <a href="{{$baseUrl.'/product/'.$p->slug}}" class="block bg-gray-50 mb-3 rounded overflow-hidden aspect-square flex items-center justify-center">
                        <img src="{{asset('storage/'.$p->thumbnail)}}" loading="lazy" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
                    </a>
                    <a href="{{$baseUrl.'/product/'.$p->slug}}">
                        <h4 class="text-xs font-semibold text-gray-700 line-clamp-2 h-8 mb-2 group-hover:text-bdblue transition">{{$p->name}}</h4>
                        <div class="font-bold text-blue-600 text-sm">?{{number_format($p->sale_price ?? $p->regular_price)}}</div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  event: 'view_item',
  ecommerce: {
    currency: 'BDT',
    value: {{ $product->sale_price ?? $product->regular_price }},
    items: [{
      item_id: '{{ $product->id }}',
      item_name: '{{ $product->name }}',
      price: {{ $product->sale_price ?? $product->regular_price }},
      quantity: 1
    }]
  }
});
</script>
@include('shop.partials.product-sticky-bar')
@endsection

