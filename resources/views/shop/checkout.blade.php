@extends('shop.layout')

@section('title', 'Checkout - ' . $client->shop_name)

@section('content')

@php
    // 🔥 Custom Domain Clean URL Logic
    $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
    $baseUrl = $cleanDomain ? 'https://' . $cleanDomain : route('shop.show', $client->slug);
@endphp

<main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 mb-20 md:mb-0" x-data="checkoutData()">

    {{-- Breadcrumb --}}
    <nav class="flex text-sm text-gray-500 mb-6">
        <a href="{{ $baseUrl }}" class="hover:text-primary transition">Home</a>
        <span class="mx-2 text-gray-300">/</span>
        <a href="{{ $cleanDomain ? $baseUrl.'/product/'.$product->slug : route('shop.product.details', [$client->slug, $product->slug]) }}" class="hover:text-primary transition">{{ $product->name }}</a>
        <span class="mx-2 text-gray-300">/</span>
        <span class="text-gray-900 font-medium">Checkout</span>
    </nav>

    {{-- 🔥 NEW: Checkout Progress Bar --}}
    <div class="max-w-3xl mx-auto mb-10 hidden sm:block">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full z-0"></div>
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-1/2 h-1 bg-primary rounded-full z-0"></div>
            
            <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold shadow-md"><i class="fas fa-check"></i></div>
                <span class="text-xs font-bold text-gray-900">Cart</span>
            </div>
            <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold shadow-md ring-4 ring-primary/20">2</div>
                <span class="text-xs font-bold text-primary">Details</span>
            </div>
            <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold">3</div>
                <span class="text-xs font-bold text-gray-400">Complete</span>
            </div>
        </div>
    </div>

    <h1 class="text-2xl md:text-3xl font-bold font-heading text-gray-900 mb-8">Secure Checkout</h1>

    {{-- Form Starts Here --}}
    <form action="{{ $cleanDomain ? $baseUrl.'/checkout/process' : route('shop.checkout.process', $client->slug) }}" method="POST" @submit="isSubmitting = true" class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
        @csrf
        
        {{-- Hidden Inputs --}}
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="color" value="{{ request('color') }}">
        <input type="hidden" name="size" value="{{ request('size') }}">
        <input type="hidden" name="quantity" :value="qty">
        <input type="hidden" name="coupon_code" :value="appliedCoupon">

        {{-- Left Column: Customer Details --}}
        <div class="lg:col-span-7 space-y-6">
            
            <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>
                
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-blue-50 text-primary flex items-center justify-center text-sm"><i class="fas fa-map-marker-alt"></i></span> 
                    Shipping Information
                </h2>

                <div class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" required placeholder="e.g. Kawsar Ahmmed" 
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="customer_phone" required placeholder="017XXXXXXXX" minlength="11" maxlength="11"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Full Address <span class="text-red-500">*</span></label>
                        <textarea name="shipping_address" required placeholder="House number, street, area, city..." rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition"></textarea>
                    </div>

                    <div class="pt-2">
                        <label class="block text-sm font-bold text-gray-700 mb-3">Delivery Area <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="delivery_area" value="inside" x-model="deliveryArea" class="peer hidden">
                                <div class="border border-gray-200 rounded-xl p-4 text-center peer-checked:border-primary peer-checked:bg-blue-50 transition relative overflow-hidden">
                                    <i class="fas fa-check-circle absolute top-2 right-2 text-primary opacity-0 peer-checked:opacity-100 transition"></i>
                                    <span class="block font-bold text-gray-800">Inside Dhaka</span>
                                    <span class="text-sm text-gray-500">৳{{ $client->delivery_charge_inside }}</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="delivery_area" value="outside" x-model="deliveryArea" class="peer hidden">
                                <div class="border border-gray-200 rounded-xl p-4 text-center peer-checked:border-primary peer-checked:bg-blue-50 transition relative overflow-hidden">
                                    <i class="fas fa-check-circle absolute top-2 right-2 text-primary opacity-0 peer-checked:opacity-100 transition"></i>
                                    <span class="block font-bold text-gray-800">Outside Dhaka</span>
                                    <span class="text-sm text-gray-500">৳{{ $client->delivery_charge_outside }}</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 🔥 NEW: Payment Method Selector --}}
            <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-sm"><i class="fas fa-wallet"></i></span> 
                    Payment Method
                </h2>

                <label class="cursor-pointer block">
                    <input type="radio" checked class="peer hidden">
                    <div class="border-2 border-primary bg-blue-50/50 rounded-xl p-4 flex items-center justify-between transition">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-primary"><i class="fas fa-truck"></i></div>
                            <div>
                                <span class="block font-bold text-gray-900 text-lg">Cash on Delivery</span>
                                <span class="text-xs text-gray-500">Pay when you receive the product.</span>
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-primary text-xl"></i>
                    </div>
                </label>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <label class="block text-sm font-bold text-gray-700 mb-1">Order Note (Optional)</label>
                <textarea name="notes" placeholder="Any special instructions for the delivery boy..." rows="2"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition"></textarea>
            </div>

        </div>

        {{-- Right Column: Order Summary & Coupon --}}
        <div class="lg:col-span-5">
            <div class="bg-white p-6 md:p-8 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 sticky top-24">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Order Summary</h2>

                {{-- Product Info --}}
                <div class="flex gap-4 mb-6">
                    <img src="{{ asset('storage/' . $product->thumbnail) }}" class="w-20 h-20 object-cover rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex-1 flex flex-col">
                        <h3 class="font-bold text-gray-800 line-clamp-2 text-sm leading-snug">{{ $product->name }}</h3>
                        <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                            @if(request('color')) <p>Color: <span class="font-bold text-gray-700">{{ request('color') }}</span></p> @endif
                            @if(request('size')) <p>Size: <span class="font-bold text-gray-700">{{ request('size') }}</span></p> @endif
                        </div>
                        <div class="flex items-end justify-between mt-auto">
                            <span class="font-extrabold text-primary text-lg">৳<span x-text="unitPrice"></span></span>
                            
                            {{-- Qty Selector --}}
                            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
                                <button type="button" @click="if(qty > 1) qty--" class="w-8 h-8 flex items-center justify-center hover:bg-gray-200 text-gray-600 transition"><i class="fas fa-minus text-xs"></i></button>
                                <span class="w-8 text-center text-sm font-bold bg-white h-8 flex items-center justify-center border-x border-gray-200" x-text="qty"></span>
                                <button type="button" @click="qty++" class="w-8 h-8 flex items-center justify-center hover:bg-gray-200 text-gray-600 transition"><i class="fas fa-plus text-xs"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Coupon Section --}}
                <div class="mb-6 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-2"><i class="fas fa-ticket-alt text-primary mr-1"></i> Have a coupon code?</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="couponInput" :disabled="appliedCoupon !== null" placeholder="Enter code here" 
                               class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none uppercase text-sm font-bold shadow-sm transition">
                        
                        <button type="button" @click="applyCoupon()" x-show="appliedCoupon === null"
                                class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-black transition shadow-md">Apply</button>
                        
                        <button type="button" @click="removeCoupon()" x-show="appliedCoupon !== null"
                                class="bg-red-50 text-red-500 border border-red-200 px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-red-100 transition tooltip" title="Remove Coupon"><i class="fas fa-times"></i></button>
                    </div>
                    <p x-show="couponMessage" class="text-xs font-bold mt-2 flex items-center gap-1" :class="couponError ? 'text-red-500' : 'text-green-600'">
                        <i class="fas" :class="couponError ? 'fa-exclamation-circle' : 'fa-check-circle'" x-show="couponMessage"></i>
                        <span x-text="couponMessage"></span>
                    </p>
                </div>

                {{-- Calculations --}}
                <div class="space-y-3 text-sm border-b border-dashed border-gray-200 pb-4 mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal (<span x-text="qty"></span> items)</span>
                        <span class="font-bold text-gray-800">৳<span x-text="subtotal"></span></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Delivery Charge</span>
                        <span class="font-bold text-gray-800">৳<span x-text="shipping"></span></span>
                    </div>
                    <div class="flex justify-between text-green-600 bg-green-50 px-2 py-1 rounded-md -mx-2" x-show="discount > 0" x-cloak>
                        <span class="font-bold">Discount (<span x-text="appliedCoupon"></span>)</span>
                        <span class="font-bold">- ৳<span x-text="discount"></span></span>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-6">
                    <span class="text-lg font-bold text-gray-900">Total Payable</span>
                    <span class="text-3xl font-extrabold text-primary tracking-tight">৳<span x-text="total"></span></span>
                </div>

                {{-- 🔥 NEW: Dynamic Delivery Date --}}
                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-primary shadow-sm"><i class="far fa-calendar-check"></i></div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Estimated Delivery</p>
                        <p class="text-sm font-bold text-gray-900" x-text="'Arrives by ' + expectedDelivery()"></p>
                    </div>
                </div>

                {{-- Submit Button with Loading State --}}
                <button type="submit" :disabled="isSubmitting" 
                        class="w-full bg-primary hover:bg-primaryDark text-white py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition shadow-xl shadow-blue-500/30 disabled:opacity-70 disabled:cursor-not-allowed group">
                    
                    <span x-show="!isSubmitting" class="flex items-center gap-2 transform group-hover:-translate-y-0.5 transition"><i class="fas fa-check-circle"></i> Confirm Order</span>
                    
                    <span x-show="isSubmitting" class="flex items-center gap-2" x-cloak>
                        <i class="fas fa-spinner fa-spin"></i> Processing...
                    </span>
                </button>

                {{-- Trust Badges --}}
                <div class="mt-6 flex flex-wrap items-center justify-center gap-4 text-gray-400">
                    <div class="flex items-center gap-1.5 text-xs font-bold tooltip" title="256-bit SSL Encryption"><i class="fas fa-lock text-gray-300"></i> Secure Checkout</div>
                    <div class="w-1 h-1 bg-gray-300 rounded-full"></div>
                    <div class="flex items-center gap-1.5 text-xs font-bold tooltip" title="100% Original Products"><i class="fas fa-medal text-gray-300"></i> Quality Verified</div>
                </div>

            </div>
        </div>
    </form>
</main>

<script>
    function checkoutData() {
        return {
            isSubmitting: false, // 🔥 NEW: Prevents double clicks
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

            // 🔥 NEW: Dynamic Expected Delivery Date
            expectedDelivery() {
                const days = this.deliveryArea === 'inside' ? 2 : 4;
                const date = new Date();
                date.setDate(date.getDate() + days);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            },

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