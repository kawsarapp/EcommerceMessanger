@extends('shop.themes.premium.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
    
    <div class="text-center mb-12">
        <div class="bg-primary/10 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-box-open text-3xl text-primary"></i>
        </div>
        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-4">Track Your Order</h1>
        <p class="text-gray-500 font-medium text-lg">Enter the mobile number you used during checkout.</p>
    </div>

    <!-- Search Form -->
    <div class="bg-white p-8 lg:p-12 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-gray-100 flex flex-col mb-12">
        <form method="GET" action="" class="w-full flex flex-col sm:flex-row gap-4">
            <input type="text" name="order_id" value="{{request('order_id')}}" placeholder="e.g. 10045" class="flex-1 bg-gray-50 border-gray-200 rounded-2xl px-6 py-5 text-lg font-bold text-gray-900 focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-inner" required>
            <button type="submit" class="btn-premium text-white px-10 py-5 rounded-2xl font-extrabold text-lg shadow-lg flex items-center justify-center gap-3">
                <i class="fas fa-search opacity-80"></i> Track
            </button>
        </form>
    </div>

    <!-- Results Section -->
    @if(request('order_id'))
        <div class="mt-8 space-y-6">
            @forelse($orders ?? [] as $o)
                <div class="bg-white rounded-[2rem] border border-gray-100 p-8 shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                        <div>
                            <span class="text-gray-400 font-bold text-xs uppercase tracking-widest">Order ID</span>
                            <h3 class="text-2xl font-extrabold text-gray-900">#{{$o->id}}</h3>
                        </div>
                        <span class="px-6 py-2.5 rounded-full text-sm font-bold uppercase tracking-widest shadow-sm 
                            @if($o->order_status == 'pending') bg-yellow-50 text-yellow-600 border border-yellow-100
                            @elseif($o->order_status == 'completed') bg-green-50 text-green-600 border border-green-100
                            @elseif($o->order_status == 'cancelled') bg-red-50 text-red-600 border border-red-100
                            @else bg-blue-50 text-blue-600 border border-blue-100 @endif">
                            <i class="fas fa-circle text-[8px] mr-2 opacity-50 relative -top-0.5"></i> {{$o->order_status}}
                        </span>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 mb-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-sm font-medium">
                            <div class="flex flex-col"><span class="text-gray-400 uppercase tracking-wider text-xs font-bold mb-1">Date</span><span class="text-gray-900">{{$o->created_at->format('d M, Y')}}</span></div>
                            <div class="flex flex-col"><span class="text-gray-400 uppercase tracking-wider text-xs font-bold mb-1">Amount</span><span class="text-gray-900 font-bold">৳{{number_format($o->total_amount)}}</span></div>
                            <div class="flex flex-col"><span class="text-gray-400 uppercase tracking-wider text-xs font-bold mb-1">Payment</span><span class="text-gray-900 uppercase">{{$o->payment_status}}</span></div>
                            @if($o->courier_name)
                                <div class="flex flex-col"><span class="text-gray-400 uppercase tracking-wider text-xs font-bold mb-1">Courier</span><span class="text-primary font-bold">{{$o->courier_name}} ({{$o->tracking_code}})</span></div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-gray-50 rounded-[2rem] border border-gray-100">
                    <i class="fas fa-search-minus text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No active orders found</h3>
                    <p class="text-gray-500 font-medium">Please check the mobile number and try again.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection

