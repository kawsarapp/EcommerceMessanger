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

    protected function getStats(): array
    {
        $userId = auth()->id();
        $isAdmin = $userId === 1;
        $client = !$isAdmin ? Client::where('user_id', $userId)->first() : null;

        // কুয়েরি ফিল্টার (Admin সব দেখবে, Client শুধু নিজেরটা)
        $orderQuery = Order::query()->when(!$isAdmin, fn($q) => $q->where('client_id', $client?->id));

        // ১. রেভিনিউ ক্যালকুলেশন
        $todaySales = (clone $orderQuery)->whereDate('created_at', Carbon::today())->sum('total_amount');
        $weeklySales = (clone $orderQuery)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total_amount');

        // ২. চার্ট ডাটা (গত ৭ দিনের বিক্রির গ্রাফ)
        $salesData = collect(range(6, 0))->map(function ($days) use ($orderQuery) {
            return (clone $orderQuery)->whereDate('created_at', Carbon::now()->subDays($days))->sum('total_amount');
        })->toArray();

        return [
            Stat::make($isAdmin ? 'Global Today Revenue' : 'Today Sales', '৳' . number_format($todaySales))
                ->description($isAdmin ? 'Across all shops' : 'Sales from your shop today')
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->chart($salesData)
                ->color('success'),

            Stat::make('Weekly Revenue', '৳' . number_format($weeklySales))
                ->description('Current week performance')
                ->chart(array_reverse($salesData))
                ->color('info'),

            Stat::make('Pending Orders', (clone $orderQuery)->where('order_status', 'processing')->count())
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('In Delivery', (clone $orderQuery)->where('order_status', 'shipped')->count())
                ->description('Currently with courier')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
        ];
    }
}