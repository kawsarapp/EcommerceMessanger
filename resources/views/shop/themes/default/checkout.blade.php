@extends('shop.themes.default.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); 
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-16" x-data="{ 
    insideDhaka: true, 
    qty: {{request('qty',1)}}, 
    price: {{$product->sale_price ?? $product->regular_price}}, 
    deliveryInside: {{$client->delivery_charge_inside ?? 60}}, 
    deliveryOutside: {{$client->delivery_charge_outside ?? 120}}, 
    couponCode: '',
    couponDiscount: 0,
    couponApplied: false,
    couponError: '',
    termsAccepted: {{ ($client->show_terms_checkbox ?? false) ? 'false' : 'true' }},
    get subtotal() { return this.qty * this.price; },
    get delivery() { return this.insideDhaka ? this.deliveryInside : this.deliveryOutside; },
    get total() { return this.subtotal + this.delivery - this.couponDiscount; },
    applyCoupon() {
        if (!this.couponCode.trim()) { this.couponError = 'Please enter a coupon code'; return; }
        this.couponError = '';
        fetch('{{ $baseUrl }}/apply-coupon', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({code: this.couponCode, client_id: {{ $client->id }}, subtotal: this.subtotal})
        }).then(r => r.json()).then(data => {
            if (data.success) { this.couponDiscount = data.discount; this.couponApplied = true; this.couponError = ''; }
            else { this.couponError = data.message || 'Invalid coupon code'; this.couponDiscount = 0; }
        }).catch(() => { this.couponError = 'Could not verify coupon'; });
    },
    removeCoupon() { this.couponCode = ''; this.couponDiscount = 0; this.couponApplied = false; this.couponError = ''; }
}">
    
    <div class="mb-10 text-center sm:text-left">
        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight mb-3">Checkout</h1>
        <p class="text-base font-medium text-slate-500">Securely complete your purchase.</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl mb-10 flex items-start sm:items-center gap-4 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fas fa-check text-lg"></i>
            </div>
            <div>
                <h4 class="text-emerald-800 font-bold text-lg mb-0.5">Order Placed Successfully</h4>
                <p class="text-emerald-600 font-medium text-sm">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-10 lg:gap-16">
        
        <!-- Left Column: Form -->
        <div class="w-full lg:w-7/12 order-2 lg:order-1 relative group/form">
            <div class="absolute -top-20 -left-20 w-64 h-64 bg-primary/10 rounded-full blur-[80px] pointer-events-none group-hover/form:scale-110 premium-transition"></div>
            <form action="{{$baseUrl.'/checkout/process'}}" method="POST" class="space-y-10 glass-panel p-8 md:p-12 rounded-[2.5rem] border border-white relative z-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                @csrf
                <input type="hidden" name="product_id" value="{{$product->id}}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <!-- Section 1 -->
                <div>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">1</div>
                        <h3 class="text-xl font-bold text-slate-900">Shipping Details</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest pl-1">Full Name</label>
                            <input type="text" name="customer_name" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 font-semibold focus:border-primary focus:ring-4 focus:ring-primary/5 focus:bg-white premium-transition placeholder-slate-400 text-sm" placeholder="John Doe">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest pl-1">Phone Number</label>
                            <input type="tel" name="customer_phone" required class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 font-semibold focus:border-primary focus:ring-4 focus:ring-primary/5 focus:bg-white premium-transition placeholder-slate-400 text-sm" placeholder="e.g. 01XXXXXXXXX">
                        </div>
                        
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest pl-1">Full Address</label>
                            <textarea name="shipping_address" required rows="3" class="w-full bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3.5 text-slate-900 font-semibold focus:border-primary focus:ring-4 focus:ring-primary/5 focus:bg-white premium-transition placeholder-slate-400 resize-none text-sm" placeholder="House, Road, Block, City"></textarea>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <!-- Section 2 -->
                <div>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">2</div>
                        <h3 class="text-xl font-bold text-slate-900">Delivery Method</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <label class="cursor-pointer group">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="bg-white border-2 border-slate-100 rounded-xl p-5 peer-checked:border-primary peer-checked:bg-primary/5 premium-transition relative overflow-hidden flex justify-between items-center group-hover:border-slate-300">
                                <div>
                                    <span class="block text-base font-bold text-slate-900 mb-1">Inside Dhaka</span>
                                    <span class="block text-sm font-semibold text-slate-500">Fast Delivery</span>
                                </div>
                                <span class="font-extrabold text-lg text-primary">৳{{$client->delivery_charge_inside ?? 60}}</span>
                                <div class="absolute inset-0 border-2 border-primary rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none"></div>
                            </div>
                        </label>
                        <label class="cursor-pointer group">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="bg-white border-2 border-slate-100 rounded-xl p-5 peer-checked:border-primary peer-checked:bg-primary/5 premium-transition relative overflow-hidden flex justify-between items-center group-hover:border-slate-300">
                                <div>
                                    <span class="block text-base font-bold text-slate-900 mb-1">Outside Dhaka</span>
                                    <span class="block text-sm font-semibold text-slate-500">Standard Delivery</span>
                                </div>
                                <span class="font-extrabold text-lg text-primary">৳{{$client->delivery_charge_outside ?? 120}}</span>
                                <div class="absolute inset-0 border-2 border-primary rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none"></div>
                            </div>
                        </label>
                    </div>
                </div>

                <hr class="border-slate-100">

                {{-- Checkout Extras: Coupon & Terms --}}
                @include('shop.partials.checkout-extras', ['client' => $client, 'product' => $product])

                <!-- Submit Section -->
                <div class="pt-6 relative">
                    <button type="submit" class="w-full bg-slate-900 text-white py-5 rounded-2xl font-bold text-lg uppercase tracking-widest hover:bg-primary premium-transition shadow-lg hover:shadow-primary/30 hover:-translate-y-1 flex items-center justify-center gap-3 relative overflow-hidden group/btn">
                        <span class="absolute inset-0 w-full h-full bg-white/20 -translate-x-full skew-x-12 group-hover/btn:animate-[shimmer_1.5s_infinite]"></span>
                        <span class="relative z-10 flex items-center gap-2">Confirm Order <i class="fas fa-arrow-right"></i></span>
                    </button>
                    <div class="flex items-center justify-center gap-2 mt-5 text-slate-400">
                        <i class="fas fa-lock text-sm"></i>
                        <span class="text-xs font-semibold uppercase tracking-wider">Cash on Delivery Available</span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Order Summary (Sticky Sidebar) -->
        <div class="w-full lg:w-5/12 order-1 lg:order-2">
            <div class="glass-panel bg-white/40 rounded-[2.5rem] p-8 md:p-10 sticky top-28 border border-white shadow-sm overflow-hidden relative group/summary">
                <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-primary/10 rounded-full blur-[80px] pointer-events-none group-hover/summary:scale-110 premium-transition z-0"></div>
                
                <h3 class="font-extrabold text-slate-900 text-2xl mb-8 tracking-tight relative z-10">Your Order</h3>
                
                <!-- Product Line Item -->
                <div class="flex gap-5 mb-8 bg-white/70 backdrop-blur-md p-4 rounded-2xl border border-white shadow-sm relative premium-transition hover:translate-x-1 hover:shadow-md z-10">
                    <!-- Badge -->
                    <div class="absolute -top-3 -right-3 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-bold text-xs shadow-md z-10" x-text="qty"></div>
                    
                    <div class="w-24 aspect-square bg-slate-50 rounded-xl border border-slate-100 p-2 flex items-center justify-center shrink-0">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </div>
                    
                    <div class="flex flex-col justify-center flex-1 py-1 pr-4">
                        <h4 class="font-bold text-slate-900 text-sm leading-snug mb-2 line-clamp-2">{{$product->name}}</h4>
                        
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if(request('color')) <span class="bg-slate-100 text-slate-600 text-[10px] uppercase font-bold px-2 py-0.5 rounded-md">{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span> @endif
                            @if(request('size')) <span class="bg-slate-100 text-slate-600 text-[10px] uppercase font-bold px-2 py-0.5 rounded-md">{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span> @endif
                        </div>
                        
                        <span class="font-extrabold text-slate-900 text-base">৳{{number_format($product->sale_price ?? $product->regular_price)}}</span>
                    </div>
                </div>

                <hr class="border-slate-200/60 mb-6 relative z-10">

                <!-- Totals -->
                <div class="space-y-4 text-sm font-semibold text-slate-600 relative z-10">
                    <div class="flex justify-between items-center">
                        <span>Items Subtotal</span>
                        <span class="text-slate-900">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Delivery Fee</span>
                        <span class="text-slate-900">৳<span x-text="delivery"></span></span>
                    </div>
                    <div x-show="couponApplied" class="flex justify-between items-center text-emerald-600">
                        <span><i class="fas fa-tag mr-1"></i> Coupon Discount</span>
                        <span>-৳<span x-text="couponDiscount"></span></span>
                    </div>
                    
                    <div class="pt-6 mt-4 border-t border-slate-200/60">
                        <div class="flex justify-between items-center bg-white/70 backdrop-blur-md p-5 rounded-2xl border border-white shadow-sm hover:shadow-md premium-transition">
                            <span class="font-extrabold text-slate-900 text-lg uppercase tracking-widest text-[13px]">Total Amount</span>
                            <span class="text-3xl font-extrabold text-primary tracking-tight">৳<span x-text="total"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
