<?php

namespace App\View\Components;

use App\Models\FlashSale;
use App\Models\Client;
use Illuminate\View\Component;

class FlashSaleBanner extends Component
{
    public ?FlashSale $flashSale = null;
    public int $secondsRemaining = 0;

    public function __construct(?int $clientId = null)
    {
        // Client detect করো: param → custom domain context → request
        $client = null;
        if ($clientId) {
            $client = Client::find($clientId);
        } elseif (app()->bound('custom_domain_client')) {
            $client = app('custom_domain_client');
        }

        if (!$client) return;

        // Flash Sale feature enabled?
        if (!$client->canAccessFeature('allow_flash_sale')) return;
        if (!$client->sellerEnabled('flash_sale')) return;

        // Active flash sale খোঁজো
        $this->flashSale = FlashSale::where('client_id', $client->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at', 'asc')
            ->first();

        if ($this->flashSale) {
            $this->secondsRemaining = max(0, (int) now()->diffInSeconds($this->flashSale->ends_at, false));
        }
    }

    public function render()
    {
        return view('components.flash-sale-banner');
    }
}
