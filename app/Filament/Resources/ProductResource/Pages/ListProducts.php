<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\ProductImporter; // ইম্পোর্টার ক্লাস কল করা হলো

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 🔥 এক্সেল ইম্পোর্ট বাটন
            Actions\ImportAction::make()
                ->importer(ProductImporter::class)
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->label('Import CSV/Excel'),
                
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}