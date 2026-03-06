<?php

namespace App\Filament\Resources\OrderResource\Schemas;

use App\Models\Product;
use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class OrderFormSchema
{
    // 🔥 Auto Calculate Total Function
    public static function updateTotal(callable $get, callable $set)
    {
        $subtotal = 0;
        $items = $get('items') ?? [];
        
        foreach ($items as $item) {
            $price = floatval($item['price'] ?? 0);
            $qty = floatval($item['quantity'] ?? 1);
            $subtotal += ($price * $qty);
        }

        $zone = $get('delivery_zone');
        $charge = 0;
        
        if ($zone) {
            $client = Client::where('user_id', auth()->id())->first();
            if ($zone === 'Inside Dhaka') $charge = floatval($client->delivery_charge_inside ?? 80);
            if ($zone === 'Outside Dhaka') $charge = floatval($client->delivery_charge_outside ?? 150);
        }

        $set('total_amount', $subtotal + $charge);
    }

    public static function schema(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    // বাম পাশের বড় অংশ (২ কলাম)
                    Group::make()
                        ->schema([
                            // ১. কাস্টমার ইনফো
                            Section::make('Customer Information')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('customer_name')
                                            ->label('Full Name')
                                            ->required(),
                                            
                                        TextInput::make('customer_phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->required(),
                                        
                                        Select::make('client_id')
                                            ->label('Shop/Merchant')
                                            ->relationship('client', 'shop_name', function (Builder $query) {
                                                if (auth()->id() !== 1) {
                                                    $query->where('user_id', auth()->id());
                                                }
                                            })
                                            ->default(fn () => Client::where('user_id', auth()->id())->first()?->id)
                                            ->disabled(fn () => auth()->id() !== 1) 
                                            ->dehydrated() 
                                            ->searchable()
                                            ->required(),
                                        
                                        TextInput::make('customer_email')
                                            ->label('Email Address')
                                            ->email(),
                                    ]),
                                ]),

                            // ২. ডেলিভারি অ্যাড্রেস ও জোন সিলেকশন
                            Section::make('Shipping & Location')
                                ->schema([
                                    Grid::make(3)->schema([
                                        // 🔥 NEW: Delivery Zone Selection
                                        Select::make('delivery_zone')
                                            ->label('Delivery Zone')
                                            ->options([
                                                'Inside Dhaka' => 'Inside Dhaka',
                                                'Outside Dhaka' => 'Outside Dhaka',
                                            ])
                                            ->dehydrated(false) // Database e save hobe na, shudhu calculation er jonno
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state === 'Inside Dhaka') $set('division', 'Dhaka');
                                                self::updateTotal($get, $set);
                                            }),

                                        TextInput::make('division')->placeholder('e.g. Dhaka'),
                                        TextInput::make('district')->placeholder('e.g. Gazipur'),
                                    ]),
                                    Textarea::make('shipping_address')
                                        ->label('Full Street Address')
                                        ->rows(2)
                                        ->required(),
                                ]),

                            // ৩. অর্ডার আইটেমস (অটো প্রাইস ক্যালকুলেশন সহ)
                            Section::make('Order Items')
                                ->schema([
                                    Repeater::make('items')
                                        ->relationship('orderItems')
                                        ->live()
                                        ->afterStateUpdated(fn ($get, $set) => self::updateTotal($get, $set))
                                        ->schema([
                                            Select::make('product_id')
                                                ->label('Product')
                                                ->options(function () {
                                                    $query = Product::query();
                                                    if (auth()->id() !== 1) {
                                                        $query->whereHas('client', fn($q) => $q->where('user_id', auth()->id()));
                                                    }
                                                    return $query->pluck('name', 'id');
                                                })
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $set('price', Product::find($state)?->sale_price ?? Product::find($state)?->regular_price ?? 0);
                                                    self::updateTotal($get, $set);
                                                }),
                                                
                                            TextInput::make('quantity')
                                                ->numeric()
                                                ->default(1)
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($get, $set) => self::updateTotal($get, $set)),
                                                
                                            TextInput::make('price')
                                                ->label('Unit Price')
                                                ->numeric()
                                                ->prefix('৳')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn ($get, $set) => self::updateTotal($get, $set)),
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
                                    Select::make('order_status')
                                        ->label('Order Status')
                                        ->options([
                                            'processing' => 'Processing',
                                            'shipped' => 'Shipped',
                                            'delivered' => 'Delivered',
                                            'cancelled' => 'Cancelled',
                                        ])
                                        ->required()
                                        ->native(false),

                                    Select::make('payment_method')
                                        ->options([
                                            'cod' => 'Cash on Delivery',
                                            'bkash' => 'bKash',
                                            'nagad' => 'Nagad',
                                        ])->required(),

                                    TextInput::make('total_amount')
                                        ->label('Grand Total')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->required(),

                                    Select::make('payment_status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'paid' => 'Paid',
                                        ])->required(),
                                ]),

                            Section::make('Notes')
                                ->schema([
                                    Textarea::make('admin_note')
                                        ->label('Internal Note')
                                        ->placeholder('Size/Color/Cancellation Note...')
                                        ->rows(3),
                                ]),
                        ])->columnSpan(1),
                ]),
        ];
    }
}