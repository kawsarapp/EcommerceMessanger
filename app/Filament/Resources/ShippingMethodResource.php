<?php

namespace App\Filament\Resources;

use App\Models\ShippingMethod;
use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Filament\Resources\ShippingMethodResource\Schemas\ShippingMethodFormSchema;
use App\Filament\Resources\ShippingMethodResource\Schemas\ShippingMethodTableSchema;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    
    protected static ?string $navigationGroup = '🏪 My Store';
    protected static ?int $navigationSort = 4;
    
    protected static ?string $modelLabel = 'Shipping Option';
    protected static ?string $pluralModelLabel = 'Shipping Options';

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
        return $form->schema(ShippingMethodFormSchema::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ShippingMethodTableSchema::columns())
            ->filters(ShippingMethodTableSchema::filters())
            ->actions(ShippingMethodTableSchema::actions())
            ->bulkActions(ShippingMethodTableSchema::bulkActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
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
