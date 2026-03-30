@extends('shop.themes.shwapno.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-[800px] mx-auto px-4 py-16 lg:py-24">
    
    <div class="bg-white border border-gray-100 shadow-sm rounded-lg p-8 sm:p-12 mb-8 relative overflow-hidden">
        {{-- Decorative background element --}}
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-red-50 rounded-full opacity-50 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-yellow-50 rounded-full opacity-50 blur-2xl pointer-events-none"></div>
        
        <div class="relative z-10 text-center mb-10">
            <h1 class="text-[28px] font-black text-gray-800 mb-3 tracking-tight">Track Your Order</h1>
            <p class="text-sm text-gray-500 max-w-md mx-auto">Enter your <strong>Order ID</strong> to see live status updates. Your Order ID was sent to you after placing the order.</p>
        </div>

        <div class="max-w-md mx-auto relative z-10">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <input type="number" name="order_id" value="{{ request('order_id') }}" placeholder="Enter Order ID (e.g. 1042)" required min="1"
                        class="w-full pl-10 pr-4 py-3.5 border border-gray-200 rounded-full text-sm font-medium focus:outline-none focus:border-swred focus:ring-2 focus:ring-red-100 transition shadow-inner">
                </div>
                <button type="submit" class="bg-swred hover:bg-[#c8161c] text-white px-8 py-3.5 rounded-full font-bold text-sm transition shadow-md whitespace-nowrap">
                    <i class="fas fa-search mr-1.5 opacity-80"></i> Track
                </button>
            </form>
            <p class="text-center text-[11px] text-gray-400 mt-3">
                <i class="fas fa-lock mr-1"></i> We only use your Order ID — your phone number is never required.
            </p>
        </div>
    </div>

    @if(request('order_id'))
    <div class="animate-fade-in">
        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-6">Results for Order <span class="text-swred">#{{ request('order_id') }}</span></h4>

        @forelse($orders ?? [] as $o)
            <div class="bg-white border border-gray-200 rounded-lg p-6 sm:p-8 mb-6 shadow-sm hover:shadow-md transition">
                <div class="flex flex-wrap justify-between items-start gap-4 mb-8 pb-6 border-b border-dashed border-gray-200">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Order #</span>
                        <span class="text-2xl font-black text-gray-800">{{ $o->id }}</span>
                    </div>
                    
                    @php
                        $statusColors = [
                            'pending'    => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                            'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'shipped'    => 'bg-purple-50 text-purple-700 border-purple-200',
                            'completed'  => 'bg-green-50 text-green-700 border-green-200',
                            'cancelled'  => 'bg-red-50 text-red-700 border-red-200'
                        ];
                        $badgeClass = $statusColors[$o->order_status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                    @endphp
                    
                    <span class="text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-widest border {{ $badgeClass }} flex items-center shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full mr-2 
                            {{ $o->order_status=='completed' ? 'bg-green-500' : ($o->order_status=='pending' ? 'bg-yellow-500 animate-pulse' : 'bg-current') }}"></span>
                        {{ $o->order_status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 block mb-1 uppercase tracking-wider">Date</span>
                        <span class="text-[13px] font-bold text-gray-700 flex items-center gap-1.5"><i class="far fa-calendar-alt text-gray-400 text-xs"></i> {{ $o->created_at->format('d M, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 block mb-1 uppercase tracking-wider">Total</span>
                        <span class="text-[15px] font-black text-swred">৳{{ number_format($o->total_amount, 0) }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 block mb-1 uppercase tracking-wider">Payment</span>
                        <span class="text-[12px] font-bold {{ $o->payment_status=='paid' ? 'text-green-600' : 'text-gray-500' }} uppercase flex items-center gap-1"><i class="fas {{ $o->payment_status=='paid' ? 'fa-check-circle' : 'fa-clock' }} text-[10px]"></i> {{ $o->payment_status }}</span>
                    </div>
                    @if($o->courier_name)
                    <div>
                        <span class="text-[10px] font-bold text-blue-500 block mb-1 uppercase tracking-wider">Courier</span>
                        <span class="text-[13px] font-bold text-gray-800">{{ $o->courier_name }}</span>
                        @if($o->tracking_code)<span class="text-[11px] text-gray-500 font-mono mt-1 block px-2 py-0.5 bg-gray-100 rounded inline-block">{{ $o->tracking_code }}</span>@endif
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-10 text-center">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm border border-gray-100">
                    <i class="fas fa-search text-gray-300 text-xl"></i>
                </div>
                <h3 class="text-[15px] font-bold text-gray-700 mb-1">Order Not Found</h3>
                <p class="text-[12px] text-gray-500 max-w-xs mx-auto">No order found with ID <span class="font-bold border-b border-gray-300 pb-0.5">#{{ request('order_id') }}</span>. Please check the ID and try again.</p>
            </div>
        @endforelse
    </div>
    @endif
</div>

@php $clean=preg_replace('/^https?:\/\//','',rtrim($client->custom_domain,'/')); $baseUrl=$clean?'https://'.$clean:route('shop.show',$client->slug); @endphp
@endsection

