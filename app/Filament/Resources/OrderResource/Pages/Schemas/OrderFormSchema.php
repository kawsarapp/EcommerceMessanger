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
                                        
                                        // ম্যানুয়াল অর্ডারের জন্য স্মার্ট ক্লায়েন্ট সিলেকশন
                                        Select::make('client_id')
                                            ->label('Shop/Merchant')
                                            ->relationship('client', 'shop_name', function (Builder $query) {
                                                if (auth()->id() !== 1) {
                                                    $query->where('user_id', auth()->id());
                                                }
                                            })
                                            ->default(fn () => Client::where('user_id', auth()->id())->first()?->id)
                                            ->disabled(fn () => auth()->id() !== 1) // ক্লায়েন্ট নিজে এটা বদলাতে পারবে না
                                            ->dehydrated() // ডিসেবল থাকলেও ডাটা সেভ হবে
                                            ->searchable()
                                            ->required(),
                                        
                                        TextInput::make('customer_email')
                                            ->label('Email Address')
                                            ->email(),
                                    ]),
                                ]),

                            // ২. ডেলিভারি অ্যাড্রেস
                            Section::make('Shipping Address')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('division')->placeholder('e.g. Dhaka'),
                                        TextInput::make('district')->placeholder('e.g. Gazipur'),
                                    ]),
                                    Textarea::make('shipping_address')
                                        ->label('Full Street Address')
                                        ->rows(3)
                                        ->required(),
                                ]),

                            // ৩. অর্ডার আইটেমস (অটো প্রাইস ক্যালকুলেশন সহ)
                            Section::make('Order Items')
                                ->schema([
                                    Repeater::make('items')
                                        ->relationship('orderItems')
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
                                                ->reactive()
                                                ->afterStateUpdated(fn ($state, $set) => 
                                                    // 🔥 ডাটাবেসের কলাম অনুযায়ী unit_price কে price করা হলো
                                                    $set('price', Product::find($state)?->sale_price ?? 0)
                                                ),
                                                
                                            TextInput::make('quantity')
                                                ->numeric()
                                                ->default(1)
                                                ->required()
                                                ->reactive(),
                                                
                                            // 🔥 ডাটাবেসের কলাম অনুযায়ী ফিল্ডের নাম price করা হলো
                                            TextInput::make('price')
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
                                        ->placeholder('ক্যান্সেলেশন বা স্পেশাল ইনস্ট্রাকশন...')
                                        ->rows(3),
                                ]),
                        ])->columnSpan(1),
                ]),
        ];
    }
}