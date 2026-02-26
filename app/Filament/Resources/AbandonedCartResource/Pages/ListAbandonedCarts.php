<?php

namespace App\Filament\Resources\AbandonedCartResource\Pages;

use App\Filament\Resources\AbandonedCartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAbandonedCarts extends ListRecords
{
    protected static string $resource = AbandonedCartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
