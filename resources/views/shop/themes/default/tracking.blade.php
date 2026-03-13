@extends('shop.themes.default.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-20">
    
    <div class="max-w-2xl mx-auto text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight mb-3">Track Your Order</h1>
        <p class="text-gray-500 font-medium text-sm lg:text-base">Enter the phone number you used during checkout to view your order status.</p>
    </div>

    <div class="max-w-md mx-auto mb-16">
        <form method="GET" action="" class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <i class="fas fa-phone-alt absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" name="phone" value="{{request('phone')}}" placeholder="01XXXXXXXXX" class="w-full bg-white border border-gray-300 rounded px-10 py-3 text-gray-900 font-medium focus:border-primary focus:ring-1 focus:ring-primary transition shadow-sm placeholder-gray-400" required>
            </div>
            <button type="submit" class="bg-primary text-white py-3 px-6 rounded font-bold text-sm hover:bg-gray-800 transition shadow-sm whitespace-nowrap">
                Check Status
            </button>
        </form>
    </div>

    @if(request('phone'))
        <div class="max-w-4xl mx-auto space-y-6">
            @forelse($orders ?? [] as $o)
                <div class="bg-white rounded border border-gray-200 p-6 md:p-8 shadow-sm">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start md:items-center pb-6 border-b border-gray-100 mb-6 gap-4">
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1">Order Number</span>
                            <h3 class="text-2xl font-bold text-gray-900">#{{$o->id}}</h3>
                        </div>
                        
                        <div>
                             <div class="inline-flex items-center gap-2 text-sm font-bold px-4 py-2 rounded-full border
                                @if($o->order_status == 'pending') border-yellow-200 text-yellow-700 bg-yellow-50
                                @elseif($o->order_status == 'completed') border-green-200 text-green-700 bg-green-50
                                @elseif($o->order_status == 'cancelled') border-red-200 text-red-600 bg-red-50
                                @else border-gray-200 text-gray-600 bg-gray-50 @endif">
                                <span class="uppercase font-semibold tracking-wide">{{$o->order_status}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1">Date Created</span>
                            <span class="text-sm font-semibold text-gray-900">{{$o->created_at->format('M d, Y')}}</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1">Amount</span>
                            <span class="text-sm font-bold text-primary">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1">Payment</span>
                            <span class="text-xs font-bold text-gray-700 uppercase bg-gray-100 border border-gray-200 px-2.5 py-1 rounded inline-block">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div class="col-span-2 md:col-span-1 border-t border-gray-100 pt-4 md:border-t-0 md:pt-0">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1">Delivery</span>
                                <span class="text-sm font-semibold text-gray-900 block">{{$o->courier_name}}</span>
                                <span class="text-xs text-gray-500 font-medium block mt-1 break-all">{{$o->tracking_code}}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-gray-50 rounded border border-dashed border-gray-300">
                    <i class="fas fa-box-open text-4xl text-gray-300 mb-4 block"></i>
                    <h3 class="text-lg font-bold text-gray-700 mb-1">No orders found</h3>
                    <p class="text-sm text-gray-500">We couldn't locate any orders for the number provided.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection