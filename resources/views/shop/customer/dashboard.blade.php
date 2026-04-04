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
                    স্বাগতম, {{ $customer->name }}!
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
                        <i class="fas fa-sign-out-alt mr-2 text-gray-400"></i> লগআউট
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Points Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <i class="fas fa-star text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">লয়্যালটি পয়েন্ট</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($loyaltyBalance) }}</p>
                </div>
            </div>

            <!-- Total Orders Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <i class="fas fa-shopping-bag text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">মোট অর্ডার</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $orders->count() }}</p>
                </div>
            </div>
            
            <!-- Pending Orders Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600 mr-4">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">পেন্ডিং অর্ডার</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $orders->whereIn('order_status', ['pending', 'processing'])->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    অর্ডার হিস্টোরি
                </h3>
            </div>
            
            @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">অর্ডার আইডি</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">তারিখ</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">টোটাল</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">স্ট্যাটাস</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">অ্যাকশন</th>
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
                                <form action="{{ $clean ? $baseUrl.'/track' : route('shop.track', $client->slug) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="phone" value="{{ $order->customer_phone }}">
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                    <button type="submit" class="text-primary hover:text-primary/80 font-semibold flex items-center justify-end w-full">
                                        বিস্তারিত <i class="fas fa-arrow-right ml-1.5 text-xs"></i>
                                    </button>
                                </form>
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
                <h3 class="text-lg font-medium text-gray-900">কোনো অর্ডার পাওয়া যায়নি</h3>
                <p class="mt-1 text-sm text-gray-500">আপনি এখনও কোনো অর্ডার করেননি।</p>
                <div class="mt-6">
                    <a href="{{ $baseUrl }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-black focus:outline-none transition-colors">
                        শপিং শুরু করুন
                    </a>
                </div>
            </div>
            @endif
        </div>
        
    </div>
</div>
@endsection
