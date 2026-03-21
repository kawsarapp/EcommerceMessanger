<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\OrderSession;
use App\Models\Order;
use Filament\Widgets\Widget;

class ConversionFunnelWidget extends Widget
{
    protected static string $view = 'filament.widgets.conversion-funnel';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_analytics');
    }

    public function getFunnelData(): array
    {
        $isAdmin = auth()->user()?->isSuperAdmin() ?? false;
        $clientId = $isAdmin ? null : Client::where('user_id', auth()->id())->value('id');

        $period = now()->subDays(30);

        // Step 1: Total conversations (unique customers)
        $conversations = Conversation::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->where('created_at', '>=', $period)
            ->distinct('sender_id')
            ->count('sender_id');

        // Step 2: Product viewed (session reached select_variant or beyond)
        $productViewed = OrderSession::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->where('updated_at', '>=', $period)
            ->whereRaw("JSON_EXTRACT(customer_info, '$.product_id') IS NOT NULL")
            ->count();

        // Step 3: Reached collect_info (address step)
        $reachedAddress = OrderSession::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->where('updated_at', '>=', $period)
            ->whereRaw("JSON_EXTRACT(customer_info, '$.step') IN ('collect_info', 'confirm_order', 'completed')")
            ->count();

        // Step 4: Orders placed
        $orders = Order::query()
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->where('created_at', '>=', $period)
            ->count();

        return [
            ['label' => '💬 মোট Conversation', 'count' => $conversations, 'color' => '#6366f1'],
            ['label' => '🛍️ Product দেখেছে', 'count' => $productViewed, 'color' => '#8b5cf6'],
            ['label' => '📝 Address দিয়েছে', 'count' => $reachedAddress, 'color' => '#a78bfa'],
            ['label' => '✅ Order করেছে', 'count' => $orders, 'color' => '#22c55e'],
        ];
    }

    public function getConversionRate(): string
    {
        $data = $this->getFunnelData();
        $total = $data[0]['count'];
        $orders = $data[3]['count'];
        if ($total <= 0) return '0%';
        return round(($orders / $total) * 100, 1) . '%';
    }
}
