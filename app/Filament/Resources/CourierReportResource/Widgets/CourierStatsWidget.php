<?php

namespace App\Filament\Resources\CourierReportResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CourierStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $query = Order::whereNotNull('courier_name');

        // Data Isolation
        if (auth()->id() !== 1) {
            $query->whereHas('client', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        // Clone query for separate stats
        $steadfastCount = (clone $query)->where('courier_name', 'steadfast')->count();
        $pathaoCount = (clone $query)->where('courier_name', 'pathao')->count();
        $redxCount = (clone $query)->where('courier_name', 'redx')->count();

        return [
            Stat::make('Steadfast Parcels', $steadfastCount)
                ->description('Total sent to Steadfast')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Pathao Parcels', $pathaoCount)
                ->description('Total sent to Pathao')
                ->descriptionIcon('heroicon-m-truck')
                ->color('danger'),

            Stat::make('RedX Parcels', $redxCount)
                ->description('Total sent to RedX')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'),
        ];
    }
}