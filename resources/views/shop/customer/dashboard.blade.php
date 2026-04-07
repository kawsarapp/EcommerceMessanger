@extends('shop.themes.' . $client->theme_name . '.layout')
@section('title', 'My Account - ' . $client->shop_name)

@section('content')
<div class="bg-gray-50 py-10 min-h-[80vh]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    {{ $client->widgets['welcome_text'] ?? 'Welcome' }}, {{ $customer->name }}!
                </h2>
                <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                    <div class="mt-2 flex items-center text-sm text-gray-500">
                        <i class="fas fa-phone text-gray-400 mr-1.5"></i>
                        {{ $customer->phone }}
                    </div>
                    @if($customer->email)
                    <div class="mt-2 flex items-center text-sm text-gray-500">
                        <i class="fas fa-envelope text-gray-400 mr-1.5"></i>
                        {{ $customer->email }}
                    </div>
                    @endif
                </div>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <form action="{{ $clean ? $baseUrl.'/logout' : route('shop.customer.logout', $client->slug) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                        <i class="fas fa-sign-out-alt mr-2 text-gray-400"></i> {{ $client->widgets['logout_text'] ?? 'Logout' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <!-- Points Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-center items-center text-center col-span-2 md:col-span-1">
                <div class="w-12 h-12 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mb-2">
                    <i class="fas fa-star text-xl"></i>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $client->widgets['loyalty_text'] ?? 'Loyalty Points' }}</p>
                <p class="text-2xl font-black text-gray-900 mt-1">{{ number_format($loyaltyBalance) }}</p>
            </div>

            <!-- Total Orders -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-center items-center text-center">
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-2">
                    <i class="fas fa-shopping-bag text-lg"></i>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $client->widgets['total_orders_text'] ?? 'Total Orders' }}</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($totalOrders) }}</p>
            </div>
            
            <!-- Pending Orders -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-center items-center text-center">
                <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mb-2">
                    <i class="fas fa-clock text-lg"></i>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $client->widgets['pending_orders_text'] ?? 'Pending' }}</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($pendingOrders) }}</p>
            </div>

            <!-- Completed Orders -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-center items-center text-center">
                <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center mb-2">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $client->widgets['completed_orders_text'] ?? 'Completed' }}</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($completedOrders) }}</p>
            </div>

            <!-- Cancelled Orders -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col justify-center items-center text-center">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-2">
                    <i class="fas fa-times-circle text-lg"></i>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $client->widgets['cancelled_orders_text'] ?? 'Cancelled' }}</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($cancelledOrders) }}</p>
            </div>
        </div>

        <!-- Order History -->
        <div x-data="{ showModal: false, selectedOrder: null }" class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ $client->widgets['order_history_text'] ?? 'Order History' }}
                </h3>
            </div>
            
            @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #{{ $order->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $order->created_at->format('d M, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                ৳{{ number_format($order->total_amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'shipped' => 'bg-indigo-100 text-indigo-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'returned' => 'bg-gray-100 text-gray-800',
                                    ];
                                    $color = $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2.5 py-1 inline-flex text-[11px] leading-5 font-semibold rounded-md {{ $color }} uppercase tracking-wider">
                                    {{ $order->order_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @php
                                    $orderData = [
                                        'id' => $order->id,
                                        'date' => $order->created_at->format('d M, Y h:i A'),
                                        'total' => number_format($order->total_amount),
                                        'subtotal' => number_format($order->subtotal),
                                        'delivery' => number_format($order->delivery_charge),
                                        'discount' => number_format($order->discount),
                                        'status' => $order->order_status,
                                        'address' => $order->shipping_address ?? 'N/A',
                                        'phone' => $order->customer_phone,
                                        'items' => $order->orderItems->map(fn($i) => ['name' => $i->product->title ?? 'Product', 'qty' => $i->quantity, 'price' => number_format($i->price), 'total' => number_format($i->price * $i->quantity)])
                                    ];
                                @endphp
                                <button type="button" @click="selectedOrder = JSON.parse('{{ addslashes(json_encode($orderData)) }}'); showModal = true" class="text-primary hover:text-primary/80 font-semibold flex items-center justify-end w-full">
                                    {{ $client->widgets['details_text'] ?? 'Details' }} <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-12 text-center">
                <div class="mx-auto h-16 w-16 text-gray-400 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-box-open text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">{{ $client->widgets['no_orders_title'] ?? 'No orders found' }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $client->widgets['no_orders_subtitle'] ?? 'You have not placed any orders yet.' }}</p>
                <div class="mt-6">
                    <a href="{{ $baseUrl }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-black focus:outline-none transition-colors">
                        {{ $client->widgets['start_shopping_text'] ?? 'Start Shopping' }}
                    </a>
                </div>
            </div>
            @endif

            <!-- Alpine.js Order Details Modal -->
            <div x-show="showModal" style="display: none" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="showModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div x-show="showModal" 
                         x-transition:enter="ease-out duration-300" 
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave="ease-in duration-200" 
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                         class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                        
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center sm:px-6">
                            <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">
                                Order Details <span class="text-primary">#<span x-text="selectedOrder?.id"></span></span>
                            </h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-500 focus:outline-none transition">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div class="px-6 py-5 sm:p-6 text-left">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <p class="text-sm font-bold text-gray-500 uppercase mb-1">Status</p>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-md bg-gray-100 text-gray-800 uppercase tracking-wider" x-text="selectedOrder?.status"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-500 uppercase mb-1">Order Date</p>
                                    <p class="text-sm text-gray-900 font-medium" x-text="selectedOrder?.date"></p>
                                </div>
                                <div class="col-span-1 sm:col-span-2 bg-gray-50 p-4 rounded-lg border border-gray-100 shadow-sm">
                                    <p class="text-sm font-bold text-gray-500 uppercase mb-2"><i class="fas fa-map-marker-alt text-primary mr-1"></i> Shipping Address</p>
                                    <p class="text-sm text-gray-900 leading-relaxed" x-text="selectedOrder?.address"></p>
                                    <p class="text-sm text-gray-600 mt-1"><i class="fas fa-phone mr-1"></i> <span x-text="selectedOrder?.phone"></span></p>
                                </div>
                            </div>
                            
                            <h4 class="font-bold text-gray-900 border-b pb-2 mb-4">Items Summary</h4>
                            <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                                <template x-for="item in selectedOrder?.items">
                                    <div class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0 p-2 hover:bg-gray-50 rounded-md transition">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="item.name"></p>
                                            <p class="text-xs text-gray-500 mt-0.5">৳<span x-text="item.price"></span> x <span x-text="item.qty"></span></p>
                                        </div>
                                        <div class="text-sm font-bold text-gray-900">
                                            ৳<span x-text="item.total"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <div class="flex justify-between mb-2 text-sm text-gray-600">
                                    <span>Subtotal</span>
                                    <span>৳<span x-text="selectedOrder?.subtotal"></span></span>
                                </div>
                                <div class="flex justify-between mb-2 text-sm text-gray-600">
                                    <span>Delivery Charge</span>
                                    <span>৳<span x-text="selectedOrder?.delivery"></span></span>
                                </div>
                                <div class="flex justify-between mb-2 text-sm text-red-500" x-show="selectedOrder?.discount !== '0'">
                                    <span>Discount</span>
                                    <span>- ৳<span x-text="selectedOrder?.discount"></span></span>
                                </div>
                                <div class="flex justify-between mt-3 pt-3 border-t font-bold text-lg text-gray-900">
                                    <span>Total</span>
                                    <span>৳<span x-text="selectedOrder?.total"></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Modal -->
        </div>
        
    </div>
</div>

@endsection
