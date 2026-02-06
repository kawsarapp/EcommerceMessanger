<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProducts extends BaseWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        $clientId = Client::where('user_id', auth()->id())->first()?->id;

        return $table
            ->query(
                Product::where('client_id', $clientId)
                    ->where('stock_quantity', '<', 10)
                    ->orderBy('stock_quantity', 'asc')
            )
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')->circular(),
                Tables\Columns\TextColumn::make('name')->limit(30),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('In Stock')
                    ->badge()
                    ->color(fn ($state) => $state < 5 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('sku')->fontFamily('mono'),
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Restock')
                    ->url(fn (Product $record): string => "/admin/products/{$record->id}/edit")
                    ->icon('heroicon-m-plus-circle')
                    ->color('primary'),
            ]);
    }
}