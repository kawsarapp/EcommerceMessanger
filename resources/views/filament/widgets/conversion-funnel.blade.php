<div class="px-4 py-5">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-1">📊 Conversion Funnel
        <span class="ml-2 text-sm font-normal text-gray-500">(Last 30 Days)</span>
    </h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">কতজন customer conversation → product → address → order পর্যন্ত গেছে</p>

    @php $steps = $this->getFunnelData(); $rate = $this->getConversionRate(); @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ($steps as $i => $step)
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow p-5 border-t-4 text-center"
             style="border-color: {{ $step['color'] }}">
            <div class="text-3xl font-black mb-1" style="color: {{ $step['color'] }}">{{ number_format($step['count']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $step['label'] }}</div>
            @if ($i > 0)
                @php $prev = $steps[$i-1]['count']; $pct = $prev > 0 ? round(($step['count']/$prev)*100) : 0; @endphp
                <div class="mt-2 text-xs font-semibold text-indigo-500">↓ {{ $pct }}% এগিয়েছে</div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 rounded-xl px-5 py-3 w-fit">
        <span class="text-2xl font-black text-green-600">{{ $rate }}</span>
        <span class="text-sm text-gray-600 dark:text-gray-400">Overall Conversion Rate</span>
    </div>

    {{-- Funnel Bar Visualization --}}
    <div class="mt-6 space-y-2">
        @foreach ($steps as $step)
        @php
            $maxCount = max(1, $steps[0]['count']);
            $width = $maxCount > 0 ? round(($step['count'] / $maxCount) * 100) : 0;
        @endphp
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500 w-32">{{ $step['label'] }}</span>
            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-5">
                <div class="h-5 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                     style="width: {{ $width }}%; background: {{ $step['color'] }};">
                    <span class="text-white text-xs font-bold">{{ number_format($step['count']) }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
