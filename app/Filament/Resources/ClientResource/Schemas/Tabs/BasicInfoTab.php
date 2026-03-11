<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Support\Str;

class BasicInfoTab
{
    public static function schema(): array
    {
        return [
            Hidden::make('user_id')->default(auth()->id()),
            Section::make('Identity')->schema([
                TextInput::make('shop_name')
                    ->label('Shop Name')
                    ->placeholder('Eg. Fashion BD')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn ($state, callable $set, $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                
                TextInput::make('slug')
                    ->label('Shop URL')
                    ->prefix(config('app.url') . '/shop/')
                    ->required()
                    ->live(onBlur: true)
                    ->unique(Client::class, 'slug', ignoreRecord: true)
                    ->helperText('Unique link for your shop. You can customize it!'),    
            ])->columns(2),

            Section::make('Contact Details')->schema([
                TextInput::make('phone')
                    ->label('Support Phone')
                    ->tel()
                    ->prefixIcon('heroicon-m-phone')
                    ->placeholder('017XXXXXXXX'),
                
                Textarea::make('address')
                    ->label('Shop Address')
                    ->rows(2)
                    ->placeholder('Full address for invoice...'),
            ])->columns(2),

            ToggleButtons::make('status')
                ->label('Shop Status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->colors(['active' => 'success', 'inactive' => 'danger'])
                ->icons(['active' => 'heroicon-o-check-circle', 'inactive' => 'heroicon-o-x-circle'])
                ->default('active')
                ->inline()
                ->visible(fn () => auth()->id() === 1),
        ];
    }
}