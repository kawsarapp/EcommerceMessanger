<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Client;
use App\Models\Order;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected static ?int $sort = 0; // Dashboard-এ সবার আগে দেখাবে

    protected int | string | array $columnSpan = 'full';

    public string $shopUrl = '';
    public string $shopName = '';
    public string $greeting = '';

    public function mount(): void
    {
        $user = auth()->user();
        $isAdmin = $user?->isSuperAdmin() ?? false;

        $client = $isAdmin
            ? Client::latest()->first()
            : Client::where('user_id', $user?->id)->first();

        if ($client) {
            $domain = $client->custom_domain
                ? 'https://' . rtrim($client->custom_domain, '/')
                : route('shop.show', $client->slug);

            $this->shopUrl  = $domain;
            $this->shopName = $client->shop_name ?? 'Your Store';
        }

        // Time-based greeting (বাংলায়)
        $hour = now()->hour;
        if ($hour < 12) {
            $this->greeting = 'শুভ সকাল';
        } elseif ($hour < 17) {
            $this->greeting = 'শুভ অপরাহ্ন';
        } else {
            $this->greeting = 'শুভ সন্ধ্যা';
        }
    }

    public static function canView(): bool
    {
        return true;
    }
}
