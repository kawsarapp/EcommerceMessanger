<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class LogisticsTab
{
    public static function schema(): array
    {
        return [
            Section::make('Delivery Fees')->schema([
                TextInput::make('delivery_charge_inside')
                    ->label('Inside Dhaka')
                    ->numeric()
                    ->prefix('৳')
                    ->default(80)
                    ->required(),
                
                TextInput::make('delivery_charge_outside')
                    ->label('Outside Dhaka')
                    ->numeric()
                    ->prefix('৳')
                    ->default(150)
                    ->required(),
            ])->columns(2),
        ];
    }
}