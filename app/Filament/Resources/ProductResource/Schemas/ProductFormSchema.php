<?php

namespace App\Filament\Resources\ProductResource\Schemas;

use App\Models\Product;
use App\Models\Client;
use App\Services\ImageOptimizer;
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
use Illuminate\Support\Facades\Storage;

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
                            ->disk('public')
                            ->directory('products/thumbnails')
                            ->visibility('public')
                            ->required()
                            ->maxSize(5120) // 5MB max upload size
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->helperText('বট এই ছবিটি কাস্টমারকে চ্যাটে পাঠাবে। Auto compressed to WebP.')
                            ->saveUploadedFileUsing(function ($file, $get) {
                                try {
                                    $optimizer = new ImageOptimizer();
                                    return $optimizer->optimize($file, 'products/thumbnails', 'product_thumbnail');
                                } catch (\Exception $e) {
                                    // Fallback: সাধারণভাবে save করো
                                    $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                                    $file->storeAs('products/thumbnails', $filename, 'public');
                                    return 'products/thumbnails/' . $filename;
                                }
                            }),


                        FileUpload::make('gallery')
                            ->label('Product Gallery (Max 4 Images)')
                            ->image()
                            ->multiple()
                            ->maxFiles(4)
                            ->reorderable()
                            ->disk('public')
                            ->directory('products/gallery')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                            ->saveUploadedFileUsing(function ($file) {
                                try {
                                    $optimizer = new ImageOptimizer();
                                    return $optimizer->optimize($file, 'products/gallery', 'product_gallery');
                                } catch (\Exception $e) {
                                    $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                                    $file->storeAs('products/gallery', $filename, 'public');
                                    return 'products/gallery/' . $filename;
                                }
                            }),

                                    
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
                                        ->visible(fn () => auth()->user()?->isSuperAdmin()),

                                    Hidden::make('client_id')
                                        ->default(fn () => Client::where('user_id', auth()->id())->first()?->id)
                                        ->visible(fn () => !auth()->user()?->isSuperAdmin()),
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
                                    ->unique(Product::class, 'slug', ignoreRecord: true, modifyRuleUsing: function ($rule, callable $get) {
                                        return $rule->where('client_id', $get('../../client_id'));
                                    })
                                    ->readOnly(),
                                    
                                TextInput::make('brand')
                                    ->placeholder('e.g. Nike, Apple')
                                    ->label('Brand Name'),
                                    
                                TextInput::make('sku')
                                    ->label('SKU / Product Code')
                                    ->placeholder('e.g. TSHIRT-001')
                                    ->required()
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, callable $get) {
                                        return $rule->where('client_id', $get('../../client_id'));
                                    }) 
                                    ->live(onBlur: true),

                                TextInput::make('warranty')
                                    ->label('Warranty')
                                    ->placeholder('e.g. 6 Months Warranty'),

                                TextInput::make('return_policy')
                                    ->label('Return Policy')
                                    ->placeholder('e.g. 7 Days Return Policy'),

                                    

                                // 🔥 NEW FIELD: AI-এর জন্য Search Tags
                                TagsInput::make('tags')
                                    ->label('Search Tags / Keywords (For AI)')
                                    ->placeholder('Type a keyword and press Enter')
                                    ->helperText('AI কে সহজে প্রোডাক্ট খুঁজে পেতে সাহায্য করবে (যেমন: boi, book, islamic, shirt, jama)')
                                    ->columnSpanFull(),
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