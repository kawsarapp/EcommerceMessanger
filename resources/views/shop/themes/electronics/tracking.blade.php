@extends('shop.themes.electronics.layout')

@section('title', 'Track Tech Order - ' . $client->shop_name)

@section('content')
<main class="flex-1 max-w-4xl mx-auto w-full px-4 py-12 mb-20 md:mb-12">
    
    <div class="text-center mb-12">
        <div class="w-16 h-16 bg-slate-900 text-primary rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl shadow-lg border border-slate-800">
            <i class="fas fa-satellite-dish"></i>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold font-heading text-slate-900 tracking-tight">Track Your Tech</h1>
        <p class="text-slate-500 mt-3 text-sm">Enter your phone number to find your parcel in real-time.</p>
    </div>

    <div class="bg-white p-3 rounded-2xl shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-slate-200 mb-12 max-w-xl mx-auto">
        <form action="{{ route('shop.track.submit', $client->slug) }}" method="POST" class="relative flex">
            @csrf
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-phone-alt text-slate-400"></i>
                </div>
                <input type="tel" name="phone" value="{{ $phone ?? '' }}" 
                       class="w-full bg-slate-50 border-none rounded-l-xl py-4 pl-12 pr-4 text-slate-800 focus:ring-0 outline-none font-mono text-lg placeholder-slate-400" 
                       placeholder="01XXXXXXXXX" required pattern="[0-9]{11}">
            </div>
            <button type="submit" class="bg-slate-900 hover:bg-black text-white px-8 rounded-xl font-bold transition flex items-center gap-2 uppercase tracking-widest text-sm shadow-md">
                Search
            </button>
        </form>
    </div>

    @if(isset($phone))
        @if(isset($orders) && $orders->count() > 0)
            <div class="space-y-6">
                @foreach($orders as $order)
                <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-slate-200">
                    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 border-b border-slate-100 pb-6 mb-6">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="font-bold text-slate-900 text-lg">Order #{{ $order->id }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded bg-slate-100 text-slate-600 font-mono">{{ $order->created_at->format('d M, Y') }}</span>
                            </div>
                            <p class="text-sm text-slate-500"><i class="fas fa-map-marker-alt text-primary mr-1"></i> {{ $order->shipping_address }}</p>
                        </div>
                        
                        <div class="text-right">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                                    'processing' => 'bg-blue-50 text-blue-600 border-blue-200',
                                    'shipped' => 'bg-purple-50 text-purple-600 border-purple-200',
                                    'delivered' => 'bg-green-50 text-green-600 border-green-200',
                                    'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                                ];
                                $statusColor = $statusColors[$order->order_status] ?? 'bg-slate-50 text-slate-600 border-slate-200';
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest border {{ $statusColor }}">
                                <span class="w-2 h-2 rounded-full {{ str_replace(['bg-', '50', 'text-', '600', 'border-', '200'], ['bg-', '500', '', '', '', ''], $statusColor) }} animate-pulse"></span>
                                {{ $order->order_status }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        @foreach($order->items as $item)
                        <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <div class="w-16 h-16 bg-white rounded-lg border border-slate-200 overflow-hidden flex-shrink-0 p-1">
                                <img src="{{ asset('storage/' . $item->product->thumbnail) }}" class="w-full h-full object-contain mix-blend-multiply">
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-sm text-slate-800 line-clamp-1">{{ $item->product->name }}</h4>
                                <div class="text-xs text-slate-500 mt-1 font-mono">
                                    Qty: {{ $item->quantity }} 
                                    @if(isset($item->attributes['color'])) | {{ $item->attributes['color'] }} @endif
                                    @if(isset($item->attributes['size'])) | {{ $item->attributes['size'] }} @endif
                                </div>
                            </div>
                            <div class="font-bold text-slate-900 font-mono text-sm">
                                ৳{{ number_format($item->price) }}
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="bg-slate-900 rounded-xl p-5 text-slate-300 font-mono text-sm">
                        <div class="flex justify-between mb-2">
                            <span>Subtotal</span>
                            <span>৳{{ number_format($order->subtotal) }}</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Shipping</span>
                            <span>৳{{ number_format($order->shipping_charge) }}</span>
                        </div>
                        @if($order->discount_amount > 0)
                        <div class="flex justify-between text-primary mb-2">
                            <span>Discount</span>
                            <span>- ৳{{ number_format($order->discount_amount) }}</span>
                        </div>
                        @endif
                        <div class="border-t border-slate-700 mt-3 pt-3 flex justify-between items-center">
                            <span class="font-bold uppercase tracking-widest text-slate-400 text-xs">Total Amount</span>
                            <span class="text-2xl font-black text-white">৳{{ number_format($order->total_amount) }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search-minus text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2 font-heading">No Records Found</h3>
                <p class="text-slate-500 text-sm max-w-xs mx-auto mb-6">We couldn't find any tech orders linked to <strong class="font-mono text-slate-900">{{ $phone }}</strong>.</p>
                <a href="{{ route('shop.show', $client->slug) }}" class="inline-flex items-center gap-2 text-primary font-bold hover:text-primaryDark transition uppercase tracking-widest text-sm">
                    Back to Store <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @endif
    @else
        <div class="text-center mt-16 opacity-30">
            <i class="fas fa-microchip text-6xl text-slate-400 mb-4"></i>
            <p class="font-bold uppercase tracking-widest text-slate-500 text-sm">Secure Tracking System</p>
        </div>
    @endif
</main>
@endsection