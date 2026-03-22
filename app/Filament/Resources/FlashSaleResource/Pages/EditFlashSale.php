<?php
namespace App\Filament\Resources\FlashSaleResource\Pages;
use App\Filament\Resources\FlashSaleResource;
use Filament\Resources\Pages\EditRecord;

class EditFlashSale extends EditRecord
{
    protected static string $resource = FlashSaleResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
