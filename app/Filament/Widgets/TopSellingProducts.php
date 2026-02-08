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

    protected function getTableRecordKey($record): string
    {
        return (string) ($record->product_id ?? $record->id);
    }

    public function table(Table $table): Table
    {
        $clientId = Client::where('user_id', auth()->id())->first()?->id;

        return $table
            ->query(
                OrderItem::query()
                    ->with('product') // 🔥 eager load (important fix)
                    ->select(
                        'product_id',
                        DB::raw('SUM(quantity) as total_qty'),
                        DB::raw('SUM(quantity * unit_price) as total_revenue')
                    )
                    ->whereHas('order', function ($q) use ($clientId) {
                        $q->where('client_id', $clientId)
                          ->where('created_at', '>=', now()->subDays(7));
                    })
                    ->groupBy('product_id')
                    ->orderByDesc('total_qty')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('product.thumbnail')
                    ->label('Image')
                    ->defaultImageUrl(asset('images/no-image.png'))
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
