<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Conversation;
use Filament\Widgets\Widget;

class MessageHeatmapWidget extends Widget
{
    protected static string $view = 'filament.widgets.message-heatmap';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_analytics');
    }

    public function getHeatmapData(): array
    {
        $isAdmin  = auth()->user()?->isSuperAdmin() ?? false;
        $clientId = $isAdmin ? null : Client::where('user_id', auth()->id())->value('id');

        $rows = Conversation::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->where('created_at', '>=', now()->subDays(28))
            ->selectRaw('EXTRACT(HOUR FROM created_at)::int as hour, EXTRACT(DOW FROM created_at)::int + 1 as dow, COUNT(*) as cnt')
            ->groupBy('hour', 'dow')
            ->get();

        // Build 7×24 grid [dow][hour] = count
        $grid = [];
        for ($d = 1; $d <= 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $grid[$d][$h] = 0;
            }
        }
        foreach ($rows as $row) {
            $grid[$row->dow][$row->hour] = (int)$row->cnt;
        }

        $maxVal = max(1, collect($rows)->max('cnt'));
        $days   = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $result = [];
        foreach (range(1, 7) as $d) {
            $dayCells = [];
            foreach (range(0, 23) as $h) {
                $cnt        = $grid[$d][$h];
                $intensity  = round(($cnt / $maxVal) * 100);
                $dayCells[] = ['hour' => $h, 'count' => $cnt, 'intensity' => $intensity];
            }
            $result[] = ['day' => $days[$d - 1], 'cells' => $dayCells];
        }

        return $result;
    }
}
