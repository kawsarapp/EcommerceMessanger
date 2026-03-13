<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // 🔥 NEW FEATURE: Order Status Quick Filter Tabs
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders'),
            'pending' => Tab::make('Pending')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('order_status', 'pending')),
            'processing' => Tab::make('Processing')
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('order_status', 'processing')),
            'shipped' => Tab::make('Shipped')
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('order_status', 'shipped')),
            'delivered' => Tab::make('Delivered')
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('order_status', 'delivered')),
        ];
    }
}