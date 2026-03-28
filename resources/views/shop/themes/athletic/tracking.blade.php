@extends('shop.themes.athletic.layout')
@section('title', 'MISSION TRACER | ' . $client->shop_name)

@section('content')
@php
$baseUrl=$client->custom_domain?'https://'.preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')):route('shop.show',$client->slug);
@endphp
<div class="max-w-[70rem] mx-auto px-4 sm:px-8 py-16 md:py-24">
    
    <!-- Heavy Headers -->
    <div class="text-center mb-16 relative">
        <div class="absolute inset-0 flex items-center justify-center -z-10 opacity-5">
            <i class="fas fa-satellite-dish text-[20rem] text-dark"></i>
        </div>
        <div class="w-32 h-32 bg-dark mx-auto mb-8 flex items-center justify-center text-primary text-6xl shadow-[8px_8px_0_#e11d48] -skew-x-[8deg] border-4 border-dark">
            <i class="fas fa-satellite-dish skew-x-[8deg]"></i>
        </div>
        <h1 class="text-6xl md:text-8xl font-display font-bold text-dark mb-4 uppercase tracking-tighter mix-blend-multiply">MISSION TRACER</h1>
        <p class="font-sans font-bold text-lg text-gray-500 max-w-lg mx-auto uppercase tracking-widest border-t-4 border-dark pt-4 line-clamp-3">ENTER YOUR COMM FREQUENCY (PHONE) TO LOCATE YOUR PACKAGE IN TRANSIT.</p>
    </div>

    <!-- Search Box -->
    <div class="max-w-3xl mx-auto mb-20">
        <form method="GET" class="relative">
            <div class="flex flex-col sm:flex-row bg-white border-[6px] border-dark shadow-[12px_12px_0_#e11d48] p-2 md:p-4 -skew-x-[4deg]">
                <div class="pl-6 text-dark flex items-center skew-x-[4deg]"><i class="fas fa-fingerprint text-4xl"></i></div>
                <input type="text" name="phone" value="{{ request('phone') }}" placeholder="ENTER 11-DIGIT FREQUENCY"
                    class="flex-1 bg-transparent border-none py-6 px-6 text-dark font-display font-bold text-3xl md:text-5xl focus:ring-0 placeholder-gray-300 uppercase skew-x-[4deg]">
                <button type="submit" class="btn-speed bg-primary px-12 py-6 text-white font-display font-bold text-3xl md:text-4xl uppercase tracking-widest transition skew-x-[4deg] border-4 border-dark mt-4 sm:mt-0">
                    <span class="skew-x-[4deg]">LOCATE</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Results Overview -->
    @if(request('phone'))
    <div>
        <h4 class="text-center mb-16 pb-8 border-b-8 border-dark">
            <span class="font-display font-bold text-4xl text-gray-400 uppercase tracking-widest block mb-2">INTELLIGENCE REPORT FOR</span>
            <span class="bg-dark text-primary px-8 py-3 text-5xl font-display font-bold uppercase tracking-widest shadow-[6px_6px_0_#e11d48] border-4 border-primary -skew-x-[6deg] inline-block">
                <span class="skew-x-[6deg] block">{{ request('phone') }}</span>
            </span>
        </h4>

        <div class="grid grid-cols-1 gap-12">
        @forelse($orders ?? [] as $o)
            <div class="bg-gray-50 border-8 border-dark p-8 md:p-12 hover:-translate-y-2 hover:shadow-[16px_16px_0_#e11d48] transition-all duration-300 relative group overflow-hidden">
                
                <div class="absolute -right-12 -top-12 text-[15rem] font-display font-black text-gray-200 select-none z-0 mix-blend-multiply opacity-30 transform group-hover:scale-110 group-hover:-rotate-12 transition-all duration-700">#{{$o->id}}</div>

                <div class="relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 pb-8 border-b-[6px] border-gray-300 gap-6">
                        <div class="flex items-end gap-6">
                            <div class="w-20 h-20 bg-dark text-primary border-4 border-primary flex items-center justify-center text-4xl -skew-x-[8deg] shrink-0">
                                <i class="fas fa-box skew-x-[8deg]"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-display font-bold text-xl text-gray-500 uppercase tracking-widest leading-none mb-2">DEPLOYMENT ID</span>
                                <span class="font-display font-black text-7xl text-dark leading-none">#{{ $o->id }}</span>
                            </div>
                        </div>
                        
                        <!-- Status Badge Brutal -->
                        <div class="px-8 py-4 border-[6px] text-3xl font-display font-bold uppercase tracking-widest -skew-x-[6deg] bg-white text-dark border-dark shadow-[4px_4px_0_111]">
                            <span class="skew-x-[6deg] block flex items-center gap-4">
                                @if($o->order_status=='pending') <i class="fas fa-hourglass-start"></i> IN QUEUE
                                @elseif($o->order_status=='processing') <i class="fas fa-cog fa-spin"></i> ENGAGED
                                @elseif($o->order_status=='shipped') <i class="fas fa-plane-departure text-primary"></i> IN TRANSIT
                                @elseif($o->order_status=='completed') <i class="fas fa-check-double text-green-600"></i> DELIVERED
                                @elseif($o->order_status=='cancelled') <i class="fas fa-skull-crossbones text-red-600"></i> ABORTED
                                @else <i class="fas fa-radar"></i> {{ $o->order_status }} @endif
                            </span>
                        </div>
                    </div>

                    <!-- Four Grid Info -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                        <div class="bg-white border-4 border-dark p-6">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">TIME STAMP</span>
                            <span class="font-display font-bold text-3xl text-dark block leading-none mb-1">{{ $o->created_at->format('d M') }}</span>
                            <span class="font-sans font-bold text-sm text-primary">{{ $o->created_at->format('h:i A') }}</span>
                        </div>
                        <div class="bg-white border-4 border-dark p-6">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">VALUE</span>
                            <span class="font-display font-bold text-4xl text-primary block leading-none mt-2">৳{{ number_format($o->total_amount) }}</span>
                        </div>
                        <div class="bg-white border-4 border-dark p-6">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">PAYMENT</span>
                            <span class="font-display font-bold text-3xl uppercase block leading-none mt-2 {{ $o->payment_status=='paid' ? 'text-green-600' : 'text-dark' }}">{{ $o->payment_status }}</span>
                        </div>
                        
                        @if($o->courier_name)
                        <div class="bg-dark text-white border-4 border-primary p-6 shadow-[6px_6px_0_#e11d48]">
                            <span class="font-sans font-bold text-xs text-gray-400 uppercase tracking-widest block mb-2 border-b-2 border-gray-700 pb-2">CARRIER</span>
                            <span class="font-display font-bold text-3xl text-primary block leading-none mb-1">{{ $o->courier_name }}</span>
                            @if($o->tracking_code)<span class="font-mono text-sm text-gray-300 truncate block">{{ $o->tracking_code }}</span>@endif
                        </div>
                        @else
                        <div class="bg-white border-4 border-dark p-6 flex flex-col justify-center border-dashed">
                            <span class="font-sans font-bold text-xs text-gray-500 uppercase tracking-widest block mb-2 border-b-2 border-gray-200 pb-2">CARRIER</span>
                            <span class="font-display font-bold text-2xl text-gray-400 block leading-none mt-2">AWAITING ASSIGNMENT</span>
                        </div>
                        @endif
                    </div>

                    <!-- Progress Bar Brutal -->
                    @if($o->order_status == 'shipped')
                    <div class="mt-8 pt-10 border-t-[6px] border-dark relative">
                        <div class="absolute -top-5 left-1/2 transform -translate-x-1/2 bg-white px-6 font-display font-bold text-2xl uppercase tracking-widest text-primary border-4 border-dark -skew-x-[6deg]">
                            <span class="skew-x-[6deg] block">TRANSIT VISUALIZATION</span>
                        </div>
                        <div class="flex justify-between text-lg font-display font-bold tracking-widest text-gray-400 mb-4 px-2">
                            <span>BASE</span><span class="text-primary">INBOUND</span><span>DROPZONE</span>
                        </div>
                        <div class="h-6 bg-gray-200 border-4 border-dark -skew-x-[8deg] relative overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-primary w-[75%]"></div>
                            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+PGVsbGlwc2UgY3g9IjIwIiBjeT0iMjAiIHJ4PSI0IiByeT0iNCIgZmlsbD0icmdiYSgyNTUsIDI1NSwgMjU1LCAwLjIpIi8+PC9zdmc+')] opacity-50 mix-blend-overlay"></div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        @empty
            <div class="text-center py-24 bg-gray-50 border-[10px] border-dark border-dashed">
                <div class="w-28 h-28 bg-dark mx-auto flex items-center justify-center text-5xl text-gray-600 border-4 border-gray-600 -skew-x-[8deg] mb-8">
                    <i class="fas fa-ghost skew-x-[8deg]"></i>
                </div>
                <h3 class="text-4xl md:text-6xl font-display font-black text-dark mb-4 uppercase tracking-tighter mix-blend-multiply">NO RECORDS FOUND</h3>
                <p class="font-sans font-bold text-lg text-gray-500 max-w-lg mx-auto uppercase tracking-widest border-t-4 border-gray-300 pt-4 mb-10">THE SYSTEM COULD NOT LOCATE ANY ACTIVE DEPLOYMENT LINKED TO THAT FREQUENCY.</p>
                
                <a href="{{ $baseUrl }}" class="btn-speed bg-primary px-12 py-6 text-white font-display font-bold text-3xl uppercase tracking-widest border-4 border-dark mx-auto w-fit shadow-[8px_8px_0_111]">
                    RE-ENGAGE SHOP
                </a>
            </div>
        @endforelse
        </div>
    </div>
    @endif
</div>
@endsection
