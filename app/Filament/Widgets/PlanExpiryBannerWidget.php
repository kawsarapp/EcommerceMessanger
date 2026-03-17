<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PlanExpiryBannerWidget extends Widget
{
    protected static string $view = 'filament.widgets.plan-expiry-banner';

    // Show at the very top, before all other widgets
    protected static ?int $sort = -100;

    // Only render if plan is expired/about to expire
    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isSuperAdmin()) return false;
        if ($user->isStaff()) return false; // staff are blocked at login anyway

        $client = $user->client;
        if (!$client) return false;

        // Show banner if expired OR expiring within 7 days
        return $client->isPlanExpired() || $client->daysUntilExpiry() <= 7;
    }

    public function getViewData(): array
    {
        $client = auth()->user()?->client;
        $isExpired = $client?->isPlanExpired() ?? true;
        $daysLeft  = $client?->daysUntilExpiry() ?? 0;

        return [
            'isExpired' => $isExpired,
            'daysLeft'  => $daysLeft,
            'planName'  => $client?->plan?->name ?? 'N/A',
            'expiresAt' => $client?->plan_ends_at?->format('d M Y') ?? '—',
        ];
    }
}
