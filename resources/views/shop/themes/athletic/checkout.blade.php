@extends('shop.themes.athletic.layout')
@section('title', 'DEPLOYMENT SECURE | ' . $client->shop_name)

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
        if(!this.coupon) { this.error = 'ENTER OVERRIDE CODE'; return; }
        this.error = '';
        fetch('{{ $baseUrl }}/api/validate-coupon', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({code: this.coupon, product_id: {{ $product->id }}, subtotal: this.subtotal})
        }).then(r => r.json()).then(d => {
            if(d.valid) { this.discount = d.discount; this.couponApplied = true; }
            else { this.error = d.message || 'INVALID CODE'; }
        }).catch(() => { this.error = 'SYSTEM ERROR'; });
    }
}">

    <div class="mb-12 border-l-[12px] border-dark pl-6">
        <h1 class="text-5xl md:text-8xl font-display font-bold uppercase tracking-tighter leading-none text-dark">SECURE YOUR <br><span class="text-primary tracking-widest">DEPLOYMENT</span></h1>
    </div>

    @if(session('success'))
        <div class="bg-primary text-white border-4 border-dark p-6 mb-12 -skew-x-[6deg] max-w-4xl shadow-[8px_8px_0_111]">
            <h4 class="font-display font-bold text-3xl uppercase tracking-widest skew-x-[6deg]">MISSION ACCOMPLISHED!</h4>
            <p class="font-sans font-bold text-lg skew-x-[6deg]">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid lg:grid-cols-12 gap-12 lg:gap-20">
        
        <!-- Left: Intelligence Form -->
        <div class="lg:col-span-7">
            <form action="{{ $baseUrl.'/checkout/process' }}" method="POST" class="space-y-12">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color'))<input type="hidden" name="color" value="{{ request('color') }}">@endif
                @if(request('size'))<input type="hidden" name="size" value="{{ request('size') }}">@endif

                <!-- Phase 1: Comm Data -->
                <div class="relative">
                    <div class="absolute -top-6 -left-6 text-9xl font-display font-bold text-gray-100 -z-10 select-none hidden md:block">01</div>
                    <h3 class="font-display font-bold text-4xl uppercase tracking-widest text-dark mb-8 border-b-8 border-dark pb-2 inline-block">1. OPERATIVE INTEL</h3>
                    
                    <div class="grid sm:grid-cols-2 gap-6 font-sans">
                        <div>
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">FULL NAME *</label>
                            <input type="text" name="customer_name" required class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 -skew-x-[4deg] focus:border-primary transition peer">
                        </div>
                        <div>
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">COMMS FREQUENCY (PHONE) *</label>
                            <input type="tel" name="customer_phone" required class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 -skew-x-[4deg] focus:border-primary transition">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="font-sans font-bold text-sm tracking-widest text-dark block mb-2 uppercase">DROP ZONE LOCATION *</label>
                            <textarea name="shipping_address" required rows="3" class="w-full bg-gray-50 border-4 border-dark font-bold text-lg p-4 -skew-x-[4deg] focus:border-primary transition resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Phase 2: Sector -->
                <div class="relative">
                    <div class="absolute -top-6 -left-6 text-9xl font-display font-bold text-gray-100 -z-10 select-none hidden md:block">02</div>
                    <h3 class="font-display font-bold text-4xl uppercase tracking-widest text-dark mb-8 border-b-8 border-dark pb-2 inline-block">2. DELIVERY SECTOR</h3>
                    
                    <div class="grid grid-cols-2 gap-4 font-display">
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="inside = true" class="peer hidden" checked>
                            <div class="bg-gray-50 border-4 border-dark p-6 peer-checked:bg-primary peer-checked:text-white transition -skew-x-[4deg] shadow-[6px_6px_0_111] hover:translate-y-1 hover:shadow-none">
                                <div class="skew-x-[4deg]">
                                    <span class="block text-3xl font-bold uppercase tracking-widest">LOCAL ZONE</span>
                                    <span class="block text-xl font-sans font-bold mt-2">৳{{ $client->delivery_charge_inside ?? 60 }}</span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="inside = false" class="peer hidden">
                            <div class="bg-gray-50 border-4 border-dark p-6 peer-checked:bg-primary peer-checked:text-white transition -skew-x-[4deg] shadow-[6px_6px_0_111] hover:translate-y-1 hover:shadow-none">
                                <div class="skew-x-[4deg]">
                                    <span class="block text-3xl font-bold uppercase tracking-widest">OUTER RIM</span>
                                    <span class="block text-xl font-sans font-bold mt-2">৳{{ $client->delivery_charge_outside ?? 120 }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Phase 3: Override Codes -->
                <div class="relative">
                    <div class="absolute -top-6 -left-6 text-9xl font-display font-bold text-gray-100 -z-10 select-none hidden md:block">03</div>
                    <h3 class="font-display font-bold text-4xl uppercase tracking-widest text-dark mb-8 border-b-8 border-dark pb-2 inline-block">3. SYSTEM OVERRIDE</h3>
                    
                    <div class="bg-dark p-6 border-[6px] border-transparent shadow-[8px_8px_0_#e11d48] -skew-x-[4deg]">
                        <div x-show="!couponApplied" class="flex flex-col sm:flex-row gap-4 skew-x-[4deg]">
                            <input type="text" x-model="coupon" placeholder="AUTHORIZATION CODE" class="flex-1 bg-dark text-white border-2 border-primary font-display font-bold text-2xl uppercase p-4 focus:ring-0">
                            <button type="button" @click="applyCoupon()" class="bg-primary text-white font-display font-bold text-2xl px-12 py-4 uppercase tracking-widest hover:bg-white hover:text-dark border-4 border-transparent transition">EXECUTE</button>
                        </div>
                        <div x-show="couponApplied" class="flex justify-between flex-row items-center border-4 border-primary p-4 bg-[#e11d48]/20 skew-x-[4deg]">
                            <span class="font-display font-bold text-3xl text-white uppercase tracking-widest"><i class="fas fa-check-circle text-primary mr-3"></i>OVERRIDE: <span x-text="coupon"></span></span>
                            <button type="button" @click="coupon=''; discount=0; couponApplied=false" class="text-white hover:text-primary"><i class="fas fa-times text-2xl"></i></button>
                        </div>
                        <p x-show="error" x-text="error" class="text-primary font-display font-bold text-2xl mt-4 skew-x-[4deg] uppercase tracking-widest"></p>
                    </div>
                </div>
                <input type="hidden" name="coupon_code" :value="couponApplied ? coupon : ''">
                <input type="hidden" name="coupon_discount" :value="discount">

                <!-- EXECUTE BTN -->
                <div class="pt-8">
                    <button type="submit" class="w-full btn-speed text-center py-6 shadow-[12px_12px_0_#e11d48] hover:shadow-[4px_4px_0_#e11d48] hover:translate-x-2 hover:translate-y-2 border-4 border-dark">
                        <span class="font-display font-bold text-4xl md:text-5xl uppercase tracking-widest">DEPLOY GEAR PACKAGE <i class="fas fa-satellite-dish ml-4"></i></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: Supply Crate -->
        <div class="lg:col-span-5 relative mt-16 md:mt-12 lg:mt-0">
            <div class="sticky top-32 bg-gray-100 border-8 border-dark p-8 md:p-12 -skew-x-[2deg]">
                <div class="skew-x-[2deg]">
                    <h3 class="font-display font-bold text-5xl uppercase tracking-tighter text-dark mb-10 pb-4 border-b-8 border-primary">PAYLOAD SUMMARY</h3>
                    
                    <div class="flex gap-6 items-center bg-white p-4 border-4 border-dark mb-8 group relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 text-9xl font-display font-bold text-gray-100 opacity-50 z-0">01</div>
                        <div class="w-24 h-24 shrink-0 border-4 border-dark relative z-10 bg-gray-50 p-2">
                            <img src="{{ asset('storage/'.$product- loading="lazy">thumbnail) }}" class="w-full h-full object-cover mix-blend-multiply">
                            <div class="absolute -top-3 -right-3 w-8 h-8 bg-primary text-white font-display font-bold text-xl flex items-center justify-center -skew-x-[4deg] border-2 border-dark" x-text="qty"></div>
                        </div>
                        <div class="flex-1 relative z-10">
                            <h4 class="font-display font-bold text-2xl uppercase tracking-widest text-dark line-clamp-2 leading-tight mb-2">{{ $product->name }}</h4>
                            <div class="flex gap-2">
                                @if(request('color'))<span class="bg-dark text-white font-sans text-xs font-bold px-2 py-1 uppercase">{{ request('color') }}</span>@endif
                                @if(request('size'))<span class="bg-dark text-white font-sans text-xs font-bold px-2 py-1 uppercase">{{ request('size') }}</span>@endif
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 items-center border-b-4 border-dashed border-gray-300 pb-8 mb-8">
                        <span class="font-display font-bold text-2xl uppercase tracking-widest text-gray-500 w-1/3">MULTIPLIER</span>
                        <div class="flex flex-1 border-4 border-dark h-16 bg-white shrink-0">
                            <button type="button" @click="if(qty>1)qty--" class="w-16 h-full flex items-center justify-center font-display font-bold text-3xl hover:bg-gray-100"><i class="fas fa-minus text-xl"></i></button>
                            <input type="number" name="qty" x-model="qty" class="flex-1 text-center font-display font-bold text-3xl p-0 focus:ring-0 border-x-4 border-dark" readonly>
                            <button type="button" @click="qty++" class="w-16 h-full flex items-center justify-center font-display font-bold text-3xl hover:bg-gray-100"><i class="fas fa-plus text-xl"></i></button>
                        </div>
                    </div>

                    <div class="space-y-4 font-display font-bold text-2xl uppercase tracking-widest mb-10">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>RAW VALUE</span>
                            <span>৳<span x-text="subtotal.toLocaleString()"></span></span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600">
                            <span>TRANSPORT PROTOCOL</span>
                            <span>৳<span x-text="delivery"></span></span>
                        </div>
                        <div x-show="couponApplied" class="flex justify-between items-center bg-primary text-white px-4 py-2 mt-4 -skew-x-[4deg]">
                            <span class="skew-x-[4deg]">SYSTEM OVERRIDE</span>
                            <span class="skew-x-[4deg]">-৳<span x-text="discount"></span></span>
                        </div>
                    </div>

                    <div class="pt-8 border-t-[10px] border-dark">
                        <div class="flex justify-between items-end">
                            <span class="font-display font-bold text-3xl uppercase tracking-widest text-dark">FINAL MASS</span>
                            <span class="font-display font-bold text-6xl md:text-7xl leading-[0.8] tracking-tighter text-primary">৳<span x-text="total.toLocaleString()"></span></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection
