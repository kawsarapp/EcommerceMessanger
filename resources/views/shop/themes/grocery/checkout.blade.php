@extends('shop.themes.grocery.layout')
@section('title', 'Quick Checkout | ' . $client->shop_name)

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
    
    <div class="mb-10 lg:mb-16">
        <div class="flex items-center gap-4 border-b border-slate-200 pb-6 mb-8">
            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary text-xl shadow-inner">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-none mb-1">Fast Checkout</h1>
                <p class="text-sm font-bold text-slate-400">Complete your fresh delivery order in seconds.</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border-2 border-emerald-200 p-6 rounded-2xl mb-12 shadow-sm flex items-start gap-4">
            <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white shrink-0 mt-0.5 shadow-md">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <h3 class="font-extrabold text-emerald-800 text-lg mb-1">Order Placed Successfully!</h3>
                <p class="text-emerald-700 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-12 lg:gap-16 items-start">
        
        <!-- Left: Form -->
        <div class="w-full lg:w-7/12 order-2 lg:order-1 bg-white rounded-[2.5rem] p-8 md:p-12 shadow-sm border border-slate-100 relative overflow-hidden">
            <!-- decorative bg blob -->
            <div class="absolute -top-32 -right-32 w-64 h-64 bg-primary/5 rounded-full blur-[80px] z-0"></div>
            
            <form action="{{$baseUrl.'/checkout/process'}}" method="POST" class="space-y-12 relative z-10 group">
                @csrf
                <input type="hidden" name="product_id" value="{{$product->id}}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <div class="space-y-8">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 font-black text-sm flex items-center justify-center shadow-inner">1</span>
                        <h3 class="font-extrabold text-xl text-slate-800">Delivery Details</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 p-6 rounded-3xl border border-slate-100">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-600 pl-2">Full Name</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                <input type="text" name="customer_name" required class="w-full bg-white border-2 border-slate-200 rounded-2xl pl-12 pr-4 py-3.5 text-slate-800 font-bold focus:border-primary focus:ring-4 focus:ring-primary/10 transition shadow-sm placeholder-slate-300" placeholder="John Doe">
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-600 pl-2">Phone Number</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                <input type="tel" name="customer_phone" required class="w-full bg-white border-2 border-slate-200 rounded-2xl pl-12 pr-4 py-3.5 text-slate-800 font-bold focus:border-primary focus:ring-4 focus:ring-primary/10 transition shadow-sm placeholder-slate-300 tracking-wide" placeholder="01XXXXXXXXX">
                            </div>
                        </div>
                        
                        <div class="flex flex-col gap-2 md:col-span-2 pt-2">
                            <label class="text-sm font-bold text-slate-600 pl-2">Full Address</label>
                            <div class="relative">
                                <i class="fas fa-map-marker-alt absolute left-4 top-4 text-slate-300"></i>
                                <textarea name="shipping_address" required rows="3" class="w-full bg-white border-2 border-slate-200 rounded-2xl pl-12 pr-4 py-3.5 text-slate-800 font-bold focus:border-primary focus:ring-4 focus:ring-primary/10 transition shadow-sm placeholder-slate-300 resize-none leading-relaxed" placeholder="House/Flat No, Road No, Area"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8 pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 font-black text-sm flex items-center justify-center shadow-inner">2</span>
                        <h3 class="font-extrabold text-xl text-slate-800">Delivery Zone</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="bg-white border-2 border-slate-200 rounded-2xl p-6 peer-checked:bg-emerald-50 peer-checked:border-primary peer-checked:shadow-sm transition-all flex justify-between items-center group-hover:border-slate-300 relative overflow-hidden">
                                <i class="fas fa-check-circle absolute right-6 top-1/2 -translate-y-1/2 text-primary text-2xl opacity-0 peer-checked:opacity-100 transition-opacity transform scale-50 peer-checked:scale-100 duration-300"></i>
                                <div class="relative z-10 w-full pr-10">
                                    <span class="block text-lg font-black text-slate-800 mb-1 leading-tight">Inside Dhaka</span>
                                    <span class="block text-sm font-bold text-primary">৳{{$client->delivery_charge_inside ?? 60}} <span class="text-slate-400 font-semibold text-xs ml-1">(Fastest)</span></span>
                                </div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="bg-white border-2 border-slate-200 rounded-2xl p-6 peer-checked:bg-emerald-50 peer-checked:border-primary peer-checked:shadow-sm transition-all flex justify-between items-center group-hover:border-slate-300 relative overflow-hidden">
                                <i class="fas fa-check-circle absolute right-6 top-1/2 -translate-y-1/2 text-primary text-2xl opacity-0 peer-checked:opacity-100 transition-opacity transform scale-50 peer-checked:scale-100 duration-300"></i>
                                <div class="relative z-10 w-full pr-10">
                                    <span class="block text-lg font-black text-slate-800 mb-1 leading-tight">Outside Dhaka</span>
                                    <span class="block text-sm font-bold text-primary">৳{{$client->delivery_charge_outside ?? 120}} <span class="text-slate-400 font-semibold text-xs ml-1">(Standard)</span></span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-8 mb-6 relative">
                    <button type="submit" class="w-full bg-primary text-white py-5 rounded-2xl font-black text-xl hover:bg-emerald-600 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/30 transition-all duration-300 flex items-center justify-center gap-3">
                        <i class="fas fa-truck-fast"></i> Confirm Order
                    </button>
                    <!-- badge -->
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] uppercase font-black tracking-widest px-4 py-1.5 rounded-full shadow-lg border-2 border-white flex items-center gap-2">
                        <i class="fas fa-money-bill-wave text-emerald-400"></i> Cash on Delivery
                    </div>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="w-full lg:w-5/12 order-1 lg:order-2">
            <div class="bg-slate-50 border border-slate-200 rounded-[2.5rem] p-8 md:p-10 sticky top-28 shadow-soft">
                <h3 class="font-black text-slate-800 text-xl mb-8 flex items-center gap-3 border-b border-slate-200 pb-4"><i class="fas fa-receipt text-slate-400"></i> Your Basket</h3>
                
                <div class="flex gap-6 mb-8 bg-white p-4 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="w-24 h-24 bg-slate-50 rounded-2xl shrink-0 p-2 flex items-center justify-center border border-slate-100 overflow-hidden relative group">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-full max-h-full object-contain mix-blend-multiply group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="flex flex-col justify-center flex-1 py-1">
                        <h4 class="font-extrabold text-slate-800 text-base leading-tight mb-2 line-clamp-2">{{$product->name}}</h4>
                        
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if(request('color')) <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-1 rounded-lg">{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span> @endif
                            @if(request('size')) <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-1 rounded-lg">{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span> @endif
                        </div>
                        
                        <div class="flex justify-between items-end w-full">
                            <span class="font-black text-slate-900 text-lg">৳{{number_format($product->sale_price ?? $product->regular_price)}} <span class="text-slate-400 text-sm font-bold ml-1">x <span x-text="qty"></span></span></span>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-4 pt-4">
                    <div class="flex justify-between items-center text-slate-500 font-bold bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                        <span>Items Total</span>
                        <span class="text-slate-800 text-lg">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-500 font-bold bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                        <span>Delivery Fee</span>
                        <span class="text-slate-800 text-lg">৳<span x-text="insideDhaka ? deliveryInside : deliveryOutside"></span></span>
                    </div>
                    
                    <div class="pt-4 mt-2">
                        <div class="flex justify-between items-center bg-primary text-white p-6 rounded-[2rem] shadow-lg shadow-primary/20 transform -rotate-1 relative overflow-hidden">
                            <!-- pattern inside final block -->
                            <i class="fas fa-shopping-bag absolute -right-6 -bottom-6 text-[100px] text-white/5 transform -rotate-12"></i>
                            
                            <span class="text-lg font-black tracking-tight relative z-10">Total to Pay</span>
                            <span class="text-3xl font-black relative z-10">৳<span x-text="total"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection