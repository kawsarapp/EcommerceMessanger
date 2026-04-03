@extends('shop.themes.shwapno.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .sw-checkout-title { font-size: 24px; font-weight: 800; color: #333; margin-bottom: 24px; text-align: center; }
    .sw-input-group { margin-bottom: 20px; }
    .sw-label { display: block; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; }
    .sw-input { w-full; border: 1px solid #d1d5db; border-radius: 4px; padding: 12px 14px; font-size: 14px; color: #4b5563; transition: all 0.2s; background: #fff; width: 100%; outline: none; }
    .sw-input:focus { border-color: #e31e24; box-shadow: 0 0 0 3px rgba(227,30,36,0.1); }
    
    .sw-box { background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .sw-box-header { font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px; }
    
    .sw-radio-container { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #e5e7eb; border-radius: 4px; cursor: pointer; transition: all 0.2s; margin-bottom: 12px; }
    .sw-radio-container:hover { border-color: #fca5a5; background: #fef2f2; }
    .sw-radio-input:checked + .sw-radio-container { border-color: #e31e24; background: #fff1f2; }
    
    .sw-btn-pill { border-radius: 9999px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer; }
    .sw-btn-red { background-color: #e31e24; color: #fff; border: 1px solid #e31e24; }
    .sw-btn-red:hover { background-color: #c8161c; border-color: #c8161c; }
</style>

<div class="max-w-[1100px] mx-auto px-4 py-10" x-data="{
    area: 'inside', // 'inside', 'outside'
    paymentMethod: 'cod',
    qty: {{ request('qty', 1) }},
    price: {{ $product->sale_price ?? $product->regular_price }},
    
    get delivery() { return this.area === 'inside' ? {{ $client->delivery_charge_inside ?? 60 }} : {{ $client->delivery_charge_outside ?? 120 }}; },
    get subtotal() { return this.qty * this.price; },
    
    couponCode: '', couponDiscount: 0, couponApplied: false, couponError: '',
    get total() { return this.subtotal + this.delivery - this.couponDiscount; },
    
    applyCoupon() {
        if(!this.couponCode) { this.couponError = 'Please enter a coupon code'; return; }
        fetch('{{ route(''shop.apply-coupon.sub'', \->slug) }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({code: this.couponCode, product_id: {{ $product->id }}, subtotal: this.subtotal})
        }).then(r => r.json()).then(d => {
            if(d.success) { this.couponDiscount = d.discount; this.couponApplied = true; this.couponError = ''; }
            else { this.couponError = d.message || 'Invalid coupon'; }
        });
    }
}">

    <h1 class="sw-checkout-title">Secure Checkout</h1>
    
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded mb-6 text-sm font-bold flex items-center justify-center gap-2">
            <i class="fas fa-check-circle text-lg"></i> {{ session('success') }}
        </div>
    @endif

    <form action="{{ $baseUrl.'/checkout/process' }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="qty" :value="qty">
        @if(request('color'))<input type="hidden" name="color" value="{{ request('color') }}">@endif
        @if(request('size'))<input type="hidden" name="size" value="{{ request('size') }}">@endif
        <input type="hidden" name="area" :value="area">
        <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
        <input type="hidden" name="coupon_discount" :value="couponDiscount">

        {{-- Left Details Form --}}
        <div class="lg:col-span-7">
            
            <div class="sw-box">
                <div class="sw-box-header"><i class="fas fa-user-circle text-gray-400"></i> Delivery Information</div>
                
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sw-input-group">
                        <label class="sw-label">Full Name <span class="text-swred">*</span></label>
                        <input type="text" name="customer_name" required placeholder="John Doe" class="sw-input">
                    </div>
                    <div class="sw-input-group">
                        <label class="sw-label">Mobile Number <span class="text-swred">*</span></label>
                        <input type="tel" name="customer_phone" required placeholder="01XXXXXXXXX" class="sw-input">
                    </div>
                </div>
                
                <div class="sw-input-group mb-0">
                    <label class="sw-label">Complete Address <span class="text-swred">*</span></label>
                    <textarea name="shipping_address" required rows="3" placeholder="House No, Road No, Area, City" class="sw-input resize-none"></textarea>
                </div>
            </div>

            <div class="sw-box">
                <div class="sw-box-header"><i class="fas fa-truck text-gray-400"></i> Shipping Zone</div>
                
                <div class="grid sm:grid-cols-2 gap-4">
                    <label class="relative block m-0 p-0">
                        <input type="radio" name="_area_temp" value="inside" @change="area = 'inside'" class="peer hidden sw-radio-input" checked>
                        <div class="sw-radio-container h-full">
                            <div class="w-5 h-5 rounded-full border border-gray-300 peer-checked:border-swred flex items-center justify-center shrink-0 bg-white mt-0.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-swred opacity-0" :class="{'opacity-100': area === 'inside'}"></div>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-gray-800">Inside Dhaka</div>
                                <div class="text-[11px] text-gray-500 mt-1">Delivery Charge: <span class="text-swred font-bold">৳{{$client->delivery_charge_inside ?? 60}}</span></div>
                            </div>
                        </div>
                    </label>

                    <label class="relative block m-0 p-0">
                        <input type="radio" name="_area_temp" value="outside" @change="area = 'outside'" class="peer hidden sw-radio-input">
                        <div class="sw-radio-container h-full">
                            <div class="w-5 h-5 rounded-full border border-gray-300 peer-checked:border-swred flex items-center justify-center shrink-0 bg-white mt-0.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-swred opacity-0" :class="{'opacity-100': area === 'outside'}"></div>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-gray-800">Outside Dhaka</div>
                                <div class="text-[11px] text-gray-500 mt-1">Delivery Charge: <span class="text-swred font-bold">৳{{$client->delivery_charge_outside ?? 120}}</span></div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="sw-box">
                <div class="sw-box-header"><i class="fas fa-wallet text-gray-400"></i> Payment Method</div>
                
                <label class="relative block">
                    <input type="radio" name="_pmt" value="cod" @change="paymentMethod='cod'" class="peer hidden sw-radio-input" checked>
                    <div class="sw-radio-container items-center mb-0">
                        <div class="w-5 h-5 rounded-full border border-gray-300 peer-checked:border-swred flex items-center justify-center shrink-0 bg-white">
                            <div class="w-2.5 h-2.5 rounded-full bg-swred opacity-0" :class="{'opacity-100': paymentMethod==='cod'}"></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="far fa-money-bill-alt text-2xl text-green-600"></i>
                            <div>
                                <div class="text-sm font-bold text-gray-800">Cash On Delivery (COD)</div>
                                <div class="text-[11px] text-gray-500">Pay when you receive the product</div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

        </div>

        {{-- Right Order Summary Area --}}
        <div class="lg:col-span-5">
            <div class="sw-box border-t-4 border-t-swyellow sticky top-6">
                <div class="text-lg font-black text-gray-800 mb-6 flex justify-between items-center">
                    Order Summary
                    <span class="bg-gray-100 text-gray-500 py-1 px-3 rounded-full text-[10px]"><span x-text="qty"></span> Items</span>
                </div>
                
                {{-- Product Preview --}}
                <div class="flex gap-4 border border-gray-100 rounded p-3 mb-6 bg-gray-50">
                    <div class="w-16 h-16 bg-white border border-gray-200 rounded shrink-0 p-1">
                        <img src="{{ asset('storage/'.$product->thumbnail) }}" loading="lazy" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col justify-center">
                        <h4 class="text-xs font-bold text-gray-700 leading-tight mb-2">{{ $product->name }}</h4>
                        <div class="text-[11px] text-gray-500 font-medium">Qty: <span x-text="qty"></span> <span class="mx-2">|</span> <span>৳<span x-text="(qty * price).toLocaleString()"></span></span></div>
                        @if(request('color') || request('size'))
                            <div class="text-[10px] text-gray-400 mt-1 uppercase">{{ request('color') }} {{ request('size') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Promo Code --}}
                <div class="mb-6 bg-white">
                    <div class="flex gap-2" x-show="!couponApplied">
                        <input type="text" x-model="couponCode" placeholder="Promo/Voucher Code" class="sw-input text-xs uppercase font-medium h-[42px]">
                        <button type="button" @click="applyCoupon()" class="bg-gray-800 hover:bg-black text-white px-5 rounded h-[42px] font-bold text-[11px] uppercase transition shadow-sm">Apply</button>
                    </div>
                    <div x-show="couponApplied" class="bg-green-50 border border-green-200 p-3 flex justify-between items-center text-xs font-bold text-green-700 rounded-sm">
                        <div><i class="fas fa-check-circle mr-1"></i> Coupon <span x-text="couponCode" class="uppercase"></span> Applied</div>
                        <button type="button" @click="couponApplied=false; couponDiscount=0; couponCode=''" class="text-red-500 hover:text-red-700 underline text-[10px]">Remove</button>
                    </div>
                    <p x-show="couponError" x-text="couponError" class="text-[10px] text-red-500 mt-1.5 font-medium"></p>
                </div>

                {{-- Totals --}}
                <div class="space-y-4 text-[13px] mb-6 border-b border-gray-100 pb-6">
                    <div class="flex justify-between text-gray-600"><span class="font-medium">Sub-Total (<span x-text="qty"></span>x)</span> <span class="font-bold">৳<span x-text="subtotal.toLocaleString()"></span></span></div>
                    <div class="flex justify-between text-gray-600"><span class="font-medium">Delivery Fee</span> <span class="font-bold text-gray-800">৳<span x-text="delivery"></span></span></div>
                    <div x-show="couponApplied" class="flex justify-between text-red-500"><span class="font-bold">Discount</span> <span class="font-bold">- ৳<span x-text="couponDiscount.toLocaleString()"></span></span></div>
                </div>
                
                <div class="flex justify-between items-center bg-gray-50 p-4 rounded mb-6 border border-gray-100">
                    <span class="text-sm font-bold text-gray-800 uppercase">Total Payable</span>
                    <span class="text-2xl font-black text-swred">৳<span x-text="total.toLocaleString()"></span></span>
                </div>

                @if(!empty($client->show_terms_checkbox))
                <div class="mb-6 p-4 bg-red-50/50 border border-red-100 rounded">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="terms" required class="mt-0.5 min-w-[16px] w-4 h-4 text-swred bg-white border-gray-300 rounded focus:ring-swred accent-swred cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-gray-800 block">
                                I have read and agree to the 
                                @if(!empty($client->terms_conditions_url))
                                    <a href="{{ $client->terms_conditions_url }}" target="_blank" class="text-blue-600 hover:underline">Terms & Conditions</a>
                                @else
                                    Terms & Conditions
                                @endif
                                <span class="text-swred ml-1">*</span>
                            </span>
                            @if(!empty($client->terms_conditions_text))
                            <div class="text-[10px] text-gray-500 font-medium leading-relaxed mt-1.5 flex gap-1.5">
                                <i class="fas fa-info-circle mt-0.5 text-blue-400"></i>
                                <span>{{ $client->terms_conditions_text }}</span>
                            </div>
                            @endif
                        </div>
                    </label>
                </div>
                @endif

                <div class="text-center">
                    <button type="submit" class="w-full sw-btn-pill sw-btn-red py-4 text-sm shadow-md hover:shadow-lg hover:-translate-y-0.5 transition duration-300">
                        CONFIRM ORDER <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
                    </button>
                    <p class="text-[9px] text-gray-400 mt-3 font-medium flex items-center justify-center"><i class="fas fa-lock text-gray-300 mr-1.5"></i> End-to-end encrypted secure checkout</p>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection
