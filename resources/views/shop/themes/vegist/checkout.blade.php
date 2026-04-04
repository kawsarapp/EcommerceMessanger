@extends('shop.themes.vegist.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .vg-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; padding: 12px 14px; font-size: 13px; color: #4b5563; transition: border 0.3s; outline: none; background: #fff; }
    .vg-input:focus { border-color: var(--tw-color-primary); box-shadow: 0 0 0 1px var(--tw-color-primary); }
    .vg-label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px; }
    .vg-section-title { font-size: 18px; font-weight: 600; color: #222; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
</style>

@php
    // Pre-compute all URLs before x-data (Blade doesn't process @php inside HTML attributes)
    $couponUrl  = $clean ? 'https://'.$clean.'/apply-coupon' : route('shop.apply-coupon.sub', $client->slug);
    $formAction = $clean ? 'https://'.$clean.'/checkout/process' : route('shop.checkout.process', $client->slug);
    $chargeInside  = (float)($client->delivery_charge_inside  ?? 50);
    $chargeOutside = (float)($client->delivery_charge_outside ?? 100);
    $defaultShipId = (isset($shippingMethods) && $shippingMethods->count() > 0) ? $shippingMethods->first()->id : 'null';
    $productPrice  = (float)($product->sale_price ?? $product->regular_price ?? 0);
    $initQty       = max(1, (int)request('qty', 1));
@endphp

<div class="bg-gray-50/50 min-h-screen pb-16" x-data="checkoutApp()">

<script>
function checkoutApp() {
    return {
        shippingMethods: {!! json_encode($shippingMethods ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        shippingMethodId: {{ $defaultShipId }},
        area: 'inside',
        paymentMethod: 'cod',
        qty: {{ $initQty }},
        price: {{ $productPrice }},
        notes: '',

        get delivery() {
            if (this.shippingMethods && this.shippingMethods.length > 0) {
                let sm = this.shippingMethods.find(m => m.id == this.shippingMethodId);
                return sm ? parseFloat(sm.cost) : 0;
            }
            return this.area === 'inside' ? {{ $chargeInside }} : {{ $chargeOutside }};
        },
        get subtotal() { return this.qty * this.price; },

        couponCode: '', couponDiscount: 0, couponApplied: false, couponError: '',
        get total() { return this.subtotal + this.delivery - this.couponDiscount; },

        async applyCoupon() {
            if (!this.couponCode) { this.couponError = 'Enter a coupon code'; return; }
            this.couponError = '';
            try {
                const res = await fetch('{{ $couponUrl }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code: this.couponCode, client_id: {{ $client->id }}, subtotal: this.subtotal })
                });
                const d = await res.json();
                if (d.success) {
                    this.couponDiscount = d.discount;
                    this.couponApplied  = true;
                    this.couponError    = '';
                } else {
                    this.couponError    = d.message || 'Invalid coupon';
                    this.couponApplied  = false;
                    this.couponDiscount = 0;
                }
            } catch(e) {
                this.couponError = 'Network error. Try again.';
            }
        }
    };
}
</script>

    <div class="max-w-[1200px] mx-auto px-4 xl:px-8 pt-8">
        <form action="{{ $formAction }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 md:gap-12 lg:gap-16">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="qty" :value="qty">
            <input type="hidden" name="payment_method" :value="paymentMethod">
            <input type="hidden" name="shipping_method_id" :value="shippingMethodId">
            <input type="hidden" name="area" :value="area">
            <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
            <input type="hidden" name="notes" :value="notes">
            @if(request('variant'))
            <input type="hidden" name="attributes" value="{{ request('variant') }}">
            @endif

            {{-- Checkout Left Column --}}
            <div class="md:col-span-7">
                
                {{-- Contact --}}
                <div class="mb-8">
                    <h2 class="vg-section-title">Contact Information</h2>
                    <div class="space-y-3">
                        <input type="text" name="customer_name" required placeholder="Your full name *" class="vg-input">
                        <input type="text" name="customer_phone" required placeholder="Phone number (01XXXXXXXXX) *" class="vg-input">
                    </div>
                </div>

                {{-- Delivery --}}
                <div class="mb-8">
                    <h2 class="vg-section-title">Delivery Address</h2>
                    <div class="space-y-3">
                        <textarea name="shipping_address" required rows="3" placeholder="Full delivery address (District, Thana, Village / Area) *" class="vg-input resize-none"></textarea>
                        <textarea name="notes" x-model="notes" rows="2" placeholder="Order notes (optional)" class="vg-input resize-none"></textarea>
                    </div>
                </div>

                {{-- Shipping method --}}
                <div class="mb-10">
                    <h2 class="vg-section-title">Shipping method</h2>
                    
                    @if(isset($shippingMethods) && $shippingMethods->count() > 0)
                        <div class="border border-gray-200 rounded divide-y divide-gray-200 bg-white shadow-sm overflow-hidden mt-3 text-[13px]">
                            @foreach($shippingMethods as $method)
                            <label class="flex justify-between items-center p-4 cursor-pointer hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="_sm_temp" value="{{ $method->id }}" @change="shippingMethodId = {{ $method->id }}" class="w-4 h-4 text-primary focus:ring-primary border-gray-300" :checked="shippingMethodId == {{ $method->id }}">
                                    <span class="font-medium text-gray-700">{{ $method->name }}</span>
                                </div>
                                <span class="font-bold text-gray-800">{!! $method->cost > 0 ? '৳'.number_format($method->cost) : 'Free' !!}</span>
                            </label>
                            @endforeach
                        </div>
                    @else
                        <div class="border border-gray-200 rounded divide-y divide-gray-200 bg-white shadow-sm overflow-hidden mt-3 text-[13px]">
                            <label class="flex justify-between items-center p-4 cursor-pointer hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="_area_temp" value="inside" @change="area = 'inside'" class="w-4 h-4 text-primary focus:ring-primary border-gray-300" :checked="area === 'inside'">
                                    <span class="font-medium text-gray-700">Inside Dhaka</span>
                                </div>
                                <span class="font-bold text-gray-800">৳{{$client->delivery_charge_inside ?? 50}}</span>
                            </label>
                            <label class="flex justify-between items-center p-4 cursor-pointer hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="_area_temp" value="outside" @change="area = 'outside'" class="w-4 h-4 text-primary focus:ring-primary border-gray-300" :checked="area === 'outside'">
                                    <span class="font-medium text-gray-700">Outside Dhaka</span>
                                </div>
                                <span class="font-bold text-gray-800">৳{{$client->delivery_charge_outside ?? 100}}</span>
                            </label>
                        </div>
                    @endif
                </div>

                {{-- Payment --}}
                <div class="mb-10">
                    <h2 class="vg-section-title">Payment</h2>
                    <p class="text-[12px] text-gray-500 mb-4">All transactions are secure and encrypted.</p>

                    <div class="border border-gray-200 rounded divide-y divide-gray-200 bg-white shadow-sm overflow-hidden text-[13px]">
                        <label class="block cursor-pointer">
                            <div class="flex justify-between items-center p-4" :class="paymentMethod === 'cod' ? 'bg-orange-50/50' : 'hover:bg-gray-50'">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="_pmt" value="cod" @change="paymentMethod='cod'" class="w-4 h-4 text-primary focus:ring-primary border-gray-300" checked>
                                    <span class="font-medium text-gray-700">Cash on Delivery (COD)</span>
                                </div>
                            </div>
                            <div x-show="paymentMethod === 'cod'" class="p-6 bg-gray-50 text-gray-600 text-center border-t border-gray-200 text-[12px]">
                                Pay with cash upon delivery.
                            </div>
                        </label>
                    </div>

                    @if($client->show_terms_checkbox ?? false)
                    <div class="mt-6 mb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" required class="w-4 h-4 text-primary bg-white border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <span class="text-[12px] text-gray-500 font-medium">I have read and agree to the <a href="{{ $clean ? $baseUrl.'/terms-conditions' : route('shop.page.slug', [$client->slug, 'terms-conditions']) }}" class="text-primary hover:underline font-bold">Terms and Conditions</a></span>
                        </label>
                    </div>
                    @endif

                    <button type="submit" class="mt-6 w-full btn-primary !py-4 text-base shadow-sm">
                        Pay now
                    </button>
                    
                                        <div class="mt-6 flex flex-wrap justify-center gap-4 text-gray-400 text-xs font-bold border-t border-gray-200 pt-6">
                        @if(isset($pages) && count($pages) > 0)
                            @foreach($pages as $page)
                                <a href="{{ $clean ? $baseUrl.'/'.$page->slug : route('shop.page.slug', [$client->slug, $page->slug]) }}" class="hover:text-primary">{{ $page->title }}</a>
                            @endforeach
                        @else
                            <span class="opacity-50">© {{ date('Y') }} {{ $client->shop_name }}</span>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Checkout Right Column --}}
            <div class="md:col-span-5 relative">
                {{-- Gray sidebar effect on large screens --}}
                <div class="absolute inset-y-0 w-screen bg-[#fafafa] -right-[100vw] hidden md:block border-l border-gray-200 pl-8"></div>
                
                <div class="md:pl-8 md:pt-4 sticky top-6 z-10">
                    
                    <div class="flex items-center gap-4 mb-6">
                        <div class="relative w-16 h-16 border border-gray-200 bg-white rounded-lg flex items-center justify-center p-1 shrink-0">
                            @if($product->thumbnail)
                            <img src="{{ asset('storage/'.$product->thumbnail) }}" class="w-full h-full object-contain mix-blend-multiply">
                            @else
                            <i class="fas fa-box text-gray-300 text-2xl"></i>
                            @endif
                            <span class="absolute -top-2 -right-2 bg-gray-500/90 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center font-bold" x-text="qty"></span>
                        </div>
                        <div class="flex-[1]">
                            <h4 class="text-[13px] font-medium text-gray-700 line-clamp-2 leading-tight">{{ $product->name }}</h4>
                            @if(request('variant'))
                                <div class="text-[10px] text-primary mt-0.5 font-medium">{{ request('variant') }}</div>
                            @elseif(request('color') || request('size'))
                                <div class="text-[10px] text-gray-400 mt-0.5 uppercase">{{ request('color') }} {{ request('size') }}</div>
                            @endif
                        </div>
                        <div class="text-[13px] font-semibold text-dark shrink-0">
                            ৳<span x-text="(qty * price).toLocaleString()"></span>
                        </div>
                    </div>

                    {{-- Coupon Code --}}
                    <div class="flex gap-2 mb-6 border-b border-gray-200 pb-6">
                        <input type="text" x-model="couponCode" placeholder="Discount code" class="vg-input !bg-white focus:shadow shadow-sm h-11">
                        <button type="button" @click="applyCoupon()" class="bg-gray-200 text-gray-500 font-bold px-6 rounded-md hover:bg-gray-300 transition h-11 text-xs shrink-0">Apply</button>
                    </div>

                    {{-- Totals --}}
                    <div class="space-y-3 text-[13px] text-gray-600 mb-6">
                        <div class="flex justify-between items-center text-sm">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-800">৳<span x-text="subtotal.toLocaleString()"></span></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span>Shipping</span>
                            <span class="text-[11px] text-gray-500" x-show="delivery === 0">Enter shipping address</span>
                            <span class="font-medium text-gray-800" x-show="delivery > 0">৳<span x-text="delivery"></span></span>
                        </div>
                    </div>

                    {{-- Grand Total --}}
                    <div class="flex justify-between items-center border-t border-gray-200 pt-5 mt-4">
                        <span class="text-base text-gray-800 font-medium">Total</span>
                        <div class="text-2xl font-bold text-dark flex items-end gap-2">
                            <span class="text-[10px] text-gray-400 font-normal mb-1 tracking-wider uppercase">BDT</span>
                            <span>৳<span x-text="total.toLocaleString()"></span></span>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection
