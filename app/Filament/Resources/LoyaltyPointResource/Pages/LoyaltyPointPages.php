<?php

namespace App\Filament\Resources\LoyaltyPointResource\Pages;

use App\Filament\Resources\LoyaltyPointResource;
use App\Models\Client;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;

class ListLoyaltyPoints extends ListRecords
{
    protected static string $resource = LoyaltyPointResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()]; }
}

class CreateLoyaltyPoint extends CreateRecord
{
    protected static string $resource = LoyaltyPointResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        if (!auth()->user()?->isSuperAdmin()) {
            $data['client_id'] = Client::where('user_id', auth()->id())->value('id');
        }
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
