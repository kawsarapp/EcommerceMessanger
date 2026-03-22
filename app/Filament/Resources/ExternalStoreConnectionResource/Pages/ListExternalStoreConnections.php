<?php
namespace App\Filament\Resources\ExternalStoreConnectionResource\Pages;
use App\Filament\Resources\ExternalStoreConnectionResource;
use Filament\Resources\Pages\ListRecords;
class ListExternalStoreConnections extends ListRecords {
    protected static string $resource = ExternalStoreConnectionResource::class;
    protected function getHeaderActions(): array {
        return [\Filament\Actions\CreateAction::make()];
    }
}
