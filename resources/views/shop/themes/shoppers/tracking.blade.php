@extends('shop.themes.shoppers.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-[800px] mx-auto px-4 py-12 md:py-20 bg-white">
    
    <div class="text-center mb-10">
        <h1 class="text-2xl font-black text-shdark mb-3 uppercase tracking-wider">Order Tracking</h1>
        <p class="text-gray-500 text-xs max-w-sm mx-auto">Please enter your 11-digit mobile number below to track the status of your current orders.</p>
    </div>

    <div class="max-w-md mx-auto mb-16">
        <form method="GET" class="flex border border-gray-300">
            <div class="bg-gray-50 px-4 flex items-center border-r border-gray-200 text-gray-400">
                <i class="fas fa-phone-alt text-sm"></i>
            </div>
            <input type="text" name="order_id" value="{{ request('order_id') }}" placeholder="Mobile Number" required
                class="flex-1 py-3 px-4 text-sm font-medium text-dark focus:outline-none focus:ring-1 focus:ring-shred border-none placeholder-gray-400">
            <button type="submit" class="bg-shred hover:bg-red-600 text-white px-6 font-bold text-xs uppercase tracking-wider transition">
                Find
            </button>
        </form>
    </div>

    @if(request('order_id'))
    <div>
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <h4 class="text-sm font-bold text-gray-700 uppercase">Search Results</h4>
            <span class="text-xs text-gray-400">Mobile: <strong class="text-shred">{{ request('order_id') }}</strong></span>
        </div>

        @forelse($orders ?? [] as $o)
            <div class="border border-gray-200 p-6 mb-6 hover:shadow-md transition">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-6 border-b border-dashed border-gray-200">
                    <div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Order ID</span>
                        <span class="text-xl font-black text-shdark tracking-tight">#{{ $o->id }}</span>
                    </div>
                    
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'shipped' => 'bg-purple-100 text-purple-800 border-purple-200',
                            'completed' => 'bg-green-100 text-green-800 border-green-200',
                            'cancelled' => 'bg-red-100 text-red-800 border-red-200'
                        ];
                        $badgeClass = $statusColors[$o->order_status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                    @endphp
                    
                    <span class="text-[10px] font-bold px-3 py-1 rounded uppercase tracking-wider border {{ $badgeClass }}">
                        {{ $o->order_status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                    <div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase block mb-1">Date Placed</span>
                        <span class="text-xs font-bold text-gray-800 block">{{ $o->created_at->format('d M, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase block mb-1">Total Amount</span>
                        <span class="text-sm font-black text-shred block">TK {{ number_format($o->total_amount) }}</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase block mb-1">Payment Status</span>
                        <span class="text-xs font-bold {{ $o->payment_status=='paid' ? 'text-green-600' : 'text-gray-600' }} uppercase">{{ $o->payment_status }}</span>
                    </div>
                    @if($o->courier_name)
                    <div>
                        <span class="text-[9px] font-bold text-blue-500 uppercase block mb-1">Shipping Sub-Agent</span>
                        <span class="text-xs font-bold text-gray-800 block">{{ $o->courier_name }}</span>
                        @if($o->tracking_code)<span class="text-[10px] text-gray-500 font-mono mt-0.5 block">{{ $o->tracking_code }}</span>@endif
                    </div>
                    @endif
                </div>

                @if($o->order_status == 'shipped')
                <div class="mt-8">
                    <div class="h-1 bg-gray-200 relative">
                        <div class="absolute top-0 left-0 h-full bg-blue-500 w-[75%]"></div>
                        <div class="absolute -top-1.5 left-0 w-4 h-4 rounded-full bg-blue-500"></div>
                        <div class="absolute -top-1.5 left-1/4 w-4 h-4 rounded-full bg-blue-500"></div>
                        <div class="absolute -top-1.5 left-[75%] w-4 h-4 rounded-full bg-blue-500 border-2 border-white shadow"></div>
                        <div class="absolute -top-1.5 right-0 w-4 h-4 rounded-full bg-gray-300"></div>
                    </div>
                    <div class="flex justify-between text-[9px] font-bold text-gray-400 mt-3 uppercase tracking-widest px-1">
                        <span class="text-blue-500">Ordered</span><span class="text-blue-500">Processing</span><span class="text-blue-500">Shipped</span><span>Delivered</span>
                    </div>
                </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 px-4 border border-gray-200 bg-gray-50">
                <i class="far fa-folder-open text-3xl text-gray-300 mb-3 block"></i>
                <h3 class="text-sm font-bold text-gray-700 mb-1">No Records Found</h3>
                <p class="text-[11px] text-gray-500">We couldn't locate any orders associated with this number.</p>
            </div>
        @endforelse
    </div>
    @endif
</div>

@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
@endsection

