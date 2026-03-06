<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
        // 🔥 Print Button on Edit Page
            Actions\Action::make('print')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn ($record) => route('orders.print', $record))
                ->openUrlInNewTab(),
                
            Actions\DeleteAction::make(),
        ];
    }
}
