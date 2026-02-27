<?php

namespace App\Filament\Resources\CourierReportResource\Pages;

use App\Filament\Resources\CourierReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourierReport extends EditRecord
{
    protected static string $resource = CourierReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
