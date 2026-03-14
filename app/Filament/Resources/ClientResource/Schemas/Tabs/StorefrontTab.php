<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;

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
                        ->maxSize(2048),
                    
                    FileUpload::make('banner')
                        ->label('Cover Banner (Wide)')
                        ->image()
                        ->directory('shops/banners')
                        ->maxSize(5120)
                        ->columnSpanFull(),
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
                ])->columns(2),
        ];
    }
}