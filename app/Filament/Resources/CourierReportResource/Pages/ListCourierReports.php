<?php

namespace App\Filament\Resources\CourierReportResource\Pages;

use App\Filament\Resources\CourierReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourierReports extends ListRecords
{
    protected static string $resource = CourierReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    // 🔥 Widget ti ekhane register kora holo
    protected function getHeaderWidgets(): array
    {
        return [
            CourierReportResource\Widgets\CourierStatsWidget::class,
        ];
    }
}