<?php

namespace App\Filament\Resources\ShippingMethodResource\Schemas;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;

class ShippingMethodTableSchema
{
    public static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->label('Method Name')
                ->weight('bold'),
                
            TextColumn::make('cost')
                ->label('Delivery Cost')
                ->sortable()
                ->money('BDT', true)
                ->color('primary')
                ->badge(),

            TextColumn::make('estimated_time')
                ->label('Est. Time')
                ->placeholder('None set')
                ->color('gray'),

            ToggleColumn::make('is_active')
                ->label('Active Status')
                ->sortable(),

            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [];
    }

    public static function actions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public static function bulkActions(): array
    {
        return [
            DeleteBulkAction::make(),
        ];
    }
}
