@extends('shop.themes.default.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 md:py-24">
    
    <div class="max-w-2xl mx-auto text-center mb-16">
        <div class="w-16 h-16 bg-slate-50 text-primary rounded-2xl flex items-center justify-center text-2xl mx-auto mb-6 border border-slate-100 shadow-sm">
            <i class="fas fa-box"></i>
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight mb-4">Track Your Order</h1>
        <p class="text-slate-500 font-medium text-base">Enter the phone number associated with your order to track its current status and details.</p>
    </div>

    <!-- Search Form -->
    <div class="max-w-lg mx-auto mb-20">
        <form method="GET" action="" class="relative group">
            <!-- decorative glow -->
            <div class="absolute inset-x-0 -bottom-4 h-full bg-primary/5 rounded-2xl filter blur-xl opacity-0 group-focus-within:opacity-100 transition duration-500 pointer-events-none"></div>
            
            <div class="relative bg-white border-2 border-slate-100 focus-within:border-primary/30 focus-within:ring-4 focus-within:ring-primary/10 rounded-2xl p-2 shadow-sm flex items-center premium-transition">
                <div class="px-4 text-slate-400 group-focus-within:text-primary premium-transition">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <input type="text" name="phone" value="{{request('phone')}}" placeholder="01XXXXXXXXX" class="w-full bg-transparent border-none py-3 text-slate-900 font-bold text-lg focus:ring-0 placeholder-slate-300 tracking-wider">
                <button type="submit" class="bg-slate-900 text-white py-3 px-8 rounded-xl font-bold uppercase tracking-widest text-sm hover:bg-primary premium-transition shadow-sm">
                    Track
                </button>
            </div>
        </form>
    </div>

    <!-- Results Section -->
    @if(request('phone'))
        <div class="max-w-4xl mx-auto space-y-8">
            <h4 class="font-bold text-slate-900 mb-6 text-center text-lg">Order History associated with <span class="text-primary">{{request('phone')}}</span></h4>
            
            @forelse($orders ?? [] as $o)
                <div class="bg-white rounded-[2rem] border border-slate-100 p-8 md:p-12 shadow-soft hover:shadow-float premium-transition border-b-4 border-b-transparent hover:border-b-primary group">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start md:items-center pb-8 border-b border-slate-100 mb-8 gap-6">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-primary group-hover:bg-primary/5 premium-transition">
                                <i class="fas fa-receipt text-xl"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Order Reference</span>
                                <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">#{{$o->id}}</h3>
                            </div>
                        </div>
                        
                        <div>
                             <div class="inline-flex items-center gap-2 text-sm font-bold px-5 py-2.5 rounded-xl border
                                @if($o->order_status == 'pending') border-amber-200 text-amber-700 bg-amber-50
                                @elseif($o->order_status == 'completed') border-emerald-200 text-emerald-700 bg-emerald-50
                                @elseif($o->order_status == 'cancelled') border-red-200 text-red-600 bg-red-50
                                @else border-slate-200 text-slate-600 bg-slate-50 @endif">
                                
                                @if($o->order_status == 'pending') <i class="fas fa-clock"></i>
                                @elseif($o->order_status == 'completed') <i class="fas fa-check-circle"></i>
                                @elseif($o->order_status == 'cancelled') <i class="fas fa-times-circle"></i>
                                @else <i class="fas fa-info-circle"></i> @endif
                                
                                <span class="uppercase tracking-widest text-xs">{{$o->order_status}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-slate-50 p-5 rounded-2xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Order Date</span>
                            <span class="text-base font-bold text-slate-900">{{$o->created_at->format('M d, Y')}}</span>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-2xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Total Amount</span>
                            <span class="text-xl font-extrabold text-slate-900">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-2xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Payment Mode</span>
                            <span class="text-[11px] font-bold text-slate-600 uppercase tracking-widest bg-white border border-slate-200 px-3 py-1 rounded-lg inline-block shadow-sm">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div class="bg-primary/5 p-5 rounded-2xl border border-primary/10">
                                <span class="text-[10px] font-bold text-primary uppercase tracking-widest block mb-2">Delivery Partner</span>
                                <span class="text-base font-bold text-slate-900 block mb-1">{{$o->courier_name}}</span>
                                <span class="text-xs font-bold text-slate-500 block truncate">{{$o->tracking_code}}</span>
                            </div>
                        @else
                            <div class="bg-slate-50 p-5 rounded-2xl flex items-center justify-center opacity-50">
                                <span class="text-xs font-semibold text-slate-400">Processing Delivery...</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-24 bg-white rounded-[2rem] border border-dashed border-slate-300 shadow-sm">
                    <div class="w-20 h-20 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 mx-auto mb-6">
                        <i class="fas fa-ghost text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2 tracking-tight">No Order Records Found</h3>
                    <p class="text-base font-medium text-slate-500 max-w-sm mx-auto">Please check the phone number and try again.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection