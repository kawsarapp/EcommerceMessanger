@extends('shop.themes.modern.layout')
@section('title', 'Complete Purchase | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); 
@endphp

<div class="max-w-[90rem] mx-auto px-6 py-16 md:py-24" x-data="{ 
    show: false,
    insideDhaka: true, 
    qty: {{request('qty',1)}}, 
    price: {{$product->sale_price ?? $product->regular_price}}, 
    deliveryInside: {{$client->delivery_charge_inside ?? 60}}, 
    deliveryOutside: {{$client->delivery_charge_outside ?? 120}}, 
    couponCode: '',
    couponDiscount: 0,
    couponApplied: false,
    couponError: '',
    termsAccepted: true,
    get subtotal() { return this.qty * this.price; },
    get delivery() { return this.insideDhaka ? this.deliveryInside : this.deliveryOutside; },
    get total() { return this.subtotal + this.delivery - this.couponDiscount; } 
}" x-init="setTimeout(() => show = true, 50)">
    
    <div class="mb-16 max-w-lg transition-all duration-300 ease-out" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
        <h1 class="text-4xl lg:text-5xl font-black tracking-tighter uppercase text-black mb-4">Checkout.</h1>
        <p class="text-gray-400 font-bold uppercase tracking-[0.15em] text-xs">Complete your purchase below.</p>
    </div>

    @if(session('success'))
        <div class="bg-black text-white p-6 mb-12 flex items-center gap-4 text-sm font-bold tracking-widest uppercase">
            <i class="fas fa-check"></i> {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 lg:gap-24 transition-all duration-500 ease-out delay-100" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
        
        <!-- Left: Form -->
        <div class="lg:col-span-7">
            <form action="{{$baseUrl.'/checkout/process'}}" method="POST" class="space-y-12">
                @csrf
                <input type="hidden" name="product_id" value="{{$product->id}}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <div>
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-6 border-b border-gray-200 pb-4">01. Contact Info</h3>
                    <div class="space-y-6">
                        <div>
                            <input type="text" name="customer_name" required class="w-full bg-transparent border-0 border-b-2 border-gray-200 px-0 py-4 text-black focus:ring-0 focus:border-black transition text-lg placeholder-gray-300 font-bold" placeholder="Full Name">
                        </div>
                        <div>
                            <input type="tel" name="customer_phone" required class="w-full bg-transparent border-0 border-b-2 border-gray-200 px-0 py-4 text-black focus:ring-0 focus:border-black transition text-lg placeholder-gray-300 font-bold" placeholder="Mobile Number (e.g. 017...)">
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-6 border-b border-gray-200 pb-4 mt-8">02. Shipping</h3>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="border border-gray-200 p-6 peer-checked:border-black peer-checked:bg-gray-50 transition text-center">
                                <span class="block font-black text-sm uppercase tracking-widest mb-1">Inside Dhaka</span>
                                <span class="block text-gray-500 font-medium">৳{{$client->delivery_charge_inside ?? 60}}</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="border border-gray-200 p-6 peer-checked:border-black peer-checked:bg-gray-50 transition text-center">
                                <span class="block font-black text-sm uppercase tracking-widest mb-1">Outside Dhaka</span>
                                <span class="block text-gray-500 font-medium">৳{{$client->delivery_charge_outside ?? 120}}</span>
                            </div>
                        </label>
                    </div>

                    <div>
                        <textarea name="shipping_address" required rows="2" class="w-full bg-transparent border-0 border-b-2 border-gray-200 px-0 py-4 text-black focus:ring-0 focus:border-black transition text-lg placeholder-gray-300 font-bold resize-none" placeholder="Detailed Address (House, Road, Area)"></textarea>
                    </div>
                </div>

                <div class="pt-8">
                    <button type="submit" class="w-full bg-black text-white hover:bg-gray-900 py-6 font-black text-sm uppercase tracking-[0.2em] transition duration-300 flex justify-center items-center gap-4">
                        Confirm Purchase <span class="opacity-50">|</span> ৳<span x-text="total"></span>
                    </button>
                    <p class="text-center text-[10px] text-gray-400 font-black tracking-widest uppercase mt-4">Cash on Delivery Enabled</p>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="lg:col-span-5">
            <div class="bg-gray-50 p-8 lg:p-12 sticky top-28 border border-gray-100">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-8 border-b border-gray-200 pb-4">Order Summary</h3>
                
                <div class="flex gap-6 mb-10 pb-8 border-b border-gray-200">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-20 aspect-[3/4] object-cover mix-blend-multiply border border-gray-200">
                    <div class="flex flex-col justify-center">
                        <h4 class="font-bold text-gray-900 text-lg uppercase tracking-tight mb-2 leading-none">{{$product->name}}</h4>
                        
                        <div class="flex items-center gap-3 text-xs text-gray-500 font-bold tracking-widest uppercase mb-4">
                            @if(request('color')) <span>{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span> @endif
                            @if(request('color') && request('size')) <span>&times;</span> @endif
                            @if(request('size')) <span>{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span> @endif
                        </div>
                        
                        <div class="font-black text-sm">৳{{number_format($product->sale_price ?? $product->regular_price)}} <span class="text-gray-400 font-medium">×</span> <span x-text="qty"></span></div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-4 font-bold text-sm text-gray-500 uppercase tracking-widest">
                    <div class="flex justify-between items-center">
                        <span>Subtotal</span>
                        <span class="text-gray-900">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Shipping</span>
                        <span class="text-gray-900">৳<span x-text="delivery"></span></span>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-6 mt-6 flex justify-between items-center text-black">
                        <span class="text-lg">Total</span>
                        <span class="text-3xl tracking-tighter">৳<span x-text="total"></span></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection