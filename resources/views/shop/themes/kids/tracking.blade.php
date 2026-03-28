@extends('shop.themes.kids.layout')
@section('title', 'Where is it?! | ' . $client->shop_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-10 py-16 md:py-24 relative overflow-hidden">
    
    <!-- background decorative clouds/shapes -->
    <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full opacity-50 blur-xl pointer-events-none"></div>
    <div class="absolute top-40 right-20 w-48 h-48 bg-primary/10 rounded-full opacity-50 blur-2xl pointer-events-none"></div>

    <div class="max-w-2xl mx-auto text-center mb-16 relative z-10">
        <div class="w-24 h-24 bg-white border-4 border-slate-100 rounded-[2rem] flex items-center justify-center text-4xl text-funblue mx-auto mb-8 shadow-float transform rotate-6 bouncy cursor-default">
            <i class="fas fa-binoculars"></i>
        </div>
        <h1 class="text-4xl md:text-6xl font-heading text-slate-800 tracking-wide mb-6">Find The Fun!</h1>
        <p class="text-slate-500 font-bold text-lg leading-relaxed bg-white/50 backdrop-blur-sm p-4 rounded-3xl border-2 border-white shadow-sm inline-block px-8 py-4">Enter the grown-up's phone number to see where the toys are hiding.</p>
    </div>

    <div class="max-w-xl mx-auto mb-20 relative z-10">
        <form method="GET" action="" class="relative bouncy group">
            <div class="absolute inset-x-0 -bottom-4 h-full bg-funyellow/30 rounded-[3rem] filter blur-xl opacity-0 group-focus-within:opacity-100 transition duration-500 pointer-events-none"></div>
            
            <div class="relative flex items-center bg-white border-8 border-slate-100 focus-within:border-funyellow rounded-[3rem] p-3 shadow-cloud transition-all duration-300 transform group-hover:scale-105">
                <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 group-focus-within:text-funyellow transition shrink-0 ml-2 border-4 border-slate-100">
                    <i class="fas fa-mobile-alt text-xl"></i>
                </div>
                <input type="text" name="phone" value="{{request('phone')}}" placeholder="01XXXXXXXXX" class="w-full bg-transparent border-none px-6 py-4 text-slate-800 font-heading text-2xl focus:ring-0 placeholder-slate-300 tracking-widest text-center" required>
                <button type="submit" class="bg-funyellow text-white h-16 w-16 md:w-32 rounded-[2rem] font-heading text-xl md:text-2xl hover:bg-yellow-500 transition shadow-inner border-4 border-white flex justify-center items-center shrink-0">
                    <span class="hidden md:block">Search</span>
                    <i class="fas fa-search block md:hidden"></i>
                </button>
            </div>
        </form>
    </div>

    @if(request('phone'))
        <div class="max-w-4xl mx-auto space-y-12 relative z-10">
            @forelse($orders ?? [] as $o)
                <div class="bg-white rounded-[3rem] p-8 md:p-14 shadow-cloud border-4 border-slate-100 relative overflow-hidden hover:border-funblue/30 transition duration-300 transform md:rotate-1 hover:rotate-0 group">
                    
                    <div class="flex flex-col md:flex-row justify-between items-center md:items-start pb-10 border-b-4 border-slate-50 relative z-10 gap-8 text-center md:text-left">
                        <div class="flex flex-col md:flex-row items-center gap-6">
                            <div class="w-20 h-20 bg-slate-50 rounded-[2rem] flex items-center justify-center text-slate-300 border-4 border-slate-100 shadow-inner group-hover:text-primary transition shrink-0 transform -rotate-12 bouncy">
                                <i class="fas fa-ticket-alt text-4xl"></i>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-slate-400 uppercase tracking-widest block mb-2">Secret Code</span>
                                <h3 class="text-4xl font-heading text-slate-800 tracking-wide text-primary">#{{$o->id}}</h3>
                            </div>
                        </div>
                        
                        <div class="mt-4 md:mt-0">
                             <div class="inline-flex items-center gap-3 text-lg font-heading px-8 py-3 rounded-full border-4 shadow-sm
                                @if($o->order_status == 'pending') border-yellow-200 text-yellow-700 bg-yellow-100 bouncy
                                @elseif($o->order_status == 'completed') border-emerald-200 text-emerald-600 bg-emerald-100
                                @elseif($o->order_status == 'cancelled') border-red-200 text-red-500 bg-red-100
                                @else border-slate-200 text-slate-500 bg-slate-100 @endif">
                                @if($o->order_status == 'pending') <i class="fas fa-hourglass-half"></i>
                                @elseif($o->order_status == 'completed') <i class="fas fa-star"></i>
                                @elseif($o->order_status == 'cancelled') <i class="fas fa-frown"></i>
                                @endif
                                <span class="uppercase tracking-wide">{{$o->order_status}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pt-10 relative z-10">
                        <div class="bg-slate-50/50 p-6 rounded-3xl border-2 border-slate-100 text-center shadow-inner hover:bg-white transition cursor-default bouncy">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-slate-300 mx-auto mb-4 border-2 border-slate-100 shadow-sm"><i class="far fa-calendar-alt text-xl"></i></div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-2">When?</span>
                            <span class="text-lg font-heading text-slate-700">{{$o->created_at->format('M d, Y')}}</span>
                        </div>
                        <div class="bg-slate-50/50 p-6 rounded-3xl border-2 border-slate-100 text-center shadow-inner hover:bg-white transition cursor-default bouncy">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-funyellow mx-auto mb-4 border-2 border-slate-100 shadow-sm"><i class="fas fa-coins text-xl"></i></div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-2">Cost</span>
                            <span class="text-2xl font-heading text-slate-800 tracking-wide">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div class="bg-slate-50/50 p-6 rounded-3xl border-2 border-slate-100 text-center shadow-inner hover:bg-white transition cursor-default bouncy">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-slate-300 mx-auto mb-4 border-2 border-slate-100 shadow-sm"><i class="fas fa-wallet text-xl"></i></div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-2">Paid By</span>
                            <span class="text-sm font-bold text-slate-600 uppercase bg-white border-2 border-slate-200 px-4 py-1.5 rounded-xl shadow-sm">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div class="bg-funblue/5 p-6 rounded-3xl border-2 border-funblue/20 text-center shadow-inner hover:bg-white transition cursor-default bouncy group">
                                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-funblue mx-auto mb-4 border-2 border-funblue/30 shadow-sm group-hover:scale-110"><i class="fas fa-truck-fast text-xl"></i></div>
                                <span class="text-xs font-bold text-funblue uppercase tracking-widest block mb-1">Bringing it</span>
                                <span class="text-lg font-heading text-slate-800 block mb-1">{{$o->courier_name}}</span>
                                <span class="text-sm font-bold text-slate-500 block truncate bg-white px-2 py-1 rounded-lg border border-slate-200 w-full">{{$o->tracking_code}}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-28 bg-white rounded-[3rem] border-4 border-dashed border-slate-300 shadow-sm relative overflow-hidden">
                    <div class="w-28 h-28 bg-slate-50 rounded-full flex items-center justify-center text-6xl text-slate-300 mx-auto mb-6 shadow-inner transform -rotate-12 border-4 border-slate-100 bouncy">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="text-3xl font-heading text-slate-600 mb-4">Aww... Nothing here.</h3>
                    <p class="text-lg font-bold text-slate-400 max-w-sm mx-auto">We couldn't sniff out anything under that number. Try another?</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection
