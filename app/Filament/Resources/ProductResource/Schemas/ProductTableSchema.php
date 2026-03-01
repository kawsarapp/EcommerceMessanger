<?php

namespace App\Filament\Resources\ProductResource\Schemas;

use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;

class ProductTableSchema
{
    public static function columns(): array
    {
        return [
            ImageColumn::make('thumbnail')
                ->label('Image')
                ->circular(),

            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->limit(30)
                ->wrap(),

            // এডমিন হলে দোকানের নাম দেখাবে
            TextColumn::make('client.shop_name')
                ->label('Shop')
                ->toggleable()
                ->sortable()
                ->visible(fn () => auth()->id() === 1)
                ->badge(),
                
            TextColumn::make('category.name')
                ->label('Category')
                ->toggleable()
                ->sortable(),

            TextColumn::make('sale_price')
                ->label('Price')
                ->money('BDT')
                ->sortable()
                ->description(fn ($record) => $record->regular_price ? "Reg: {$record->regular_price}৳" : ''),

            TextColumn::make('stock_quantity')
                ->label('Stock')
                ->sortable()
                ->alignCenter()
                ->color(fn ($state) => $state <= 5 ? 'danger' : 'success'),

            TextColumn::make('stock_status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'in_stock' => 'success',
                    'out_of_stock' => 'danger',
                    'pre_order' => 'warning',
                    default => 'gray',
                }),

            IconColumn::make('is_featured')
                ->boolean()
                ->label('Featured'),

            TextColumn::make('created_at')
                ->dateTime('d M, Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('client')
                ->relationship('client', 'shop_name')
                ->visible(fn () => auth()->id() === 1),
                
            Tables\Filters\SelectFilter::make('category')
                ->relationship('category', 'name'),
            
            Tables\Filters\SelectFilter::make('stock_status')
                ->options([
                    'in_stock' => 'In Stock',
                    'out_of_stock' => 'Out of Stock',
                    'pre_order' => 'Pre Order',
                ]),
                
            Tables\Filters\Filter::make('is_featured')
                ->label('Featured Products')
                ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }
}