@extends('shop.themes.athletic.layout')
@section('title', 'চেকআউট | ' . $client->shop_name)

@section('content')
@php
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug);
@endphp

<div class="max-w-[100rem] mx-auto px-4 sm:px-8 py-12 md:py-20" x-data="{
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
        if(!this.coupon) { this.error = 'কুপন কোড লিখুন'; return; }
        this.error = '';
        fetch('{{ route(''shop.apply-coupon.sub'', \->slug) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({code: this.coupon, product_id: {{ $product->id }}, subtotal: this.subtotal})
        }).then(r => r.json()).then(d => {
            if(d.success) { this.discount = d.discount; this.couponApplied = true; }
            else { this.error = d.message || 'কুপন কোড সঠিক নয়'; }
        }).catch(() => { this.error = 'সার্ভার সমস্যা। আবার চেষ্টা করুন।'; });
    }
}">

    <div class="mb-12 border-l-[12px] border-dark pl-6">
        <h1 class="text-5xl md:text-8xl font-display font-bold uppercase tracking-tighter leading-none text-dark">অর্ডার <br><span class="text-primary tracking-widest">কনফার্ম করুন</span></h1>
    </div>

    @if(session('success'))
        <div class="bg-primary text-white border-4 border-dark p-6 mb-12 -skew-x-[6deg] max-w-4xl shadow-dark-lg">
            <h4 class="font-display font-bold text-3xl uppercase tracking-widest skew-x-[6deg]"><i class="fas fa-check-circle mr-2"></i> অর্ডার সফল হয়েছে!</h4>
            <p class="font-sans font-bold text-lg skew-x-[6deg]">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border-4 border-red-500 p-6 mb-12 max-w-4xl">
            <ul class="font-sans font-bold text-red-700 space-y-1">
                @foreach($errors->all() as $e)
                    <li><i class="fas fa-exclamation-circle mr-2"></i> {{$e}}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-12 gap-12 lg:gap-20">
        
        <!-- Left: Order Form -->
        <div class="lg:col-span-7">
            <form action="{{ $baseUrl.'/checkout/process' }}" method="POST" class="space-y-12">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color'))<input type="hidden" name="color" value="{{ request('color') }}">@endif
                @if(request('size'))<input type="hidden" name="size" value="{{ request('size') }}">@endif

                <!-- Step 1: Personal Info -->
                <div class="relative">
                    <div class="absolute -top-6 -left-6 text-9xl font-display font-bold text-gray-100 -z-10 select-none hidden md:block">01</div>
                    <h3 class="font-display font-bold text-4xl uppercase tracking-widest text-dark mb-8 border-b-8 border-dark pb-2 inline-block">১. আপনার তথ্য</h3>
                    
                    <div class="grid sm:grid-cols-2 gap-6 font-sans">
                        <div>
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">পূর্ণ নাম *</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required placeholder="আপনার নাম লিখুন" class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 focus:border-primary transition outline-none">
                        </div>
                        <div>
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">ফোন নম্বর *</label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="01XXXXXXXXX" class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 focus:border-primary transition outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">ডেলিভারি ঠিকানা *</label>
                            <textarea name="shipping_address" required rows="3" placeholder="বাড়ি নম্বর, রাস্তা, এলাকা, জেলা..." class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 focus:border-primary transition resize-none outline-none">{{ old('shipping_address') }}</textarea>
                        </div>
                        @if($client->show_email_field ?? false)
                        <div class="sm:col-span-2">
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">ইমেইল (ঐচ্ছিক)</label>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="email@example.com" class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 focus:border-primary transition outline-none">
                        </div>
                        @endif
                        @if($client->show_note_field ?? true)
                        <div class="sm:col-span-2">
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">অর্ডার নোট (ঐচ্ছিক)</label>
                            <textarea name="note" rows="2" placeholder="বিশেষ কোনো নির্দেশনা থাকলে লিখুন..." class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 focus:border-primary transition resize-none outline-none">{{ old('note') }}</textarea>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Step 2: Delivery Area -->
                <div class="relative">
                    <div class="absolute -top-6 -left-6 text-9xl font-display font-bold text-gray-100 -z-10 select-none hidden md:block">02</div>
                    <h3 class="font-display font-bold text-4xl uppercase tracking-widest text-dark mb-8 border-b-8 border-dark pb-2 inline-block">২. ডেলিভারি এলাকা</h3>
                    
                    <div class="grid grid-cols-2 gap-4 font-display">
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="inside = true" class="peer hidden" checked>
                            <div class="bg-gray-50 border-4 border-dark p-6 peer-checked:bg-primary peer-checked:text-white transition -skew-x-[4deg] shadow-dark-md hover:translate-y-1 hover:shadow-none">
                                <div class="skew-x-[4deg]">
                                    <span class="block text-3xl font-bold uppercase tracking-widest">ঢাকার ভেতরে</span>
                                    <span class="block text-xl font-sans font-bold mt-2">ডেলিভারি: ৳{{ $client->delivery_charge_inside ?? 60 }}</span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="inside = false" class="peer hidden">
                            <div class="bg-gray-50 border-4 border-dark p-6 peer-checked:bg-primary peer-checked:text-white transition -skew-x-[4deg] shadow-dark-md hover:translate-y-1 hover:shadow-none">
                                <div class="skew-x-[4deg]">
                                    <span class="block text-3xl font-bold uppercase tracking-widest">ঢাকার বাইরে</span>
                                    <span class="block text-xl font-sans font-bold mt-2">ডেলিভারি: ৳{{ $client->delivery_charge_outside ?? 120 }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 3: Coupon Code -->
                @if($client->coupon_active ?? true)
                <div class="relative">
                    <div class="absolute -top-6 -left-6 text-9xl font-display font-bold text-gray-100 -z-10 select-none hidden md:block">03</div>
                    <h3 class="font-display font-bold text-4xl uppercase tracking-widest text-dark mb-8 border-b-8 border-dark pb-2 inline-block">৩. কুপন কোড</h3>
                    
                    <div class="bg-dark p-6 border-[6px] border-transparent shadow-primary-lg -skew-x-[4deg]">
                        <div x-show="!couponApplied" class="flex flex-col sm:flex-row gap-4 skew-x-[4deg]">
                            <input type="text" x-model="coupon" placeholder="কুপন কোড লিখুন" class="flex-1 bg-dark text-white border-2 border-primary font-display font-bold text-2xl uppercase p-4 focus:ring-0 outline-none">
                            <button type="button" @click="applyCoupon()" class="bg-primary text-white font-display font-bold text-2xl px-12 py-4 uppercase tracking-widest hover:bg-white hover:text-dark border-4 border-transparent transition">প্রয়োগ করুন</button>
                        </div>
                        <div x-show="couponApplied" class="flex justify-between flex-row items-center border-4 border-primary p-4 bg-[#e11d48]/20 skew-x-[4deg]">
                            <span class="font-display font-bold text-3xl text-white uppercase tracking-widest"><i class="fas fa-check-circle text-primary mr-3"></i>কুপন: <span x-text="coupon"></span></span>
                            <button type="button" @click="coupon=''; discount=0; couponApplied=false" class="text-white hover:text-primary"><i class="fas fa-times text-2xl"></i></button>
                        </div>
                        <p x-show="error" x-text="error" class="text-primary font-display font-bold text-2xl mt-4 skew-x-[4deg] uppercase tracking-widest"></p>
                    </div>
                </div>
                <input type="hidden" name="coupon_code" :value="couponApplied ? coupon : ''">
                <input type="hidden" name="coupon_discount" :value="discount">
                @endif

                <!-- Step 4: Payment Method -->
                <div class="relative pt-6">
                    <div class="absolute top-0 -left-6 text-9xl font-display font-bold text-gray-100 -z-10 select-none hidden md:block">{{ ($client->coupon_active ?? true) ? '04' : '03' }}</div>
                    <h3 class="font-display font-bold text-4xl uppercase tracking-widest text-dark mb-8 border-b-8 border-dark pb-2 inline-block">{{ ($client->coupon_active ?? true) ? '৪' : '৩' }}. পেমেন্ট পদ্ধতি</h3>
                    
                    <div class="grid grid-cols-1 gap-4 font-display">
                        @if($client->cod_active ?? true)
                        <label class="cursor-pointer group">
                            <input type="radio" name="payment_method" value="cod" class="peer hidden" checked>
                            <div class="bg-gray-50 border-4 border-dark p-6 peer-checked:bg-dark peer-checked:text-white transition -skew-x-[4deg] shadow-dark-md hover:translate-y-1 hover:shadow-none flex items-center gap-4">
                                <i class="fas fa-money-bill-wave text-4xl text-green-600 peer-checked:text-primary transition-colors skew-x-[4deg]"></i>
                                <div class="skew-x-[4deg]">
                                    <span class="block text-2xl font-bold uppercase tracking-widest">ক্যাশ অন ডেলিভারি (COD)</span>
                                    <span class="block text-sm font-sans font-bold text-gray-500 peer-checked:text-gray-300">পণ্য পেয়ে টাকা দিন</span>
                                </div>
                            </div>
                        </label>
                        @endif

                        @if(($client->full_payment_active ?? false) || ($client->partial_payment_active ?? false))
                        <label class="cursor-pointer group">
                            <input type="radio" name="payment_method" value="online" class="peer hidden" {{ !($client->cod_active ?? true) ? 'checked' : '' }}>
                            <div class="bg-gray-50 border-4 border-dark p-6 peer-checked:bg-dark peer-checked:text-white transition -skew-x-[4deg] shadow-dark-md hover:translate-y-1 hover:shadow-none flex items-center gap-4">
                                <i class="fas fa-credit-card text-4xl text-blue-600 peer-checked:text-primary transition-colors skew-x-[4deg]"></i>
                                <div class="skew-x-[4deg]">
                                    <span class="block text-2xl font-bold uppercase tracking-widest">অনলাইন পেমেন্ট</span>
                                    <span class="block text-sm font-sans font-bold text-gray-500 peer-checked:text-gray-300">bKash / Nagad / Card</span>
                                </div>
                            </div>
                        </label>
                        @endif
                    </div>
                </div>

                @if($client->show_terms_checkbox)
                <div class="bg-primary/10 border-l-8 border-primary p-6 mt-8 -skew-x-[4deg]">
                    <label class="flex items-start gap-4 cursor-pointer group skew-x-[4deg]">
                        <input type="checkbox" name="terms" required class="mt-1 w-6 h-6 border-4 border-dark text-primary focus:ring-0 rounded-none bg-white shrink-0 shadow-dark-xs">
                        <span class="font-sans font-bold tracking-wide text-dark text-sm md:text-base cursor-pointer">
                            আমি <a href="{{ $client->terms_conditions_url ?? '#' }}" class="text-primary hover:text-dark uppercase underline decoration-2 underline-offset-4 transition-colors" target="_blank">{{ $client->terms_conditions_text ?? 'নিয়মাবলি ও শর্তাবলি' }}</a> পড়েছি এবং সম্মত আছি।
                        </span>
                    </label>
                </div>
                @endif

                <!-- Submit Button -->
                <div class="pt-8">
                    <button type="submit" class="w-full btn-speed text-center py-6 shadow-primary-xl hover:shadow-primary-sm hover:translate-x-2 hover:translate-y-2 border-4 border-dark">
                        <span class="font-display font-bold text-4xl md:text-5xl uppercase tracking-widest">অর্ডার কনফার্ম করুন <i class="fas fa-check-circle ml-4"></i></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary -->
        <div class="lg:col-span-5 relative mt-16 md:mt-12 lg:mt-0">
            <div class="sticky top-32 bg-gray-100 border-8 border-dark p-8 md:p-12 -skew-x-[2deg]">
                <div class="skew-x-[2deg]">
                    <h3 class="font-display font-bold text-5xl uppercase tracking-tighter text-dark mb-10 pb-4 border-b-8 border-primary">অর্ডার সারসংক্ষেপ</h3>
                    
                    <!-- Product Preview -->
                    <div class="flex gap-6 items-center bg-white p-4 border-4 border-dark mb-8 group relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 text-9xl font-display font-bold text-gray-100 opacity-50 z-0">01</div>
                        <div class="w-24 h-24 shrink-0 border-4 border-dark relative z-10 bg-gray-50 p-2">
                            <img src="{{ asset('storage/'.$product->thumbnail) }}" class="w-full h-full object-cover mix-blend-multiply" alt="{{ $product->name }}">
                            <div class="absolute -top-3 -right-3 w-8 h-8 bg-primary text-white font-display font-bold text-xl flex items-center justify-center -skew-x-[4deg] border-2 border-dark" x-text="qty"></div>
                        </div>
                        <div class="flex-1 relative z-10">
                            <h4 class="font-display font-bold text-2xl uppercase tracking-widest text-dark line-clamp-2 leading-tight mb-2">{{ $product->name }}</h4>
                            <div class="flex gap-2 flex-wrap">
                                @if(request('color'))<span class="bg-dark text-white font-sans text-xs font-bold px-2 py-1 uppercase">{{ request('color') }}</span>@endif
                                @if(request('size'))<span class="bg-dark text-white font-sans text-xs font-bold px-2 py-1 uppercase">{{ request('size') }}</span>@endif
                            </div>
                            <p class="text-sm font-sans text-gray-500 mt-1">৳{{ number_format($product->sale_price ?? $product->regular_price) }} × <span x-text="qty"></span></p>
                        </div>
                    </div>

                    <!-- Quantity Adjuster -->
                    <div class="flex gap-4 items-center border-b-4 border-dashed border-gray-300 pb-8 mb-8">
                        <span class="font-display font-bold text-2xl uppercase tracking-widest text-gray-500 w-1/3">পরিমাণ</span>
                        <div class="flex flex-1 border-4 border-dark h-16 bg-white shrink-0">
                            <button type="button" @click="if(qty>1)qty--" class="w-16 h-full flex items-center justify-center font-display font-bold text-3xl hover:bg-gray-100"><i class="fas fa-minus text-xl"></i></button>
                            <input type="number" name="qty" x-model="qty" class="flex-1 text-center font-display font-bold text-3xl p-0 focus:ring-0 border-x-4 border-dark" readonly>
                            <button type="button" @click="qty++" class="w-16 h-full flex items-center justify-center font-display font-bold text-3xl hover:bg-gray-100"><i class="fas fa-plus text-xl"></i></button>
                        </div>
                    </div>

                    <!-- Price Breakdown -->
                    <div class="space-y-4 font-display font-bold text-2xl uppercase tracking-widest mb-10">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>পণ্যের মূল্য</span>
                            <span>৳<span x-text="subtotal.toLocaleString()"></span></span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600">
                            <span>ডেলিভারি চার্জ</span>
                            <span>৳<span x-text="delivery"></span></span>
                        </div>
                        <div x-show="couponApplied" class="flex justify-between items-center bg-primary text-white px-4 py-2 mt-4 -skew-x-[4deg]">
                            <span class="skew-x-[4deg]">কুপন ছাড়</span>
                            <span class="skew-x-[4deg]">-৳<span x-text="discount"></span></span>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="pt-8 border-t-[10px] border-dark">
                        <div class="flex justify-between items-end">
                            <span class="font-display font-bold text-3xl uppercase tracking-widest text-dark">সর্বমোট</span>
                            <span class="font-display font-bold text-6xl md:text-7xl leading-[0.8] tracking-tighter text-primary">৳<span x-text="total.toLocaleString()"></span></span>
                        </div>
                        <p class="text-xs font-sans text-gray-500 mt-3">সকল চার্জ ও ট্যাক্স অন্তর্ভুক্ত।</p>
                    </div>

                    <!-- Trust Badges -->
                    <div class="mt-8 pt-6 border-t-4 border-gray-300 grid grid-cols-3 gap-4 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <i class="fas fa-shield-alt text-2xl text-primary"></i>
                            <span class="text-xs font-sans font-bold text-gray-600 uppercase">নিরাপদ</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <i class="fas fa-truck text-2xl text-primary"></i>
                            <span class="text-xs font-sans font-bold text-gray-600 uppercase">দ্রুত ডেলিভারি</span>
                        </div>
                        <div class="flex flex-col items-center gap-1">
                            <i class="fas fa-headset text-2xl text-primary"></i>
                            <span class="text-xs font-sans font-bold text-gray-600 uppercase">সাপোর্ট</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection
