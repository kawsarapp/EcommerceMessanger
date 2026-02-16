@extends('shop.layout')

@section('title', 'Track Order - ' . $client->shop_name)

@section('content')
<main class="flex-1 max-w-3xl mx-auto w-full px-4 py-12 mb-20 md:mb-0">
    
    <div class="text-center mb-10">
        <div class="w-16 h-16 bg-blue-50 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm border border-blue-100">
            <i class="fas fa-search-location"></i>
        </div>
        <h1 class="text-3xl font-bold font-heading text-gray-900">Track Your Order</h1>
        <p class="text-gray-500 mt-2">Enter your phone number to check the latest status.</p>
    </div>

    <div class="bg-white p-2 rounded-2xl shadow-lg border border-gray-100 mb-10 max-w-xl mx-auto">
        <form action="{{ route('shop.track.submit', $client->slug) }}" method="POST" class="relative">
            @csrf
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-phone-alt text-gray-400"></i>
                </div>
                <input type="tel" name="phone" value="{{ $phone ?? '' }}" 
                       class="w-full pl-11 pr-32 py-4 bg-gray-50 border border-transparent rounded-xl focus:bg-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition font-medium text-gray-800 placeholder-gray-400" 
                       placeholder="e.g. 017XXXXXXXX" required autofocus>
                
                <button type="submit" class="absolute right-2 top-2 bottom-2 bg-gray-900 hover:bg-black text-white px-6 rounded-lg font-bold text-sm transition shadow-md flex items-center gap-2">
                    Track <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>

    @if(isset($orders))
        @if($orders->count() > 0)
            <div class="space-y-6">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-lg font-bold text-gray-800">Recent Orders ({{ $orders->count() }})</h3>
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded font-bold">Found</span>
                </div>
                
                @foreach($orders as $order)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition duration-300">
                    
                    <div class="bg-gray-50/50 px-6 py-4 flex flex-wrap justify-between items-center border-b border-gray-100 gap-2">
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Order ID</span>
                            <p class="text-lg font-bold text-gray-900 font-mono">#{{ $order->id }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Date placed</span>
                            <p class="text-sm font-medium text-gray-700 flex items-center gap-1">
                                <i class="far fa-calendar-alt"></i> {{ $order->created_at->format('d M, Y') }}
                            </p>
                        </div>
                    </div>

                    <div class="p-6">
                        @php
                            $statusConfig = match($order->order_status) {
                                'pending' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200', 'icon' => 'fa-clock'],
                                'processing' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'fa-cog fa-spin'],
                                'shipped' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'icon' => 'fa-shipping-fast'],
                                'delivered' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'icon' => 'fa-check-circle'],
                                'cancelled' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'fa-times-circle'],
                                default => ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'border' => 'border-gray-200', 'icon' => 'fa-info-circle']
                            };
                        @endphp
                        
                        <div class="flex flex-wrap items-center gap-3 mb-6">
                            <div class="px-4 py-2 rounded-full border text-sm font-bold flex items-center gap-2 {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }} {{ $statusConfig['border'] }}">
                                <i class="fas {{ $statusConfig['icon'] }}"></i>
                                {{ ucfirst($order->order_status) }}
                            </div>
                            
                            @if($order->order_status == 'shipped')
                                <p class="text-xs text-purple-600 font-medium animate-pulse flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span> On the way
                                </p>
                            @endif
                        </div>

                        <div class="space-y-4">
                            @foreach($order->orderItems as $item) {{-- Controller এ with('orderItems') ব্যবহার করতে হবে --}}
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 border border-gray-200">
                                    <img src="{{ asset('storage/' . ($item->product->thumbnail ?? '')) }}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-800 text-sm truncate">{{ $item->product->name ?? 'Product Removed' }}</h4>
                                    <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                        <span class="bg-gray-100 px-2 py-0.5 rounded">Qty: {{ $item->quantity }}</span>
                                        @if(isset($item->variant)) 
                                            <span class="bg-gray-100 px-2 py-0.5 rounded">{{ $item->variant }}</span> 
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900 text-sm">৳{{ number_format($item->price * $item->quantity) }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-6 pt-4 border-t border-dashed border-gray-200 flex justify-between items-center">
                            <span class="font-medium text-gray-500 text-sm">Total Amount</span>
                            <span class="text-xl font-bold text-primary">৳{{ number_format($order->total_amount) }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 bg-white rounded-3xl shadow-sm border border-gray-100">
                <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-3xl opacity-50"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">No Orders Found</h3>
                <p class="text-gray-500 max-w-xs mx-auto mb-6">We couldn't find any orders linked to <strong>{{ $phone }}</strong>.</p>
                <a href="{{ route('shop.show', $client->slug) }}" class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                    Browse Products <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @endif
    @else
        <div class="text-center mt-12 opacity-60">
            <i class="fas fa-shield-alt text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-400">Your information is secure.</p>
        </div>
    @endif

</main>
@endsection