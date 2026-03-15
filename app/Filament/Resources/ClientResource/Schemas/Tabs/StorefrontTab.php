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
                        ->label('Popup Expires At')
                        ->helperText('এই সময়ের পর পপআপ আর দেখাবে না।')
                        ->visible(fn ($get) => $get('popup_active')),
                ])->columns(2),

            Section::make('🧩 Widget Controls')
                ->description('আপনার শপের প্রতিটি UI widget আলাদাভাবে ON/OFF করুন।')
                ->schema([
                    Toggle::make('widgets.show_hero_banner')
                        ->label('Hero Banner')
                        ->helperText('হোম পেজের উপরে বড় ব্যানার ইমেজ')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('widgets.show_search_bar')
                        ->label('Search Bar')
                        ->helperText('হেডারে প্রোডাক্ট সার্চ বার')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('widgets.show_category_filter')
                        ->label('Category Filter')
                        ->helperText('ক্যাটাগরি ফিল্টার ট্যাব/পিল')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('widgets.show_trust_badges')
                        ->label('Trust Badges')
                        ->helperText('ডেলিভারি, সিকিউরিটি, রিটার্ন ব্যাজ')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('widgets.show_reviews')
                        ->label('Customer Reviews')
                        ->helperText('প্রোডাক্ট রিভিউ সেকশন')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('widgets.show_floating_chat')
                        ->label('Floating Chat Widget')
                        ->helperText('নিচে ডান পাশে ফ্লোটিং চ্যাট বাটন')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('widgets.show_announcement_bar')
                        ->label('Announcement Bar')
                        ->helperText('হেডারের উপরে অ্যানাউন্সমেন্ট বার')
                        ->default(true)
                        ->onColor('success'),
                    Toggle::make('widgets.show_social_links')
                        ->label('Social Media Links')
                        ->helperText('ফুটারে সোশ্যাল মিডিয়া লিংক')
                        ->default(true)
                        ->onColor('success'),
                ])->columns(2),
        ];
    }
}