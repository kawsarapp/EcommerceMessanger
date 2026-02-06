<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Client;
use Illuminate\Support\Carbon;

class UsageOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $user = auth()->user();
        
        if ($user->id === 1) {
            return [
                Stat::make('System Pulse', 'Healthy')
                    ->description('All systems operational')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->color('success')
                    ->chart([5, 10, 8, 15, 12, 20]),
            ];
        }

        $client = Client::with(['plan', 'products', 'orders', 'conversations'])->where('user_id', $user->id)->first();

        if (!$client || !$client->plan) {
            return [
                Stat::make('Account Status', 'Pending Setup')
                    ->description('Please contact support for a plan.')
                    ->color('danger'),
            ];
        }

        // পারসেন্টেজ আনা (Model থেকে)
        $aiUsage = $client->getAiUsagePercentage();
        $orderUsage = $client->getOrderUsagePercentage();
        $productUsage = $client->getProductUsagePercentage();

        return [
            Stat::make('Subscription', $client->plan->name)
                ->description($client->plan_ends_at ? 'Expires: ' . $client->plan_ends_at->format('d M, Y') : 'Life-time access')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),

            Stat::make('AI Bot Usage', $aiUsage . '%')
                ->description('Monthly AI message quota')
                ->chart([$aiUsage, 100])
                ->color($aiUsage > 90 ? 'danger' : ($aiUsage > 70 ? 'warning' : 'success')),

            Stat::make('Order Limit', $orderUsage . '%')
                ->description('Orders processed this month')
                ->chart([$orderUsage, 100])
                ->color($orderUsage > 85 ? 'danger' : 'info'),

            Stat::make('Inventory Capacity', $productUsage . '%')
                ->description($client->products()->count() . ' / ' . $client->plan->product_limit . ' Products')
                ->color($productUsage >= 100 ? 'danger' : 'success'),
        ];
    }
}