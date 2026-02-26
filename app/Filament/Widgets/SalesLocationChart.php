<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class SalesLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Sales by Location (Heatmap)';
    
    // ড্যাশবোর্ডে উইজেটটি কত নম্বরে দেখাবে
    protected static ?int $sort = 2; 

    protected function getData(): array
    {
        $user = auth()->user();
        $clientId = $user->client->id ?? null;

        $query = Order::select('district', DB::raw('count(*) as total'))
            ->whereNotNull('district')
            ->where('order_status', '!=', 'cancelled'); // বাতিল হওয়া অর্ডার বাদ
        
        // যদি সেলার হয়, তবে শুধু তার নিজের দোকানের ডাটা দেখবে
        if ($user->id !== 1 && $clientId) {
            $query->where('client_id', $clientId);
        }

        // সবচেয়ে বেশি অর্ডার আসা সেরা ৭টি জেলা
        $data = $query->groupBy('district')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Orders',
                    'data' => $data->pluck('total')->toArray(),
                    // চার্টের জন্য সুন্দর কিছু কালার
                    'backgroundColor' => ['#f87171', '#fb923c', '#fbbf24', '#34d399', '#60a5fa', '#a78bfa', '#f472b6'],
                ],
            ],
            'labels' => $data->pluck('district')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // আপনি চাইলে 'pie' বা 'bar' ও দিতে পারেন
    }
}