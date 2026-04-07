@extends('shop.themes.pikabo.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 md:py-16">
    
    <div class="text-center mb-10">
        <h1 class="text-3xl font-extrabold text-dark mb-3 tracking-tight">Track Your Order</h1>
        <p class="text-gray-500 text-sm max-w-md mx-auto">Enter your mobile number to check the processing status and tracking updates for your recent orders.</p>
    </div>

    <div class="max-w-md mx-auto mb-12">
        <form method="GET" class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden flex">
            <div class="bg-gray-50 px-4 flex items-center border-r border-gray-200 text-gray-400">
                <i class="fas fa-hashtag"></i>
            </div>
            <input type="text" name="order_id" value="{{ request('order_id') }}" placeholder="Enter 11-digit mobile number"
                class="flex-1 py-3.5 px-4 text-sm font-medium text-dark focus:outline-none border-none placeholder-gray-400">
            <button type="submit" class="bg-primary hover:bg-bddeep text-white px-6 font-bold text-sm uppercase tracking-wider transition">
                Track
            </button>
        </form>
    </div>

    @if(request('order_id'))
    <div>
        <h4 class="text-center mb-6 text-sm font-bold text-gray-700 border-b border-gray-200 pb-4">
            Orders for <span class="bg-primary/10 text-primary px-2 py-0.5 rounded ml-1">{{ request('order_id') }}</span>
        </h4>

        @forelse($orders ?? [] as $o)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4 hover:border-primary transition relative overflow-hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5 pb-5 border-b border-gray-100">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Order Number</span>
                        <span class="text-2xl font-black text-dark tracking-tight">#{{ $o->id }}</span>
                    </div>
                    <span class="text-[10px] font-bold px-3 py-1.5 rounded uppercase tracking-wider
                        @if($o->order_status=='pending') bg-yellow-100 text-yellow-800
                        @elseif($o->order_status=='processing') bg-blue-100 text-blue-800
                        @elseif($o->order_status=='shipped') bg-purple-100 text-purple-800
                        @elseif($o->order_status=='completed') bg-green-100 text-green-800
                        @elseif($o->order_status=='cancelled') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800 @endif border border-black/5">
                        <i class="fas fa-circle text-[8px] mr-1"></i> {{ $o->order_status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Date</span>
                        <span class="text-sm font-bold text-gray-800 block">{{ $o->created_at->format('d M, Y') }}</span>
                        <span class="text-[10px] text-gray-500">{{ $o->created_at->format('h:i A') }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Total Amount</span>
                        <span class="text-lg font-black text-blue-600 block leading-tight">&#2547;{{ number_format($o->total_amount) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Payment</span>
                        <span class="text-xs font-bold {{ $o->payment_status=='paid' ? 'text-green-600' : 'text-gray-600' }} uppercase">{{ $o->payment_status }}</span>
                    </div>
                    @if($o->courier_name)
                    <div>
                        <span class="text-[10px] font-bold text-primary uppercase block mb-1">Courier</span>
                        <span class="text-sm font-bold text-gray-800 block">{{ $o->courier_name }}</span>
                        @if($o->tracking_code)<span class="text-[10px] text-gray-500 font-mono">{{ $o->tracking_code }}</span>@endif
                    </div>
                    @endif
                </div>

                @if($o->order_status == 'shipped')
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <div class="flex justify-between text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-widest">
                        <span>Ordered</span><span>Processing</span><span class="text-primary">Shipped</span><span>Delivered</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full w-[75%]"></div>
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="text-center py-16 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <i class="fas fa-search text-3xl text-gray-300 mb-4 inline-block"></i>
                <h3 class="text-base font-bold text-gray-700 mb-2">No orders found</h3>
                <p class="text-[11px] text-gray-500 mb-6">We couldn't find any orders matching this mobile number.</p>
                <a href="{{ $baseUrl }}" class="bg-white border border-gray-200 hover:border-primary text-primary px-6 py-2 rounded text-sm font-bold transition shadow-sm">
                    Return to Shopping
                </a>
            </div>
        @endforelse
    </div>
    @endif
</div>

@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
@endsection

