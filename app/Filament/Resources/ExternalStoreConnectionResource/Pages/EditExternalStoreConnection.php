<?php
namespace App\Filament\Resources\ExternalStoreConnectionResource\Pages;
use App\Filament\Resources\ExternalStoreConnectionResource;
use Filament\Resources\Pages\EditRecord;
class EditExternalStoreConnection extends EditRecord {
    protected static string $resource = ExternalStoreConnectionResource::class;
    protected function getHeaderActions(): array {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
