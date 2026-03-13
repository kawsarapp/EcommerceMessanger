@extends('shop.themes.luxury.layout')
@section('title', 'Acquisition | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); 
@endphp

<div class="max-w-[100rem] mx-auto px-4 sm:px-12 py-16 md:py-24" x-data="{ 
    insideDhaka: true, 
    qty: {{request('qty',1)}}, 
    price: {{$product->sale_price ?? $product->regular_price}}, 
    deliveryInside: {{$client->delivery_charge_inside ?? 60}}, 
    deliveryOutside: {{$client->delivery_charge_outside ?? 120}}, 
    get total() { return (this.qty * this.price) + (this.insideDhaka ? this.deliveryInside : this.deliveryOutside); } 
}">
    
    <div class="mb-16 md:mb-24 text-center">
        <span class="text-[9px] font-bold uppercase tracking-[0.5em] text-primary mb-4 block">Secure Checkout</span>
        <h1 class="font-serif font-light text-5xl md:text-6xl text-white tracking-widest uppercase">The Final Detail</h1>
    </div>

    @if(session('success'))
        <div class="border border-white/20 bg-dark/50 backdrop-blur-sm p-6 mb-16 text-center shadow-[0_0_30px_rgba(212,175,55,0.1)]">
            <span class="text-xs font-light text-white uppercase tracking-[0.3em]"><i class="fas fa-check text-primary mr-3"></i> {{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-16 lg:gap-24">
        
        <!-- Left: Form -->
        <div class="w-full lg:w-7/12">
            <form action="{{$baseUrl.'/checkout/'.$product->slug}}" method="POST" class="space-y-16 pb-12">
                @csrf
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <div class="pb-16 border-b border-white/5">
                    <h3 class="font-sans text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-10 text-center md:text-left">Personal Information</h3>
                    <div class="space-y-10">
                        <div>
                            <input type="text" name="customer_name" required class="w-full bg-transparent border-0 border-b border-gray-600 px-0 py-4 text-white focus:ring-0 focus:border-white transition text-sm font-light tracking-widest placeholder-gray-600 text-center md:text-left" placeholder="Full Name">
                        </div>
                        <div>
                            <input type="tel" name="customer_phone" required class="w-full bg-transparent border-0 border-b border-gray-600 px-0 py-4 text-white focus:ring-0 focus:border-white transition text-sm font-light tracking-widest placeholder-gray-600 text-center md:text-left" placeholder="Mobile Number (01...)">
                        </div>
                        <div>
                            <textarea name="shipping_address" required rows="2" class="w-full bg-transparent border-0 border-b border-gray-600 px-0 py-4 text-white focus:ring-0 focus:border-white transition text-sm font-light tracking-widest placeholder-gray-600 resize-none text-center md:text-left" placeholder="Delivery Address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="pb-16 border-b border-white/5">
                    <h3 class="font-sans text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-10 text-center md:text-left">Delivery Zone</h3>
                    
                    <div class="flex flex-col sm:flex-row gap-6">
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="border border-white/10 p-8 text-center peer-checked:border-white transition duration-500 bg-surface/50 backdrop-blur-md">
                                <span class="block font-sans text-[10px] font-bold uppercase tracking-[0.2em] text-white mb-3">Capital City</span>
                                <span class="block text-xs font-light text-primary tracking-widest">৳{{$client->delivery_charge_inside ?? 60}}</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="border border-white/10 p-8 text-center peer-checked:border-white transition duration-500 bg-surface/50 backdrop-blur-md">
                                <span class="block font-sans text-[10px] font-bold uppercase tracking-[0.2em] text-white mb-3">Outside Capital</span>
                                <span class="block text-xs font-light text-primary tracking-widest">৳{{$client->delivery_charge_outside ?? 120}}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-8">
                    <button type="submit" class="w-full bg-white text-black py-6 font-semibold text-[10px] tracking-[0.4em] uppercase hover:bg-gray-200 transition duration-500 shadow-[0_0_30px_rgba(255,255,255,0.05)]">
                        Confirm Acquisition
                    </button>
                    <p class="text-center text-[9px] text-gray-600 font-light tracking-[0.3em] uppercase mt-8">Cash presented upon delivery.</p>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="w-full lg:w-5/12">
            <div class="bg-surface/80 backdrop-blur-xl p-8 md:p-14 sticky top-32 border border-white/5 shadow-2xl shadow-black/50">
                <h3 class="font-sans text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-12 pb-6 border-b border-white/5 text-center md:text-left">Chosen Masterpiece</h3>
                
                <div class="flex flex-col md:flex-row gap-8 mb-12 items-center md:items-start text-center md:text-left">
                    <div class="w-32 aspect-[4/5] overflow-hidden border border-white/5 bg-dark shrink-0">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="w-full h-full object-cover mix-blend-lighten">
                    </div>
                    <div class="flex flex-col justify-center py-2 h-full gap-4">
                        <h4 class="font-serif font-light text-2xl text-white leading-tight tracking-wide">{{$product->name}}</h4>
                        
                        <div class="text-[10px] text-gray-500 font-light tracking-[0.2em] uppercase space-y-2">
                            @if(request('color')) <p>Shade <span class="text-gray-300 ml-2">{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span></p> @endif
                            @if(request('size')) <p>Size <span class="text-gray-300 ml-2">{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span></p> @endif
                            <p>Units <span class="text-gray-300 ml-2" x-text="qty"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-6 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500 pt-10 border-t border-white/5">
                    <div class="flex justify-between">
                        <span>Creation</span>
                        <span class="text-gray-300 font-light text-xs tracking-widest">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Transit</span>
                        <span class="text-gray-300 font-light text-xs tracking-widest">৳<span x-text="insideDhaka ? deliveryInside : deliveryOutside"></span></span>
                    </div>
                    
                    <div class="border-t border-white/5 pt-8 mt-8 flex justify-between items-end text-white text-xs">
                        <span class="tracking-[0.3em]">Final Tribute</span>
                        <span class="text-2xl font-light font-serif tracking-widest text-primary">৳<span x-text="total"></span></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection