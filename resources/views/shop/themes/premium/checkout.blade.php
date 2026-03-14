@extends('shop.themes.premium.layout')
@section('title', 'Secure Checkout | ' . $client->shop_name)

@section('content')
@php $baseUrl = $client->custom_domain ? 'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')) : route('shop.show',$client->slug); @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16" x-data="{ insideDhaka: true, qty: {{request('qty',1)}}, price: {{$product->sale_price ?? $product->regular_price}}, deliveryInside: {{$client->delivery_charge_inside ?? 60}}, deliveryOutside: {{$client->delivery_charge_outside ?? 120}}, get total() { return (this.qty * this.price) + (this.insideDhaka ? this.deliveryInside : this.deliveryOutside); } }">
    
    <div class="mb-12 text-center max-w-2xl mx-auto">
        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-4">Complete Your Order</h1>
        <p class="text-gray-500 font-medium">Please fill in your details below to place the order.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-2xl mb-12 shadow-sm text-green-700 font-bold text-center flex items-center justify-center gap-3">
            <i class="fas fa-check-circle text-2xl"></i> {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-12 lg:gap-20">
        
        <!-- Left: Form -->
        <div class="lg:w-3/5">
            <form action="{{$baseUrl.'/checkout/process'}}" method="POST" class="bg-white p-8 lg:p-12 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-gray-100 space-y-8">
                @csrf
                <input type="hidden" name="product_id" value="{{$product->id}}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2 mb-6"><i class="fas fa-map-marker-alt text-primary opacity-80"></i> Shipping Information</h3>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="customer_name" required class="w-full bg-gray-50 border-gray-200 rounded-xl px-5 py-4 text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-inner font-medium" placeholder="E.g. John Doe">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Mobile Number</label>
                        <input type="tel" name="customer_phone" required class="w-full bg-gray-50 border-gray-200 rounded-xl px-5 py-4 text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-inner font-medium" placeholder="01XXXXXXXXX">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-4">Delivery Area</label>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                                <div class="border-2 border-gray-200 rounded-xl p-5 text-center peer-checked:border-primary peer-checked:bg-primary/5 transition hover:border-gray-300">
                                    <span class="block font-bold text-sm text-gray-800 tracking-wide">Inside Dhaka</span>
                                    <span class="block text-primary font-extrabold mt-1">৳{{$client->delivery_charge_inside ?? 60}}</span>
                                </div>
                            </label>
                            
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                                <div class="border-2 border-gray-200 rounded-xl p-5 text-center peer-checked:border-primary peer-checked:bg-primary/5 transition hover:border-gray-300">
                                    <span class="block font-bold text-sm text-gray-800 tracking-wide">Outside Dhaka</span>
                                    <span class="block text-primary font-extrabold mt-1">৳{{$client->delivery_charge_outside ?? 120}}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Delivery Address (Detailed)</label>
                        <textarea name="shipping_address" required rows="3" class="w-full bg-gray-50 border-gray-200 rounded-xl px-5 py-4 text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-inner font-medium" placeholder="House no, Road no, Area, City"></textarea>
                    </div>
                </div>

                <div class="pt-8 mt-10 border-t border-gray-100 hidden sm:block">
                    <button type="submit" class="w-full btn-premium text-white rounded-2xl py-5 font-extrabold text-xl shadow-lg flex justify-center items-center gap-3">
                        <i class="fas fa-lock text-sm opacity-80"></i> Confirm Order — ৳<span x-text="total"></span>
                    </button>
                    <p class="text-center text-xs text-gray-400 font-medium mt-4"><i class="fas fa-shield-check"></i> Cash on Delivery available.</p>
                </div>

                <!-- Mobile sticky submit button -->
                <div class="fixed bottom-0 left-0 w-full bg-white p-4 border-t border-gray-200 shadow-[0_-10px_40px_rgba(0,0,0,0.1)] z-50 sm:hidden flex justify-between items-center gap-4">
                     <div class="flex flex-col">
                        <span class="text-xs text-gray-500 font-bold uppercase tracking-widest">Total</span>
                        <span class="text-2xl font-extrabold text-gray-900">৳<span x-text="total"></span></span>
                     </div>
                     <button type="submit" class="btn-premium text-white px-8 py-4 rounded-xl font-bold flex-1 text-center shadow-lg">Confirm</button>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="lg:w-2/5">
            <div class="bg-gray-50 rounded-[2rem] p-8 lg:p-10 sticky top-28 ring-1 ring-gray-200/50">
                <h3 class="text-xl font-bold text-gray-900 mb-8 border-b border-gray-200 pb-4">Order Summary</h3>
                
                <div class="flex gap-6 mb-8 group">
                    <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-24 h-32 object-cover rounded-xl shadow-sm border border-gray-100 group-hover:scale-105 transition">
                    <div class="flex flex-col justify-center">
                        <h4 class="font-bold text-gray-900 text-lg leading-snug mb-2">{{$product->name}}</h4>
                        
                        <div class="flex items-center gap-2 text-sm text-gray-500 font-medium mb-1">
                            @if(request('color')) <span class="bg-white px-3 py-1 rounded-md border border-gray-200">Color: <span class="text-gray-900 font-bold">{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span></span> @endif
                            @if(request('size')) <span class="bg-white px-3 py-1 rounded-md border border-gray-200">Size: <span class="text-gray-900 font-bold">{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span></span> @endif
                        </div>
                        
                        <div class="text-primary font-bold mt-2">৳{{number_format($product->sale_price ?? $product->regular_price)}} × <span x-text="qty"></span></div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-4 border-t border-gray-200 pt-8 mt-2">
                    <div class="flex justify-between items-center text-gray-600 font-medium">
                        <span>Subtotal</span>
                        <span class="text-gray-900 font-bold text-lg">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-600 font-medium">
                        <span>Delivery Fee</span>
                        <span class="text-gray-900 font-bold text-lg">৳<span x-text="insideDhaka ? deliveryInside : deliveryOutside"></span></span>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-6 mt-4 flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-900">Total</span>
                        <span class="text-3xl font-extrabold text-primary">৳<span x-text="total"></span></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
