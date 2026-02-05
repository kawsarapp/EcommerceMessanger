<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopSellingProducts extends BaseWidget
{
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $clientId = Client::where('user_id', auth()->id())->first()?->id;

        return $table
            ->query(
                OrderItem::query()
                    ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
                    ->whereHas('order', function($q) use ($clientId) {
                        $q->where('client_id', $clientId)
                          ->where('created_at', '>=', now()->subHours(24));
                    })
                    ->groupBy('product_id')
                    ->orderBy('total_qty', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('Top Seller (24h)'),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Sold Qty')
                    ->badge()
                    ->color('success'),
            ]);
    }
}