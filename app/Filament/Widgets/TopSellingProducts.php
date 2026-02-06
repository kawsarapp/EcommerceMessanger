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
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $clientId = Client::where('user_id', auth()->id())->first()?->id;

        return $table
            ->query(
                OrderItem::query()
                    ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * unit_price) as total_revenue'))
                    ->whereHas('order', function($q) use ($clientId) {
                        $q->where('client_id', $clientId)
                          ->where('created_at', '>=', now()->subDays(7)); // গত ৭ দিনের ডাটা
                    })
                    ->groupBy('product_id')
                    ->orderBy('total_qty', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('product.thumbnail')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Sold')
                    ->badge()
                    ->color('success')
                    ->suffix(' Units'),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('BDT')
                    ->sortable(),
            ]);
    }
}