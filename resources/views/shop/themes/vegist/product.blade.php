@extends('shop.themes.vegist.layout')

@section('title', $product->name . ' - ' . $client->shop_name)

@section('content')

{{-- Breadcrumb --}}
<div class="bg-[#fcfdfa] py-6 mb-8 border-b border-gray-100">
    <div class="max-w-[1400px] mx-auto px-4 xl:px-8 text-center text-[12px] text-gray-500 font-medium tracking-wide">
        <a href="{{$baseUrl}}" class="hover:text-primary transition">Home</a>
        <span class="mx-2">/</span>
        <span class="text-dark">{{$product->name}}</span>
    </div>
</div>

<div x-data="productData()" class="max-w-[1400px] mx-auto px-4 xl:px-8 pb-16">
    <form @submit.prevent="addToCart" class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        {{-- Left: Galleries --}}
        <div class="lg:col-span-4">
            <div class="bg-gray-50 flex items-center justify-center p-8 mb-4 border border-gray-100 mix-blend-multiply overflow-hidden rounded relative max-h-[500px]">
                <img :src="activeImage" class="w-full h-auto object-contain transition duration-300">
                
                @if($product->compare_price > $product->price && $product->compare_price > 0)
                @php $percent = round((($product->compare_price - $product->price) / $product->compare_price) * 100); @endphp
                <div class="absolute top-4 right-4 bg-red-600 text-white text-xs font-bold px-2 py-1 z-10 rounded-sm shadow-sm">
                    -{{$percent}}%
                </div>
                @endif
            </div>

            @if($product->images && count($images = is_string($product->images) ? json_decode($product->images, true) : $product->images) > 0)
            <div class="grid grid-cols-4 gap-3">
                @if($product->thumbnail)
                <div @click="activeImage = '{{asset('storage/'.$product->thumbnail)}}'" class="border cursor-pointer p-2 bg-gray-50 mix-blend-multiply rounded" :class="activeImage === '{{asset('storage/'.$product->thumbnail)}}' ? 'border-primary' : 'border-gray-100 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-auto object-contain">
                </div>
                @endif
                
                @foreach($images as $img)
                <div @click="activeImage = '{{asset('storage/'.$img)}}'" class="border cursor-pointer p-2 bg-gray-50 mix-blend-multiply rounded" :class="activeImage === '{{asset('storage/'.$img)}}' ? 'border-primary' : 'border-gray-100 hover:border-gray-300'">
                    <img src="{{asset('storage/'.$img)}}" class="w-full h-auto object-contain">
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Center: Core Details --}}
        <div class="lg:col-span-5">
            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-3">{{$product->name}}</h1>
            
            <div class="flex items-center gap-4 mb-4 pb-4 border-b border-gray-100">
                <div class="flex items-center text-[#ffb522] text-xs gap-1">
                    @php $rating = $product->average_rating ?? 5; @endphp
                    @for($i=1; $i<=5; $i++)
                        @if($i <= $rating) <i class="fas fa-star"></i>
                        @elseif($i - 0.5 <= $rating) <i class="fas fa-star-half-alt"></i>
                        @else <i class="far fa-star text-gray-300"></i>
                        @endif
                    @endfor
                    <span class="text-gray-500 ml-1 text-xs">({{ $product->reviews_count ?? 1 }} review)</span>
                </div>
                <div class="text-[12px] text-gray-500">
                    <span class="font-bold text-dark mr-1">Availability:</span>
                    @if($product->stock > 0)
                        <span class="text-green-600"><i class="fas fa-circle text-[8px] mr-1 pb-[1px] relative -top-0.5"></i> In stock</span>
                    @else
                        <span class="text-red-500"><i class="fas fa-circle text-[8px] mr-1 pb-[1px] relative -top-0.5"></i> Out of stock</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 mb-6">
                <span class="text-2xl font-bold text-dark" x-text="'৳' + currentPrice"></span>
                @if($product->compare_price > $product->price && $product->compare_price > 0)
                <span class="text-base text-gray-400 line-through">৳{{number_format($product->compare_price)}}</span>
                @endif
            </div>

            @if($product->short_description)
            <p class="text-[13px] text-gray-500 mb-8 leading-relaxed">
                {{$product->short_description}}
            </p>
            @endif

            {{-- Variants (Sizes) --}}
            @if(isset($product->attributes) && is_array($product->attributes) && count($product->attributes) > 0)
            <div class="mb-6">
                <div class="flex items-center gap-4 mb-2">
                    <span class="text-[14px] font-bold text-dark">Size:</span>
                    <span class="text-[13px] text-gray-400" x-text="selectedVariantName"></span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($product->attributes as $attr)
                        @if(!empty($attr['name']) && !empty($attr['price']))
                        <label class="cursor-pointer">
                            <input type="radio" name="variant" value="{{$attr['price']}}" data-name="{{$attr['name']}}" class="peer hidden" @change="updateVariant($event)" @if($loop->first) checked x-init="$nextTick(() => { updateVariant({target: $el}) })" @endif>
                            <div class="px-4 py-2 border border-gray-200 rounded text-[12px] font-medium text-gray-600 peer-checked:border-primary peer-checked:text-primary transition">
                                {{$attr['name']}}
                            </div>
                        </label>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Quantity & Buttons --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 mb-6">
                <div class="flex items-center w-32 border border-gray-200 rounded shrink-0 h-11 bg-white">
                    <button type="button" @click="if(qty > 1) qty--" class="w-10 h-full flex items-center justify-center text-gray-500 hover:text-primary transition text-lg">-</button>
                    <input type="number" x-model="qty" class="w-full h-full text-center border-x border-gray-200 focus:outline-none text-sm font-bold bg-transparent" min="1" readonly>
                    <button type="button" @click="qty++" class="w-10 h-full flex items-center justify-center text-gray-500 hover:text-primary transition text-lg">+</button>
                </div>

                @if(!($client->widgets['show_order_button'] ?? true) && ($client->widgets['show_chat_button'] ?? true))
                    @if($client->phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->phone) }}?text=I am interested in {{$product->name}}" target="_blank" class="flex-1 w-full bg-[#25d366] text-white h-11 flex justify-center items-center rounded font-bold text-sm tracking-wide hover:bg-[#128c7e] transition">
                        <i class="fab fa-whatsapp mr-2 text-lg"></i> ORDER VIA WHATSAPP
                    </a>
                    @endif
                @else
                    <button type="submit" class="flex-1 w-full btn-primary h-11 rounded" :disabled="isLoading">
                        <span x-show="!isLoading">Add to cart</span>
                        <span x-show="isLoading" class="flex justify-center items-center gap-2"><i class="fas fa-spinner fa-spin"></i> Adding...</span>
                    </button>
                    <button type="button" @click="buyNow" class="flex-1 w-full btn-dark h-11 rounded" :disabled="isLoading">
                        Buy it now
                    </button>
                @endif
            </div>

            {{-- Wishlist Toggle --}}
            <button type="button" class="flex items-center gap-2 text-sm text-gray-500 hover:text-primary transition mb-8 group">
                <i class="far fa-heart group-hover:hidden"></i>
                <i class="fas fa-heart hidden group-hover:block text-primary"></i>
                <span>Add to Wishlist</span>
            </button>

            {{-- Meta Data --}}
            <div class="border-t border-gray-100 pt-6 text-[12px] text-gray-500 flex flex-col gap-2">
                <p><strong class="text-dark">SKU:</strong> {{$product->sku ?? 'N/A'}}</p>
                @if($product->category)
                <p><strong class="text-dark">Category:</strong> <a href="#" class="hover:text-primary transition">{{$product->category->name}}</a></p>
                @endif
                <div class="flex items-center gap-3 mt-1">
                    <strong class="text-dark">Share:</strong>
                    <div class="flex items-center gap-2">
                        <a href="#" class="text-gray-400 hover:text-blue-600 transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-blue-400 transition"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-600 transition"><i class="fab fa-pinterest-p"></i></a>
                        <a href="#" class="text-gray-400 hover:text-[#25D366] transition"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-6">
                <i class="fab fa-cc-visa text-2xl text-blue-800"></i>
                <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                <i class="fab fa-cc-paypal text-2xl text-blue-500"></i>
            </div>
        </div>

        {{-- Right Column: Information Cards --}}
        <div class="lg:col-span-3">
            <div class="bg-gray-50 flex flex-col gap-4">
                <div class="bg-white border border-gray-100 p-6 flex flex-col items-center text-center">
                    <i class="fas fa-truck text-3xl text-primary opacity-60 mb-3"></i>
                    <h4 class="text-sm font-bold text-dark mb-2 uppercase">Delivery info</h4>
                    <p class="text-[11px] text-gray-500 leading-relaxed">From then, delivery is generally within 2-10 days, depending on your location.</p>
                </div>
                
                <div class="bg-white border border-gray-100 p-6 flex flex-col items-center text-center">
                    <i class="fas fa-dollar-sign text-3xl text-primary opacity-60 mb-3"></i>
                    <h4 class="text-sm font-bold text-dark mb-2 uppercase">30 days returns</h4>
                    <p class="text-[11px] text-gray-500 leading-relaxed">Not the right fit? No worries. We'll arrange pick up and a full refund within 7 days including the delivery fee.</p>
                </div>
                
                <div class="bg-white border border-gray-100 p-6 flex flex-col items-center text-center">
                    <i class="fas fa-award text-3xl text-primary opacity-60 mb-3"></i>
                    <h4 class="text-sm font-bold text-dark mb-2 uppercase">10 year warranty</h4>
                    <p class="text-[11px] text-gray-500 leading-relaxed">Quality comes first and our products are designed to last.</p>
                </div>
            </div>
        </div>

    </form>

    {{-- Tabs Section --}}
    <div class="mt-16" x-data="{ tab: 'description' }">
        <div class="flex justify-center border-b border-gray-200">
            <button @click="tab = 'description'" :class="tab === 'description' ? 'border-b-2 border-primary text-primary font-bold' : 'text-gray-500 hover:text-primary'" class="px-6 py-4 text-sm uppercase transition bg-transparent">Description</button>
            <button @click="tab = 'additional'" :class="tab === 'additional' ? 'border-b-2 border-primary text-primary font-bold' : 'text-gray-500 hover:text-primary'" class="px-6 py-4 text-sm uppercase transition bg-transparent">Additional Information</button>
            <button @click="tab = 'reviews'" :class="tab === 'reviews' ? 'border-b-2 border-primary text-primary font-bold' : 'text-gray-500 hover:text-primary'" class="px-6 py-4 text-sm uppercase transition bg-transparent">Reviews ({{ $product->reviews_count ?? 0 }})</button>
        </div>
        
        <div class="py-10 border border-t-0 p-8 pt-10 text-sm text-gray-600 leading-relaxed border-gray-100" style="min-height: 250px;">
            <div x-show="tab === 'description'" x-cloak>
                @if($product->description)
                    {!! nl2br(e($product->description)) !!}
                @else
                    <p class="mb-4">No detailed description available for this product.</p>
                @endif
                
                {{-- Example structural mock derived from screenshot --}}
                <h4 class="text-dark font-bold text-base mt-8 mb-4">More Detail</h4>
                <ul class="list-disc pl-5 space-y-2 mb-8">
                    <li>Lorem ipsum is simply dummy text of the printing and typesetting industry</li>
                    <li>Lorem ipsum has been the industry's standard dummy text</li>
                    <li>Type here your detail one by one</li>
                </ul>
            </div>
            <div x-show="tab === 'additional'" x-cloak>
                <p>Additional specifications and dimensions would be displayed here.</p>
            </div>
            <div x-show="tab === 'reviews'" x-cloak>
                @include('shop.partials.product-reviews', ['product' => $product, 'client' => $client])
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if(isset($relatedProducts) && count($relatedProducts) > 0)
    <div class="mt-20">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-bold text-dark">Related Products</h2>
            <div class="w-16 h-1 bg-primary mx-auto mt-4 rounded-full"></div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            @foreach($relatedProducts->take(5) as $rp)
            <div class="bg-white group">
                <div class="relative bg-gray-50 aspect-square flex items-center justify-center p-6 overflow-hidden mb-4 mix-blend-multiply border border-transparent group-hover:border-gray-100 transition">
                    
                    @if($rp->compare_price > $rp->price && $rp->compare_price > 0)
                    @php $percent = round((($rp->compare_price - $rp->price) / $rp->compare_price) * 100); @endphp
                    <div class="absolute top-3 right-3 bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 z-10 shadow-sm">
                        -{{$percent}}%
                    </div>
                    @endif
                    
                    <a href="{{$clean?$baseUrl.'/product/'.$rp->slug:route('shop.product', ['shop' => $client->slug, 'product' => $rp->slug])}}" class="block w-full h-full">
                        @if($rp->thumbnail)
                            <img src="{{asset('storage/'.$rp->thumbnail)}}" class="w-full h-full object-contain group-hover:scale-105 transition duration-500 mix-blend-multiply">
                        @endif
                    </a>

                    <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition duration-300 z-20">
                        <button class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white shadow-lg transition">
                            <i class="far fa-heart"></i>
                        </button>
                        <a href="{{$clean?$baseUrl.'/product/'.$rp->slug:route('shop.product', ['shop' => $client->slug, 'product' => $rp->slug])}}" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white shadow-lg transition">
                            <i class="fas fa-shopping-bag"></i>
                        </a>
                    </div>
                </div>

                <div class="text-left px-1">
                    <a href="{{$clean?$baseUrl.'/product/'.$rp->slug:route('shop.product', ['shop' => $client->slug, 'product' => $rp->slug])}}" class="text-[13px] text-gray-600 hover:text-primary transition line-clamp-1 mb-1">
                        {{$rp->name}}
                    </a>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-[15px] font-bold text-dark">৳{{number_format($rp->price)}}</span>
                        @if($rp->compare_price > $rp->price && $rp->compare_price > 0)
                        <span class="text-[12px] text-gray-400 line-through">৳{{number_format($rp->compare_price)}}</span>
                        @endif
                    </div>
                    <div class="flex items-center text-[#ffb522] text-[10px] gap-0.5">
                        @php $rating = $rp->average_rating ?? 5; @endphp
                        @for($i=1; $i<=5; $i++)
                            @if($i <= $rating) <i class="fas fa-star"></i>
                            @elseif($i - 0.5 <= $rating) <i class="fas fa-star-half-alt"></i>
                            @else <i class="far fa-star text-gray-300"></i>
                            @endif
                        @endfor
                        <span class="text-gray-400 ml-1">({{ $rp->reviews_count ?? 0 }} reviews)</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
    function productData() {
        return {
            qty: 1,
            basePrice: {{ $product->price }},
            currentPrice: {{ $product->price }},
            selectedVariantName: '',
            activeImage: '{{ $product->thumbnail ? asset("storage/".$product->thumbnail) : "" }}',
            isLoading: false,

            updateVariant(event) {
                let p = parseFloat(event.target.value);
                this.currentPrice = p;
                this.selectedVariantName = event.target.getAttribute('data-name');
            },

            async addToCart() {
                this.isLoading = true;
                try {
                    let fd = new FormData();
                    fd.append('_token', '{{csrf_token()}}');
                    fd.append('product_id', {{$product->id}});
                    fd.append('quantity', this.qty);
                    if(this.selectedVariantName) {
                        fd.append('attributes', this.selectedVariantName);
                        fd.append('price', this.currentPrice);
                    }
                    
                    let res = await fetch("{{route('shop.cart.add', $client->slug)}}", {
                        method: 'POST',
                        body: fd
                    });
                    
                    if(res.ok) {
                        let data = await res.json();
                        // Trigger mobile nav cart update logic if generic
                        window.location.reload();
                    }
                } catch(e) {
                    console.error(e);
                } finally {
                    this.isLoading = false;
                }
            },

            async buyNow() {
                await this.addToCart();
                window.location.href = "{{$clean?$baseUrl.'/checkout':route('shop.checkout',$client->slug)}}";
            }
        }
    }
</script>

@endsection
