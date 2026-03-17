<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class StaffAccountWidget extends Widget
{
    protected static string $view = 'filament.widgets.staff-account-widget';

    protected static ?int $sort = -3; // Highest priority on dashboard

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }
}
