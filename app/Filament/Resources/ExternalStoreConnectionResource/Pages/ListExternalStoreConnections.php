<?php
namespace App\Filament\Resources\ExternalStoreConnectionResource\Pages;
use App\Filament\Resources\ExternalStoreConnectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExternalStoreConnections extends ListRecords {
    protected static string $resource = ExternalStoreConnectionResource::class;

    protected function getHeaderActions(): array {
        return [
            // ⬇️ Download WordPress Plugin button
            Actions\Action::make('download_plugin')
                ->label('⬇️ Download WordPress Plugin')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('plugin.download'))
                ->openUrlInNewTab(false)
                ->tooltip('Click to download the NeuralCart WordPress plugin (ZIP file)'),

            Actions\CreateAction::make(),
        ];
    }
}
