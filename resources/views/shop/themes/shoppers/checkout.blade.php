@extends('shop.themes.shoppers.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php 
    $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); 
    $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); 
@endphp

<style>
    .sh-breadcrumb { font-size: 11px; color: #6b7280; font-weight: 500; padding: 12px 16px; border-bottom: 1px solid #f3f4f6; margin-bottom: 24px; }
    .sh-input { width: 100%; border: 1px solid #d1d5db; border-radius: 2px; padding: 10px 14px; font-size: 13px; color: #4b5563; transition: border 0.2s; outline: none; background: #fff; }
    .sh-input:focus { border-color: #eb484e; box-shadow: 0 0 0 1px #eb484e1a; }
    .sh-label { display: block; font-size: 12px; font-weight: 700; color: #4b5563; margin-bottom: 6px; }
    .sh-box { border: 1px solid #e5e7eb; background: #fff; border-radius: 2px; }
    .sh-box-title { font-size: 16px; font-weight: 700; color: #333; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; display: flex; align-items: center; gap: 8px; }
    
    .sh-radio-btn { display: flex; align-items: flex-start; gap: 12px; border: 1px solid #e5e7eb; padding: 16px; border-radius: 2px; cursor: pointer; transition: all 0.2s; background: #fff; }
    .sh-radio-btn:hover { border-color: #eb484e; }
    .sh-radio-input:checked + .sh-radio-btn { border-color: #eb484e; background: #fff0f1; }
</style>

<div class="max-w-[1240px] mx-auto bg-[#fafafa]" x-data="checkoutApp()">
<script>
function checkoutApp() {
    return {
        shippingMethods: {!! json_encode($shippingMethods ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        shippingMethodId: {{ (isset($shippingMethods) && $shippingMethods->count() > 0) ? $shippingMethods->first()->id : 'null' }},
        area: 'inside', // 'inside', 'outside'
        paymentMethod: 'cod',
        qty: {{ request('qty', 1) }},
        price: {{ $product->sale_price ?? $product->regular_price }},
        
        get delivery() {
            if (this.shippingMethods && this.shippingMethods.length > 0) {
                let sm = this.shippingMethods.find(m => m.id == this.shippingMethodId);
                return sm ? parseFloat(sm.cost) : 0;
            } else {
                return this.area === 'inside' ? {{ $client->delivery_charge_inside ?? 50 }} : {{ $client->delivery_charge_outside ?? 100 }};
            }
        },
        get subtotal() { return this.qty * this.price; },
        
        couponCode: '', couponDiscount: 0, couponApplied: false, couponError: '',
        get total() { return this.subtotal + this.delivery - this.couponDiscount; },
        
        applyCoupon() {
            if(!this.couponCode) { this.couponError = 'Enter a coupon code'; return; }
            fetch('{{ $clean ? $baseUrl.'/apply-coupon' : route('shop.apply-coupon.sub', $client->slug) }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({code: this.couponCode, product_id: {{ $product->id }}, subtotal: this.subtotal})
            }).then(r => r.json()).then(d => {
                if(d.success) { this.couponDiscount = d.discount; this.couponApplied = true; this.couponError = ''; }
                else { this.couponError = d.message || 'Invalid coupon'; }
            });
        }
    };
}
</script>
    
    <div class="sh-breadcrumb flex items-center gap-2 bg-white mb-8 border-t">
        <a href="{{$baseUrl}}" class="hover:text-shred transition">Home</a>
        <i class="fas fa-angle-double-right text-[8px] text-gray-400 mt-[1px]"></i>
        <span class="text-gray-400 font-normal">Checkout</span>
    </div>

    <div class="px-4 pb-16">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 p-4 mb-6 text-sm font-bold flex items-center gap-2">
                <i class="fas fa-check-circle text-lg"></i> {{ session('success') }}
            </div>
        @endif

        <form action="{{ $baseUrl.'/checkout/process' }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="qty" :value="qty">
            @if(request('color'))<input type="hidden" name="color" value="{{ request('color') }}">@endif
            @if(request('size'))<input type="hidden" name="size" value="{{ request('size') }}">@endif
            <input type="hidden" name="shipping_method_id" :value="shippingMethodId">
            <input type="hidden" name="area" :value="area">
            <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
            <input type="hidden" name="coupon_discount" :value="couponDiscount">

            {{-- Checkout Left Flow --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- Address Box --}}
                <div class="sh-box shadow-sm">
                    <div class="sh-box-title"><span class="bg-shred text-white w-6 h-6 rounded flex items-center justify-center text-xs">1</span> CUSTOMER INFORMATION</div>
                    <div class="p-6">
                        <div class="grid sm:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="sh-label">First & Last Name <span class="text-shred">*</span></label>
                                <input type="text" name="customer_name" required placeholder="Enter full name" class="sh-input">
                            </div>
                            <div>
                                <label class="sh-label">Mobile Number <span class="text-shred">*</span></label>
                                <input type="tel" name="customer_phone" required placeholder="01XXXXXXXXX" class="sh-input font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="sh-label">Delivery Address <span class="text-shred">*</span></label>
                            <textarea name="shipping_address" required rows="3" placeholder="House/Apt, Street, Area" class="sh-input resize-none"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Delivery Options --}}
                <div class="sh-box shadow-sm">
                    <div class="sh-box-title"><span class="bg-shred text-white w-6 h-6 rounded flex items-center justify-center text-xs">2</span> DELIVERY OPTION</div>
                    <div class="p-6 grid sm:grid-cols-2 gap-4">
                        @if(isset($shippingMethods) && $shippingMethods->count() > 0)
                            @foreach($shippingMethods as $method)
                            <label class="relative block">
                                <input type="radio" name="_sm_temp" value="{{ $method->id }}" @change="shippingMethodId = {{ $method->id }}" class="peer hidden sh-radio-input" :checked="shippingMethodId == {{ $method->id }}">
                                <div class="sh-radio-btn h-full shadow-sm">
                                    <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-shred flex items-center justify-center shrink-0 mt-0.5">
                                        <div class="w-2 h-2 rounded-full bg-shred opacity-0 peer-checked:opacity-100" :class="{'opacity-100': shippingMethodId == {{ $method->id }}}"></div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[13px] font-bold text-gray-800">{{ $method->name }}</div>
                                        <div class="text-[11px] text-gray-500 mt-1">Delivery Charge: <span class="text-shred font-bold">{!! $method->cost > 0 ? 'TK '.number_format($method->cost) : 'Free' !!}</span></div>
                                        @if($method->estimated_time)
                                            <div class="text-[10px] text-gray-400 mt-1">{{ $method->estimated_time }}</div>
                                        @endif
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        @else
                            <label class="relative block">
                                <input type="radio" name="_area_temp" value="inside" @change="area = 'inside'" class="peer hidden sh-radio-input" :checked="area === 'inside'">
                                <div class="sh-radio-btn h-full shadow-sm">
                                    <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-shred flex items-center justify-center shrink-0 mt-0.5">
                                        <div class="w-2 h-2 rounded-full bg-shred opacity-0 peer-checked:opacity-100" :class="{'opacity-100': area === 'inside'}"></div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[13px] font-bold text-gray-800">Inside Dhaka</div>
                                        <div class="text-[11px] text-gray-500 mt-1">Delivery Charge: <span class="text-shred font-bold">TK {{$client->delivery_charge_inside ?? 50}}</span></div>
                                        <div class="text-[10px] text-gray-400 mt-1">2-3 Working Days</div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative block">
                                <input type="radio" name="_area_temp" value="outside" @change="area = 'outside'" class="peer hidden sh-radio-input" :checked="area === 'outside'">
                                <div class="sh-radio-btn h-full shadow-sm">
                                    <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-shred flex items-center justify-center shrink-0 mt-0.5">
                                        <div class="w-2 h-2 rounded-full bg-shred opacity-0 peer-checked:opacity-100" :class="{'opacity-100': area === 'outside'}"></div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[13px] font-bold text-gray-800">Outside Dhaka</div>
                                        <div class="text-[11px] text-gray-500 mt-1">Delivery Charge: <span class="text-shred font-bold">TK {{$client->delivery_charge_outside ?? 100}}</span></div>
                                        <div class="text-[10px] text-gray-400 mt-1">3-5 Working Days</div>
                                    </div>
                                </div>
                            </label>
                        @endif
                    </div>
                </div>

                {{-- Payment --}}
                <div class="sh-box shadow-sm">
                    <div class="sh-box-title"><span class="bg-shred text-white w-6 h-6 rounded flex items-center justify-center text-xs">3</span> PAYMENT METHOD</div>
                    <div class="p-6">
                        <label class="relative block mb-3">
                            <input type="radio" name="_pmt" value="cod" @change="paymentMethod='cod'" class="peer hidden sh-radio-input" checked>
                            <div class="sh-radio-btn shadow-sm items-center">
                                <div class="w-4 h-4 rounded-full border border-gray-300 peer-checked:border-shred flex items-center justify-center shrink-0">
                                    <div class="w-2 h-2 rounded-full bg-shred opacity-0 peer-checked:opacity-100" :class="{'opacity-100': paymentMethod==='cod'}"></div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-hand-holding-usd text-[22px] text-gray-400"></i>
                                    <div>
                                        <div class="text-[13px] font-bold text-gray-800">Cash On Delivery (COD)</div>
                                        <div class="text-[10px] text-gray-500">Pay when you receive the product</div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            {{-- Checkout Right Flow (Review) --}}
            <div class="lg:col-span-4">
                <div class="sh-box shadow-sm sticky top-6">
                    <div class="sh-box-title bg-shdark text-white border-shdark">ORDER SUMMARY</div>
                    
                    <div class="p-6">
                        {{-- Product Preview --}}
                        <div class="flex gap-4 border-b border-gray-100 pb-4 mb-4">
                            <div class="w-16 h-16 border border-gray-200 bg-white p-1 shrink-0">
                                <img src="{{ asset('storage/'.$product->thumbnail) }}" class="w-full h-full object-contain">
                            </div>
                            <div class="flex flex-col justify-center">
                                <h4 class="text-xs font-medium text-gray-700 line-clamp-2 leading-tight mb-2">{{ $product->name }}</h4>
                                <div class="text-[11px] text-gray-500 font-bold">Qty: <span x-text="qty"></span> <span class="mx-2">|</span> <span class="text-shred">TK <span x-text="(qty * price).toLocaleString()"></span></span></div>
                                @if(request('color') || request('size'))
                                    <div class="text-[9px] text-gray-400 mt-1 uppercase">{{ request('color') }} {{ request('size') }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- Coupon Form --}}
                        <div class="mb-5 pb-5 border-b border-gray-100">
                            <div class="flex gap-0" x-show="!couponApplied">
                                <input type="text" x-model="couponCode" placeholder="Enter coupon code" class="sh-input !border-r-0 !rounded-r-none uppercase font-mono h-10">
                                <button type="button" @click="applyCoupon()" class="bg-gray-800 hover:bg-black text-white px-4 h-10 font-bold text-[11px] uppercase transition rounded-r">Apply</button>
                            </div>
                            <div x-show="couponApplied" class="bg-green-50 border border-green-200 p-2 flex justify-between items-center text-[11px] font-bold text-green-700">
                                <div><i class="fas fa-check mr-1"></i> Coupon '<span x-text="couponCode" class="uppercase"></span>' Applied</div>
                                <button type="button" @click="couponApplied=false; couponDiscount=0; couponCode=''" class="text-red-500 hover:underline">Remove</button>
                            </div>
                            <p x-show="couponError" x-text="couponError" class="text-[10px] text-shred mt-1 font-medium"></p>
                        </div>

                        {{-- Totals --}}
                        <div class="space-y-3 text-xs mb-6">
                            <div class="flex justify-between text-gray-600"><span class="font-medium">Sub-Total:</span> <span>TK <span x-text="subtotal.toLocaleString()"></span></span></div>
                            <div class="flex justify-between text-gray-600"><span class="font-medium">Delivery Fee:</span> <span>TK <span x-text="delivery"></span></span></div>
                            <div x-show="couponApplied" class="flex justify-between text-shred"><span class="font-bold">Discount:</span> <span class="font-bold">-TK <span x-text="couponDiscount.toLocaleString()"></span></span></div>
                        </div>
                        
                        <div class="flex justify-between items-end border-t border-gray-200 pt-4 mb-8">
                            <span class="text-sm font-bold text-gray-800 uppercase">Total Amount:</span>
                            <span class="text-2xl font-black text-shred tracking-tight leading-none">TK <span x-text="total.toLocaleString()"></span></span>
                        </div>

                        @if($client->show_terms_checkbox ?? false)
                        <div class="mb-5">
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" required class="mt-1 w-4 h-4 text-shred bg-white border-gray-300 rounded focus:ring-shred focus:ring-2">
                                <span class="text-[11px] text-gray-600 font-medium group-hover:text-gray-800 transition">
                                    I have read and agree to the <a href="#" class="text-shred hover:underline font-bold">Terms and Conditions</a> and <a href="#" class="text-shred hover:underline font-bold">Privacy Policy</a>
                                </span>
                            </label>
                        </div>
                        @endif

                        <button type="submit" class="w-full bg-shred hover:bg-[#d63d42] text-white font-bold py-4 text-sm uppercase tracking-wider transition rounded-sm shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-lock"></i> PLACE ORDER
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection
