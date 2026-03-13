@extends('shop.themes.default.layout')
@section('title', 'Checkout | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); 
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-10" x-data="{ 
    insideDhaka: true, 
    qty: {{request('qty',1)}}, 
    price: {{$product->sale_price ?? $product->regular_price}}, 
    deliveryInside: {{$client->delivery_charge_inside ?? 60}}, 
    deliveryOutside: {{$client->delivery_charge_outside ?? 120}}, 
    get total() { return (this.qty * this.price) + (this.insideDhaka ? this.deliveryInside : this.deliveryOutside); } 
}">
    
    <div class="mb-8 border-b border-gray-200 pb-6">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Checkout</h1>
        <p class="text-sm font-medium text-gray-500 mt-2">Please complete your details below to place your order.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-md mb-8 flex items-center gap-3 shadow-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-8 lg:gap-12 items-start">
        
        <!-- Left: Form -->
        <div class="w-full lg:w-7/12 order-2 lg:order-1 bg-white rounded border border-gray-200 p-6 md:p-8 shadow-sm">
            
            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="space-y-8">
                @csrf
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <div class="space-y-6">
                    <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">Shipping Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wide">Full Name</label>
                            <input type="text" name="customer_name" required class="w-full bg-gray-50 border border-gray-300 rounded px-4 py-2.5 text-gray-900 focus:border-primary focus:ring-1 focus:ring-primary transition shadow-inner placeholder-gray-400 text-sm" placeholder="Enter your full name">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wide">Phone Number</label>
                            <input type="tel" name="customer_phone" required class="w-full bg-gray-50 border border-gray-300 rounded px-4 py-2.5 text-gray-900 focus:border-primary focus:ring-1 focus:ring-primary transition shadow-inner placeholder-gray-400 text-sm" placeholder="01XXXXXXXXX">
                        </div>
                        
                        <div class="flex flex-col gap-1.5 md:col-span-2">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wide">Complete Address</label>
                            <textarea name="shipping_address" required rows="3" class="w-full bg-gray-50 border border-gray-300 rounded px-4 py-2.5 text-gray-900 focus:border-primary focus:ring-1 focus:ring-primary transition shadow-inner placeholder-gray-400 resize-none text-sm" placeholder="House, Street, Area, City"></textarea>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 pt-2">
                    <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">Delivery Option</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="bg-white border border-gray-300 rounded p-4 peer-checked:bg-blue-50/30 peer-checked:border-primary transition-colors hover:bg-gray-50 relative">
                                <span class="block text-sm font-bold text-gray-900 mb-1">Inside Dhaka</span>
                                <span class="block text-sm font-semibold text-primary">৳{{$client->delivery_charge_inside ?? 60}}</span>
                                <i class="fas fa-check-circle absolute right-4 top-1/2 -translate-y-1/2 text-primary opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="bg-white border border-gray-300 rounded p-4 peer-checked:bg-blue-50/30 peer-checked:border-primary transition-colors hover:bg-gray-50 relative">
                                <span class="block text-sm font-bold text-gray-900 mb-1">Outside Dhaka</span>
                                <span class="block text-sm font-semibold text-primary">৳{{$client->delivery_charge_outside ?? 120}}</span>
                                <i class="fas fa-check-circle absolute right-4 top-1/2 -translate-y-1/2 text-primary opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-200">
                    <button type="submit" class="w-full bg-primary text-white py-4 rounded font-bold text-base hover:bg-gray-800 transition shadow flex items-center justify-center gap-2">
                        <i class="fas fa-lock text-sm opacity-80"></i> Place Order Successfully
                    </button>
                    <p class="text-center text-xs text-gray-500 font-medium mt-4">Payment method: Cash on Delivery</p>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="w-full lg:w-5/12 order-1 lg:order-2">
            <div class="bg-gray-50 border border-gray-200 rounded p-6 md:p-8 sticky top-24 shadow-sm">
                <h3 class="font-bold text-gray-900 text-lg mb-6 border-b border-gray-200 pb-4">Order Summary</h3>
                
                <div class="flex gap-4 mb-6 relative">
                    <div class="w-20 aspect-square bg-white rounded border border-gray-200 p-2 flex items-center justify-center shrink-0">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </div>
                    <div class="flex flex-col justify-center flex-1">
                        <h4 class="font-bold text-gray-900 text-sm leading-snug mb-1 line-clamp-2">{{$product->name}}</h4>
                        
                        <div class="flex flex-wrap gap-2 mb-2 text-xs text-gray-600 font-medium">
                            @if(request('color')) <span>Color: {{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span> @endif
                            @if(request('size')) <span class="{{request('color') ? 'pl-2 border-l border-gray-300' : ''}}">Size: {{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span> @endif
                        </div>
                        
                        <div class="flex justify-between items-end w-full">
                            <span class="font-bold text-gray-900 text-sm">৳{{number_format($product->sale_price ?? $product->regular_price)}} <span class="text-gray-500 font-medium ml-1 text-xs">x <span x-text="qty"></span></span></span>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-3 pt-4 border-t border-gray-200 text-sm font-medium text-gray-600">
                    <div class="flex justify-between items-center">
                        <span>Subtotal</span>
                        <span class="text-gray-900 font-bold">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Shipping</span>
                        <span class="text-gray-900 font-bold">৳<span x-text="insideDhaka ? deliveryInside : deliveryOutside"></span></span>
                    </div>
                    
                    <div class="pt-4 mt-2 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-900 text-base">Total</span>
                            <span class="text-2xl font-bold text-primary">৳<span x-text="total"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection