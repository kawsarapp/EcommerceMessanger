<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\Schemas\MenuFormSchema;
use App\Filament\Resources\MenuResource\Schemas\MenuTableSchema;
use App\Models\Menu;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';
    
    protected static ?string $navigationGroup = 'Site Settings';
    
    protected static ?string $modelLabel = 'Navigation Menu';
    protected static ?string $pluralModelLabel = 'Navigation Menus';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
        return $query->where('client_id', $clientId);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(MenuFormSchema::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(MenuTableSchema::columns())
            ->filters(MenuTableSchema::filters())
            ->actions(MenuTableSchema::actions())
            ->bulkActions(MenuTableSchema::bulkActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }

    // ==========================================
    // 🔒 PERMISSION LOGIC
    // ==========================================

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        $client = $user->client;
        return $client && $client->hasActivePlan();
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        $client = $user->client;
        return $client && $client->id === $record->client_id && $client->hasActivePlan();
    }

    public static function canDelete(Model $record): bool
    {
        return self::canEdit($record);
    }
}
