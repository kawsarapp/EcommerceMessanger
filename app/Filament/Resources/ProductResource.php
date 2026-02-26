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
            ->bulkActions(ProductTableSchema::bulkActions());
    }

    public static function getEloquentQuery(): Builder
    {
        // সুপার এডমিন (ID 1) সব দেখবে, বাকিরা শুধু নিজেরটা দেখবে
        if (auth()->id() === 1) { 
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->whereHas('client', function ($query) {
            $query->where('user_id', auth()->id());
        });
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
        if ($user->id === 1) return true;

        return $user->client && $user->client->hasActivePlan();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        if ($user->id === 1) return true;

        $client = $user->client;

        if (!$client || !$client->hasActivePlan()) {
            return false; 
        }

        return $client->products()->count() < $client->plan->product_limit;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if ($user->id === 1) return true;

        return $user->client && 
               $user->client->id === $record->client_id && 
               $user->client->hasActivePlan();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if ($user->id === 1) return true;

        return $user->client && $user->client->id === $record->client_id;
    }
}