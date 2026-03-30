<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PermissionGuide extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = '⚙️ Settings & Tools';
    protected static ?string $navigationLabel = 'Permission Guide';
    protected static ?string $title           = '🔐 Permission System — সম্পূর্ণ গাইড';
    protected static ?int    $navigationSort  = 11;

    protected static string $view = 'filament.pages.permission-guide';

    /** শুধু Super Admin দেখতে পারবে */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
