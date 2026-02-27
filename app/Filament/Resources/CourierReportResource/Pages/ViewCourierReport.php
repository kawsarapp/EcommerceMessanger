<?php

namespace App\Filament\Resources\CourierReportResource\Pages;

use App\Filament\Resources\CourierReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourierReport extends ViewRecord
{
    protected static string $resource = CourierReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
