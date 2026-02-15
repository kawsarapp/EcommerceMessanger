<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - {{ $client->shop_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="{{ route('shop.show', $client->slug) }}" class="flex items-center gap-2 text-gray-600 hover:text-blue-600 font-semibold">
                <i class="fas fa-arrow-left"></i> Back to Shop
            </a>
            <h1 class="text-lg font-bold text-gray-800">{{ $client->shop_name }}</h1>
        </div>
    </header>

    <main class="flex-1 max-w-2xl mx-auto w-full px-4 py-12">
        
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-search-location"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Track Your Order</h2>
            <p class="text-gray-500 mt-2">Enter your phone number to check order status</p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 mb-8">
            <form action="{{ route('shop.track.submit', $client->slug) }}" method="POST" class="relative">
                @csrf
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-phone text-gray-400"></i>
                    </div>
                    <input type="tel" name="phone" value="{{ $phone ?? '' }}" 
                           class="w-full pl-11 pr-4 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition font-medium text-gray-800" 
                           placeholder="Ex: 017XXXXXXXX" required>
                    <button type="submit" class="absolute right-2 top-2 bottom-2 bg-blue-600 hover:bg-blue-700 text-white px-6 rounded-lg font-semibold transition shadow-md">
                        Track
                    </button>
                </div>
            </form>
        </div>

        @if(isset($orders))
            @if($orders->count() > 0)
                <div class="space-y-6">
                    <h3 class="text-xl font-bold text-gray-800 px-1">Found {{ $orders->count() }} Order(s)</h3>
                    
                    @foreach($orders as $order)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-b border-gray-100">
                            <div>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Order ID</span>
                                <p class="text-lg font-bold text-gray-900">#{{ $order->id }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Date</span>
                                <p class="text-sm font-medium text-gray-700">{{ $order->created_at->format('d M, Y') }}</p>
                            </div>
                        </div>

                        <div class="px-6 py-6">
                            @php
                                $statusColor = match($order->order_status) {
                                    'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'shipped' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'delivered' => 'bg-green-100 text-green-700 border-green-200',
                                    'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                                $statusIcon = match($order->order_status) {
                                    'pending' => 'fa-clock',
                                    'processing' => 'fa-cog fa-spin',
                                    'shipped' => 'fa-truck',
                                    'delivered' => 'fa-check-circle',
                                    'cancelled' => 'fa-times-circle',
                                    default => 'fa-info-circle'
                                };
                            @endphp
                            
                            <div class="flex items-center gap-3 mb-6">
                                <div class="px-4 py-2 rounded-full border text-sm font-bold flex items-center gap-2 {{ $statusColor }}">
                                    <i class="fas {{ $statusIcon }}"></i>
                                    {{ ucfirst($order->order_status) }}
                                </div>
                                @if($order->order_status == 'shipped')
                                    <p class="text-sm text-gray-500 animate-pulse">Estimated Delivery: 2-3 Days</p>
                                @endif
                            </div>

                            <div class="space-y-3">
                                @foreach($order->items as $item)
                                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="w-12 h-12 bg-white rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                                        <img src="{{ asset('storage/' . $item->product->thumbnail) }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-800 text-sm line-clamp-1">{{ $item->product->name }}</h4>
                                        <p class="text-xs text-gray-500">Qty: {{ $item->quantity }} x ৳{{ number_format($item->unit_price) }}</p>
                                    </div>
                                    <div class="font-bold text-gray-900">৳{{ number_format($item->price) }}</div>
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-6 pt-4 border-t border-dashed border-gray-200 flex justify-between items-center">
                                <span class="font-medium text-gray-500">Total Amount</span>
                                <span class="text-xl font-bold text-blue-600">৳{{ number_format($order->total_amount) }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach

                </div>
            @else
                <div class="text-center py-12 bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">No Orders Found</h3>
                    <p class="text-gray-500 mt-2">We couldn't find any orders for <strong>{{ $phone }}</strong>.</p>
                    <p class="text-sm text-gray-400 mt-1">Please check the number and try again.</p>
                </div>
            @endif
        @endif

    </main>

    <footer class="bg-white py-6 border-t border-gray-200 mt-auto">
        <div class="text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} {{ $client->shop_name }}. All rights reserved.
        </div>
    </footer>

</body>
</html>