<?php
namespace App\Filament\Resources\WebhookEndpointResource\Pages;
use App\Filament\Resources\WebhookEndpointResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhookEndpoint extends CreateRecord
{
    protected static string $resource = WebhookEndpointResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!auth()->user()?->isSuperAdmin()) {
            $data['client_id'] = Client::where('user_id', auth()->id())->value('id');
        }
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
