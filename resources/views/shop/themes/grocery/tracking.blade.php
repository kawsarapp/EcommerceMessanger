@extends('shop.themes.grocery.layout')
@section('title', 'Track Delivery | ' . $client->shop_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 md:py-24">
    
    <div class="max-w-2xl mx-auto text-center mb-16">
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center text-3xl text-primary mx-auto mb-6 shadow-inner relative">
            <i class="fas fa-map-marked-alt relative z-10"></i>
            <span class="absolute top-0 right-0 w-5 h-5 bg-secondary rounded-full border-4 border-white animate-bounce"></span>
        </div>
        <h1 class="text-4xl md:text-5xl font-black text-slate-800 tracking-tight mb-4">Track Delivery</h1>
        <p class="text-slate-500 font-bold text-base leading-relaxed">Enter your registered mobile number below to see exactly where your fresh groceries are right now.</p>
    </div>

    <div class="max-w-xl mx-auto mb-20">
        <form method="GET" action="" class="relative group">
            <div class="absolute inset-x-0 -bottom-4 h-full bg-primary/10 rounded-[2rem] filter blur-xl opacity-0 group-focus-within:opacity-100 transition duration-500 pointer-events-none"></div>
            
            <div class="relative flex items-center bg-white border-2 border-slate-200 focus-within:border-primary rounded-[2rem] p-2 shadow-sm focus-within:shadow-xl focus-within:shadow-primary/10 transition-all duration-300">
                <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 group-focus-within:text-primary transition shrink-0 ml-2">
                    <i class="fas fa-mobile-alt text-lg"></i>
                </div>
                <input type="text" name="phone" value="{{request('phone')}}" placeholder="E.g. 017XXXXXXXX" class="w-full bg-transparent border-none px-4 py-4 text-slate-800 font-black text-lg focus:ring-0 placeholder-slate-300 tracking-wider" required>
                <button type="submit" class="bg-primary text-white h-14 px-8 rounded-[1.5rem] font-black text-base hover:bg-emerald-600 transition shadow-md whitespace-nowrap hidden sm:block mr-1">
                    Track Now
                </button>
                <button type="submit" class="bg-primary text-white w-14 h-14 rounded-full font-black text-base hover:bg-emerald-600 transition shadow-md shrink-0 block sm:hidden mr-1">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    @if(request('phone'))
        <div class="max-w-4xl mx-auto space-y-10">
            @forelse($orders ?? [] as $o)
                <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-soft border border-slate-100 relative overflow-hidden group hover:border-primary/30 transition duration-300">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start md:items-center pb-8 border-b border-slate-100 relative z-10 gap-6">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 group-hover:bg-primary/5 group-hover:text-primary transition shrink-0 border border-slate-100">
                                <i class="fas fa-shopping-bag text-2xl"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Order Ref</span>
                                <h3 class="text-3xl font-black text-slate-800 tracking-tight">#{{$o->id}}</h3>
                            </div>
                        </div>
                        
                        <div class="w-full sm:w-auto text-left sm:text-right">
                             <div class="inline-flex items-center gap-2 text-sm font-black px-5 py-2.5 rounded-xl border-2
                                @if($o->order_status == 'pending') border-yellow-200 text-yellow-700 bg-yellow-50
                                @elseif($o->order_status == 'completed') border-emerald-200 text-emerald-700 bg-emerald-50
                                @elseif($o->order_status == 'cancelled') border-red-200 text-red-600 bg-red-50
                                @else border-slate-200 text-slate-500 bg-slate-50 @endif">
                                @if($o->order_status == 'pending') <i class="fas fa-clock"></i>
                                @elseif($o->order_status == 'completed') <i class="fas fa-check-circle"></i>
                                @elseif($o->order_status == 'cancelled') <i class="fas fa-times-circle"></i>
                                @endif
                                <span class="uppercase">{{$o->order_status}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-8 relative z-10">
                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 text-center sm:text-left">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2"><i class="far fa-calendar-alt text-slate-300 mr-1"></i> Order Date</span>
                            <span class="text-base font-extrabold text-slate-700">{{$o->created_at->format('M d, Y')}}</span>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 text-center sm:text-left">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2"><i class="fas fa-money-bill-wave text-slate-300 mr-1"></i> Total Bill</span>
                            <span class="text-lg font-black text-slate-900 tracking-tight">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 text-center sm:text-left">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2"><i class="fas fa-credit-card text-slate-300 mr-1"></i> Payment Mode</span>
                            <span class="text-[11px] font-black text-slate-600 uppercase bg-white border border-slate-200 px-3 py-1 rounded-lg">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div class="bg-primary/5 p-5 rounded-2xl border border-primary/20 text-center sm:text-left">
                                <span class="text-[10px] font-black text-primary uppercase tracking-widest block mb-2"><i class="fas fa-truck text-primary/50 mr-1"></i> Delivery By</span>
                                <span class="text-base font-extrabold text-slate-800 block mb-1">{{$o->courier_name}}</span>
                                <span class="text-xs font-bold text-slate-500 block truncate">{{$o->tracking_code}}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-24 bg-white rounded-[3rem] border-2 border-dashed border-slate-200">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center text-4xl text-slate-300 mx-auto mb-6 shadow-inner">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-700 mb-2">No Orders Found</h3>
                    <p class="text-base font-bold text-slate-500">We couldn't find any recent deliveries tied to this number.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection
