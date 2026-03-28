<?php

namespace App\Filament\Resources\MenuResource\Schemas;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;

class MenuTableSchema
{
    public static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->label('Menu Name'),
                
            TextColumn::make('location')
                ->label('Theme Location')
                ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                ->badge()
                ->color('primary'),

            TextColumn::make('items_count')
                ->counts('items')
                ->label('Total Links')
                ->badge()
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
        return [
            SelectFilter::make('location')
                ->options([
                    'primary_header' => 'Header Primary Menu',
                    'footer_1' => 'Footer Link Column 1',
                    'footer_2' => 'Footer Link Column 2',
                    'footer_3' => 'Footer Link Column 3',
                    'mobile_nav' => 'Mobile Main Navigation',
                ])
                ->label('Filter by Location'),
        ];
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
