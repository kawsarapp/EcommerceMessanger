@extends('shop.themes.pikabo.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<div class="bg-[#f8f9fa] py-8" x-data="checkoutApp()">
<script>
function checkoutApp() {
    return {
        shippingMethods: {!! json_encode($shippingMethods ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        shippingMethodId: {{ (isset($shippingMethods) && $shippingMethods->count() > 0) ? $shippingMethods->first()->id : 'null' }},
        area: 'inside', // 'office', 'inside', 'outside'
        paymentMethod: 'cod', // COD for simplicity here representing 'Pay in 30 min'
        paymentType: 'full', // 'full', 'partial'
        qty: {{ request('qty', 1) }},
        price: {{ $product->sale_price ?? $product->regular_price }},
        
        // Variables for calculations
        get delivery() {
            if (this.shippingMethods && this.shippingMethods.length > 0) {
                let sm = this.shippingMethods.find(m => m.id == this.shippingMethodId);
                return sm ? parseFloat(sm.cost) : 0;
            } else {
                if(this.area === 'office') return 0;
                return this.area === 'inside' ? {{ $client->delivery_charge_inside ?? 50 }} : {{ $client->delivery_charge_outside ?? 100 }};
            }
        },
        get subtotal() { return this.qty * this.price; },
        
        // Coupon logic
        couponCode: '',
        couponDiscount: 0,
        couponApplied: false,
        couponError: '',
        
        get total() { return this.subtotal + this.delivery - this.couponDiscount; },
        
        applyCoupon() {
            if(!this.couponCode) { this.couponError = 'Please enter a coupon code'; return; }
            this.couponError = '';
            fetch('{{ $clean ? $baseUrl.'/apply-coupon' : route('shop.apply-coupon.sub', $client->slug) }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({code: this.couponCode, product_id: {{ $product->id }}, subtotal: this.subtotal})
            }).then(r => r.json()).then(d => {
                if(d.success) { this.couponDiscount = d.discount; this.couponApplied = true; }
                else { this.couponError = d.message || 'Invalid coupon code'; }
            }).catch(err => { this.couponError = 'Error verifying coupon'; });
        }
    };
}
</script>

    <div class="max-w-[1280px] mx-auto px-4">
        
        {{-- Breadcrumb --}}
        <nav class="flex items-center text-xs text-gray-400 mb-6 font-medium">
            <a href="{{$baseUrl}}" class="hover:text-primary transition">Home</a>
            <i class="fas fa-chevron-right text-[8px] mx-3"></i>
            <span class="text-gray-600">Checkout</span>
        </nav>

        @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 shadow-sm rounded-r-lg flex items-center gap-3">
            <div class="text-green-500 text-xl"><i class="fas fa-check-circle"></i></div>
            <div>
                <h4 class="text-green-800 font-bold text-sm">Order Placed Successfully!</h4>
                <p class="text-green-600 text-xs mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- Left Column: Form Setup --}}
            <div class="lg:col-span-8">
                
                {{-- Title --}}
                <div class="flex items-center gap-3 mb-6">
                    <i class="fas fa-shopping-cart text-blue-500 text-xl"></i>
                    <h1 class="text-xl font-extrabold text-dark tracking-tight">Checkout</h1>
                </div>

                <form action="{{ $baseUrl.'/checkout/process' }}" method="POST" id="checkout-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="qty" :value="qty">
                    @if(request('color'))<input type="hidden" name="color" value="{{ request('color') }}">@endif
                    @if(request('size'))<input type="hidden" name="size" value="{{ request('size') }}">@endif
                    <input type="hidden" name="shipping_method_id" :value="shippingMethodId">
                    <input type="hidden" name="area" :value="area">
                    <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
                    <input type="hidden" name="coupon_discount" :value="couponDiscount">

                    {{-- Customer Information --}}
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-sm font-semibold text-gray-800">Customer Information</h2>
                            <a href="#" class="text-[11px] text-blue-500 hover:text-blue-700 font-medium flex items-center gap-1.5"><i class="fas fa-sign-in-alt"></i> Login to auto-fill</a>
                        </div>
                        
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5"><i class="far fa-user text-gray-400 mr-1"></i> Full Name *</label>
                                <input type="text" name="customer_name" required placeholder="Enter your full name" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-dark placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary transition shadow-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5"><i class="fas fa-mobile-alt text-gray-400 mr-1"></i> Mobile Number *</label>
                                <input type="tel" name="customer_phone" required placeholder="01XXXXXXXXX" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-dark placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary transition shadow-sm bg-white">
                                <span class="text-[10px] text-gray-400 mt-1 block">Enter 11-digit mobile number</span>
                            </div>
                            <div class="sm:col-span-2 mt-2">
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5"><i class="fas fa-map-marker-alt text-gray-400 mr-1"></i> Delivery Address *</label>
                                <textarea name="shipping_address" required rows="3" placeholder="Enter your complete delivery address with house number, street, area, and district" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-dark placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary transition shadow-sm resize-none bg-white"></textarea>
                                <span class="text-[10px] text-gray-400 mt-1 block flex items-center gap-1"><i class="fas fa-info-circle text-blue-400"></i> <a href="{{ `$clean ? `$baseUrl.`"/track`" : route(`"shop.track`", `$client->slug) }}" class="text-blue-500 hover:underline">Track Order</a> to save addresses for faster checkout next time</span>
                            </div>
                        </div>
                    </div>

                    {{-- Select Delivery Area --}}
                    <div class="mb-8">
                        <h2 class="text-sm font-semibold text-gray-800 mb-3">Select Delivery Option</h2>
                        
                        @if(isset($shippingMethods) && $shippingMethods->count() > 0)
                            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($shippingMethods as $method)
                                <label class="cursor-pointer">
                                    <input type="radio" name="temp_sm" value="{{ $method->id }}" @change="shippingMethodId = {{ $method->id }}" class="peer hidden" :checked="shippingMethodId == {{ $method->id }}">
                                    <div class="border border-gray-300 rounded-md p-3 peer-checked:border-primary peer-checked:bg-primary/10/50 transition relative bg-white flex items-start gap-3 shadow-sm hover:border-gray-400 h-full">
                                        <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-primary flex items-center justify-center shrink-0 mt-0.5">
                                            <div class="w-2 h-2 rounded-full bg-primary rounded-full opacity-0" :class="{'opacity-100': shippingMethodId == {{ $method->id }}}"></div>
                                        </div>
                                        <div>
                                            <div class="text-[11px] font-bold text-dark flex items-center gap-1.5"><i class="fas fa-truck text-primary"></i> {{ $method->name }}</div>
                                            <div class="text-xs text-gray-500 font-medium">{!! $method->cost > 0 ? '৳'.number_format($method->cost) : 'Free' !!}</div>
                                            @if($method->estimated_time)
                                                <div class="text-[9px] text-gray-400 mt-0.5"><i class="far fa-clock"></i> {{ $method->estimated_time }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            <div class="mt-3 bg-primary/10/50 border border-blue-100 rounded-md py-2 px-3 flex items-center text-[10px] text-gray-600">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i> 
                                Selected: <span class="font-bold text-gray-800 ml-1" x-text="shippingMethods.find(m => m.id == shippingMethodId)?.name"></span> 
                                <span class="mx-2 text-gray-300">•</span> Charge:  <span x-text="delivery > 0 ? '৳'+delivery : 'Free'" class="font-bold text-gray-800 mx-1"></span>
                            </div>
                        @else
                            <div class="grid sm:grid-cols-3 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="_area_selector" value="office" @change="area = 'office'" class="peer hidden" :checked="area === 'office'">
                                    <div class="border border-gray-300 rounded-md p-3 peer-checked:border-primary peer-checked:bg-primary/10/50 transition relative bg-white flex items-start gap-3 shadow-sm hover:border-gray-400 h-full">
                                        <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-primary flex items-center justify-center shrink-0 mt-0.5">
                                            <div class="w-2 h-2 rounded-full bg-primary rounded-full opacity-0" :class="{'opacity-100': area === 'office'}"></div>
                                        </div>
                                        <div>
                                            <div class="text-[11px] font-bold text-dark flex items-center gap-1.5"><i class="fas fa-building text-gray-400"></i> Office Pickup</div>
                                            <div class="text-xs text-gray-500 font-medium">Free</div>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="cursor-pointer">
                                    <input type="radio" name="_area_selector" value="inside" @change="area = 'inside'" class="peer hidden" :checked="area === 'inside'">
                                    <div class="border border-gray-300 rounded-md p-3 peer-checked:border-primary peer-checked:bg-primary/10/50 transition relative bg-white flex items-start gap-3 shadow-sm hover:border-gray-400 h-full">
                                        <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-primary flex items-center justify-center shrink-0 mt-0.5">
                                            <div class="w-2 h-2 rounded-full bg-primary rounded-full opacity-0" :class="{'opacity-100': area === 'inside'}"></div>
                                        </div>
                                        <div>
                                            <div class="text-[11px] font-bold text-dark flex items-center gap-1.5"><i class="fas fa-map-marker-alt text-blue-500"></i> Inside Dhaka</div>
                                            <div class="text-xs text-gray-500 font-medium">৳{{$client->delivery_charge_inside ?? 50}}</div>
                                        </div>
                                    </div>
                                </label>

                                <label class="cursor-pointer">
                                    <input type="radio" name="_area_selector" value="outside" @change="area = 'outside'" class="peer hidden" :checked="area === 'outside'">
                                    <div class="border border-gray-300 rounded-md p-3 peer-checked:border-primary peer-checked:bg-primary/10/50 transition relative bg-white flex items-start gap-3 shadow-sm hover:border-gray-400 h-full">
                                        <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-primary flex items-center justify-center shrink-0 mt-0.5">
                                            <div class="w-2 h-2 rounded-full bg-primary rounded-full opacity-0" :class="{'opacity-100': area === 'outside'}"></div>
                                        </div>
                                        <div>
                                            <div class="text-[11px] font-bold text-dark flex items-center gap-1.5"><i class="fas fa-truck text-green-500"></i> Outside Dhaka</div>
                                            <div class="text-xs text-gray-500 font-medium">৳{{$client->delivery_charge_outside ?? 100}}</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="mt-3 bg-primary/10/50 border border-blue-100 rounded-md py-2 px-3 flex items-center text-[10px] text-gray-600">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i> Selected: <span x-text="area==='office'?'Office Pickup':(area==='inside'?'Inside Dhaka':'Outside Dhaka')" class="font-bold text-gray-800 ml-1"></span> 
                                <span class="mx-2 text-gray-300">•</span> Charge:  <span x-text="delivery > 0 ? '৳'+delivery : 'Free'" class="font-bold text-gray-800 mx-1"></span>
                                <span class="mx-2 text-gray-300">•</span> Time: Same day 
                                <span class="mx-2 text-gray-300">•</span> <i class="fas fa-gift text-primary mr-1"></i> Free delivery above ৳10,000.00
                            </div>
                        @endif
                    </div>

                    {{-- Payment Method --}}
                    <div class="mb-8">
                        <h2 class="text-sm font-semibold text-gray-800 mb-3">Payment Method</h2>
                        
                        <div class="flex gap-4 border-b border-gray-200 mb-4 pb-1">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="payment_type" value="full" @change="paymentType='full'" class="peer hidden" checked>
                                <div class="w-3.5 h-3.5 rounded-full border flex items-center justify-center peer-checked:border-primary">
                                    <div class="w-1.5 h-1.5 rounded-full bg-primary opacity-0" :class="{'opacity-100': paymentType==='full'}"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-600 peer-checked:text-dark">Full Payment</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="payment_type" value="partial" @change="paymentType='partial'" class="peer hidden">
                                <div class="w-3.5 h-3.5 rounded-full border flex items-center justify-center peer-checked:border-primary">
                                    <div class="w-1.5 h-1.5 rounded-full bg-primary opacity-0" :class="{'opacity-100': paymentType==='partial'}"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-500 peer-checked:text-dark">Partial Payment (10%)</span>
                            </label>
                        </div>

                        <div class="grid sm:grid-cols-3 gap-3 mb-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="_pmt" value="cod" @change="paymentMethod='cod'" class="peer hidden" checked>
                                <div class="border border-gray-300 rounded-md px-3 py-4 peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary transition relative bg-white shadow-sm flex items-center gap-2 hover:border-gray-400 h-full">
                                    <div class="w-3 h-3 rounded-full border flex-shrink-0 flex items-center justify-center peer-checked:border-primary">
                                        <div class="w-1.5 h-1.5 rounded-full bg-primary opacity-0" :class="{'opacity-100': paymentMethod==='cod'}"></div>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="text-[11px] font-bold text-gray-800 flex items-center gap-1.5"><i class="fas fa-clock text-orange-500"></i> Pay in 30 min</div>
                                        <div class="text-[9px] text-primary mt-0.5 leading-tight">Order will be cancelled if not paid</div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="cursor-pointer">
                                <input type="radio" name="_pmt" value="bkash" @change="paymentMethod='bkash'" class="peer hidden">
                                <div class="border border-gray-300 rounded-md px-3 py-4 peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary transition relative bg-white shadow-sm flex items-center gap-2 hover:border-gray-400 h-full">
                                    <div class="w-3 h-3 rounded-full border flex-shrink-0 flex items-center justify-center peer-checked:border-primary">
                                        <div class="w-1.5 h-1.5 rounded-full bg-primary opacity-0" :class="{'opacity-100': paymentMethod==='bkash'}"></div>
                                    </div>
                                    <div class="text-[11px] font-bold text-gray-800 flex items-center gap-1.5"><i class="fas fa-mobile text-[#e2136e]"></i> bKash</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="_pmt" value="online" @change="paymentMethod='online'" class="peer hidden">
                                <div class="border border-gray-300 rounded-md px-3 py-4 peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary transition relative bg-white shadow-sm flex items-center gap-2 hover:border-gray-400 h-full">
                                    <div class="w-3 h-3 rounded-full border flex-shrink-0 flex items-center justify-center peer-checked:border-primary">
                                        <div class="w-1.5 h-1.5 rounded-full bg-primary opacity-0" :class="{'opacity-100': paymentMethod==='online'}"></div>
                                    </div>
                                    <div class="text-[11px] font-bold text-gray-800 flex items-center gap-1.5"><i class="fas fa-shield-alt text-green-600"></i> Pay Online</div>
                                </div>
                            </label>
                        </div>

                        <div class="w-full sm:w-1/3 pr-1.5">
                            <label class="cursor-pointer h-full">
                                <input type="radio" name="_pmt" value="credit" @change="paymentMethod='credit'" class="peer hidden">
                                <div class="border border-gray-300 rounded-md px-3 py-4 peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary transition relative bg-white shadow-sm flex items-center gap-2 hover:border-gray-400 h-full">
                                    <div class="w-3 h-3 rounded-full border flex-shrink-0 flex items-center justify-center peer-checked:border-primary">
                                        <div class="w-1.5 h-1.5 rounded-full bg-primary opacity-0" :class="{'opacity-100': paymentMethod==='credit'}"></div>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="text-[11px] font-bold text-gray-800 flex items-center gap-1.5"><i class="fas fa-credit-card text-bddeep"></i> Use Credit</div>
                                        <div class="text-[9px] text-gray-500 mt-0.5">Check by phone</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="mt-8 mb-4">
                        @if($client->show_terms_checkbox ?? false)
                        <div class="mb-4 bg-primary/10/50 border border-blue-100 rounded-md p-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" required class="mt-0.5 w-4 h-4 text-primary bg-white border-gray-300 rounded focus:ring-primary">
                                <span class="text-xs text-gray-700 font-medium leading-tight">
                                    I have read and agree to the <a href="{{ $clean ? $baseUrl.'/terms-conditions' : route('shop.page.slug', [$client->slug, 'terms-conditions']) }}" class="text-primary hover:underline font-bold">Terms and Conditions</a>, 
                                    <a href="{{ $clean ? $baseUrl.'/privacy-policy' : route('shop.page.slug', [$client->slug, 'privacy-policy']) }}" class="text-primary hover:underline font-bold">Privacy Policy</a>, and 
                                    <a href="{{ $clean ? $baseUrl.'/return-policy' : route('shop.page.slug', [$client->slug, 'return-policy']) }}" class="text-primary hover:underline font-bold">Return Policy</a>.
                                </span>
                            </label>
                        </div>
                        @endif

                        <button type="submit" class="w-full bg-[#111827] hover:bg-[#1f2937] text-white rounded-md py-3.5 font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
                            <i class="fas fa-wallet text-gray-300"></i> Place Order
                        </button>
                    </div>
                    
                    @if(!($client->show_terms_checkbox ?? false))
                    <div class="text-center">
                        <span class="text-[10px] text-gray-400">By placing your order, you agree to our <a href="{{ $clean ? $baseUrl.'/terms-conditions' : route('shop.page.slug', [$client->slug, 'terms-conditions']) }}" class="underline">Terms and Conditions</a></span>
                    </div>
                    @endif

                </form>
            </div>

            {{-- Right Column: Order Summary --}}
            <div class="lg:col-span-4 lg:sticky lg:top-20">
                <div class="bg-white border text-sm border-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2 text-primary font-bold">
                        <i class="fas fa-file-invoice"></i> Order Summary
                    </div>
                    
                    <div class="p-4 bg-white">
                        <div class="text-xs font-semibold text-gray-500 mb-3 border-b border-dashed border-gray-200 pb-2">Items in Cart</div>
                        
                        {{-- Product Meta --}}
                        <div class="flex gap-3 mb-6 relative group">
                            <div class="w-12 h-12 bg-white rounded border border-gray-200 p-1 shrink-0 flex items-center justify-center relative">
                                <img src="{{ asset('storage/'.$product->thumbnail) }}" class="max-w-full max-h-full object-contain">
                            </div>
                            <div class="flex-1 flex flex-col justify-center">
                                <h4 class="font-bold text-gray-800 text-[11px] line-clamp-1 pr-10 hover:text-primary transition cursor-pointer" title="{{ $product->name }}">{{ $product->name }}</h4>
                                <div class="text-[10px] text-gray-500 font-medium">Qty: <span x-text="qty"></span> &times; ৳{{ number_format($product->sale_price ?? $product->regular_price) }}</div>
                                @if(request('color') || request('size'))
                                    <div class="text-[9px] text-gray-400 mt-0.5 font-medium uppercase">{{ request('color') }} {{ request('size') }}</div>
                                @endif
                                <div class="absolute right-0 top-1.5 font-bold text-dark text-[11px]">৳<span x-text="(qty * price).toLocaleString()"></span></div>
                            </div>
                        </div>

                        {{-- Coupon Form --}}
                        <div class="bg-primary/10/50 p-3 rounded-md border border-blue-50 mb-6">
                            <div class="text-[11px] font-bold text-gray-600 mb-2 flex items-center gap-1.5"><i class="fas fa-tag text-primary"></i> Coupon Code</div>
                            <div class="flex gap-1" x-show="!couponApplied">
                                <input type="text" x-model="couponCode" placeholder="ENTER COUPON CODE" 
                                    class="flex-1 text-xs px-2 py-1 border border-gray-300 rounded focus:outline-none focus:border-primary uppercase font-mono shadow-sm bg-white text-gray-700">
                                <button type="button" @click="applyCoupon()" class="bg-[#4d61fc] hover:bg-blue-600 text-white px-3 py-1.5 rounded text-[11px] font-bold uppercase transition flex items-center gap-1 shadow-sm"><i class="fas fa-check"></i> Apply</button>
                            </div>
                            <div x-show="couponApplied" class="flex justify-between items-center text-[10px] font-bold text-green-600">
                                <div><i class="fas fa-check-circle mr-1"></i> Coupon '<span x-text="couponCode" class="uppercase"></span>' Applied</div>
                                <button type="button" @click="couponApplied=false; couponDiscount=0; couponCode=''" class="text-primary hover:text-red-700"><i class="fas fa-times"></i> Remove</button>
                            </div>
                            <p x-show="couponError" x-text="couponError" class="text-[10px] font-medium text-primary mt-1.5"></p>
                        </div>

                        {{-- Totals --}}
                        <div class="space-y-3 pt-2 text-xs font-medium">
                            <div class="flex justify-between items-center"><span class="text-gray-500">Subtotal</span><span class="text-gray-700 font-semibold">৳<span x-text="subtotal.toLocaleString()"></span></span></div>
                            
                            <div class="flex justify-between items-center text-gray-500">
                                <span class="flex items-center gap-1.5"><i class="fas fa-truck text-gray-400"></i> Delivery 
                                    <span class="text-[9px] bg-gray-100 px-1 rounded ml-1 font-bold text-gray-500" x-show="shippingMethods.length === 0">
                                        (<span x-text="area==='office'?'Office Pickup':(area==='inside'?'Inside Dhaka':'Outside Dhaka')"></span>)
                                    </span>
                                    <span class="text-[9px] bg-gray-100 px-1 rounded ml-1 font-bold text-gray-500" x-show="shippingMethods.length > 0" style="display: none;">
                                        (<span x-text="shippingMethods.find(m => m.id == shippingMethodId)?.name"></span>)
                                    </span>
                                </span>
                                <span class="text-gray-700 font-semibold text-[11px]" :class="{'text-green-600 font-bold': delivery === 0}" x-text="delivery === 0 ? 'Free' : '৳'+delivery"></span>
                            </div>
                            
                            <div x-show="couponApplied" class="flex justify-between items-center"><span class="text-primary">Discount (<span x-text="couponCode" class="uppercase text-[9px]"></span>)</span><span class="text-primary font-bold">-৳<span x-text="couponDiscount.toLocaleString()"></span></span></div>
                        </div>
                    </div>
                    
                    {{-- Grand Total --}}
                    <div class="px-5 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                        <span class="font-bold text-gray-800 text-base">Total</span>
                        <span class="text-2xl font-black text-[#0084d6] tracking-tight">৳<span x-text="total.toLocaleString()"></span></span>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>
@endsection
