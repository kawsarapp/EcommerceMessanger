<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\ColorPicker;
use App\Services\ImageOptimizer;

class StorefrontBodyTab
{
    public static function schema(): array
    {
        return [
            // ─── Shop Display Controls ────────────────────────────
            Section::make('🛒 Shop Display Controls')
                ->description('আপনার shop এ কোন button ও section গুলো দেখাবে তা নিয়ন্ত্রণ করুন।')
                ->schema([
                    Toggle::make('show_order_button')
                        ->label('✅ Show "Buy Now / Order" Button')
                        ->helperText('বন্ধ করলে শুধু Chat Button দেখাবে, Order button থাকবে না।')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('show_chat_button')
                        ->label('💬 Show Chat Button')
                        ->helperText('চালু করলে WhatsApp/Messenger chat button product page এ দেখাবে।')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('show_stock')
                        ->label('📦 Show Stock Status')
                        ->helperText('Product page এ stock এর পরিমাণ দেখাবে কিনা।')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('show_related_products')
                        ->label('🔗 Show Related Products')
                        ->helperText('Product page এর নিচে related products দেখাবে কিনা।')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('show_return_warranty')
                        ->label('🛡️ Show Warranty & Return Policy')
                        ->helperText('Product page এ return ও warranty policy দেখাবে কিনা।')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('show_terms_checkbox')
                        ->label('📋 Show Terms & Conditions at Checkout')
                        ->helperText('চালু করলে checkout এ customer কে Terms এ সম্মতি দিতে হবে।')
                        ->default(false)
                        ->onColor('warning')
                        ->inline(false)
                        ->live(),

                    TextInput::make('terms_conditions_url')
                        ->label('Terms & Conditions Page URL')
                        ->placeholder('https://example.com/terms')
                        ->url()
                        ->visible(fn ($get) => $get('show_terms_checkbox'))
                        ->helperText('ক্লিক করলে এই পেজ খুলবে।')
                        ->columnSpanFull(),

                    Textarea::make('terms_conditions_text')
                        ->label('Custom Terms Text (Optional)')
                        ->placeholder('অর্ডার করার পর ক্যান্সেল করা যাবে না...')
                        ->rows(2)
                        ->visible(fn ($get) => $get('show_terms_checkbox'))
                        ->helperText('Checkbox এর নিচে ছোট বর্ণনা দেখাবে।')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ─── Payment Methods ──────────────────────────────────
            Section::make('💳 Payment Methods')
                ->description('Checkout এ কোন payment options দেখাবে তা নির্বাচন করুন। Advanced gateway গুলো "Payments" সেকশনে সেট করুন।')
                ->schema([
                    Toggle::make('cod_active')
                        ->label('🏠 Cash on Delivery (COD)')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('partial_payment_active')
                        ->label('🔀 Partial Advance Payment')
                        ->helperText('Order এর কিছু অংশ আগে দেওয়ার option।')
                        ->default(false)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('full_payment_active')
                        ->label('💰 Full Pre-Payment')
                        ->helperText('সম্পূর্ণ payment আগে দেওয়ার option।')
                        ->default(false)
                        ->onColor('success')
                        ->inline(false),
                ])
                ->columns(3),

            // ─── Customer Auth ────────────────────────────────────
            Section::make('🔐 Customer Account System')
                ->description('Customer রা কিভাবে account খুলতে ও login করতে পারবে তা নিয়ন্ত্রণ করুন।')
                ->schema([
                    Select::make('customer_auth_mode')
                        ->label('Registration & Login Mode')
                        ->options([
                            'email' => '📧 Email & Password (Standard Secure Mode)',
                            'phone' => '📱 Phone Number & Password',
                            'both'  => '✅ Allow Both Email and Phone',
                        ])
                        ->default('email')
                        ->required()
                        ->native(false)
                        ->helperText('Buyers রা account তৈরি করে orders track করতে এবং points earn করতে পারবে।'),
                ])
                ->columns(1),

            // ─── Homepage Offer Banner ────────────────────────────
            Section::make('🎯 Homepage Offer Banner')
                ->description('Category section এর উপরে একটি বড় offer banner দেখাবে (countdown timer সহ)।')
                ->schema([
                    Toggle::make('homepage_banner_active')
                        ->label('Enable Offer Banner')
                        ->default(false)
                        ->live()
                        ->onColor('success')
                        ->inline(false),

                    TextInput::make('homepage_banner_title')
                        ->label('Banner Title')
                        ->placeholder('🔥 Mega Sale! Up to 70% Off')
                        ->visible(fn ($get) => $get('homepage_banner_active')),

                    Textarea::make('homepage_banner_subtitle')
                        ->label('Banner Subtitle')
                        ->placeholder('সীমিত সময়ের জন্য সব category তে বিশাল discount!')
                        ->rows(2)
                        ->visible(fn ($get) => $get('homepage_banner_active')),

                    FileUpload::make('homepage_banner_image')
                        ->label('Banner Image')
                        ->image()
                        ->directory('shops/homepage-banners')
                        ->maxSize(8192)
                        ->visible(fn ($get) => $get('homepage_banner_active'))
                        ->helperText('Wide format recommended — 1600×500px।'),

                    TextInput::make('homepage_banner_link')
                        ->label('Banner Link (Optional)')
                        ->url()
                        ->visible(fn ($get) => $get('homepage_banner_active')),

                    DateTimePicker::make('homepage_banner_timer')
                        ->label('⏰ Countdown Timer (Offer Ends At)')
                        ->helperText('এই সময়ের একটি countdown timer দেখাবে।')
                        ->visible(fn ($get) => $get('homepage_banner_active')),
                ])
                ->columns(2),

            // ─── Popup Banner ─────────────────────────────────────
            Section::make('🪟 Popup Offer Banner')
                ->description('Shop এ ঢুকলেই customer এর সামনে এই offer popup দেখাবে।')
                ->schema([
                    Toggle::make('popup_active')
                        ->label('Enable Popup Banner')
                        ->default(false)
                        ->live()
                        ->onColor('success')
                        ->inline(false),

                    TextInput::make('popup_title')
                        ->label('Popup Title')
                        ->visible(fn ($get) => $get('popup_active')),

                    Textarea::make('popup_description')
                        ->label('Popup Description')
                        ->visible(fn ($get) => $get('popup_active')),

                    FileUpload::make('popup_image')
                        ->label('Popup Image')
                        ->image()
                        ->directory('shops/popups')
                        ->visible(fn ($get) => $get('popup_active')),

                    TextInput::make('popup_link')
                        ->label('Redirect Link (Optional)')
                        ->url()
                        ->visible(fn ($get) => $get('popup_active')),

                    DateTimePicker::make('popup_expires_at')
                        ->label('Popup Expires At (Optional)')
                        ->helperText('এই সময়ের পর আর popup দেখাবে না।')
                        ->visible(fn ($get) => $get('popup_active')),

                    TextInput::make('popup_delay')
                        ->label('Popup Delay (Seconds)')
                        ->numeric()
                        ->default(3)
                        ->helperText('কত second পর popup আসবে।')
                        ->visible(fn ($get) => $get('popup_active')),

                    Select::make('popup_pages')
                        ->label('Show on Pages')
                        ->multiple()
                        ->options([
                            'home'     => '🏠 Homepage',
                            'product'  => '📦 Product Details Page',
                            'checkout' => '🛒 Checkout Page',
                        ])
                        ->helperText('খালি রাখলে সব page এ দেখাবে।')
                        ->visible(fn ($get) => $get('popup_active'))
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // ─── Trust Badges ─────────────────────────────────────
            Section::make('🏅 Trust Badges')
                ->description('Homepage এ trust badge section এর items customize করুন।')
                ->schema([
                    Toggle::make('widgets.trust_badges.active')
                        ->label('Enable Trust Badges Section')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    TextInput::make('widgets.trust_badges.text')
                        ->label('Section Title')
                        ->placeholder('Why Choose Us?'),

                    ColorPicker::make('widgets.trust_badges.color')
                        ->label('Icon Color')
                        ->default('#10b981'),

                    Repeater::make('widgets.trust_badges.items')
                        ->label('Trust Badge Items')
                        ->schema([
                            TextInput::make('icon')
                                ->label('Font Awesome Icon')
                                ->placeholder('fa-truck')
                                ->helperText('e.g. fa-truck, fa-shield-alt, fa-headset'),
                            TextInput::make('title')
                                ->label('Title')
                                ->placeholder('Fast Delivery')
                                ->required(),
                            TextInput::make('subtitle')
                                ->label('Subtitle')
                                ->placeholder('Same day delivery available'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->collapsible()
                        ->addActionLabel('+ Add Badge')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),
        ];
    }
}
