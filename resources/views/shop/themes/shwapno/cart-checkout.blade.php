@extends('shop.themes.shwapno.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php
    $clean   = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain ?? '', '/'));
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);
    $cartUrl = $clean ? $baseUrl.'/cart' : route('shop.cart', $client->slug);
    $formAction = $clean
        ? $baseUrl.'/cart/checkout/process'
        : route('shop.cart.checkout.process', $client->slug);
    $couponUrl = $clean
        ? $baseUrl.'/apply-coupon'
        : route('shop.apply-coupon.sub', $client->slug);

    $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
@endphp

<style>
    .vg-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #4b5563; transition: border 0.3s; outline: none; background: #fff; }
    .vg-input:focus { border-color: var(--tw-color-primary); box-shadow: 0 0 0 2px color-mix(in srgb, var(--tw-color-primary) 15%, transparent); }
    .vg-label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 6px; }
    .vg-section { background: #fff; border: 1px solid #f1f5f9; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
    .vg-section-title { font-size: 16px; font-weight: 700; color: #222; margin-bottom: 18px; }
</style>

<div class="bg-gray-50/50 min-h-screen pb-20" x-data="checkoutApp()">

<script>
function checkoutApp() {
    return {
        area: 'inside',
        paymentMethod: 'cod',
        shippingMethods: {!! json_encode($shippingMethods ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        shippingMethodId: {{ (isset($shippingMethods) && $shippingMethods->count() > 0) ? $shippingMethods->first()->id : 'null' }},
        subtotal: {{ $subtotal }},
        notes: '',
        couponCode: '', couponDiscount: 0, couponApplied: false, couponMsg: '', couponOk: false,

        get delivery() {
            if (this.shippingMethods && this.shippingMethods.length > 0) {
                let sm = this.shippingMethods.find(m => m.id == this.shippingMethodId);
                return sm ? parseFloat(sm.cost) : 0;
            }
            return this.area === 'inside' ? {{ $client->delivery_charge_inside ?? 50 }} : {{ $client->delivery_charge_outside ?? 100 }};
        },
        get total() { return this.subtotal + this.delivery - this.couponDiscount; },

        async applyCoupon() {
            if (!this.couponCode) return;
            this.couponMsg = ''; this.couponOk = false;
            const res = await fetch('{{ $couponUrl }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ code: this.couponCode, client_id: {{ $client->id }}, subtotal: this.subtotal })
            });
            const d = await res.json();
            if (d.success) {
                this.couponDiscount = d.discount; this.couponApplied = true;
                this.couponMsg = d.message; this.couponOk = true;
            } else {
                this.couponMsg = d.message || 'Invalid coupon'; this.couponApplied = false; this.couponDiscount = 0;
            }
        }
    };
}
</script>

<div class="max-w-[1200px] mx-auto px-4 xl:px-8 pt-8">

    {{-- Back to cart --}}
    <a href="{{ $cartUrl }}" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-primary transition mb-6">
        <i class="fas fa-arrow-left text-xs"></i> Back to Cart
    </a>

    <form action="{{ $formAction }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
        @csrf
        <input type="hidden" name="payment_method" :value="paymentMethod">
        <input type="hidden" name="shipping_method_id" :value="shippingMethodId">
        <input type="hidden" name="area" :value="area">
        <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
        <input type="hidden" name="notes" :value="notes">

        {{-- Left Column --}}
        <div class="lg:col-span-7 space-y-5">

            {{-- Contact --}}
            <div class="vg-section">
                <h2 class="vg-section-title"><i class="far fa-user text-primary mr-2"></i>Contact Information</h2>
                <div class="space-y-3">
                    <div>
                        <label class="vg-label">Full Name *</label>
                        <input type="text" name="customer_name" required placeholder="Your full name" class="vg-input">
                    </div>
                    <div>
                        <label class="vg-label">Phone Number *</label>
                        <input type="text" name="customer_phone" required placeholder="01XXXXXXXXX" class="vg-input">
                    </div>
                </div>
            </div>

            {{-- Delivery Address --}}
            <div class="vg-section">
                <h2 class="vg-section-title"><i class="fas fa-map-marker-alt text-primary mr-2"></i>Delivery Address</h2>
                <div class="space-y-3">
                    <div>
                        <label class="vg-label">Full Address *</label>
                        <textarea name="shipping_address" required rows="3"
                            placeholder="House, Road, Area, District..."
                            class="vg-input resize-none"></textarea>
                    </div>
                    <div>
                        <label class="vg-label">Order Notes (optional)</label>
                        <textarea name="notes" x-model="notes" rows="2"
                            placeholder="Special instructions..."
                            class="vg-input resize-none"></textarea>
                    </div>
                </div>
            </div>

            {{-- Shipping Method --}}
            <div class="vg-section">
                <h2 class="vg-section-title"><i class="fas fa-truck text-primary mr-2"></i>Shipping Method</h2>
                @if(isset($shippingMethods) && $shippingMethods->count() > 0)
                <div class="space-y-2">
                    @foreach($shippingMethods as $method)
                    <label class="flex justify-between items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-primary/40 hover:bg-primary/5 transition"
                           :class="shippingMethodId == {{ $method->id }} ? 'border-primary bg-primary/5' : ''">
                        <div class="flex items-center gap-3">
                            <input type="radio" @change="shippingMethodId = {{ $method->id }}"
                                   :checked="shippingMethodId == {{ $method->id }}"
                                   class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                            <span class="text-sm font-medium text-gray-700">{{ $method->name }}</span>
                        </div>
                        <span class="text-sm font-bold text-dark">{!! $method->cost > 0 ? '?'.number_format($method->cost) : '<span class="text-green-500">Free</span>' !!}</span>
                    </label>
                    @endforeach
                </div>
                @else
                <div class="space-y-2">
                    <label class="flex justify-between items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-primary/40 transition"
                           :class="area === 'inside' ? 'border-primary bg-primary/5' : ''">
                        <div class="flex items-center gap-3">
                            <input type="radio" value="inside" @change="area='inside'" :checked="area==='inside'" class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                            <span class="text-sm font-medium text-gray-700">Inside Dhaka</span>
                        </div>
                        <span class="text-sm font-bold text-dark">?{{ $client->delivery_charge_inside ?? 50 }}</span>
                    </label>
                    <label class="flex justify-between items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-primary/40 transition"
                           :class="area === 'outside' ? 'border-primary bg-primary/5' : ''">
                        <div class="flex items-center gap-3">
                            <input type="radio" value="outside" @change="area='outside'" :checked="area==='outside'" class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                            <span class="text-sm font-medium text-gray-700">Outside Dhaka</span>
                        </div>
                        <span class="text-sm font-bold text-dark">?{{ $client->delivery_charge_outside ?? 100 }}</span>
                    </label>
                </div>
                @endif
            </div>

            {{-- Payment --}}
            <div class="vg-section">
                <h2 class="vg-section-title"><i class="fas fa-credit-card text-primary mr-2"></i>Payment Method</h2>
                <label class="flex items-center gap-3 p-4 border border-primary bg-primary/5 rounded-xl cursor-pointer">
                    <input type="radio" name="_pmt" value="cod" @change="paymentMethod='cod'" class="w-4 h-4 text-primary focus:ring-primary border-gray-300" checked>
                    <div>
                        <span class="text-sm font-semibold text-dark">Cash on Delivery (COD)</span>
                        <p class="text-xs text-gray-400 mt-0.5">Pay when you receive your order</p>
                    </div>
                </label>

                @if($client->show_terms_checkbox ?? false)
                <label class="flex items-start gap-2 cursor-pointer mt-4">
                    <input type="checkbox" required class="w-4 h-4 text-primary bg-white border-gray-300 rounded focus:ring-primary mt-0.5">
                    <span class="text-xs text-gray-500">I agree to the <a href="{{ $clean ? $baseUrl.'/terms-conditions' : route('shop.page.slug', [$client->slug, 'terms-conditions']) }}" class="text-primary hover:underline font-bold">Terms and Conditions</a></span>
                </label>
                @endif

                <button type="submit"
                        class="mt-5 w-full btn-primary rounded-xl !py-4 text-base shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                    <i class="fas fa-lock text-sm"></i> Place Order — ?<span x-text="Math.round(total).toLocaleString()"></span>
                </button>
            </div>
        </div>

        {{-- Right Column: Order Summary --}}
        <div class="lg:col-span-5">
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm sticky top-6">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-dark">Order Summary
                        <span class="text-gray-400 font-normal">({{ count($cart) }} items)</span>
                    </h3>
                </div>
                <div class="p-6">
                    {{-- Items List --}}
                    <div class="space-y-4 mb-5 max-h-[280px] overflow-y-auto pr-1">
                        @foreach($cart as $item)
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gray-50 rounded-lg border border-gray-100 flex items-center justify-center p-1 shrink-0 relative">
                                @if($item['thumbnail'])
                                <img src="{{ asset('storage/'.$item['thumbnail']) }}" class="w-full h-full object-contain mix-blend-multiply">
                                @else
                                <i class="fas fa-box text-gray-300"></i>
                                @endif
                                <span class="absolute -top-1.5 -right-1.5 bg-gray-500 text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center font-bold">{{ $item['qty'] }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-dark line-clamp-1">{{ $item['name'] }}</p>
                                @if($item['variant'])
                                <p class="text-[10px] text-primary">{{ $item['variant'] }}</p>
                                @endif
                            </div>
                            <span class="text-xs font-bold text-dark shrink-0">?{{ number_format($item['price'] * $item['qty']) }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Coupon --}}
                    <div class="flex gap-2 mb-5 border-t border-gray-100 pt-5">
                        <input type="text" x-model="couponCode" placeholder="Discount code" class="vg-input !rounded-lg !py-2.5 text-xs">
                        <button type="button" @click="applyCoupon()"
                                class="bg-dark text-white font-bold px-5 rounded-lg hover:bg-primary transition text-xs shrink-0">Apply</button>
                    </div>
                    <p x-show="couponMsg" class="text-xs mb-4 -mt-3" :class="couponOk ? 'text-green-500' : 'text-red-400'" x-text="couponMsg"></p>

                    {{-- Totals --}}
                    <div class="space-y-2.5 text-sm border-t border-gray-100 pt-4">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span class="font-medium text-dark">?{{ number_format($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Shipping</span>
                            <span class="font-medium text-dark">?<span x-text="delivery.toLocaleString()"></span></span>
                        </div>
                        <div x-show="couponDiscount > 0" class="flex justify-between text-green-500">
                            <span>Coupon discount</span>
                            <span class="font-medium">- ?<span x-text="couponDiscount.toLocaleString()"></span></span>
                        </div>
                        <div class="flex justify-between font-bold text-dark text-base pt-2 border-t border-gray-100">
                            <span>Total</span>
                            <span>?<span x-text="Math.round(total).toLocaleString()"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
@endsection

