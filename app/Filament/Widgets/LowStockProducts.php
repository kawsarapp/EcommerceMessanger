<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProducts extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $clientId = Client::where('user_id', auth()->id())->first()?->id;

        return $table
            ->query(
                Product::where('client_id', $clientId)
                    ->where('stock_quantity', '<', 10)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')->circular(),
                Tables\Columns\TextColumn::make('name')->label('Low Stock Product'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Remaining')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('sale_price')->money('BDT'),
            ]);
    }
}