<?php
namespace App\Filament\Resources\ExternalStoreConnectionResource\Pages;
use App\Filament\Resources\ExternalStoreConnectionResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;
class CreateExternalStoreConnection extends CreateRecord {
    protected static string $resource = ExternalStoreConnectionResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        if (!auth()->user()?->isSuperAdmin()) {
            $data['client_id'] = Client::where('user_id', auth()->id())->value('id');
        }
        return $data;
    }
}
