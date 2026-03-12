<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;

class StorefrontTab
{
    public static function schema(): array
    {
        return [
            Section::make('Visual Identity')
                ->description('Upload logo and banner to make your shop look professional.')
                ->schema([
                    FileUpload::make('logo')
                        ->label('Shop Logo (Square)')
                        ->image()
                        ->avatar()
                        ->directory('shops/logos')
                        ->maxSize(2048),
                    
                    FileUpload::make('banner')
                        ->label('Cover Banner (Wide)')
                        ->image()
                        ->directory('shops/banners')
                        ->maxSize(5120)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Theme & Announcements')->schema([
                // 🔥 NEW: Expanded Theme Options
                Select::make('theme_name')
                    ->label('Storefront Theme')
                    ->options([
                        'default'     => 'Default Classic (Standard E-commerce)',
                        'modern'      => 'Modern Minimal (Clean & Fast)',
                        'fashion'     => 'Fashion Pro (Apparel & Clothing)',
                        'electronics' => 'Tech & Gadgets (Electronics)',
                        'grocery'     => 'Supermarket (Grocery & Daily Needs)',
                        'luxury'      => 'Premium Luxury (Jewelry & Watches)',
                        'kids'        => 'Kids Corner (Toys & Baby Products)',
                    ])
                    ->default('default')
                    ->searchable() // থিম বেশি হলে সার্চ করার সুবিধা
                    ->required()
                    ->helperText('Choose a layout for your store.'),

                ColorPicker::make('primary_color')
                    ->label('Brand Color')
                    ->default('#4f46e5')
                    ->helperText('This color will be used for buttons and links.'),
                
                TextInput::make('announcement_text')
                    ->label('Announcement Bar')
                    ->placeholder('🎉 Eid Sale is Live! Get 10% Off.')
                    ->helperText('Shows at the top of your shop header.')
                    ->columnSpanFull(),
            ])->columns(2),
        ];
    }
}