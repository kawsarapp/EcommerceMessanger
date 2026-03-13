<?php

namespace App\Filament\Resources\OrderResource\Schemas;

use App\Models\Product;
use App\Models\Client;
use App\Models\Order;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        $charge = floatval($get('shipping_charge') ?? 0);
        
        if ($zone) {
            $client = Client::where('user_id', auth()->id())->first();
            if ($zone === 'Inside Dhaka') $charge = floatval($client->delivery_charge_inside ?? 80);
            if ($zone === 'Outside Dhaka') $charge = floatval($client->delivery_charge_outside ?? 150);
            $set('shipping_charge', $charge);
            $set('delivery_zone', null);
        }

        $discount = floatval($get('discount_amount') ?? 0);
        $total = ($subtotal + $charge) - $discount;

        $set('subtotal', $subtotal);
        $set('total_amount', $total > 0 ? $total : 0);
    }

    public static function schema(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    // Left Column (Customer & Items)
                    Group::make()
                        ->schema([
                            // ১. Customer Info
                            Section::make('Customer Information')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('customer_name')
                                            ->label('Full Name')
                                            ->required(),
                                            
                                        // 🔥 FIXED: Added Live Debounce for real-time tracking
                                        TextInput::make('customer_phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->required()
                                            ->live(debounce: 500),
                                        
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

                                    // 🔥 NEW: Real-time Customer History Tracking
                                    Placeholder::make('customer_history')
                                        ->label('Customer Trust Score (Auto Check)')
                                        ->content(function (callable $get) {
                                            $phone = $get('customer_phone');
                                            $clientId = $get('client_id');
                                            
                                            // 11 digit er niche hole check korbe na
                                            if (!$phone || strlen($phone) < 11) {
                                                return new HtmlString('<span class="text-gray-400 text-sm italic">Type valid 11-digit phone number to check history...</span>');
                                            }

                                            // Database theke order history search
                                            $query = Order::where('customer_phone', 'like', "%{$phone}%");
                                            if ($clientId) {
                                                $query->where('client_id', $clientId);
                                            }

                                            $total = (clone $query)->count();
                                            
                                            // Kono order na thakle (New Customer)
                                            if ($total === 0) {
                                                return new HtmlString('<span class="text-blue-600 font-bold px-3 py-1 bg-blue-50 rounded-lg border border-blue-200 shadow-sm"><i class="heroicon-o-sparkles"></i> 🆕 New Customer (First Time Order)</span>');
                                            }

                                            // Status count kora
                                            $delivered = (clone $query)->where('order_status', 'delivered')->count();
                                            $cancelled = (clone $query)->whereIn('order_status', ['cancelled', 'returned'])->count();
                                            
                                            // Courier History count kora
                                            $couriers = (clone $query)->whereNotNull('courier_name')
                                                ->select('courier_name', DB::raw('count(*) as count'))
                                                ->groupBy('courier_name')
                                                ->pluck('count', 'courier_name')
                                                ->toArray();
                                                
                                            $courierText = '';
                                            if (!empty($couriers)) {
                                                $cList = [];
                                                foreach ($couriers as $name => $c) {
                                                    $cList[] = htmlspecialchars(ucfirst($name), ENT_QUOTES, 'UTF-8') . " ({$c})";
                                                }
                                                $courierText = "<div class='text-sm text-gray-500 mt-2 border-t border-gray-200 pt-2'>🚚 <b>Couriers Used:</b> " . implode(', ', $cList) . "</div>";
                                            }

                                            // Risk Warning (Jodi return rate 50% er beshi hoy)
                                            $warningClass = ($total > 1 && $cancelled > $delivered) ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200';

                                            return new HtmlString(
                                                "<div class='p-4 rounded-xl border shadow-sm {$warningClass}'>" .
                                                    "<div class='flex flex-wrap gap-4'>" .
                                                        "<span class='text-gray-800 font-bold'>📦 Total Orders: {$total}</span>" .
                                                        "<span class='text-green-600 font-bold'>✅ Delivered: {$delivered}</span>" .
                                                        "<span class='text-red-600 font-bold'>❌ Cancelled/Return: {$cancelled}</span>" .
                                                    "</div>" .
                                                    $courierText .
                                                "</div>"
                                            );
                                        })
                                        ->columnSpanFull(),
                                ]),

                            // ২. Shipping Address
                            Section::make('Shipping & Location')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Select::make('delivery_zone')
                                            ->label('Auto Set Shipping')
                                            ->options([
                                                'Inside Dhaka' => 'Inside Dhaka',
                                                'Outside Dhaka' => 'Outside Dhaka',
                                            ])
                                            ->dehydrated(false) 
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

                            // ৩. Order Items
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

                    // Right Column (Pricing & Status)
                    Group::make()
                        ->schema([
                            Section::make('Pricing & Discounts')
                                ->schema([
                                    TextInput::make('subtotal')
                                        ->label('Subtotal')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->default(0),

                                    TextInput::make('shipping_charge')
                                        ->label('Delivery Charge')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($get, $set) => self::updateTotal($get, $set)),

                                    TextInput::make('discount_amount')
                                        ->label('Discount Amount')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($get, $set) => self::updateTotal($get, $set)),

                                    TextInput::make('coupon_code')
                                        ->label('Coupon Code Used')
                                        ->placeholder('e.g. EID2026'),

                                    TextInput::make('total_amount')
                                        ->label('Grand Total')
                                        ->numeric()
                                        ->prefix('৳')
                                        ->readOnly()
                                        ->extraInputAttributes(['class' => 'font-bold text-primary']),
                                ]),

                            Section::make('Status & Payment')
                                ->schema([
                                    Select::make('order_status')
                                        ->label('Order Status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'processing' => 'Processing',
                                            'shipped' => 'Shipped',
                                            'delivered' => 'Delivered',
                                            'cancelled' => 'Cancelled',
                                            'returned' => 'Returned',
                                        ])
                                        ->required()
                                        ->native(false),

                                    Select::make('payment_method')
                                        ->options([
                                            'cod' => 'Cash on Delivery',
                                            'bkash' => 'bKash',
                                            'nagad' => 'Nagad',
                                        ])->required(),

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