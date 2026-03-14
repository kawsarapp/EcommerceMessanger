@extends('shop.themes.electronics.layout')
@section('title', 'Finalize System | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); 
@endphp

<div class="max-w-[100rem] mx-auto px-4 md:px-8 py-10" x-data="{ 
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
}">
    
    <div class="mb-8 font-mono text-[10px] font-bold text-gray-500 tracking-widest uppercase flex items-center gap-2">
        <a href="{{$baseUrl}}" class="hover:text-primary transition">Root</a> <span class="text-gray-700">/</span> <span class="text-gray-400">Checkout</span> <span class="text-gray-700">/</span> <span class="text-white">Form</span>
    </div>

    @if(session('success'))
        <div class="bg-primary/10 border border-primary/20 p-6 rounded-xl mb-10 text-primary font-mono text-sm uppercase flex items-center gap-4">
            <span class="w-2 h-2 rounded-full bg-primary animate-ping"></span> {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-10 lg:gap-16">
        
        <!-- Left: Form -->
        <div class="w-full lg:w-7/12 order-2 lg:order-1">
            <h1 class="text-3xl font-black text-white tracking-tight mb-8">Initialize Order Process</h1>
            
            <form action="{{$baseUrl.'/checkout/process'}}" method="POST" class="bg-panel tech-border rounded-2xl p-6 md:p-10 space-y-10 group">
                @csrf
                <input type="hidden" name="product_id" value="{{$product->id}}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <div class="space-y-6">
                    <div class="flex items-center gap-3 border-b border-gray-800 pb-4 mb-6 relative">
                        <i class="fas fa-user-circle text-primary text-xl"></i>
                        <h3 class="font-bold text-white text-lg">Contact Modules</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest font-mono">End User Name</label>
                            <input type="text" name="customer_name" required class="bg-dark tech-border rounded-xl px-4 py-3 text-white focus:ring-1 focus:ring-primary focus:border-primary transition font-medium" placeholder="E.g. System Admin">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest font-mono">Mobile Comms (Phone)</label>
                            <input type="tel" name="customer_phone" required class="bg-dark tech-border rounded-xl px-4 py-3 text-white focus:ring-1 focus:ring-primary focus:border-primary transition font-mono tracking-widest" placeholder="01XXXXXXXXX">
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-3 border-b border-gray-800 pb-4 mb-6 relative">
                        <i class="fas fa-route text-primary text-xl"></i>
                        <h3 class="font-bold text-white text-lg">Transport Logistics</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="bg-dark border border-gray-800 rounded-xl p-5 peer-checked:bg-primary/5 peer-checked:border-primary peer-checked:shadow-[0_0_15px_rgba(14,165,233,0.15)] transition-all flex justify-between items-center group-hover:border-gray-700">
                                <div>
                                    <span class="block text-sm font-bold text-white mb-1">Local Zone (Dhaka)</span>
                                    <span class="block text-[10px] font-mono uppercase text-gray-500 tracking-widest">Standard Delivery</span>
                                </div>
                                <span class="font-mono font-bold text-primary">৳{{$client->delivery_charge_inside ?? 60}}</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="bg-dark border border-gray-800 rounded-xl p-5 peer-checked:bg-primary/5 peer-checked:border-primary peer-checked:shadow-[0_0_15px_rgba(14,165,233,0.15)] transition-all flex justify-between items-center group-hover:border-gray-700">
                                <div>
                                    <span class="block text-sm font-bold text-white mb-1">Outer Rim</span>
                                    <span class="block text-[10px] font-mono uppercase text-gray-500 tracking-widest">Extended Delivery</span>
                                </div>
                                <span class="font-mono font-bold text-primary">৳{{$client->delivery_charge_outside ?? 120}}</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex flex-col gap-2 pt-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest font-mono">Coordinates / Address</label>
                        <textarea name="shipping_address" required rows="3" class="bg-dark tech-border rounded-xl px-4 py-3 text-white focus:ring-1 focus:ring-primary focus:border-primary transition font-medium" placeholder="Sector 7, Block B, Address"></textarea>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-800">
                    <button type="submit" class="w-full bg-primary text-white py-5 rounded-xl font-bold tech-glow tech-border transition-all flex justify-center items-center gap-3 uppercase tracking-widest text-sm hover:bg-white hover:text-black">
                        <i class="fas fa-lock"></i> Authorize Payment <span class="hidden sm:inline-block">— Cash on Delivery</span>
                    </button>
                    <div class="flex justify-center gap-6 mt-6">
                        <span class="flex items-center gap-2 text-[10px] font-mono text-gray-500 uppercase tracking-widest"><i class="fas fa-shield-alt text-gray-400"></i> Secure Node</span>
                        <span class="flex items-center gap-2 text-[10px] font-mono text-gray-500 uppercase tracking-widest"><i class="fas fa-box text-gray-400"></i> Encrypted Transfer</span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="w-full lg:w-5/12 order-1 lg:order-2">
            <div class="bg-panel border border-primary/20 rounded-2xl p-6 lg:p-8 sticky top-24 shadow-[0_0_30px_rgba(14,165,233,0.05)]">
                <div class="flex items-center gap-3 border-b border-gray-800 pb-4 mb-8">
                    <i class="fas fa-list-alt text-primary text-xl"></i>
                    <h3 class="font-bold text-white text-lg">System Cart</h3>
                </div>
                
                <div class="flex gap-4 mb-8">
                    <div class="w-20 aspect-square bg-white rounded-lg p-2 tech-border flex items-center justify-center shrink-0">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply">
                    </div>
                    <div class="flex flex-col justify-center flex-1">
                        <h4 class="font-bold text-gray-300 text-sm mb-2 leading-tight">{{$product->name}}</h4>
                        
                        <div class="flex flex-wrap gap-2 mb-2">
                            @if(request('color')) <span class="bg-dark tech-border text-[9px] font-mono text-gray-400 px-2 py-0.5 rounded">{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span> @endif
                            @if(request('size')) <span class="bg-dark tech-border text-[9px] font-mono text-gray-400 px-2 py-0.5 rounded">{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span> @endif
                        </div>
                        
                        <div class="flex justify-between items-end w-full">
                            <span class="font-bold text-primary font-mono text-sm">৳{{number_format($product->sale_price ?? $product->regular_price)}} <span class="text-gray-500 text-xs">x <span x-text="qty"></span></span></span>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-4 border-t border-gray-800 pt-6">
                    <div class="flex justify-between items-center text-xs font-mono font-bold text-gray-500 uppercase tracking-widest">
                        <span>Terminal Subtotal</span>
                        <span class="text-gray-300">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between items-center text-xs font-mono font-bold text-gray-500 uppercase tracking-widest">
                        <span>Transport Cost</span>
                        <span class="text-gray-300">৳<span x-text="delivery"></span></span>
                    </div>
                    
                    <div class="border-t border-gray-800 pt-6 mt-2 flex justify-between items-center bg-dark/50 rounded-lg p-4 tech-border">
                        <span class="text-sm font-bold text-white uppercase tracking-widest">Final Total</span>
                        <span class="text-2xl font-black text-primary font-mono bg-primary/10 px-3 py-1 rounded">৳<span x-text="total"></span></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection