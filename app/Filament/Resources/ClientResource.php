<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\Schemas\ClientFormSchema;
use App\Filament\Resources\ClientResource\Schemas\ClientTableSchema;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return auth()->user()?->isSuperAdmin() ? (string) static::getModel()::count() : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['shop_name', 'slug', 'fb_page_id', 'custom_domain', 'phone'];
    }

    // 🚀 Schema গুলো আলাদা ক্লাস থেকে কল করা হচ্ছে
    public static function form(Form $form): Form
    {
        return $form->schema(ClientFormSchema::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ClientTableSchema::columns())
            ->defaultSort('created_at', 'desc')
            ->filters(ClientTableSchema::filters())
            ->actions(ClientTableSchema::actions())
            ->bulkActions(ClientTableSchema::bulkActions());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->user()?->isSuperAdmin()) return $query;
        return $query->where('user_id', auth()->id());
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    // --- Permissions ---
    public static function canCreate(): bool 
    { 
        return auth()->user()?->isSuperAdmin() ?? false; 
    } 
    
    public static function canDelete(Model $record): bool 
    { 
        return auth()->user()?->isSuperAdmin() ?? false; 
    }
    
    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() || $record->user_id === auth()->id();
    }
}