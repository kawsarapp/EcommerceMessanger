@extends('shop.themes.bdshop.layout')
@section('title', 'অর্ডার করুন | ' . $client->shop_name)

@section('content')
@php
$baseUrl = $client->custom_domain ? 'https://' . preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : route('shop.show', $client->slug);
@endphp

<div class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-8" x-data="{
    insideDhaka: true,
    qty: {{ request('qty', 1) }},
    price: {{ $product->sale_price ?? $product->regular_price }},
    deliveryInside: {{ $client->delivery_charge_inside ?? 60 }},
    deliveryOutside: {{ $client->delivery_charge_outside ?? 120 }},
    couponCode: '',
    couponDiscount: 0,
    couponApplied: false,
    couponError: '',
    termsAccepted: {{ ($client->show_terms_checkbox ?? false) ? 'false' : 'true' }},
    get subtotal() { return this.qty * this.price; },
    get delivery() { return this.insideDhaka ? this.deliveryInside : this.deliveryOutside; },
    get total() { return this.subtotal + this.delivery - this.couponDiscount; },
    applyCoupon() {
        if (!this.couponCode.trim()) { this.couponError = 'কুপন কোড লিখুন'; return; }
        this.couponError = '';
        fetch('{{ $baseUrl }}/api/validate-coupon', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({code: this.couponCode, product_id: {{ $product->id }}, subtotal: this.subtotal})
        }).then(r => r.json()).then(data => {
            if (data.valid) { this.couponDiscount = data.discount; this.couponApplied = true; this.couponError = ''; }
            else { this.couponError = data.message || 'কুপন কোড সঠিক নয়'; this.couponDiscount = 0; }
        }).catch(() => { this.couponError = 'কুপন যাচাই করা যায়নি'; });
    },
    removeCoupon() { this.couponCode = ''; this.couponDiscount = 0; this.couponApplied = false; this.couponError = ''; }
}">

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center text-xs text-slate-500 font-medium">
        <a href="{{ $baseUrl }}" class="hover:text-primary transition">হোম</a>
        <i class="fas fa-chevron-right text-[8px] mx-2 text-slate-300"></i>
        <span class="text-dark">অর্ডার করুন</span>
    </nav>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl mb-6 flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-white"></i>
            </div>
            <div>
                <h4 class="text-emerald-800 font-bold">অর্ডার সফল হয়েছে!</h4>
                <p class="text-emerald-600 text-sm">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">

        {{-- Left: Form --}}
        <div class="lg:col-span-7 order-2 lg:order-1">
            <form action="{{ $baseUrl . '/checkout/process' }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{ array_is_list((array)request('color')) ? request('color') : request('color')[0] }}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{ array_is_list((array)request('size')) ? request('size') : request('size')[0] }}"> @endif

                {{-- Shipping Details --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6">
                    <h3 class="text-base font-bold text-dark mb-5 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-primary text-white text-sm font-bold flex items-center justify-center">১</span>
                        ডেলিভারি তথ্য
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-500 block mb-2">আপনার নাম *</label>
                            <input type="text" name="customer_name" required placeholder="সম্পূর্ণ নাম"
                                class="w-full border-2 border-slate-200 rounded-xl px-4 py-3.5 text-sm font-medium focus:border-primary focus:ring-2 focus:ring-primary/10 transition placeholder-slate-400">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 block mb-2">মোবাইল নম্বর *</label>
                            <input type="tel" name="customer_phone" required placeholder="01XXXXXXXXX"
                                class="w-full border-2 border-slate-200 rounded-xl px-4 py-3.5 text-sm font-medium focus:border-primary focus:ring-2 focus:ring-primary/10 transition placeholder-slate-400">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-bold text-slate-500 block mb-2">সম্পূর্ণ ঠিকানা *</label>
                            <textarea name="shipping_address" required rows="3" placeholder="বাড়ি নং, রোড, এলাকা, জেলা"
                                class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm font-medium focus:border-primary focus:ring-2 focus:ring-primary/10 transition placeholder-slate-400 resize-none"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Delivery Area --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6">
                    <h3 class="text-base font-bold text-dark mb-5 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-primary text-white text-sm font-bold flex items-center justify-center">২</span>
                        ডেলিভারি এরিয়া
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-primary peer-checked:bg-primary/5 transition text-center hover:border-primary/50">
                                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-city text-primary text-lg"></i>
                                </div>
                                <span class="block text-sm font-bold text-dark">ঢাকার ভিতরে</span>
                                <span class="block text-xl font-bold text-primary mt-1">৳{{ $client->delivery_charge_inside ?? 60 }}</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-primary peer-checked:bg-primary/5 transition text-center hover:border-primary/50">
                                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <i class="fas fa-map-marked-alt text-primary text-lg"></i>
                                </div>
                                <span class="block text-sm font-bold text-dark">ঢাকার বাইরে</span>
                                <span class="block text-xl font-bold text-primary mt-1">৳{{ $client->delivery_charge_outside ?? 120 }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Coupon --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6">
                    <h3 class="text-base font-bold text-dark mb-4 flex items-center gap-2">
                        <i class="fas fa-tag text-primary"></i> কুপন কোড
                    </h3>
                    <div class="flex gap-2" x-show="!couponApplied">
                        <input type="text" x-model="couponCode" placeholder="কুপন কোড লিখুন"
                            class="flex-1 border-2 border-slate-200 rounded-xl px-4 py-3 text-sm font-medium focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder-slate-400">
                        <button type="button" @click="applyCoupon()" class="px-6 py-3 bg-primary text-white rounded-xl font-bold text-sm hover:bg-primary/90 transition">প্রয়োগ</button>
                    </div>
                    <div x-show="couponApplied" class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-emerald-500"></i>
                            <span class="font-bold text-emerald-700 text-sm" x-text="couponCode"></span>
                            <span class="text-emerald-600 text-xs bg-emerald-100 px-2 py-0.5 rounded-full" x-text="'৳' + couponDiscount + ' সেভ'"></span>
                        </div>
                        <button type="button" @click="removeCoupon()" class="text-red-500 text-sm font-bold hover:text-red-600"><i class="fas fa-times"></i></button>
                    </div>
                    <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
                    <input type="hidden" name="coupon_discount" :value="couponDiscount">
                    <p x-show="couponError" x-text="couponError" class="text-red-500 text-xs font-bold mt-2"></p>
                </div>

                {{-- Terms --}}
                @if($client->show_terms_checkbox ?? false)
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="terms_accepted" x-model="termsAccepted" class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary/20 mt-0.5" required>
                        <span class="text-sm text-slate-600 font-medium">
                            আমি <a href="{{ $client->terms_conditions_url ?? '#' }}" target="_blank" class="text-primary font-bold hover:underline">শর্তাবলী</a> পড়েছি এবং সম্মত আছি
                            @if($client->terms_conditions_text)<span class="block text-xs text-slate-400 mt-1">{{ $client->terms_conditions_text }}</span>@endif
                        </span>
                    </label>
                </div>
                @endif

                {{-- Submit Button --}}
                <button type="submit" class="w-full py-4 bg-primary text-white rounded-xl font-bold text-base uppercase tracking-wider hover:bg-primary/90 transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <i class="fas fa-lock text-sm"></i> অর্ডার কনফার্ম করুন
                </button>
                <div class="flex items-center justify-center gap-4 text-xs text-slate-400 font-medium">
                    <span class="flex items-center gap-1"><i class="fas fa-shield-check text-emerald-500"></i> নিরাপদ চেকআউট</span>
                    <span>|</span>
                    <span class="flex items-center gap-1"><i class="fas fa-money-bill text-primary"></i> ক্যাশ অন ডেলিভারি</span>
                </div>
            </form>
        </div>

        {{-- Right: Order Summary --}}
        <div class="lg:col-span-5 order-1 lg:order-2">
            <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 lg:sticky lg:top-24">
                <h3 class="font-bold text-dark text-base mb-5 flex items-center gap-2">
                    <i class="fas fa-receipt text-primary"></i> অর্ডার সারাংশ
                </h3>

                {{-- Product Info --}}
                <div class="flex gap-4 bg-slate-50 rounded-xl p-4 mb-5 relative">
                    <div class="absolute -top-2 -right-2 w-7 h-7 bg-primary text-white rounded-full flex items-center justify-center text-xs font-bold shadow-md" x-text="qty"></div>
                    <div class="w-20 h-20 bg-white rounded-lg border p-2 shrink-0 flex items-center justify-center">
                        <img src="{{ asset('storage/' . $product->thumbnail) }}" class="max-w-full max-h-full object-contain">
                    </div>
                    <div class="flex-1 py-1">
                        <h4 class="font-bold text-dark text-sm line-clamp-2 mb-2">{{ $product->name }}</h4>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            @if(request('color'))<span class="bg-slate-200 text-dark text-[10px] font-bold px-2 py-0.5 rounded">{{ array_is_list((array)request('color')) ? request('color') : request('color')[0] }}</span>@endif
                            @if(request('size'))<span class="bg-slate-200 text-dark text-[10px] font-bold px-2 py-0.5 rounded">{{ array_is_list((array)request('size')) ? request('size') : request('size')[0] }}</span>@endif
                        </div>
                        <span class="font-bold text-primary text-lg">৳{{ number_format($product->sale_price ?? $product->regular_price) }}</span>
                    </div>
                </div>

                {{-- Quantity Adjuster --}}
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100">
                    <span class="text-sm font-semibold text-slate-600">পরিমাণ</span>
                    <div class="flex items-center border-2 border-slate-200 rounded-xl overflow-hidden">
                        <button type="button" @click="if(qty>1)qty--" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-dark hover:bg-slate-50 transition">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span class="w-12 text-center font-bold text-base" x-text="qty"></span>
                        <button type="button" @click="qty++" class="w-10 h-10 flex items-center justify-center text-slate-500 hover:text-dark hover:bg-slate-50 transition">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </div>

                {{-- Price Breakdown --}}
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">সাবটোটাল</span>
                        <span class="font-bold text-dark">৳<span x-text="subtotal.toLocaleString()"></span></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">ডেলিভারি চার্জ</span>
                        <span class="font-bold text-dark">৳<span x-text="delivery"></span></span>
                    </div>
                    <div x-show="couponApplied" class="flex justify-between text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg">
                        <span><i class="fas fa-tag mr-1"></i>কুপন ডিসকাউন্ট</span>
                        <span class="font-bold">-৳<span x-text="couponDiscount"></span></span>
                    </div>

                    <div class="pt-4 mt-2 border-t-2 border-dashed border-slate-200">
                        <div class="flex justify-between items-center bg-gradient-to-r from-primary/10 to-orange-50 p-4 rounded-xl">
                            <span class="font-bold text-dark text-base">সর্বমোট</span>
                            <span class="text-2xl font-bold text-primary">৳<span x-text="total.toLocaleString()"></span></span>
                        </div>
                    </div>
                </div>

                {{-- Trust Note --}}
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <i class="fas fa-info-circle text-primary"></i>
                        <span>পণ্য হাতে পাওয়ার পর পেমেন্ট করুন</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
