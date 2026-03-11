@extends('shop.themes.electronics.layout')

@section('title', 'Secure Checkout | ' . $client->shop_name)

@section('content')

@php
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
@endphp

<main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-20 md:mb-12" x-data="checkoutData()">

    {{-- Tech Style Breadcrumb --}}
    <nav class="flex text-xs md:text-sm text-slate-500 mb-8 font-mono tracking-wide">
        <a href="{{ $baseUrl }}" class="hover:text-primary transition uppercase">Store</a>
        <span class="mx-3 text-slate-300">/</span>
        <a href="{{ $cleanDomain ? $baseUrl.'/product/'.$product->slug : route('shop.product.details', [$client->slug, $product->slug]) }}" class="hover:text-primary transition uppercase truncate max-w-[150px]">{{ $product->name }}</a>
        <span class="mx-3 text-slate-300">/</span>
        <span class="text-slate-900 font-bold uppercase">Checkout</span>
    </nav>

    <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        {{-- 🔥 Left: Customer Information Form --}}
        <div class="w-full lg:w-3/5 bg-white p-6 md:p-8 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-xl font-bold font-heading text-slate-900 mb-6 flex items-center gap-2 border-b border-slate-100 pb-4">
                <i class="fas fa-shipping-fast text-primary"></i> Shipping Details
            </h2>

            <form action="{{ $cleanDomain ? $baseUrl.'/checkout/'.$product->slug : route('shop.checkout.submit', [$client->slug, $product->slug]) }}" method="POST" id="checkout-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="color" value="{{ request('color') }}">
                <input type="hidden" name="size" value="{{ request('size') }}">
                <input type="hidden" name="coupon_code" :value="appliedCoupon">

                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" required placeholder="e.g. John Doe" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:bg-white focus:ring-1 focus:ring-primary focus:border-primary outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2">Phone Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="customer_phone" required placeholder="01XXXXXXXXX" pattern="[0-9]{11}" title="Must be 11 digits"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm font-mono focus:bg-white focus:ring-1 focus:ring-primary focus:border-primary outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2">Delivery Area <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="delivery_area" value="inside" x-model="deliveryArea" class="peer hidden" required>
                                <div class="border-2 border-slate-200 rounded-lg p-4 text-center peer-checked:border-primary peer-checked:bg-blue-50 transition hover:border-slate-300">
                                    <span class="block text-sm font-bold text-slate-800">Inside Dhaka</span>
                                    <span class="text-xs text-slate-500 font-mono">৳{{ $client->delivery_charge_inside }}</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="delivery_area" value="outside" x-model="deliveryArea" class="peer hidden" required>
                                <div class="border-2 border-slate-200 rounded-lg p-4 text-center peer-checked:border-primary peer-checked:bg-blue-50 transition hover:border-slate-300">
                                    <span class="block text-sm font-bold text-slate-800">Outside Dhaka</span>
                                    <span class="text-xs text-slate-500 font-mono">৳{{ $client->delivery_charge_outside }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2">Full Address <span class="text-red-500">*</span></label>
                        <textarea name="shipping_address" required rows="3" placeholder="House/Flat No, Road, Area..." 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:bg-white focus:ring-1 focus:ring-primary focus:border-primary outline-none transition"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2">Order Notes (Optional)</label>
                        <input type="text" name="notes" placeholder="Any specific instructions?" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:bg-white focus:ring-1 focus:ring-primary focus:border-primary outline-none transition">
                    </div>
                </div>
            </form>
        </div>

        {{-- 🔥 Right: Order Summary (Tech Box) --}}
        <div class="w-full lg:w-2/5 sticky top-28 space-y-6">
            <div class="bg-slate-900 text-white p-6 md:p-8 rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-slate-800">
                <h2 class="text-lg font-bold font-heading mb-6 border-b border-slate-700 pb-4 flex items-center gap-2 uppercase tracking-widest">
                    <i class="fas fa-shopping-bag text-primary"></i> Order Summary
                </h2>
                
                <div class="flex items-center gap-4 mb-6 bg-slate-800 p-3 rounded-lg border border-slate-700">
                    <div class="w-16 h-16 bg-white rounded-md overflow-hidden flex-shrink-0 p-1">
                        <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-full h-full object-contain mix-blend-multiply">
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-sm leading-tight line-clamp-2 text-slate-200">{{ $product->name }}</h4>
                        <div class="text-xs text-slate-400 mt-1 flex gap-2 font-mono">
                            @if(request('color')) <span>Color: {{ request('color') }}</span> @endif
                            @if(request('size')) <span>Size: {{ request('size') }}</span> @endif
                        </div>
                    </div>
                </div>

                {{-- Quantity Selector --}}
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-slate-700">
                    <span class="text-sm font-medium text-slate-300">Quantity</span>
                    <div class="flex items-center bg-slate-800 rounded-lg border border-slate-700 p-1">
                        <button type="button" @click="if(qty > 1) qty--" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-700 rounded transition"><i class="fas fa-minus text-xs"></i></button>
                        <input type="number" form="checkout-form" name="quantity" x-model="qty" class="w-10 text-center bg-transparent border-none text-white font-mono font-bold focus:ring-0 p-0 text-sm" readonly>
                        <button type="button" @click="qty++" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-700 rounded transition"><i class="fas fa-plus text-xs"></i></button>
                    </div>
                </div>

                {{-- Coupon Box --}}
                <div class="mb-6 pb-6 border-b border-slate-700">
                    <div class="flex gap-2" x-show="!appliedCoupon">
                        <input type="text" x-model="couponInput" placeholder="Discount Code" class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-primary outline-none transition font-mono uppercase">
                        <button type="button" @click="applyCoupon" class="bg-primary hover:bg-primaryDark text-white px-4 rounded-lg text-sm font-bold transition">Apply</button>
                    </div>
                    <div x-show="appliedCoupon" class="flex justify-between items-center bg-green-500/10 border border-green-500/20 p-3 rounded-lg" x-cloak>
                        <span class="text-green-400 text-sm font-bold font-mono"><i class="fas fa-check-circle mr-1"></i> <span x-text="appliedCoupon"></span> applied!</span>
                        <button type="button" @click="removeCoupon" class="text-slate-400 hover:text-red-400"><i class="fas fa-times"></i></button>
                    </div>
                    <p x-show="couponMessage" class="text-xs mt-2" :class="couponError ? 'text-red-400' : 'text-green-400'" x-text="couponMessage"></p>
                </div>

                {{-- Calculation --}}
                <div class="space-y-3 text-sm text-slate-300 mb-6 font-mono">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-bold text-white" x-text="'৳' + subtotal.toLocaleString()"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Delivery Fee</span>
                        <span class="font-bold text-white" x-text="deliveryFee > 0 ? '৳' + deliveryFee : 'Select Area'"></span>
                    </div>
                    <div class="flex justify-between text-green-400" x-show="discount > 0" x-cloak>
                        <span>Discount</span>
                        <span class="font-bold" x-text="'- ৳' + discount.toLocaleString()"></span>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-slate-700 mb-8">
                    <span class="text-sm font-bold uppercase tracking-widest text-slate-400">Total</span>
                    <span class="text-3xl font-black text-white font-mono tracking-tighter" x-text="'৳' + total.toLocaleString()"></span>
                </div>

                <button type="submit" form="checkout-form" class="w-full bg-primary hover:bg-primaryDark text-white py-4 rounded-xl font-bold text-lg text-center flex items-center justify-center gap-3 transition shadow-[0_0_20px_rgba(14,165,233,0.3)] transform hover:-translate-y-1 uppercase tracking-widest">
                    <i class="fas fa-lock"></i> Confirm Order
                </button>
            </div>
            
            {{-- Trust Badges --}}
            <div class="flex justify-center gap-6 text-slate-400 text-3xl">
                <i class="fas fa-shield-check tooltip" title="Safe & Secure"></i>
                <i class="fas fa-truck-fast tooltip" title="Quick Delivery"></i>
                <i class="fas fa-headset tooltip" title="24/7 Support"></i>
            </div>
        </div>
    </div>
</main>

<script>
    function checkoutData() {
        return {
            qty: {{ request('qty', 1) }},
            unitPrice: {{ $product->sale_price ?? $product->regular_price }},
            deliveryArea: '',
            feeInside: {{ $client->delivery_charge_inside }},
            feeOutside: {{ $client->delivery_charge_outside }},
            couponInput: '',
            appliedCoupon: null,
            discount: 0,
            couponMessage: '',
            couponError: false,

            get subtotal() { return this.qty * this.unitPrice; },
            get deliveryFee() {
                if (this.deliveryArea === 'inside') return this.feeInside;
                if (this.deliveryArea === 'outside') return this.feeOutside;
                return 0;
            },
            get total() { return (this.subtotal + this.deliveryFee) - this.discount; },

            applyCoupon() {
                if(!this.couponInput) return;
                fetch('{{ route('shop.apply-coupon') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ code: this.couponInput, client_id: {{ $client->id }}, subtotal: this.subtotal })
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
                    this.couponMessage = "Connection Error!";
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