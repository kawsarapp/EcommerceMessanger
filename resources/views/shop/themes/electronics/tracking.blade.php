@extends('shop.themes.electronics.layout')
@section('title', 'Logistics Radar | ' . $client->shop_name)

@section('content')
<div class="max-w-[100rem] mx-auto px-4 md:px-8 py-16 md:py-24">
    
    <div class="mb-8 font-mono text-[10px] font-bold text-gray-500 tracking-widest uppercase flex items-center justify-center gap-2 mb-10">
        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span> Terminal System / Radar Online
    </div>

    <div class="max-w-2xl mx-auto text-center mb-16">
        <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-4 flex justify-center items-center gap-4">
            <i class="fas fa-satellite-dish text-primary opacity-80"></i> Package Radar
        </h1>
        <p class="text-gray-500 font-medium text-sm">Input the primary communication number used during the checkout sequence to fetch transport diagnostics.</p>
    </div>

    <div class="max-w-2xl mx-auto bg-panel tech-border rounded-2xl p-6 md:p-8 mb-20 relative overflow-hidden group">
        <!-- Decoration light -->
        <div class="absolute inset-0 bg-gradient-to-r from-primary/0 via-primary/5 to-primary/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-[2s] pointer-events-none"></div>
        
        <form method="GET" action="" class="flex flex-col sm:flex-row gap-4 relative z-10">
            <div class="relative flex-1">
                <i class="fas fa-fingerprint absolute left-4 top-1/2 -translate-y-1/2 text-gray-600"></i>
                <input type="text" name="phone" value="{{request('phone')}}" placeholder="Comm Number (01XXXXXXXXX)" class="w-full bg-dark tech-border border border-gray-700 rounded-xl pl-12 pr-4 py-4 text-white focus:ring-1 focus:ring-primary focus:border-primary transition font-mono tracking-widest shadow-inner placeholder-gray-600" required>
            </div>
            <button type="submit" class="bg-primary text-white px-8 py-4 rounded-xl font-bold tech-glow tech-border transition-all flex items-center justify-center gap-2 uppercase tracking-widest text-xs hover:bg-white hover:text-black">
                <i class="fas fa-search"></i> Scan
            </button>
        </form>
    </div>

    @if(request('phone'))
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="flex items-center gap-3 text-primary font-mono text-sm uppercase font-bold tracking-widest mb-6">
                <span class="w-1.5 h-6 bg-primary"></span> Scan Results Compiled
            </div>

            @forelse($orders ?? [] as $o)
                <div class="bg-panel tech-border rounded-2xl p-6 md:p-8 border border-gray-800 hover:border-gray-700 transition relative overflow-hidden group">
                    <!-- Subtle background grid pattern idea using css linear-gradients -->
                    <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: linear-gradient(#ffffff 1px, transparent 1px), linear-gradient(90deg, #ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start md:items-center pb-6 border-b border-gray-800 relative z-10 gap-4">
                        <div class="flex items-center gap-4">
                            <div class="bg-dark/80 rounded-lg p-3 tech-border shrink-0">
                                <i class="fas fa-box text-2xl text-gray-500"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest font-mono block mb-1">Sector ID</span>
                                <h3 class="text-2xl font-black text-white tracking-tight">#{{$o->id}}</h3>
                            </div>
                        </div>
                        
                        <div class="w-full sm:w-auto text-left sm:text-right">
                             <span class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest font-mono px-4 py-2 rounded border
                                @if($o->order_status == 'pending') border-yellow-500/30 text-yellow-500 bg-yellow-500/10
                                @elseif($o->order_status == 'completed') border-green-500/30 text-green-500 bg-green-500/10
                                @elseif($o->order_status == 'cancelled') border-red-500/30 text-red-500 bg-red-500/10
                                @else border-gray-600/50 text-gray-400 bg-gray-800/50 @endif">
                                @if($o->order_status == 'pending') <i class="fas fa-hourglass-half"></i> 
                                @elseif($o->order_status == 'completed') <i class="fas fa-check-double"></i> 
                                @elseif($o->order_status == 'cancelled') <i class="fas fa-ban"></i> 
                                @endif
                                Status: {{$o->order_status}}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 pt-6 relative z-10">
                        <div class="bg-dark/50 p-4 rounded-xl tech-border border border-gray-800/50">
                            <i class="fas fa-calendar-alt text-gray-600 mb-3 text-lg"></i>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest font-mono block mb-1">Time Logged</span>
                            <span class="text-sm font-medium text-white">{{$o->created_at->format('M d, Y')}}</span>
                        </div>
                        <div class="bg-dark/50 p-4 rounded-xl tech-border border border-gray-800/50">
                            <i class="fas fa-file-invoice-dollar text-gray-600 mb-3 text-lg"></i>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest font-mono block mb-1">Total Value</span>
                            <span class="text-base font-black text-white font-mono tracking-tight text-primary">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div class="bg-dark/50 p-4 rounded-xl tech-border border border-gray-800/50">
                            <i class="fas fa-credit-card text-gray-600 mb-3 text-lg"></i>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest font-mono block mb-1">Protocol</span>
                            <span class="text-xs font-bold text-white uppercase tracking-wider bg-gray-800 px-2 py-0.5 rounded">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div class="bg-primary/5 p-4 rounded-xl tech-border border border-primary/20">
                                <i class="fas fa-truck-fast text-primary mb-3 text-lg"></i>
                                <span class="text-[10px] font-bold text-primary uppercase tracking-widest font-mono block mb-1">Logistics ID / Courier</span>
                                <span class="text-sm font-bold text-white block">{{$o->courier_name}}</span>
                                <span class="text-xs font-mono text-gray-400 mt-1 block tracking-wider bg-dark px-2 py-1 rounded inline-block border border-gray-700">{{$o->tracking_code}}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-dark tech-border border border-red-500/20 rounded-2xl relative overflow-hidden">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500/50 mb-4 block animate-pulse"></i>
                    <h3 class="text-xl font-bold text-white mb-2 font-mono">Archive Empty</h3>
                    <p class="text-sm font-mono text-gray-500 uppercase tracking-widest">No dispatch logs found under this identity.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection