@extends('shop.themes.kids.layout')
@section('title', 'Get Ready! | ' . $client->shop_name)

@section('content')
@php 
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug); 
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-10 py-10" x-data="{ 
    insideDhaka: true, 
    qty: {{request('qty',1)}}, 
    price: {{$product->sale_price ?? $product->regular_price}}, 
    deliveryInside: {{$client->delivery_charge_inside ?? 60}}, 
    deliveryOutside: {{$client->delivery_charge_outside ?? 120}}, 
    get total() { return (this.qty * this.price) + (this.insideDhaka ? this.deliveryInside : this.deliveryOutside); } 
}">
    
    <div class="mb-12 relative text-center lg:text-left">
        <!-- Floating star for fun -->
        <i class="fas fa-star text-funyellow text-4xl absolute -top-6 -left-6 animate-bounce hidden lg:block"></i>
        
        <h1 class="text-4xl md:text-5xl font-heading text-slate-800 tracking-wide mb-2 inline-block relative">
            Almost Yours!
            <!-- underline squiggle concept via pseudo styling in css but here using simple border -->
            <div class="w-full h-2 bg-primary rounded-full transform -rotate-2 mt-1 hidden sm:block"></div>
        </h1>
        <p class="text-base font-bold text-slate-500 mt-2">Tell us where to send the fun stuff!</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-100 border-4 border-white p-8 rounded-[2rem] mb-12 shadow-cloud flex flex-col md:flex-row items-center gap-6 text-center md:text-left bouncy cursor-default">
            <div class="w-16 h-16 bg-emerald-400 rounded-full flex items-center justify-center text-white text-3xl shrink-0 shadow-inner">
                <i class="fas fa-laugh-beam"></i>
            </div>
            <div>
                <h3 class="font-heading text-emerald-800 text-2xl mb-1">Woohoo! Order Received!</h3>
                <p class="text-emerald-700 font-bold text-lg">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-12 lg:gap-16 items-start">
        
        <!-- Left: Form -->
        <div class="w-full lg:w-7/12 order-2 lg:order-1 bg-white rounded-[3rem] p-8 md:p-12 shadow-cloud border-4 border-slate-50 relative overflow-hidden group">
            
            <form action="{{$baseUrl.'/checkout/process'}}" method="POST" class="space-y-12 relative z-10 group">
                @csrf
                <input type="hidden" name="product_id" value="{{$product->id}}">
                <input type="hidden" name="qty" :value="qty">
                @if(request('color')) <input type="hidden" name="color" value="{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}"> @endif
                @if(request('size')) <input type="hidden" name="size" value="{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}"> @endif
                
                <div class="space-y-8">
                    <h3 class="font-heading text-2xl text-slate-800 flex items-center gap-3">
                        <div class="w-10 h-10 bg-funblue text-white rounded-xl flex items-center justify-center text-lg transform rotate-6 border-2 border-slate-100 shadow-sm">1</div>
                        Who are you?
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-2">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-500 pl-4 uppercase tracking-widest">Grown-up's Name</label>
                            <input type="text" name="customer_name" required class="w-full bg-slate-50 border-4 border-slate-100 rounded-[2rem] px-6 py-4 text-slate-800 font-bold focus:border-funblue focus:ring-0 transition shadow-inner placeholder-slate-300 text-lg" placeholder="e.g. Super Mom">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-500 pl-4 uppercase tracking-widest">Phone Number</label>
                            <input type="tel" name="customer_phone" required class="w-full bg-slate-50 border-4 border-slate-100 rounded-[2rem] px-6 py-4 text-slate-800 font-bold focus:border-funblue focus:ring-0 transition shadow-inner placeholder-slate-300 text-lg" placeholder="01XXXXXXXXX">
                        </div>
                        
                        <div class="flex flex-col gap-2 md:col-span-2 pt-2">
                            <label class="text-sm font-bold text-slate-500 pl-4 uppercase tracking-widest">Home Address</label>
                            <textarea name="shipping_address" required rows="3" class="w-full bg-slate-50 border-4 border-slate-100 rounded-[2rem] px-6 py-4 text-slate-800 font-bold focus:border-funblue focus:ring-0 transition shadow-inner placeholder-slate-300 resize-none text-lg" placeholder="Where does the fun live?"></textarea>
                        </div>
                    </div>
                </div>

                <div class="space-y-8 pt-6 border-t-4 border-slate-50">
                    <h3 class="font-heading text-2xl text-slate-800 flex items-center gap-3">
                        <div class="w-10 h-10 bg-funyellow text-white rounded-xl flex items-center justify-center text-lg transform -rotate-6 border-2 border-slate-100 shadow-sm">2</div>
                        Where is it going?
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 p-2">
                        <label class="cursor-pointer group bouncy">
                            <input type="radio" name="area" value="inside" @change="insideDhaka = true" class="peer hidden" checked>
                            <div class="bg-white border-4 border-slate-100 rounded-[2rem] p-6 peer-checked:bg-funblue/10 peer-checked:border-funblue transition-all relative overflow-hidden text-center shadow-sm">
                                <div class="w-16 h-16 bg-funblue/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-white text-funblue text-2xl group-hover:scale-110 transition-transform"><i class="fas fa-city"></i></div>
                                <span class="block text-xl font-heading text-slate-800 mb-1">Inside Dhaka</span>
                                <span class="block text-base font-bold text-funblue bg-white w-fit mx-auto px-4 py-1 rounded-xl shadow-inner border-2 border-slate-50">৳{{$client->delivery_charge_inside ?? 60}}</span>
                            </div>
                        </label>
                        <label class="cursor-pointer group bouncy">
                            <input type="radio" name="area" value="outside" @change="insideDhaka = false" class="peer hidden">
                            <div class="bg-white border-4 border-slate-100 rounded-[2rem] p-6 peer-checked:bg-funyellow/20 peer-checked:border-funyellow transition-all relative overflow-hidden text-center shadow-sm">
                                <div class="w-16 h-16 bg-funyellow/30 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-white text-yellow-600 text-2xl group-hover:scale-110 transition-transform"><i class="fas fa-tree"></i></div>
                                <span class="block text-xl font-heading text-slate-800 mb-1">Outside Dhaka</span>
                                <span class="block text-base font-bold text-yellow-600 bg-white w-fit mx-auto px-4 py-1 rounded-xl shadow-inner border-2 border-slate-50">৳{{$client->delivery_charge_outside ?? 120}}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-8">
                    <button type="submit" class="w-full bg-primary text-white py-6 rounded-[2rem] font-heading text-2xl hover:bg-pink-600 transition shadow-float hover:-translate-y-2 border-4 border-white flex flex-col items-center justify-center relative overflow-hidden group">
                        <!-- shine effect -->
                        <div class="absolute inset-0 -translate-x-full group-hover:animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
                        <span class="relative z-10 flex items-center gap-3">Send It Now! <i class="fas fa-rocket text-funyellow"></i></span>
                        <span class="text-sm font-bold text-pink-200 mt-1">Pay when you get it</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right: Order Summary Sidebar -->
        <div class="w-full lg:w-5/12 order-1 lg:order-2">
            <!-- Stickiness and design -->
            <div class="bg-funblue border-4 border-white w-full rounded-[3rem] p-8 md:p-10 sticky top-28 shadow-cloud transform md:rotate-2 hover:rotate-0 transition-transform duration-500">
                
                <h3 class="font-heading text-white text-2xl mb-8 flex items-center gap-3 border-b-4 border-white/20 pb-6">
                    <i class="fas fa-box-open text-funyellow"></i> In The Box
                </h3>
                
                <div class="flex gap-6 mb-8 bg-white/10 p-4 rounded-3xl border-4 border-white/20 backdrop-blur-sm shadow-inner group">
                    <div class="w-24 h-24 bg-white rounded-2xl shrink-0 p-2 flex items-center justify-center border-4 border-slate-100 overflow-hidden relative shadow-sm bouncy transform -rotate-3">
                        <img src="{{asset('storage/'.$product->thumbnail)}}" class="max-w-[90%] max-h-[90%] object-contain mix-blend-multiply group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="flex flex-col justify-center flex-1 py-1 text-white">
                        <h4 class="font-heading text-xl leading-tight mb-2 drop-shadow-sm">{{$product->name}}</h4>
                        
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if(request('color')) <span class="bg-white/20 text-white border border-white/30 text-xs font-bold px-3 py-1 rounded-xl shadow-inner">{{array_is_list((array)request('color')) ? request('color') : request('color')[0]}}</span> @endif
                            @if(request('size')) <span class="bg-white/20 text-white border border-white/30 text-xs font-bold px-3 py-1 rounded-xl shadow-inner">{{array_is_list((array)request('size')) ? request('size') : request('size')[0]}}</span> @endif
                        </div>
                        
                        <div class="flex justify-between items-end w-full">
                            <span class="font-heading text-2xl text-funyellow drop-shadow-md">৳{{number_format($product->sale_price ?? $product->regular_price)}} <span class="text-blue-200 text-sm font-bold ml-1">x <span x-text="qty"></span></span></span>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="space-y-4 pt-4">
                    <div class="flex justify-between items-center text-blue-100 font-bold bg-black/10 p-4 rounded-2xl border-2 border-white/10 shadow-inner">
                        <span>Toy Cost</span>
                        <span class="text-white text-xl">৳<span x-text="qty * price"></span></span>
                    </div>
                    <div class="flex justify-between items-center text-blue-100 font-bold bg-black/10 p-4 rounded-2xl border-2 border-white/10 shadow-inner">
                        <span>Delivery Journey</span>
                        <span class="text-white text-xl">৳<span x-text="insideDhaka ? deliveryInside : deliveryOutside"></span></span>
                    </div>
                    
                    <div class="pt-6 mt-4">
                        <div class="flex justify-between items-center bg-white text-slate-800 p-6 rounded-[2rem] shadow-cloud transform -rotate-2 relative overflow-hidden border-4 border-slate-100">
                            <!-- pattern inside final block -->
                            <i class="fas fa-gift absolute -right-6 -bottom-6 text-7xl text-primary/10 transform rotate-12"></i>
                            
                            <span class="text-xl font-heading tracking-tight relative z-10 text-slate-500">Grand Total</span>
                            <span class="text-4xl font-heading text-primary relative z-10 drop-shadow-sm bouncy">৳<span x-text="total"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection