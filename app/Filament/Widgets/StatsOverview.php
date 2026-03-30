<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Client;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1; // Show right below QuickActions

    protected function getStats(): array
    {
        $userId = auth()->id();
        $isAdmin = auth()->user()?->isSuperAdmin() ?? false;
        $client = !$isAdmin ? Client::where('user_id', $userId)->first() : null;

        $orderQuery = Order::query()->when(!$isAdmin, fn($q) => $q->where('client_id', $client?->id));

        $todaySales = (clone $orderQuery)->whereDate('created_at', Carbon::today())->sum('total_amount');
        $yesterdaySales = (clone $orderQuery)->whereDate('created_at', Carbon::yesterday())->sum('total_amount');
        $weeklySales = (clone $orderQuery)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total_amount');
        $monthlySales = (clone $orderQuery)->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->sum('total_amount');

        // 🔥 Trend Calculation (Percentage)
        $todayDiff = $todaySales - $yesterdaySales;
        $trendIcon = $todayDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendColor = $todayDiff >= 0 ? 'success' : 'danger';
        
        $percentChange = $yesterdaySales > 0 ? abs($todayDiff / $yesterdaySales) * 100 : ($todaySales > 0 ? 100 : 0);
        $trendText = ($todayDiff >= 0 ? '+' : '-') . number_format($percentChange, 1) . '% vs yesterday';

        $salesData = collect(range(6, 0))->map(function ($days) use ($orderQuery) {
            return (clone $orderQuery)->whereDate('created_at', Carbon::now()->subDays($days))->sum('total_amount');
        })->toArray();

        $ordersUrl = route('filament.admin.resources.orders.index');

        return [
            Stat::make($isAdmin ? 'Global Today Revenue' : 'Today Sales', '৳' . number_format($todaySales))
                ->description($trendText)
                ->descriptionIcon($trendIcon)
                ->chart($salesData)
                ->color($trendColor)
                ->url($ordersUrl), // Link to orders

            Stat::make('Weekly Revenue', '৳' . number_format($weeklySales))
                ->description('Current week performance')
                ->chart(array_reverse($salesData))
                ->color('info')
                ->url($ordersUrl),

            Stat::make('Monthly Revenue', '৳' . number_format($monthlySales))
                ->description(Carbon::now()->format('F') . ' performance')
                ->color('success')
                ->url($ordersUrl),

            Stat::make('Pending Orders', (clone $orderQuery)->where('order_status', 'processing')->count())
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url($ordersUrl . '?tableFilters[order_status][value]=processing'), // Filtered link
        ];
    }
}