<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Client;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $clientId = Client::where('user_id', auth()->id())->first()?->id;

        if (!$clientId && auth()->id() !== 1) return [];

        // ১. আজকের বিক্রি (TK + Qty)
        $todayOrders = Order::where('client_id', $clientId)->whereDate('created_at', Carbon::today());
        $todaySales = $todayOrders->sum('total_amount');
        $todayCount = $todayOrders->count();

        // ২. এই সপ্তাহের বিক্রি
        $weeklySales = Order::where('client_id', $clientId)
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('total_amount');

        // ৩. স্ট্যাটাস অনুযায়ী অর্ডার সংখ্যা
        $processing = Order::where('client_id', $clientId)->where('order_status', 'processing')->count();
        $shipped = Order::where('client_id', $clientId)->where('order_status', 'shipped')->count();
        $delivered = Order::where('client_id', $clientId)->where('order_status', 'delivered')->count();

        return [
            Stat::make('Today Sales', '৳' . number_format($todaySales))
                ->description($todayCount . ' items sold today')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('Weekly Revenue', '৳' . number_format($weeklySales))
                ->description('Current week total')
                ->color('info'),

            Stat::make('Pending Orders', $processing)
                ->description('Needs processing')
                ->color('warning'),

            Stat::make('In Delivery', $shipped)
                ->description('Shipped to courier')
                ->color('primary'),
        ];
    }
}