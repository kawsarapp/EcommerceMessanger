<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Order;
use Filament\Widgets\Widget;

class CustomerLtvWidget extends Widget
{
    protected static string $view = 'filament.widgets.customer-ltv';
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_analytics');
    }

    public function getTopCustomers(): array
    {
        $isAdmin  = auth()->user()?->isSuperAdmin() ?? false;
        $clientId = $isAdmin ? null : Client::where('user_id', auth()->id())->value('id');

        return Order::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->whereIn('order_status', ['completed', 'delivered', 'shipped', 'processing'])
            ->selectRaw('sender_id, customer_name, customer_phone, SUM(total_amount) as ltv, COUNT(*) as order_count, AVG(total_amount) as avg_order')
            ->groupBy('sender_id', 'customer_name', 'customer_phone')
            ->orderByDesc('ltv')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'name'        => $row->customer_name ?: 'Unknown',
                'phone'       => $row->customer_phone,
                'ltv'         => number_format($row->ltv),
                'order_count' => $row->order_count,
                'avg_order'   => number_format($row->avg_order),
            ])
            ->toArray();
    }

    public function getSummaryStats(): array
    {
        $isAdmin  = auth()->user()?->isSuperAdmin() ?? false;
        $clientId = $isAdmin ? null : Client::where('user_id', auth()->id())->value('id');

        $query = Order::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->whereIn('order_status', ['completed', 'delivered', 'shipped', 'processing']);

        $totalRevenue  = $query->sum('total_amount');
        $uniqueCustomers = (clone $query)->distinct('sender_id')->count('sender_id');
        $avgLtv = $uniqueCustomers > 0 ? $totalRevenue / $uniqueCustomers : 0;

        $repeatCustomers = Order::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->selectRaw('sender_id, COUNT(*) as cnt')
            ->groupBy('sender_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return [
            'total_revenue'     => number_format($totalRevenue),
            'unique_customers'  => $uniqueCustomers,
            'avg_ltv'           => number_format($avgLtv),
            'repeat_customers'  => $repeatCustomers,
            'repeat_rate'       => $uniqueCustomers > 0 ? round(($repeatCustomers / $uniqueCustomers) * 100) . '%' : '0%',
        ];
    }
}
