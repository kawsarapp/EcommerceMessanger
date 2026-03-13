@extends('shop.themes.fashion.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); 
@endphp

<div class="max-w-[100rem] mx-auto px-4 sm:px-8 py-16 md:py-24" x-data="{ 
    insideDhaka: true, 
    qty: {{request('qty',1)}}, 
    price: {{$product->sale_price ?? $product->regular_price}}, 
    deliveryInside: {{$client->delivery_charge_inside ?? 60}}, 
    deliveryOutside: {{$client->delivery_charge_outside ?? 120}}, 
    get total() { return (this.qty * this.price) + (this.insideDhaka ? this.deliveryInside : this.deliveryOutside); } 
}">
    
    <div class="mb-12 md:mb-20">
        <h1 class="font-heading font-black text-4xl md:text-6xl text-primary text-center">Secure Checkout</h1>
    </div>

    @if(session('success'))
        <div class="border border-green-200 bg-green-50/50 p-6 mb-12 text-center">
            <span class="text-xs font-bold text-green-700 uppercase tracking-widest"><i class="fas fa-check mr-2"></i> {{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-16 lg:gap-24">
        
        <!-- Left: Form -->
        <div class="w-full lg:w-7/12 order-2 lg:order-1">
            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="space-y-12 pb-12">
                @csrf
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <div class="pb-10 border-b border-gray-100">
                    <h3 class="font-heading font-semibold text-2xl mb-8">1. Delivery Address</h3>
                    <div class="space-y-8">
                        <div>
                            <input type="text" name="customer_name" required class="w-full bg-transparent border-0 border-b border-gray-300 px-0 py-3 text-gray-900 focus:ring-0 focus:border-black transition text-sm font-medium tracking-wide placeholder-gray-400" placeholder="Recipient's Name">
                        </div>
                        <div>
                            <input type="tel" name="customer_phone" required class="w-full bg-transparent border-0 border-b border-gray-300 px-0 py-3 text-gray-900 focus:ring-0 focus:border-black transition text-sm font-medium tracking-wide placeholder-gray-400" placeholder="Mobile Number (01...)">
                        </div>
                        <div>
                            <textarea name="shipping_address" required rows="2" class="w-full bg-transparent border-0 border-b border-gray-300 px-0 py-3 text-gray-900 focus:ring-0 focus:border-black transition text-sm font-medium tracking-wide placeholder-gray-400 resize-none" placeholder="Detailed Address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="pb-10 border-b border-gray-100">
                    <h3 class="font-heading font-semibold text-2xl mb-8">2. Shipping Method</h3>
                    
                    <div class="flex flex-col sm:flex-row gap-6">
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="border border-gray-200 p-8 text-center peer-checked:border-black transition group-hover:border-gray-400">
                                <span class="block text-xs font-bold uppercase tracking-[0.1em] text-gray-900 mb-2">Inside Dhaka</span>
                                <span class="block text-sm font-medium text-gray-500">৳{{$client->delivery_charge_inside ?? 60}}</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="border border-gray-200 p-8 text-center peer-checked:border-black transition group-hover:border-gray-400">
                                <span class="block text-xs font-bold uppercase tracking-[0.1em] text-gray-900 mb-2">Outside Dhaka</span>
                                <span class="block text-sm font-medium text-gray-500">৳{{$client->delivery_charge_outside ?? 120}}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-primary text-white py-6 font-semibold text-xs tracking-[0.2em] uppercase hover:bg-black transition duration-300">
                        Complete Order
                    </button>
                    <p class="text-center text-[10px] text-gray-400 font-medium tracking-widest uppercase mt-6">Payment upon delivery.</p>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="w-full lg:w-5/12 order-1 lg:order-2">
            <div class="bg-gray-50/50 p-8 lg:p-12 sticky top-32 border border-gray-100">
                <h3 class="font-heading font-semibold text-2xl mb-10 pb-4 border-b border-gray-200">Summary</h3>
                
                <div class="flex gap-6 mb-10">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-24 aspect-[3/4] object-cover border border-gray-200">
                    <div class="flex flex-col justify-center flex-1">
                        <h4 class="font-heading font-semibold text-xl mb-2">{{$product->name}}</h4>
                        
                        <div class="text-xs text-gray-500 font-medium tracking-wide mb-4 space-y-1">
                            @if(request('color')) <p>Color: {{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</p> @endif
                            @if(request('size')) <p>Size: {{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</p> @endif
                            <p>Quantity: <span x-text="qty"></span></p>
                        </div>
                        
                        <div class="font-medium text-sm">৳{{number_format($product->sale_price ?? $product->regular_price)}}</div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-4 text-xs font-bold uppercase tracking-widest text-gray-500 pt-8 border-t border-gray-200">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="text-gray-900">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipping</span>
                        <span class="text-gray-900">৳<span x-text="insideDhaka ? deliveryInside : deliveryOutside"></span></span>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-6 mt-6 flex justify-between text-black text-sm">
                        <span>Est. Total</span>
                        <span class="text-xl font-heading font-bold">৳<span x-text="total"></span></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection