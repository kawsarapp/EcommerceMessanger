<div class="px-4 py-5">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-1">💎 Customer Lifetime Value
        <span class="ml-2 text-sm font-normal text-gray-500">(Top 10)</span>
    </h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">সবচেয়ে valuable কাস্টমারদের তালিকা</p>

    @php $customers = $this->getTopCustomers(); $stats = $this->getSummaryStats(); @endphp

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 text-center">
            <div class="text-2xl font-black text-indigo-600">৳{{ $stats['avg_ltv'] }}</div>
            <div class="text-xs text-gray-500 mt-1">গড় LTV</div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 text-center">
            <div class="text-2xl font-black text-purple-600">{{ $stats['unique_customers'] }}</div>
            <div class="text-xs text-gray-500 mt-1">মোট Customer</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 text-center">
            <div class="text-2xl font-black text-green-600">৳{{ $stats['total_revenue'] }}</div>
            <div class="text-xs text-gray-500 mt-1">মোট Revenue</div>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 text-center">
            <div class="text-2xl font-black text-orange-600">{{ $stats['repeat_customers'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Repeat Buyers</div>
        </div>
        <div class="bg-pink-50 dark:bg-pink-900/20 rounded-xl p-4 text-center">
            <div class="text-2xl font-black text-pink-600">{{ $stats['repeat_rate'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Repeat Rate</div>
        </div>
    </div>

    {{-- Top Customers Table --}}
    @if(count($customers) > 0)
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 font-semibold">#</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 font-semibold">নাম</th>
                    <th class="px-4 py-3 text-left text-xs text-gray-500 font-semibold">Phone</th>
                    <th class="px-4 py-3 text-right text-xs text-gray-500 font-semibold">মোট খরচ (LTV)</th>
                    <th class="px-4 py-3 text-right text-xs text-gray-500 font-semibold">Orders</th>
                    <th class="px-4 py-3 text-right text-xs text-gray-500 font-semibold">গড় Order</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($customers as $i => $c)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="px-4 py-3 text-gray-400 font-mono">
                        @if($i === 0) 🥇
                        @elseif($i === 1) 🥈
                        @elseif($i === 2) 🥉
                        @else {{ $i + 1 }}
                        @endif
                    </td>
                    <td class="px-4 py-3 font-semibold text-gray-800 dark:text-white">{{ $c['name'] }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $c['phone'] }}</td>
                    <td class="px-4 py-3 text-right font-bold text-green-600">৳{{ $c['ltv'] }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $c['order_count'] }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">৳{{ $c['avg_order'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-10 text-gray-400">এখনো কোনো order নেই</div>
    @endif
</div>
