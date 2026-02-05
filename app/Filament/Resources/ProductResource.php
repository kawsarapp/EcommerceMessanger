<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Shop Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        // Left Column (Media & Description) - Span 2
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Product Media')
                                    ->schema([
                                        Forms\Components\FileUpload::make('thumbnail')
                                            ->label('Main Image')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('products/thumbnails')
                                            ->visibility('public')
                                            ->required()
                                            ->helperText('বট এই ছবিটি কাস্টমারকে চ্যাটে পাঠাবে।'),
                                    
                                        Forms\Components\FileUpload::make('gallery')
                                            ->label('Product Gallery (Max 4 Images)')
                                            ->image()
                                            ->multiple()
                                            ->maxFiles(4)
                                            ->reorderable()
                                            ->directory('products/gallery')
                                            ->visibility('public'),
                                    ]),
                                
                                Forms\Components\Section::make('Video & Description')
                                    ->schema([
                                        Forms\Components\TextInput::make('video_url')
                                            ->label('Product Video URL')
                                            ->placeholder('YouTube or Vimeo link')
                                            ->url(),
                                            
                                        Forms\Components\RichEditor::make('description')
                                            ->label('Full Description')
                                            ->placeholder('পণ্যের বিস্তারিত বিবরণ এখানে লিখুন...')
                                            ->columnSpanFull(),
                                    ]),
                            ])->columnSpan(2),

                        // Right Column (Settings & Pricing) - Span 1
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Inventory & Category')
                                    ->schema([
                                        // --- [ADMIN ONLY FEATURE] ---
                                        // যদি এডমিন হয়, তবে সে ক্লায়েন্ট সিলেক্ট করতে পারবে
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

                                        Forms\Components\Select::make('category_id')
                                            ->label('Category')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                                TextInput::make('slug')->required(),
                                            ]) // ছোট বোনাস: এখান থেকেই ক্যাটাগরি বানানোর অপশন
                                            ->required(),

                                        Forms\Components\TextInput::make('sale_price')
                                            ->label('Sale Price')
                                            ->numeric()
                                            ->prefix('৳')
                                            ->required(),
                                            
                                        Forms\Components\TextInput::make('regular_price')
                                            ->label('Regular Price')
                                            ->numeric()
                                            ->prefix('৳')
                                            ->placeholder('Optional'),

                                        Forms\Components\TextInput::make('stock_quantity')
                                            ->label('Stock Count')
                                            ->numeric()
                                            ->default(10)
                                            ->required(),
                                            
                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured Product')
                                            ->onColor('success'),
                                    ]),
                                
                                Forms\Components\Section::make('Stock Status')
                                    ->schema([
                                        Forms\Components\Select::make('stock_status')
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
                        Forms\Components\Section::make('Basic Information & Variations')
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->label('Product Name')
                                        ->placeholder('e.g. Premium Cotton T-Shirt')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                    Forms\Components\TextInput::make('slug')
                                        ->required()
                                        ->unique(Product::class, 'slug', ignoreRecord: true)
                                        ->readOnly(), // স্লাগ অটো জেনারেট হবে, তাই রিড-অনলি রাখা ভালো
                                        
                                    Forms\Components\TextInput::make('brand')
                                        ->placeholder('e.g. Nike, Apple')
                                        ->label('Brand Name'),
                                        
                                    Forms\Components\TextInput::make('sku')
                                        ->label('SKU / Product Code')
                                        ->placeholder('e.g. TSHIRT-001')
                                        ->required(),
                                ]),

                                Forms\Components\Section::make('Variations (AI & Display)')
                                    ->description('কালার এবং সাইজ অ্যাড করলে কাস্টমার ডিটেইলস মডালে দেখতে পাবে।')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Forms\Components\TagsInput::make('colors')
                                                ->label('Colors')
                                                ->placeholder('Add color & Enter')
                                                ->helperText('Ex: Red, Blue, Black'),

                                            Forms\Components\TagsInput::make('sizes')
                                                ->label('Sizes')
                                                ->placeholder('Add size & Enter')
                                                ->helperText('Ex: M, L, XL, 42, 44'),

                                            Forms\Components\TextInput::make('material')
                                                ->label('Material')
                                                ->placeholder('e.g. 100% Cotton'),
                                        ]),
                                    ]),
                            ])->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Image')
                    ->circular(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap(),

                // এডমিন হলে দোকানের নাম দেখাবে
                TextColumn::make('client.shop_name')
                    ->label('Shop')
                    ->toggleable()
                    ->sortable()
                    ->visible(fn () => auth()->id() === 1) // শুধুমাত্র এডমিন দেখবে
                    ->badge(),
                    
                TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('sale_price')
                    ->label('Price')
                    ->money('BDT')
                    ->sortable()
                    ->description(fn ($record) => $record->regular_price ? "Reg: {$record->regular_price}৳" : ''),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : 'success'),

                TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'out_of_stock' => 'danger',
                        'pre_order' => 'warning',
                        default => 'gray',
                    }),

                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),

                TextColumn::make('created_at')
                    ->dateTime('d M, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'shop_name')
                    ->visible(fn () => auth()->id() === 1), // এডমিন ফিল্টার
                    
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'out_of_stock' => 'Out of Stock',
                        'pre_order' => 'Pre Order',
                    ]),
                    
                Tables\Filters\Filter::make('is_featured')
                    ->label('Featured Products')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // সুপার এডমিন (ID 1) সব দেখবে, বাকিরা শুধু নিজেরটা দেখবে
        if (auth()->id() === 1) { 
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->whereHas('client', function ($query) {
            $query->where('user_id', auth()->id());
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    // --- PERMISSION LOGIC (Fix & Upgrade) ---

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if ($user->id === 1) return true; // এডমিন সব দেখবে

        // ক্লায়েন্ট যদি একটিভ থাকে এবং প্ল্যান থাকে তবেই দেখবে
        return $user->client && $user->client->hasActivePlan();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // ১. এডমিন হলে সব সময় পারমিশন থাকবে
        if ($user->id === 1) return true;

        $client = $user->client;

        // ২. ক্লায়েন্ট বা প্ল্যান না থাকলে ব্লক
        if (!$client || !$client->hasActivePlan()) {
            return false; 
        }

        // ৩. প্রোডাক্ট লিমিট চেক (Product count < Limit)
        // plan->product_limit যদি 0 হয় তার মানে আনলিমিটেড (অপশনাল লজিক), অথবা লিমিট ক্রস করলে false
        return $client->products()->count() < $client->plan->product_limit;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if ($user->id === 1) return true;

        // নিজের প্রোডাক্ট কিনা এবং প্ল্যান একটিভ কিনা চেক
        return $user->client && 
               $user->client->id === $record->client_id && 
               $user->client->hasActivePlan();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if ($user->id === 1) return true;

        return $user->client && $user->client->id === $record->client_id;
    }
}