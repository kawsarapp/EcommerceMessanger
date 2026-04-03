@extends('shop.themes.daraz.layout')
@section('title', 'অর্ডার করুন | ' . $client->shop_name)

@section('content')
@php
$baseUrl = $client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug);
@endphp

<div class="max-w-7xl mx-auto px-4 py-4 md:py-8" x-data="{
    inside: true,
    qty: {{ request('qty', 1) }},
    price: {{ $product->sale_price ?? $product->regular_price }},
    deliveryInside: {{ $client->delivery_charge_inside ?? 60 }},
    deliveryOutside: {{ $client->delivery_charge_outside ?? 120 }},
    coupon: '',
    discount: 0,
    couponApplied: false,
    error: '',
    get subtotal() { return this.qty * this.price; },
    get delivery() { return this.inside ? this.deliveryInside : this.deliveryOutside; },
    get total() { return this.subtotal + this.delivery - this.discount; },
    applyCoupon() {
        if(!this.coupon.trim()) { this.error = 'কুপন কোড লিখুন'; return; }
        this.error = '';
        fetch('{{ route(''shop.apply-coupon.sub'', \->slug) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({code: this.coupon, product_id: {{ $product->id }}, subtotal: this.subtotal})
        }).then(r => r.json()).then(d => {
            if(d.success) { this.discount = d.discount; this.couponApplied = true; }
            else { this.error = d.message || 'কুপন কোড সঠিক নয়'; }
        }).catch(() => { this.error = 'কুপন যাচাই করা যায়নি'; });
    }
}">

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center text-xs text-gray-500">
        <a href="{{ $baseUrl }}" class="hover:text-primary transition">হোম</a>
        <i class="fas fa-chevron-right text-[8px] mx-2 text-gray-300"></i>
        <span class="text-dark font-medium">অর্ডার করুন</span>
    </nav>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 p-4 rounded-xl mb-6 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white"><i class="fas fa-check"></i></div>
            <div><h4 class="text-green-800 font-bold">অর্ডার সফল হয়েছে!</h4><p class="text-green-600 text-sm">{{ session('success') }}</p></div>
        </div>
    @endif

    <div class="grid lg:grid-cols-12 gap-6">
        {{-- Form --}}
        <div class="lg:col-span-7">
            <form action="{{ $baseUrl.'/checkout/process' }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color'))<input type="hidden" name="color" value="{{ request('color') }}">@endif
                @if(request('size'))<input type="hidden" name="size" value="{{ request('size') }}">@endif

                {{-- Shipping Info --}}
                <div class="bg-white rounded-2xl p-5 md:p-6">
                    <h3 class="font-bold text-dark mb-5 flex items-center gap-3">
                        <span class="w-8 h-8 hero-gradient text-white text-sm font-bold rounded-full flex items-center justify-center">১</span>
                        ডেলিভারি তথ্য
                    </h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-2">আপনার নাম *</label>
                            <input type="text" name="customer_name" required placeholder="সম্পূর্ণ নাম"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-primary transition">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-2">মোবাইল নম্বর *</label>
                            <input type="tel" name="customer_phone" required placeholder="01XXXXXXXXX"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-primary transition">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-bold text-gray-500 block mb-2">সম্পূর্ণ ঠিকানা *</label>
                            <textarea name="shipping_address" required rows="3" placeholder="বাড়ি নং, রোড, এলাকা, জেলা"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-primary transition resize-none"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Delivery Area --}}
                <div class="bg-white rounded-2xl p-5 md:p-6">
                    <h3 class="font-bold text-dark mb-5 flex items-center gap-3">
                        <span class="w-8 h-8 hero-gradient text-white text-sm font-bold rounded-full flex items-center justify-center">২</span>
                        ডেলিভারি এরিয়া
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="inside = true" class="peer hidden" checked>
                            <div class="border-2 border-gray-200 rounded-xl p-4 peer-checked:border-primary peer-checked:bg-primary/5 transition text-center hover:border-primary/50">
                                <i class="fas fa-city text-primary text-2xl mb-2"></i>
                                <span class="block font-bold text-dark">ঢাকার ভিতরে</span>
                                <span class="block text-xl font-bold text-primary mt-1">৳{{ $client->delivery_charge_inside ?? 60 }}</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="inside = false" class="peer hidden">
                            <div class="border-2 border-gray-200 rounded-xl p-4 peer-checked:border-primary peer-checked:bg-primary/5 transition text-center hover:border-primary/50">
                                <i class="fas fa-map-marked-alt text-primary text-2xl mb-2"></i>
                                <span class="block font-bold text-dark">ঢাকার বাইরে</span>
                                <span class="block text-xl font-bold text-primary mt-1">৳{{ $client->delivery_charge_outside ?? 120 }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Coupon --}}
                <div class="bg-white rounded-2xl p-5 md:p-6">
                    <h3 class="font-bold text-dark mb-4 flex items-center gap-2"><i class="fas fa-tag text-primary"></i> কুপন কোড</h3>
                    <div x-show="!couponApplied" class="flex gap-2">
                        <input type="text" x-model="coupon" placeholder="কুপন কোড লিখুন"
                            class="flex-1 border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-primary transition">
                        <button type="button" @click="applyCoupon()" class="btn-primary px-6 py-3 text-white rounded-xl font-bold text-sm transition">প্রয়োগ</button>
                    </div>
                    <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i><span class="font-bold text-green-700 text-sm" x-text="coupon"></span><span class="text-green-600 text-xs bg-green-100 px-2 py-0.5 rounded-full" x-text="'৳'+discount+' সেভ'"></span></div>
                        <button type="button" @click="coupon=''; discount=0; couponApplied=false" class="text-red-500 text-sm font-bold hover:text-red-600"><i class="fas fa-times"></i></button>
                    </div>
                    <input type="hidden" name="coupon_code" :value="couponApplied ? coupon : ''">
                    <input type="hidden" name="coupon_discount" :value="discount">
                    <p x-show="error" x-text="error" class="text-red-500 text-xs font-bold mt-2"></p>
                </div>

                {{-- Payment Methods --}}
                <div class="bg-white rounded-2xl p-5 md:p-6">
                    <h3 class="font-bold text-dark mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 hero-gradient text-white text-sm font-bold rounded-full flex items-center justify-center">৩</span>
                        পেমেন্ট মেথড
                    </h3>
                    <div class="space-y-3">
                        @if($client->cod_active ?? true)
                        <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary transition">
                            <input type="radio" name="payment_method" value="cod" class="text-primary focus:ring-primary h-4 w-4" checked>
                            <div>
                                <span class="font-bold text-dark block text-sm">ক্যাশ অন ডেলিভারি (COD)</span>
                                <span class="text-xs text-gray-500">পণ্য হাতে পেয়ে পেমেন্ট করুন</span>
                            </div>
                        </label>
                        @endif
                        
                        @if($client->full_payment_active || ($client->partial_payment_active ?? false))
                        <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary transition">
                            <input type="radio" name="payment_method" value="online" class="text-primary focus:ring-primary h-4 w-4" {{ !($client->cod_active ?? true) ? 'checked' : '' }}>
                            <div>
                                <span class="font-bold text-dark block text-sm">অনলাইন পেমেন্ট (bKash/Card/Nagad)</span>
                                <span class="text-xs text-gray-500">নিরাপদ অনলাইন পেমেন্ট করুন</span>
                            </div>
                        </label>
                        @endif
                    </div>
                </div>

                {{-- Terms and Conditions --}}
                @if($client->show_terms_checkbox)
                <div class="bg-white rounded-2xl p-5 md:p-6 flex items-start gap-3">
                    <input type="checkbox" name="terms" id="terms" class="mt-1 text-primary focus:ring-primary rounded h-4 w-4 border-gray-300" required>
                    <label for="terms" class="text-sm text-gray-600">
                        আমি এই ওয়েবসাইটের <a href="{{ $client->terms_conditions_url ?? '#' }}" class="text-primary hover:underline font-medium" target="_blank">{{ $client->terms_conditions_text ?? 'শর্তাবলী' }}</a> পড়েছি এবং সম্মত আছি।
                    </label>
                </div>
                @endif

                {{-- Submit --}}
                <button type="submit" class="btn-primary w-full py-4 text-white rounded-xl font-bold text-base uppercase flex items-center justify-center gap-2 transition">
                    <i class="fas fa-lock"></i> অর্ডার কনফার্ম করুন
                </button>
                <p class="text-center text-xs text-gray-400 flex items-center justify-center gap-4">
                    <span><i class="fas fa-shield-check text-green-500 mr-1"></i> নিরাপদ চেকআউট</span>
                    <span><i class="fas fa-money-bill text-primary mr-1"></i> ক্যাশ অন ডেলিভারি</span>
                </p>
            </form>
        </div>

        {{-- Summary --}}
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl p-5 md:p-6 lg:sticky lg:top-24">
                <h3 class="font-bold text-dark mb-5 flex items-center gap-2"><i class="fas fa-receipt text-primary"></i> অর্ডার সারাংশ</h3>
                
                {{-- Product --}}
                <div class="flex gap-4 bg-gray-50 rounded-xl p-4 mb-5 relative">
                    <div class="absolute -top-2 -right-2 w-7 h-7 hero-gradient text-white rounded-full flex items-center justify-center text-xs font-bold shadow" x-text="qty"></div>
                    <div class="w-20 h-20 bg-white rounded-xl border p-2 shrink-0 flex items-center justify-center">
                        <img src="{{ asset('storage/'.$product->thumbnail) }}" class="max-w-full max-h-full object-contain">
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-dark text-sm line-clamp-2 mb-1">{{ $product->name }}</h4>
                        <div class="flex flex-wrap gap-1 mb-2">
                            @if(request('color'))<span class="bg-gray-200 text-dark text-[10px] font-bold px-2 py-0.5 rounded">{{ request('color') }}</span>@endif
                            @if(request('size'))<span class="bg-gray-200 text-dark text-[10px] font-bold px-2 py-0.5 rounded">{{ request('size') }}</span>@endif
                        </div>
                        <span class="font-bold text-primary text-lg">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                    </div>
                </div>

                {{-- Qty --}}
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                    <span class="text-sm font-semibold text-gray-600">পরিমাণ</span>
                    <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden">
                        <button type="button" @click="if(qty>1)qty--" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-dark hover:bg-gray-50 transition"><i class="fas fa-minus text-xs"></i></button>
                        <span class="w-12 text-center font-bold" x-text="qty"></span>
                        <button type="button" @click="qty++" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-dark hover:bg-gray-50 transition"><i class="fas fa-plus text-xs"></i></button>
                    </div>
                </div>

                {{-- Totals --}}
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">সাবটোটাল</span><span class="font-bold text-dark">৳<span x-text="subtotal.toLocaleString()"></span></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">ডেলিভারি চার্জ</span><span class="font-bold text-dark">৳<span x-text="delivery"></span></span></div>
                    <div x-show="couponApplied" class="flex justify-between text-green-600 bg-green-50 px-3 py-2 rounded-lg"><span><i class="fas fa-tag mr-1"></i>কুপন ডিসকাউন্ট</span><span class="font-bold">-৳<span x-text="discount"></span></span></div>
                    <div class="pt-4 border-t-2 border-dashed border-gray-200">
                        <div class="flex justify-between items-center bg-gradient-to-r from-primary/5 to-orange-50 p-4 rounded-xl">
                            <span class="font-bold text-dark">সর্বমোট</span>
                            <span class="text-2xl font-bold text-primary">৳<span x-text="total.toLocaleString()"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
