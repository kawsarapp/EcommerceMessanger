<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class ConnectionGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Connection Guide';
    protected static ?string $title = 'How to Connect Your Shop';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.connection-guide';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
