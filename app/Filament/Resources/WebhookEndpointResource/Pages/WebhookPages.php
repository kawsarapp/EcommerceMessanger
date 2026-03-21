<?php

namespace App\Filament\Resources\WebhookEndpointResource\Pages;

use App\Filament\Resources\WebhookEndpointResource;
use App\Models\Client;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

class ListWebhookEndpoints extends ListRecords
{
    protected static string $resource = WebhookEndpointResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}

class CreateWebhookEndpoint extends CreateRecord
{
    protected static string $resource = WebhookEndpointResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        if (!auth()->user()?->isSuperAdmin()) {
            $data['client_id'] = Client::where('user_id', auth()->id())->value('id');
        }
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}

class EditWebhookEndpoint extends EditRecord
{
    protected static string $resource = WebhookEndpointResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
