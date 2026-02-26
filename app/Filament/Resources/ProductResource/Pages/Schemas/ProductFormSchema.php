<?php

namespace App\Filament\Resources\ProductResource\Schemas;

use App\Models\Product;
use App\Models\Client;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Group;
use Illuminate\Support\Str;

class ProductFormSchema
{
    public static function schema(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    // Left Column (Media & Description) - Span 2
                    Group::make()
                        ->schema([
                            Section::make('Product Media')
                                ->schema([
                                    FileUpload::make('thumbnail')
                                        ->label('Main Image')
                                        ->image()
                                        ->imageEditor()
                                        ->directory('products/thumbnails')
                                        ->visibility('public')
                                        ->required()
                                        ->helperText('বট এই ছবিটি কাস্টমারকে চ্যাটে পাঠাবে।'),
                                
                                    FileUpload::make('gallery')
                                        ->label('Product Gallery (Max 4 Images)')
                                        ->image()
                                        ->multiple()
                                        ->maxFiles(4)
                                        ->reorderable()
                                        ->directory('products/gallery')
                                        ->visibility('public'),
                                ]),
                            
                            Section::make('Video & Description')
                                ->schema([
                                    TextInput::make('video_url')
                                        ->label('Product Video URL')
                                        ->placeholder('YouTube or Vimeo link')
                                        ->url(),
                                        
                                    RichEditor::make('description')
                                        ->label('Full Description')
                                        ->placeholder('পণ্যের বিস্তারিত বিবরণ এখানে লিখুন...')
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(2),

                    // Right Column (Settings & Pricing) - Span 1
                    Group::make()
                        ->schema([
                            Section::make('Inventory & Category')
                                ->schema([
                                    // --- [ADMIN ONLY FEATURE] ---
                                    Select::make('client_id')
                                        ->label('Assign to Shop (Admin Only)')
                                        ->relationship('client', 'shop_name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->visible(fn () => auth()->id() === 1),

                                    // সাধারণ ক্লায়েন্টের জন্য এটি অটোমেটিক এবং লুকায়িত থাকবে
                                    Hidden::make('client_id')
                                        ->default(fn () => Client::where('user_id', auth()->id())->first()?->id)
                                        ->visible(fn () => auth()->id() !== 1),
                                    // -----------------------------

                                    Select::make('category_id')
                                        ->label('Category')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            TextInput::make('name')->required(),
                                            TextInput::make('slug')->required(),
                                        ])
                                        ->required(),

                                    TextInput::make('sale_price')
                                        ->label('Sale Price')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required(),
                                        
                                    TextInput::make('regular_price')
                                        ->label('Regular Price')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->placeholder('Optional'),

                                    TextInput::make('stock_quantity')
                                        ->label('Stock Count')
                                        ->numeric()
                                        ->default(10)
                                        ->required(),
                                        
                                    Toggle::make('is_featured')
                                        ->label('Featured Product')
                                        ->onColor('success'),
                                ]),
                            
                            Section::make('Stock Status')
                                ->schema([
                                    Select::make('stock_status')
                                        ->options([
                                            'in_stock' => '✅ In Stock',
                                            'out_of_stock' => '❌ Out of Stock',
                                            'pre_order' => '⏳ Pre Order',
                                        ])
                                        ->default('in_stock')
                                        ->required(),
                                ]),
                        ])->columnSpan(1),
                        
                    // Bottom Full Width (Basic Info & Variations)
                    Section::make('Basic Information & Variations')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Product Name')
                                    ->placeholder('e.g. Premium Cotton T-Shirt')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                TextInput::make('slug')
                                    ->required()
                                    ->unique(Product::class, 'slug', ignoreRecord: true)
                                    ->readOnly(),
                                    
                                TextInput::make('brand')
                                    ->placeholder('e.g. Nike, Apple')
                                    ->label('Brand Name'),
                                    
                                TextInput::make('sku')
                                    ->label('SKU / Product Code')
                                    ->placeholder('e.g. TSHIRT-001')
                                    ->required(),
                            ]),

                            Section::make('Variations (AI & Display)')
                                ->description('কালার এবং সাইজ অ্যাড করলে কাস্টমার ডিটেইলস মডালে দেখতে পাবে।')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TagsInput::make('colors')
                                            ->label('Colors')
                                            ->placeholder('Add color & Enter')
                                            ->helperText('Ex: Red, Blue, Black'),

                                        TagsInput::make('sizes')
                                            ->label('Sizes')
                                            ->placeholder('Add size & Enter')
                                            ->helperText('Ex: M, L, XL, 42, 44'),

                                        TextInput::make('material')
                                            ->label('Material')
                                            ->placeholder('e.g. 100% Cotton'),
                                    ]),
                                ]),
                        ])->columnSpanFull(),
                ]),
        ];
    }
}