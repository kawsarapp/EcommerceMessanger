<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;
use App\Filament\Resources\ClientResource\Schemas\Tabs\AdminPermissionsTab;

class EditAdminPermissions extends EditRecord
{
    protected static string $resource = ClientResource::class;
    protected static ?string $title = '🔑 Admin Permissions';
    protected static ?string $navigationIcon = 'heroicon-m-shield-check';

    public function form(Form $form): Form
    {
        return $form->schema(
            AdminPermissionsTab::schema()
        );
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
