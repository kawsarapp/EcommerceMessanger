<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;

class PaymentGatewaysTab
{
    public static function schema(): array
    {
        return [

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 📲 bKash Merchant
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Section::make('📲 bKash Merchant Number')
                ->description('আপনার bKash Merchant নম্বর add করলে Customer সরাসরি checkout থেকে bKash এ payment করতে পারবে। Customer এর কাছ থেকে transaction ID নেওয়া হবে।')
                ->schema([
                    Toggle::make('payment_gateways.bkash_merchant.active')
                        ->label('bKash Merchant চালু করুন')
                        ->default(false)
                        ->live()
                        ->onColor('success'),

                    TextInput::make('payment_gateways.bkash_merchant.number')
                        ->label('bKash Merchant Number')
                        ->placeholder('01XXXXXXXXX')
                        ->tel()
                        ->maxLength(20)
                        ->visible(fn ($get) => $get('payment_gateways.bkash_merchant.active'))
                        ->helperText('যে নম্বরে Customer bKash করবে।')
                        ->required(fn ($get) => $get('payment_gateways.bkash_merchant.active')),

                    TextInput::make('payment_gateways.bkash_merchant.account_name')
                        ->label('Account Name (Optional)')
                        ->placeholder('আপনার ব্যবসার নাম')
                        ->visible(fn ($get) => $get('payment_gateways.bkash_merchant.active'))
                        ->helperText('Checkout page এ দেখাবে।'),
                ])
                ->columns(2)
                ->collapsible(),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 📱 bKash Personal
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Section::make('📱 bKash Personal Number')
                ->description('আপনার personal bKash ব্যবহার করলে এটি চালু করুন। Customer manually টাকা পাঠাবে এবং transaction ID দেবে।')
                ->schema([
                    Toggle::make('payment_gateways.bkash_personal.active')
                        ->label('bKash Personal চালু করুন')
                        ->default(false)
                        ->live()
                        ->onColor('success'),

                    TextInput::make('payment_gateways.bkash_personal.number')
                        ->label('bKash Personal Number')
                        ->placeholder('01XXXXXXXXX')
                        ->tel()
                        ->maxLength(20)
                        ->visible(fn ($get) => $get('payment_gateways.bkash_personal.active'))
                        ->helperText('যে নম্বরে Customer bKash করবে।')
                        ->required(fn ($get) => $get('payment_gateways.bkash_personal.active')),

                    TextInput::make('payment_gateways.bkash_personal.account_name')
                        ->label('Account Name (Optional)')
                        ->placeholder('আপনার নাম')
                        ->visible(fn ($get) => $get('payment_gateways.bkash_personal.active'))
                        ->helperText('Checkout page এ দেখাবে।'),
                ])
                ->columns(2)
                ->collapsible(),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 💳 SSL Commerz
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Section::make('💳 SSL Commerz (Card / Bank / Mobile Banking)')
                ->description('SSL Commerz এর মাধ্যমে Visa, Mastercard, Nagad, Rocket সহ সব ধরনের payment accept করুন। SSL Commerz থেকে আলাদা merchant account খুলতে হবে।')
                ->schema([
                    Toggle::make('payment_gateways.sslcommerz.active')
                        ->label('SSL Commerz চালু করুন')
                        ->default(false)
                        ->live()
                        ->onColor('success'),

                    Toggle::make('payment_gateways.sslcommerz.is_live')
                        ->label('Live Mode (Production)')
                        ->default(false)
                        ->onColor('danger')
                        ->offColor('warning')
                        ->visible(fn ($get) => $get('payment_gateways.sslcommerz.active'))
                        ->helperText('⚠️ Live চালু করলে real টাকা কাটবে। Test করার সময় বন্ধ রাখুন।'),

                    TextInput::make('payment_gateways.sslcommerz.store_id')
                        ->label('Store ID')
                        ->placeholder('your_store_id')
                        ->visible(fn ($get) => $get('payment_gateways.sslcommerz.active'))
                        ->required(fn ($get) => $get('payment_gateways.sslcommerz.active'))
                        ->helperText('SSL Commerz merchant dashboard থেকে পাবেন।'),

                    TextInput::make('payment_gateways.sslcommerz.store_password')
                        ->label('Store Password')
                        ->placeholder('your_store_password')
                        ->password()
                        ->revealable()
                        ->visible(fn ($get) => $get('payment_gateways.sslcommerz.active'))
                        ->required(fn ($get) => $get('payment_gateways.sslcommerz.active'))
                        ->helperText('SSL Commerz merchant dashboard থেকে পাবেন।'),
                ])
                ->columns(2)
                ->collapsible(),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 🌙 Surjopay
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Section::make('🌙 Surjopay')
                ->description('Surjopay payment gateway integrate করুন। Surjopay এর merchant account থাকলে API credentials দিয়ে চালু করুন।')
                ->schema([
                    Toggle::make('payment_gateways.surjopay.active')
                        ->label('Surjopay চালু করুন')
                        ->default(false)
                        ->live()
                        ->onColor('success'),

                    Toggle::make('payment_gateways.surjopay.is_live')
                        ->label('Live Mode (Production)')
                        ->default(false)
                        ->onColor('danger')
                        ->offColor('warning')
                        ->visible(fn ($get) => $get('payment_gateways.surjopay.active'))
                        ->helperText('⚠️ Live চালু করলে real টাকা কাটবে।'),

                    TextInput::make('payment_gateways.surjopay.username')
                        ->label('Surjopay Username / Merchant ID')
                        ->placeholder('your_merchant_username')
                        ->visible(fn ($get) => $get('payment_gateways.surjopay.active'))
                        ->required(fn ($get) => $get('payment_gateways.surjopay.active'))
                        ->helperText('Surjopay dashboard থেকে পাবেন।'),

                    TextInput::make('payment_gateways.surjopay.password')
                        ->label('Surjopay Password')
                        ->placeholder('your_password')
                        ->password()
                        ->revealable()
                        ->visible(fn ($get) => $get('payment_gateways.surjopay.active'))
                        ->required(fn ($get) => $get('payment_gateways.surjopay.active'))
                        ->helperText('Surjopay merchant account password।'),
                ])
                ->columns(2)
                ->collapsible(),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 💰 Payment Mode Settings (Partial / Full)
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Section::make('💰 Payment Mode Settings')
                ->description('Customer কে কোন ধরনের payment করতে দেবেন তা ঠিক করুন।')
                ->schema([
                    Toggle::make('cod_active')
                        ->label('💵 Cash on Delivery (COD)')
                        ->helperText('Order deliver করার পর টাকা নেওয়া হবে।')
                        ->default(true)
                        ->onColor('success'),

                    Toggle::make('partial_payment_active')
                        ->label('💸 Partial Payment (Advance)')
                        ->helperText('Customer একটা নির্দিষ্ট অংশ advance দিতে পারবে।')
                        ->default(false)
                        ->live()
                        ->onColor('info'),

                    TextInput::make('partial_payment_amount')
                        ->label('Minimum Advance Amount (৳)')
                        ->numeric()
                        ->prefix('৳')
                        ->placeholder('100')
                        ->default(0)
                        ->visible(fn ($get) => $get('partial_payment_active'))
                        ->helperText('০ রাখলে Customer যেকোনো পরিমাণ advance দিতে পারবে।'),

                    Toggle::make('full_payment_active')
                        ->label('✅ Full Pre-Payment')
                        ->helperText('Customer delivery এর আগেই পুরো payment করবে।')
                        ->default(false)
                        ->onColor('success'),
                ])
                ->columns(2),
        ];
    }
}
