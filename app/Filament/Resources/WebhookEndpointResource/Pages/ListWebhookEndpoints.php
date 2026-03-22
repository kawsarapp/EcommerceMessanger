<?php
namespace App\Filament\Resources\WebhookEndpointResource\Pages;
use App\Filament\Resources\WebhookEndpointResource;
use Filament\Resources\Pages\ListRecords;

class ListWebhookEndpoints extends ListRecords
{
    protected static string $resource = WebhookEndpointResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}
