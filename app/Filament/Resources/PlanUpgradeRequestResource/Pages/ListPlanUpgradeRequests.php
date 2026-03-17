<?php

namespace App\Filament\Resources\PlanUpgradeRequestResource\Pages;

use App\Filament\Resources\PlanUpgradeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanUpgradeRequests extends ListRecords
{
    protected static string $resource = PlanUpgradeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
