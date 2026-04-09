@extends('shop.themes.modern.layout')
@section('title', 'Track Details | ' . $client->shop_name)

@section('content')
<div class="max-w-3xl mx-auto px-6 py-24 md:py-32">
    
    <div class="mb-16 text-center">
        <h1 class="text-4xl lg:text-5xl font-black tracking-tighter uppercase text-black mb-4">OrderStatus.</h1>
        <p class="text-gray-400 font-bold uppercase tracking-[0.15em] text-xs">Enter your mobile number to view details.</p>
    </div>

    <!-- Minimal Search Form -->
    <div class="mb-20">
        <form method="GET" action="" class="flex flex-col sm:flex-row border-b-2 border-gray-200 focus-within:border-black transition-colors duration-300">
            <input type="text" name="order_id" value="{{request('order_id')}}" placeholder="e.g. 10045" class="flex-1 bg-transparent border-0 px-0 py-6 text-xl font-bold text-black focus:ring-0 placeholder-gray-300 text-center sm:text-left tracking-widest" required>
            <button type="submit" class="text-xs font-black uppercase tracking-[0.2em] px-8 py-6 hover:text-gray-500 transition-colors">
                Locate
            </button>
        </form>
    </div>

    <!-- Results Section -->
    @if(request('order_id'))
        <div class="space-y-12">
            @forelse($orders ?? [] as $o)
                <div class="bg-gray-50 p-8 md:p-12 border border-gray-100 hover:border-black transition-colors duration-500">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 border-b border-gray-200 pb-8 mb-8">
                        <div>
                            <span class="text-gray-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2 block">Order Identity</span>
                            <h3 class="text-3xl font-black text-black tracking-tighter">#{{$o->id}}</h3>
                        </div>
                        <div class="text-right">
                             <span class="text-[10px] font-black uppercase tracking-[0.2em] px-4 py-2 bg-white border border-gray-200 shadow-sm
                                @if($o->order_status == 'pending') text-yellow-600
                                @elseif($o->order_status == 'completed' || $o->order_status == 'delivered') text-green-600
                                @elseif($o->order_status == 'cancelled') text-red-600
                                @else text-black @endif">
                                {{$o->order_status}}
                            </span>
                        </div>
                    </div>

                    <!-- Visual Order Timeline -->
                    @php
                        $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                        $currentStat = strtolower($o->order_status);
                        if($currentStat == 'completed') $currentStat = 'delivered'; // normalize
                        $currentIndex = array_search($currentStat, $statuses);
                        if($currentIndex === false) $currentIndex = 0;
                        if(in_array($currentStat, ['cancelled', 'returned'])) $currentIndex = -1;
                    @endphp
                    
                    <div class="w-full py-4 mb-10 overflow-hidden">
                        @if($currentIndex >= 0)
                        <div class="relative w-full px-4 sm:px-12">
                            <!-- Background Track -->
                            <div class="absolute top-[7px] left-8 sm:left-16 right-8 sm:right-16 h-[2px] bg-gray-200"></div>
                            <!-- Active Track -->
                            <div class="absolute top-[7px] left-8 sm:left-16 h-[2px] bg-black transition-all duration-1000" style="width: calc({{ ($currentIndex / 3) * 100 }}% - 2rem)"></div>
                            
                            <!-- Nodes -->
                            <div class="relative flex justify-between z-10 w-full">
                                @foreach($statuses as $index => $status)
                                <div class="flex flex-col items-center">
                                    <div class="w-4 h-4 rounded-full flex items-center justify-center transition-colors duration-500 {{ $index <= $currentIndex ? 'bg-black text-white' : 'bg-gray-200 text-transparent' }} ring-4 ring-gray-50">
                                        @if($index <= $currentIndex)
                                            <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        @endif
                                    </div>
                                    <span class="mt-3 text-[9px] sm:text-[10px] font-black uppercase tracking-[0.1em] sm:tracking-[0.15em] {{ $index <= $currentIndex ? 'text-black' : 'text-gray-400' }}">{{ $status }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @else
                        <div class="p-4 bg-red-50 border border-red-100 text-red-600 text-sm font-bold uppercase tracking-widest text-center mt-2">
                            This Order is currently marked as: {{ $o->order_status }}
                        </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        <div>
                            <span class="text-gray-400 uppercase tracking-[0.15em] text-[10px] font-black mb-2 block">Purchased</span>
                            <span class="text-gray-900 font-bold text-sm">{{$o->created_at->format('M d, Y')}}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 uppercase tracking-[0.15em] text-[10px] font-black mb-2 block">Total</span>
                            <span class="text-gray-900 font-black text-sm">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 uppercase tracking-[0.15em] text-[10px] font-black mb-2 block">Payment</span>
                            <span class="text-gray-900 font-bold text-sm uppercase">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div>
                                <span class="text-gray-400 uppercase tracking-[0.15em] text-[10px] font-black mb-2 block">Dispatch</span>
                                <span class="text-black font-black text-sm underline decoration-1 underline-offset-4">{{$o->courier_name}} <br><span class="text-gray-500">{{$o->tracking_code}}</span></span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-gray-50 border border-gray-100 border-dashed">
                    <p class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">No records found.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection

