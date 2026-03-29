<?php

namespace App\Filament\Resources\MenuResource\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Category;
use App\Models\Client;
use App\Models\Page;
use Illuminate\Support\Str;

class MenuFormSchema
{
    public static function schema(): array
    {
        return [
            Section::make('Menu Settings')
                ->description('Configure the core properties of your navigation menu.')
                ->schema([
                    // SuperAdmin only: assign this menu to a specific shop
                    Select::make('client_id')
                        ->label('Assign to Shop')
                        ->options(Client::orderBy('shop_name')->pluck('shop_name', 'id'))
                        ->searchable()
                        ->required()
                        ->visible(fn () => auth()->user()?->isSuperAdmin())
                        ->placeholder('Select a shop...'),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Menu Name')
                                ->required()
                                ->placeholder('e.g. Main Navigation')
                                ->maxLength(255),
                            
                            Select::make('location')
                                ->label('Theme Location')
                                ->options([
                                    'primary_header' => 'Header Primary Menu',
                                    'footer_1' => 'Footer Link Column 1',
                                    'footer_2' => 'Footer Link Column 2',
                                    'footer_3' => 'Footer Link Column 3',
                                    'mobile_nav' => 'Mobile Main Navigation',
                                ])
                                ->helperText('Where this menu should be displayed on supported themes.')
                                ->nullable(),
                                
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false),
                        ]),
                ]),

            Section::make('Menu Items')
                ->description('Drag to reorder your links. Add categories, pages, or custom URLs.')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->orderColumn('sort_order')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('label')
                                        ->required()
                                        ->label('Link Label')
                                        ->columnSpan(1),
                                        
                                    Select::make('type')
                                        ->required()
                                        ->label('Link Type')
                                        ->options([
                                            'custom_link' => 'Custom URL',
                                            'category' => 'Product Category',
                                            'page' => 'Custom Page',
                                        ])
                                        ->default('custom_link')
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('reference_id', null))
                                        ->columnSpan(1),
                                        
                                    // Custom URL
                                    TextInput::make('url')
                                        ->label('URL')
                                        ->placeholder('https://...')
                                        ->columnSpan(1)
                                        ->visible(fn (Get $get) => $get('type') === 'custom_link'),

                                    // Category Select
                                    Select::make('reference_id')
                                        ->label('Select Item')
                                        ->columnSpan(1)
                                        ->searchable()
                                        ->visible(fn (Get $get) => in_array($get('type'), ['category', 'page']))
                                        ->options(function (Get $get) {
                                            $user = auth()->user();
                                            $clientId = $user->isSuperAdmin() ? request('client_id') : ($user->client_id ?? ($user->client->id ?? null));

                                            if ($get('type') === 'category') {
                                                return Category::where('is_global', true)
                                                    ->orWhere('client_id', $clientId)
                                                    ->pluck('name', 'id');
                                            }
                                            if ($get('type') === 'page') {
                                                return Page::where('client_id', $clientId)->pluck('title', 'id');
                                            }
                                            return [];
                                        })
                                        ->required(fn (Get $get) => in_array($get('type'), ['category', 'page'])),
                                        
                                    Select::make('target')
                                        ->label('Open in')
                                        ->options([
                                            '_self' => 'Same Window',
                                            '_blank' => 'New Tab',
                                        ])
                                        ->default('_self')
                                        ->columnSpan([
                                            'default' => 3,
                                            'md' => 1,
                                            'lg' => 1
                                        ]),
                                ]),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('Add Menu Link')
                ]),
        ];
    }
}
