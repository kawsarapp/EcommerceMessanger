<?php

namespace App\Filament\Resources\ShippingMethodResource\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ShippingMethodFormSchema
{
    public static function schema(): array
    {
        return [
            Section::make('Shipping Options Configuration')
                ->description('Create dynamic shipping zones or courier methods that your customers can choose during checkout.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Method Name')
                                ->placeholder('e.g. Inside Dhaka, RedX Courier, Pathao')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),
                            
                            TextInput::make('cost')
                                ->label('Shipping Rate (৳)')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->placeholder('e.g. 60')
                                ->columnSpan(1),

                            TextInput::make('estimated_time')
                                ->label('Estimated Delivery Time')
                                ->placeholder('e.g. 2-3 Days')
                                ->maxLength(255)
                                ->helperText('Optional: Displayed alongside the price to set customer expectations.')
                                ->columnSpan(1),
                                
                            Toggle::make('is_active')
                                ->label('Currently Active')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(1),
                        ]),
                ]),
        ];
    }
}
