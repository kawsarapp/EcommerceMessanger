@extends('shop.themes.fashion.layout')
@section('title', 'Track Order | ' . $client->shop_name)

@section('content')
<div class="max-w-[100rem] mx-auto px-4 sm:px-8 py-20 md:py-32">
    
    <div class="max-w-xl mx-auto text-center mb-16">
        <h1 class="font-heading font-black text-4xl md:text-5xl text-primary mb-6">Track Order</h1>
        <p class="text-gray-500 font-medium text-sm leading-relaxed">Enter your registered mobile number below to retrieve the status of your recent purchases.</p>
    </div>

    <div class="max-w-2xl mx-auto mb-20">
        <form method="GET" action="" class="flex flex-col sm:flex-row gap-0 group">
            <input type="text" name="phone" value="{{request('phone')}}" placeholder="01XXXXXXXXX" class="flex-1 bg-transparent border-0 border-b border-gray-300 px-0 py-5 text-lg font-medium text-gray-900 focus:ring-0 focus:border-black transition tracking-widest text-center sm:text-left placeholder-gray-300" required>
            <button type="submit" class="border-b border-gray-300 group-focus-within:border-black text-xs font-bold uppercase tracking-[0.2em] px-8 py-5 hover:text-gray-500 transition-all sm:ml-4 mt-4 sm:mt-0">
                Track
            </button>
        </form>
    </div>

    @if(request('phone'))
        <div class="max-w-4xl mx-auto space-y-12">
            @forelse($orders ?? [] as $o)
                <div class="border border-gray-200 p-8 md:p-12">
                    <div class="flex flex-col sm:flex-row justify-between items-start md:items-center border-b border-gray-100 pb-8 mb-8">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] block mb-2">Order No.</span>
                            <h3 class="font-heading font-black text-3xl">#{{$o->id}}</h3>
                        </div>
                        <div class="mt-4 sm:mt-0">
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] px-4 py-2 border 
                                @if($o->order_status == 'pending') border-yellow-200 text-yellow-600 bg-yellow-50/30
                                @elseif($o->order_status == 'completed') border-green-200 text-green-700 bg-green-50/30
                                @elseif($o->order_status == 'cancelled') border-red-200 text-red-600 bg-red-50/30
                                @else border-gray-200 text-black @endif">
                                {{$o->order_status}}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] block mb-3">Date</span>
                            <span class="text-sm font-medium text-gray-900">{{$o->created_at->format('M d, Y')}}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] block mb-3">Total Amount</span>
                            <span class="text-sm font-medium text-gray-900">৳{{number_format($o->total_amount)}}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] block mb-3">Payment</span>
                            <span class="text-sm font-medium text-gray-900 uppercase">{{$o->payment_status}}</span>
                        </div>
                        @if($o->courier_name)
                            <div>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] block mb-3">Delivery Partner</span>
                                <span class="text-sm font-medium text-gray-900">{{$o->courier_name}}<br><span class="text-xs text-gray-500 mt-1 block">{{$o->tracking_code}}</span></span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-24 border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No order found with this number.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
@endsection