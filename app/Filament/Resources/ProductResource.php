<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\Schemas\ProductFormSchema;
use App\Filament\Resources\ProductResource\Schemas\ProductTableSchema;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Shop Management';

    // 🚀 Schema গুলো আলাদা ক্লাস থেকে কল করা হচ্ছে
    public static function form(Form $form): Form
    {
        return $form->schema(ProductFormSchema::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ProductTableSchema::columns())
            ->filters(ProductTableSchema::filters())
            ->actions(ProductTableSchema::actions())
            ->bulkActions(ProductTableSchema::bulkActions())
            ->defaultSort('id', 'desc'); // 🔥 Latest products first
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['client', 'category']);
        $user = auth()->user();

        if ($user?->isSuperAdmin()) { 
            return $query;
        }

        $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
        return $query->where('client_id', $clientId);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    // ==========================================
    // 🔒 PERMISSION LOGIC (100% Kept Intact)
    // ==========================================

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_products');
        }

        $client = $user->client;
        // hasActivePlan() now respects admin_permissions override
        return $client && $client->hasActivePlan();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('edit_products');
        }

        $client = $user->client;
        if (!$client || !$client->hasActivePlan()) {
            return false; 
        }

        return !$client->hasReachedProductLimit();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('edit_products') && $user->client_id === $record->client_id;
        }

        return $user->client &&
               $user->client->id === $record->client_id &&
               $user->client->hasActivePlan();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('delete_products') && $user->client_id === $record->client_id;
        }

        return $user->client && $user->client->id === $record->client_id;
    }
}