<div class="px-4 py-5">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-1">🔥 Message Heatmap
        <span class="ml-2 text-sm font-normal text-gray-500">(Last 28 Days)</span>
    </h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">কোন সময়ে সবচেয়ে বেশি কাস্টমার message পাঠায়</p>

    @php $heatmap = $this->getHeatmapData(); @endphp

    <div class="overflow-x-auto">
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr>
                    <th class="text-left pr-2 w-10 text-gray-500 dark:text-gray-400"></th>
                    @foreach(range(0, 23) as $h)
                    <th class="text-center text-gray-400 dark:text-gray-500 pb-1 font-normal" style="min-width: 24px;">
                        {{ $h % 3 === 0 ? str_pad($h, 2, '0', STR_PAD_LEFT) : '' }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($heatmap as $row)
                <tr>
                    <td class="text-gray-500 dark:text-gray-400 pr-2 py-0.5 text-right">{{ $row['day'] }}</td>
                    @foreach($row['cells'] as $cell)
                    <td class="py-0.5 px-px">
                        <div class="w-full h-5 rounded-sm cursor-default transition-opacity"
                             title="{{ $cell['count'] }} messages at {{ str_pad($cell['hour'], 2, '0', STR_PAD_LEFT) }}:00"
                             style="background: {{ $cell['intensity'] > 0 ? 'rgba(99, 102, 241, '.($cell['intensity']/100).')' : 'rgba(229,231,235,0.5)' }}">
                        </div>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 flex items-center gap-2 text-xs text-gray-400">
        <span>কম</span>
        @foreach([10, 25, 50, 75, 100] as $i)
        <div class="w-4 h-4 rounded-sm" style="background: rgba(99, 102, 241, {{ $i/100 }})"></div>
        @endforeach
        <span>বেশি</span>
    </div>
</div>
