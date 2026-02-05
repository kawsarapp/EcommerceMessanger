<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Shop Management';

    /**
     * ডাটা আইসোলেশন: ক্লায়েন্ট শুধুমাত্র নিজের অর্ডার দেখবে।
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        // সুপার এডমিন (ID 1) সব দেখবে, বাকিরা শুধু নিজেরটা
        if (auth()->id() === 1) {
            return $query;
        }
        return $query->whereHas('client', function (Builder $query) {
            $query->where('user_id', auth()->id());
        });
    }

    /**
     * মেনুতে পেন্ডিং অর্ডারের সংখ্যা দেখানোর জন্য ব্যাজ
     */
    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::where('order_status', 'processing');
        if (auth()->id() !== 1) {
            $query->whereHas('client', fn($q) => $q->where('user_id', auth()->id()));
        }
        return $query->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        // বাম পাশের বড় অংশ (২ কলাম)
                        Group::make()
                            ->schema([
                                // ১. কাস্টমার ইনফো
                                Section::make('Customer Information')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('customer_name')
                                                ->label('Full Name')
                                                ->required(),
                                            Forms\Components\TextInput::make('customer_phone')
                                                ->label('Phone Number')
                                                ->tel()
                                                ->required(),
                                            
                                            // ম্যানুয়াল অর্ডারের জন্য স্মার্ট ক্লায়েন্ট সিলেকশন
                                            Forms\Components\Select::make('client_id')
                                                ->label('Shop/Merchant')
                                                ->relationship('client', 'shop_name', function (Builder $query) {
                                                    if (auth()->id() !== 1) {
                                                        $query->where('user_id', auth()->id());
                                                    }
                                                })
                                                ->default(fn () => Client::where('user_id', auth()->id())->first()?->id)
                                                ->disabled(fn () => auth()->id() !== 1) // ক্লায়েন্ট নিজে এটা বদলাতে পারবে না
                                                ->dehydrated() // ডিসেবল থাকলেও ডাটা সেভ হবে
                                                ->searchable()
                                                ->required(),
                                            
                                            Forms\Components\TextInput::make('customer_email')
                                                ->label('Email Address')
                                                ->email(),
                                        ]),
                                    ]),

                                // ২. ডেলিভারি অ্যাড্রেস
                                Section::make('Shipping Address')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('division')->placeholder('e.g. Dhaka'),
                                            Forms\Components\TextInput::make('district')->placeholder('e.g. Gazipur'),
                                        ]),
                                        Forms\Components\Textarea::make('shipping_address')
                                            ->label('Full Street Address')
                                            ->rows(3)
                                            ->required(),
                                    ]),

                                // ৩. অর্ডার আইটেমস (অটো প্রাইস ক্যালকুলেশন সহ)
                                Section::make('Order Items')
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('orderItems') // নিশ্চিত করুন আপনার মডেলে এই রিলেশন আছে
                                            ->schema([
                                                Forms\Components\Select::make('product_id')
                                                    ->label('Product')
                                                    ->options(function () {
                                                        $query = Product::query();
                                                        if (auth()->id() !== 1) {
                                                            $query->whereHas('client', fn($q) => $q->where('user_id', auth()->id()));
                                                        }
                                                        return $query->pluck('name', 'id');
                                                    })
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(fn ($state, $set) => 
                                                        $set('unit_price', Product::find($state)?->sale_price ?? 0)
                                                    ),
                                                Forms\Components\TextInput::make('quantity')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->reactive(),
                                                Forms\Components\TextInput::make('unit_price')
                                                    ->label('Unit Price')
                                                    ->numeric()
                                                    ->prefix('৳')
                                                    ->required(),
                                            ])
                                            ->columns(3)
                                            ->reorderableWithButtons()
                                            ->itemLabel(fn (array $state): ?string => 
                                                isset($state['product_id']) ? Product::find($state['product_id'])?->name : 'New Item'
                                            ),
                                    ]),
                            ])->columnSpan(2),

                        // ডান পাশের অংশ (১ কলাম)
                        Group::make()
                            ->schema([
                                Section::make('Status & Payment')
                                    ->schema([
                                        Forms\Components\Select::make('order_status')
                                            ->label('Order Status')
                                            ->options([
                                                'processing' => 'Processing',
                                                'shipped' => 'Shipped',
                                                'delivered' => 'Delivered',
                                                'cancelled' => 'Cancelled',
                                            ])
                                            ->required()
                                            ->native(false),

                                        Forms\Components\Select::make('payment_method')
                                            ->options([
                                                'cod' => 'Cash on Delivery',
                                                'bkash' => 'bKash',
                                                'nagad' => 'Nagad',
                                            ])->required(),

                                        Forms\Components\TextInput::make('total_amount')
                                            ->label('Grand Total')
                                            ->numeric()
                                            ->prefix('৳')
                                            ->required(),

                                        Forms\Components\Select::make('payment_status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'paid' => 'Paid',
                                            ])->required(),
                                    ]),

                                Section::make('Notes')
                                    ->schema([
                                        Forms\Components\Textarea::make('admin_note')
                                            ->label('Internal Note')
                                            ->placeholder('ক্যান্সেলেশন বা স্পেশাল ইনস্ট্রাকশন...')
                                            ->rows(3),
                                    ]),
                            ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('customer_image')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Order $record): string => $record->customer_phone ?? ''),

                TextColumn::make('client.shop_name')
                    ->label('Shop')
                    ->toggleable(isToggledHiddenByDefault: auth()->id() !== 1),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                SelectColumn::make('order_status')
                    ->label('Status')
                    ->options([
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->selectablePlaceholder(false),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('order_status')
                    ->label('Filter Status')
                    ->options([
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}