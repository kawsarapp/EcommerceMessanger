@extends('shop.layout')

@section('title', 'Checkout - ' . $client->shop_name)

@section('content')
<main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-20 md:mb-0"
      x-data="checkoutData()">

    <nav class="flex text-sm text-gray-500 mb-6">
        <a href="{{ route('shop.show', $client->slug ?? '') }}" class="hover:text-primary transition">Home</a>
        <span class="mx-2 text-gray-300">/</span>
        <a href="{{ $client->custom_domain ? route('shop.product.custom', $product->slug) : route('shop.product.details', [$client->slug, $product->slug]) }}" class="hover:text-primary transition">{{ $product->name }}</a>
        <span class="mx-2 text-gray-300">/</span>
        <span class="text-gray-900 font-medium">Checkout</span>
    </nav>

    <h1 class="text-3xl font-bold font-heading text-gray-900 mb-8">Secure Checkout</h1>

    <form action="{{ $client->custom_domain ? route('shop.checkout.process.custom') : route('shop.checkout.process', $client->slug) }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        @csrf
        
        {{-- Hidden Inputs --}}
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="color" value="{{ request('color') }}">
        <input type="hidden" name="size" value="{{ request('size') }}">
        <input type="hidden" name="quantity" :value="qty">
        <input type="hidden" name="coupon_code" :value="appliedCoupon">

        {{-- Left Column: Customer Details --}}
        <div class="lg:col-span-7 space-y-6">
            
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-primary"></i> Shipping Information
                </h2>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" required placeholder="e.g. Kawsar Ahmmed" 
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="customer_phone" required placeholder="017XXXXXXXX" minlength="11" maxlength="11"
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Full Address <span class="text-red-500">*</span></label>
                        <textarea name="shipping_address" required placeholder="House number, street, area, city..." rows="3"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">Delivery Area <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="delivery_area" value="inside" x-model="deliveryArea" class="peer hidden">
                                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-primary peer-checked:bg-blue-50 transition">
                                    <span class="block font-bold text-gray-800">Inside Dhaka</span>
                                    <span class="text-sm text-gray-500">৳{{ $client->delivery_charge_inside }}</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="delivery_area" value="outside" x-model="deliveryArea" class="peer hidden">
                                <div class="border-2 border-gray-200 rounded-xl p-4 text-center peer-checked:border-primary peer-checked:bg-blue-50 transition">
                                    <span class="block font-bold text-gray-800">Outside Dhaka</span>
                                    <span class="text-sm text-gray-500">৳{{ $client->delivery_charge_outside }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Order Note (Optional)</label>
                        <textarea name="notes" placeholder="Any special instructions..." rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Order Summary & Coupon --}}
        <div class="lg:col-span-5">
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Order Summary</h2>

                {{-- Product Info --}}
                <div class="flex gap-4 mb-6">
                    <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-20 h-20 object-cover rounded-lg border border-gray-100">
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 line-clamp-2 text-sm">{{ $product->name }}</h3>
                        <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                            @if(request('color')) <p>Color: <span class="font-semibold">{{ request('color') }}</span></p> @endif
                            @if(request('size')) <p>Size: <span class="font-semibold">{{ request('size') }}</span></p> @endif
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="font-bold text-primary">৳<span x-text="unitPrice"></span></span>
                            
                            {{-- Qty Selector --}}
                            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                <button type="button" @click="if(qty > 1) qty--" class="px-2 py-1 bg-gray-50 hover:bg-gray-100 text-gray-600"><i class="fas fa-minus text-xs"></i></button>
                                <span class="px-3 py-1 text-sm font-bold bg-white" x-text="qty"></span>
                                <button type="button" @click="qty++" class="px-2 py-1 bg-gray-50 hover:bg-gray-100 text-gray-600"><i class="fas fa-plus text-xs"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Coupon Section --}}
                <div class="mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Have a coupon code?</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="couponInput" :disabled="appliedCoupon !== null" placeholder="Enter code here" 
                               class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 focus:ring-primary outline-none uppercase text-sm font-bold">
                        
                        <button type="button" @click="applyCoupon()" x-show="appliedCoupon === null"
                                class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-black transition">Apply</button>
                        
                        <button type="button" @click="removeCoupon()" x-show="appliedCoupon !== null"
                                class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-600 transition"><i class="fas fa-times"></i></button>
                    </div>
                    <p x-show="couponMessage" class="text-xs font-bold mt-2" :class="couponError ? 'text-red-500' : 'text-green-600'" x-text="couponMessage"></p>
                </div>

                {{-- Calculations --}}
                <div class="space-y-3 text-sm border-b border-gray-100 pb-4 mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-bold text-gray-800">৳<span x-text="subtotal"></span></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Delivery Charge</span>
                        <span class="font-bold text-gray-800">৳<span x-text="shipping"></span></span>
                    </div>
                    <div class="flex justify-between text-green-600" x-show="discount > 0" x-cloak>
                        <span>Discount (<span x-text="appliedCoupon"></span>)</span>
                        <span class="font-bold">- ৳<span x-text="discount"></span></span>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-6">
                    <span class="text-lg font-bold text-gray-900">Total Payable</span>
                    <span class="text-2xl font-extrabold text-primary">৳<span x-text="total"></span></span>
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-primaryDark text-white py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition shadow-xl shadow-blue-500/30 transform hover:-translate-y-1">
                    <i class="fas fa-check-circle"></i> Confirm Order
                </button>
                <p class="text-center text-xs text-gray-400 mt-4"><i class="fas fa-lock"></i> Cash on Delivery available.</p>
            </div>
        </div>
    </form>
</main>

<script>
    function checkoutData() {
        return {
            qty: {{ request('qty', 1) }},
            unitPrice: {{ $product->sale_price ?? $product->regular_price }},
            deliveryArea: 'inside',
            insideCharge: {{ $client->delivery_charge_inside ?? 80 }},
            outsideCharge: {{ $client->delivery_charge_outside ?? 150 }},
            
            couponInput: '',
            appliedCoupon: null,
            discount: 0,
            couponMessage: '',
            couponError: false,

            get subtotal() { return this.qty * this.unitPrice; },
            get shipping() { return this.deliveryArea === 'inside' ? this.insideCharge : this.outsideCharge; },
            get total() { return (this.subtotal + this.shipping) - this.discount; },

            applyCoupon() {
                if (!this.couponInput.trim()) return;
                
                fetch('{{ route('shop.apply-coupon') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        code: this.couponInput,
                        client_id: {{ $client->id }},
                        subtotal: this.subtotal
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.appliedCoupon = this.couponInput.toUpperCase();
                        this.discount = data.discount;
                        this.couponError = false;
                        this.couponMessage = data.message;
                    } else {
                        this.couponError = true;
                        this.couponMessage = data.message;
                    }
                })
                .catch(() => {
                    this.couponError = true;
                    this.couponMessage = "Something went wrong!";
                });
            },

            removeCoupon() {
                this.appliedCoupon = null;
                this.couponInput = '';
                this.discount = 0;
                this.couponMessage = '';
                this.couponError = false;
            }
        }
    }
</script>
@endsection