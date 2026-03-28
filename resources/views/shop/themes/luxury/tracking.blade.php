@extends('shop.themes.luxury.layout')
@section('title', 'Trace | ' . $client->shop_name)

@section('content')
<div class="max-w-[100rem] mx-auto px-4 sm:px-12 py-24 md:py-32">
    
    <div class="max-w-2xl mx-auto text-center mb-24">
        <span class="text-[9px] font-bold uppercase tracking-[0.5em] text-primary mb-6 block">Trace Your Order</span>
        <h1 class="font-serif font-light text-5xl md:text-6xl text-white mb-8 tracking-widest uppercase">The Journey.</h1>
    </div>

    <div class="max-w-xl mx-auto mb-28">
        <form method="GET" action="" class="flex flex-col border-b border-white/20 focus-within:border-white transition duration-500 group relative">
            <input type="text" name="phone" value="{{request('phone')}}" placeholder="Registered Identity (01X...)" class="w-full bg-transparent border-none px-0 py-6 text-sm font-light text-white focus:ring-0 transition tracking-widest text-center placeholder-gray-600" required>
            <button type="submit" class="absolute right-0 top-1/2 -translate-y-1/2 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500 group-focus-within:text-white transition hover:text-primary">
                Reveal
            </button>
        </form>
    </div>

    @if(request('phone'))
        <div class="max-w-5xl mx-auto space-y-16">
            @forelse($orders ?? [] as $o)
                <div class="bg-surface/50 border border-white/5 p-10 md:p-16 relative overflow-hidden group hover:border-white/20 transition duration-700">
                    <!-- Subtle ambient glow -->
                    <div class="absolute -top-40 -left-40 w-80 h-80 bg-primary/5 rounded-full blur-[100px] pointer-events-none transition duration-[2s] group-hover:bg-primary/10"></div>
                    
                    <div class="flex flex-col md:flex-row justify-between items-center md:items-end border-b border-white/5 pb-12 mb-12 relative z-10 text-center md:text-left">
                        <div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-[0.4em] block mb-4">Tracking Code</span>
                            <h3 class="font-serif font-light text-4xl text-white tracking-widest hidden md:block">#{{$o->id}}</h3>
                            <h3 class="font-serif font-light text-3xl text-white tracking-widest md:hidden">#{{$o->id}}</h3>
                        </div>
                        <div class="mt-8 md:mt-0">
                            <span class="text-[10px] font-light uppercase tracking-[0.3em] px-6 py-3 border border-white/10 block
                                @if($o->order_status == 'pending') text-yellow-500 
                                @elseif($o->order_status == 'completed') text-white 
                                @elseif($o->order_status == 'cancelled') text-red-500/80 
                                @else text-gray-400 @endif
                                backdrop-blur-sm">
                                Status: {{$o->order_status}}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-12 relative z-10 text-center md:text-left">
                        <div>
                            <span class="text-[9px] text-gray-600 font-bold uppercase tracking-[0.3em] block mb-4 border-b border-white/5 inline-block pb-2">Acquired</span>
                            <span class="text-xs font-light text-gray-300 tracking-wider block">{{$o->created_at->format('d F Y')}}</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-gray-600 font-bold uppercase tracking-[0.3em] block mb-4 border-b border-white/5 inline-block pb-2">Tribute</span>
                            <span class="text-xs font-light text-gray-300 tracking-wider block">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-gray-600 font-bold uppercase tracking-[0.3em] block mb-4 border-b border-white/5 inline-block pb-2">Protocol</span>
                            <span class="text-xs font-light text-gray-300 tracking-wider block uppercase">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div>
                                <span class="text-[9px] text-gray-600 font-bold uppercase tracking-[0.3em] block mb-4 border-b border-white/5 inline-block pb-2">Voyage</span>
                                <span class="text-xs font-light text-primary tracking-wider block">{{$o->courier_name}}</span>
                                <span class="text-[10px] text-gray-500 mt-2 block tracking-widest">{{$o->tracking_code}}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-32 border border-white/5 bg-surface/30">
                    <p class="text-[10px] font-medium text-gray-600 uppercase tracking-[0.4em]">No archives found under this identity.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection
