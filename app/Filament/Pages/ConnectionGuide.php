<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Client;

class ConnectionGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';
    protected static ?string $navigationGroup = '🔌 Integrations';
    protected static ?string $navigationLabel = '🔌 Integration & API Docs';
    protected static ?string $title = 'Integration & API Documentation';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.connection-guide';

    public string $apiKey    = '';
    public string $appUrl    = '';
    public string $shopName  = '';

    public function mount(): void
    {
        $user = auth()->user();
        $client = $user?->client;

        $this->apiKey   = $client?->api_token ?? 'YOUR_API_KEY';
        $this->shopName = $client?->shop_name ?? 'Your Shop';
        $this->appUrl   = rtrim(config('app.url'), '/');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
