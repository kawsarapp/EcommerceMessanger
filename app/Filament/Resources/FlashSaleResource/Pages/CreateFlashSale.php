<?php
namespace App\Filament\Resources\FlashSaleResource\Pages;
use App\Filament\Resources\FlashSaleResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;

class CreateFlashSale extends CreateRecord
{
    protected static string $resource = FlashSaleResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!auth()->user()?->isSuperAdmin()) {
            $data['client_id'] = Client::where('user_id', auth()->id())->value('id');
        }
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
