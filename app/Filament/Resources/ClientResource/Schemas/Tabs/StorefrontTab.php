<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DateTimePicker;
use App\Services\ImageOptimizer;

class StorefrontTab
{
    public static function schema(): array
    {
        return [
            Section::make('Visual Identity')
                ->description('Upload logo and banner to make your shop look professional.')
                ->schema([
                    FileUpload::make('logo')
                        ->label('Shop Logo (Square)')
                        ->image()
                        ->avatar()
                        ->directory('shops/logos')
                        ->maxSize(3072)
                        ->helperText('📐 Recommended: 200×200px square. Max 3MB. PNG/JPG.')
                        ->saveUploadedFileUsing(function ($file) {
                            try {
                                return (new ImageOptimizer())->optimize($file, 'shops/logos', 'shop_logo');
                            } catch (\Exception $e) {
                                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                                $file->storeAs('shops/logos', $filename, 'public');
                                return 'shops/logos/' . $filename;
                            }
                        }),
                    
                    FileUpload::make('banner')
                        ->label('Cover Banner (Wide)')
                        ->image()
                        ->directory('shops/banners')
                        ->maxSize(8192)
                        ->helperText('📐 Recommended: 1600×600px wide. Max 8MB. JPG/WebP.')
                        ->columnSpanFull()
                        ->saveUploadedFileUsing(function ($file) {
                            try {
                                return (new ImageOptimizer())->optimize($file, 'shops/banners', 'shop_banner');
                            } catch (\Exception $e) {
                                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                                $file->storeAs('shops/banners', $filename, 'public');
                                return 'shops/banners/' . $filename;
                            }
                        }),
                ])->columns(2),

            Section::make('Theme & Announcements')->schema([
                Select::make('theme_name')
                    ->label('Storefront Theme')
                    ->options([
                        'default'     => 'Default Classic (Standard E-commerce)',
                        'modern'      => 'Modern Minimal (Clean & Fast)',
                        'fashion'     => 'Fashion Pro (Apparel & Clothing)',
                        'electronics' => 'Tech & Gadgets (Electronics)',
                        'grocery'     => 'Supermarket (Grocery & Daily Needs)',
                        'luxury'      => 'Premium Luxury (Jewelry & Watches)',
                        'kids'        => 'Kids Corner (Toys & Baby Products)',
                        'premium'     => '✨ Ultra Premium VIP (Vibrant & Glassmorphism)',
                        'bdshop'      => '🇧🇩 BD Shop (Daraz/BDShop Style)',
                    ])
                    ->default('default')
                    ->searchable()
                    ->required()
                    ->helperText('Choose a layout for your store.'),

                ColorPicker::make('primary_color')
                    ->label('Brand Color')
                    ->default('#4f46e5')
                    ->helperText('This color will be used for buttons and links.'),
                
                TextInput::make('announcement_text')
                    ->label('Announcement Bar')
                    ->placeholder('🎉 Eid Sale is Live! Get 10% Off.')
                    ->helperText('Shows at the top of your shop header.')
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('🎯 Homepage Offer Banner')
                ->description('ক্যাটাগরি সেকশনের উপরে একটি বড় অফার ব্যানার দেখাবে (টাইমার সহ)।')
                ->schema([
                    Toggle::make('homepage_banner_active')
                        ->label('Enable Offer Banner')
                        ->default(false)
                        ->live()
                        ->onColor('success'),

                    TextInput::make('homepage_banner_title')
                        ->label('Banner Title')
                        ->placeholder('🔥 Mega Sale! Up to 70% Off')
                        ->visible(fn ($get) => $get('homepage_banner_active')),

                    Textarea::make('homepage_banner_subtitle')
                        ->label('Banner Subtitle')
                        ->placeholder('সীমিত সময়ের জন্য সব ক্যাটাগরিতে বিশাল ডিসকাউন্ট!')
                        ->rows(2)
                        ->visible(fn ($get) => $get('homepage_banner_active')),

                    FileUpload::make('homepage_banner_image')
                        ->label('Banner Image')
                        ->image()
                        ->directory('shops/homepage-banners')
                        ->visible(fn ($get) => $get('homepage_banner_active')),

                    TextInput::make('homepage_banner_link')
                        ->label('Banner Link (Optional)')
                        ->url()
                        ->visible(fn ($get) => $get('homepage_banner_active')),

                    DateTimePicker::make('homepage_banner_timer')
                        ->label('Countdown Timer (Offer Ends At)')
                        ->helperText('এই সময়ের একটি কাউন্টডাউন টাইমার দেখাবে।')
                        ->visible(fn ($get) => $get('homepage_banner_active')),
                ])->columns(2),

            Section::make('🛒 Shop Display Controls')
                ->description('আপনার শপে কোন বাটনগুলো দেখাবে তা নিয়ন্ত্রণ করুন।')
                ->schema([
                    Toggle::make('show_order_button')
                        ->label('Show "Buy Now / Order" Button')
                        ->helperText('বন্ধ করলে শুধু Chat Button দেখাবে, Order Button থাকবে না।')
                        ->default(true)
                        ->onColor('success'),
                    
                    Toggle::make('show_chat_button')
                        ->label('Show Chat Button')
                        ->helperText('চালু করলে WhatsApp/Messenger চ্যাট বাটন প্রোডাক্ট পেজে দেখাবে।')
                        ->default(true)
                        ->onColor('success'),
                    
                    Toggle::make('show_terms_checkbox')
                        ->label('Show Terms & Conditions at Checkout')
                        ->helperText('চালু করলে চেকআউটে কাস্টমারকে Terms & Conditions এ সম্মতি দিতে হবে।')
                        ->default(false)
                        ->onColor('warning')
                        ->live(),
                    
                    TextInput::make('terms_conditions_url')
                        ->label('Terms & Conditions Page URL')
                        ->placeholder('https://example.com/terms')
                        ->url()
                        ->visible(fn ($get) => $get('show_terms_checkbox'))
                        ->helperText('যদি কোনো URL দেন, তাহলে "Terms & Conditions" লেখায় ক্লিক করলে সেই পেজ ওপেন হবে।'),

                    Textarea::make('terms_conditions_text')
                        ->label('Custom Terms Text (Optional)')
                        ->placeholder('অর্ডার করার পর ক্যান্সেল করা যাবে না...')
                        ->rows(2)
                        ->visible(fn ($get) => $get('show_terms_checkbox'))
                        ->helperText('শর্তের একটি ছোট বর্ণনা। এটি চেকবক্সের নিচে দেখাবে।'),

                    Toggle::make('show_stock')
                        ->label('Show Stock Status')
                        ->helperText('প্রোডাক্ট পেজে স্টক এর পরিমাণ দেখাবে কিনা।')
                        ->default(true)
                        ->onColor('success'),
                        
                    Toggle::make('show_related_products')
                        ->label('Show Related Products')
                        ->helperText('প্রোডাক্ট পেজের নিচে রিলেটেড প্রোডাক্ট দেখাবে কিনা।')
                        ->default(true)
                        ->onColor('success'),
                        
                    Toggle::make('show_return_warranty')
                        ->label('Show Warranty & Return Policy')
                        ->helperText('প্রোডাক্ট পেজে রিটার্ন ও ওয়ারেন্টি পলিসি দেখাবে কিনা।')
                        ->default(true)
                        ->onColor('success'),
                ])->columns(2),
                
            Section::make('Payment Methods')
                ->schema([
                    Toggle::make('cod_active')
                        ->label('Cash on Delivery (COD)')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('partial_payment_active')
                        ->label('Partial Payment Allowed')
                        ->default(false)
                        ->onColor('success'),
                    Toggle::make('full_payment_active')
                        ->label('Full Pre-Payment Allowed')
                        ->default(false)
                        ->onColor('success'),
                ])->columns(3),
                
            Section::make('Footer Settings')
                ->schema([
                    Textarea::make('footer_text')
                        ->label('Custom Footer Text')
                        ->placeholder('Copyright 2026. All rights reserved.'),
                    Repeater::make('footer_links')
                        ->label('Dynamic Footer Links')
                        ->schema([
                            TextInput::make('title')->label('Link Title')->required(),
                            TextInput::make('url')->label('Link URL')->url()->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible(),
                ])->columns(1),
                
            Section::make('Offer Popup Banner')
                ->description('শপে ঢুকলেই ইউজারের সামনে এই অফার পপআপ শো করবে।')
                ->schema([
                    Toggle::make('popup_active')
                        ->label('Enable Popup Banner')
                        ->default(false)
                        ->live()
                        ->onColor('success'),
                        
                    TextInput::make('popup_title')
                        ->label('Popup Title')
                        ->visible(fn ($get) => $get('popup_active')),
                        
                    Textarea::make('popup_description')
                        ->label('Popup Description')
                        ->visible(fn ($get) => $get('popup_active')),
                        
                    FileUpload::make('popup_image')
                        ->label('Banner Image')
                        ->image()
                        ->directory('shops/popups')
                        ->visible(fn ($get) => $get('popup_active')),
                        
                    TextInput::make('popup_link')
                        ->label('Redirect Link (Optional)')
                        ->url()
                        ->visible(fn ($get) => $get('popup_active')),
                        
                    DateTimePicker::make('popup_expires_at')
                        ->label('Offer Expires At (Optional)')
                        ->helperText('এই সময়ের পর আর পপআপ দেখাবে না।')
                        ->visible(fn ($get) => $get('popup_active')),

                    TextInput::make('popup_delay')
                        ->label('Popup Delay (Seconds)')
                        ->numeric()
                        ->default(3)
                        ->helperText('কত সেকেন্ড পর পপআপ আসবে।')
                        ->visible(fn ($get) => $get('popup_active')),

                    \Filament\Forms\Components\Select::make('popup_pages')
                        ->label('Show on specific pages')
                        ->multiple()
                        ->options([
                            'home' => 'Homepage',
                            'product' => 'Product Details Page',
                            'checkout' => 'Checkout Page',
                        ])
                        ->helperText('খালি রাখলে সব পেজে দেখাবে।')
                        ->visible(fn ($get) => $get('popup_active'))
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('🧩 Dynamic Widget Controls')
                ->description('আপনার শপের প্রতিটি UI widget আলাদাভাবে কাস্টমাইজ করুন (রং, টেক্সট, লিংক এবং শো/হাইড)।')
                ->schema([
                    Section::make('Hero Banner Widget')
                        ->schema([
                            Toggle::make('widgets.hero_banner.active')->label('Enable Hero Banner')->default(true)->columnSpanFull()->onColor('success'),
                            TextInput::make('widgets.hero_banner.text')->label('Banner Overlay Text')->placeholder('Welcome to our shop!'),
                            TextInput::make('widgets.hero_banner.link')->label('Banner Link')->url(),
                            ColorPicker::make('widgets.hero_banner.color')->label('Text/Accent Color'),
                        ])->columns(3)->collapsible(),

                    Section::make('Search Bar Widget')
                        ->schema([
                            Toggle::make('widgets.search_bar.active')->label('Enable Search Bar')->default(true)->columnSpanFull()->onColor('success'),
                            TextInput::make('widgets.search_bar.text')->label('Search Placeholder')->placeholder('Search for products...'),
                            ColorPicker::make('widgets.search_bar.color')->label('Search Button Color'),
                        ])->columns(2)->collapsible(),

                    Section::make('Category Filter Widget')
                        ->schema([
                            Toggle::make('widgets.category_filter.active')->label('Enable Category Filter')->default(true)->columnSpanFull()->onColor('success'),
                            TextInput::make('widgets.category_filter.text')->label('Section Title')->placeholder('Categories'),
                            ColorPicker::make('widgets.category_filter.color')->label('Active Tab/Badge Color'),
                        ])->columns(2)->collapsible(),

                    Section::make('Featured / Flash Sale Widget')
                        ->schema([
                            Toggle::make('widgets.flash_sale.active')->label('Enable Featured Section')->default(true)->columnSpanFull()->onColor('success'),
                            TextInput::make('widgets.flash_sale.text')->label('Section Title')->placeholder('Feature Products'),
                            TextInput::make('widgets.flash_sale.link')->label('View All Link')->url(),
                            ColorPicker::make('widgets.flash_sale.color')->label('Section Badge Color')->default('#ef4444'),
                        ])->columns(3)->collapsible(),

                    Section::make('Trust Badges Widget')
                        ->schema([
                            Toggle::make('widgets.trust_badges.active')->label('Enable Trust Badges')->default(true)->columnSpanFull()->onColor('success'),
                            TextInput::make('widgets.trust_badges.text')->label('Section Title')->placeholder('Why Choose Us?'),
                            ColorPicker::make('widgets.trust_badges.color')->label('Icon Color')->default('#10b981'),
                        ])->columns(2)->collapsible(),

                    Section::make('Floating Chat Widget')
                        ->schema([
                            Toggle::make('widgets.floating_chat.active')->label('Enable Floating Chat')->default(true)->columnSpanFull()->onColor('success'),
                            TextInput::make('widgets.floating_chat.link')->label('Chat Link')->url(),
                            ColorPicker::make('widgets.floating_chat.color')->label('Chat Icon Color')->default('#25D366'),
                        ])->columns(2)->collapsible(),
                ]),
        ];
    }
}