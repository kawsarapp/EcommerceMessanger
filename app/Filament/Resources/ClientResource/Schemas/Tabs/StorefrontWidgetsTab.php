<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use App\Services\ImageOptimizer;

class StorefrontWidgetsTab
{
    public static function schema(): array
    {
        return [
            // ─── Intro ────────────────────────────────────────────
            Section::make('🧩 Widget Management')
                ->description('আপনার shop এর প্রতিটি UI section আলাদাভাবে ON/OFF এবং customize করুন। প্রতিটি widget কে collapse করে রাখতে পারবেন।')
                ->schema([])
                ->columns(1),

            // ─── Hero Banner ──────────────────────────────────────
            Section::make('🖼️ Hero Banner Widget')
                ->description('Homepage এর সবার উপরে বড় hero banner section।')
                ->schema([
                    Toggle::make('widgets.hero_banner.active')
                        ->label('Enable Hero Banner')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->columnSpanFull(),

                    TextInput::make('widgets.hero_banner.text')
                        ->label('Banner Overlay Text')
                        ->placeholder('BANNER OVERLAY TEXT')
                        ->helperText('Banner এর উপরে বড় হরফে যে text দেখাবে।'),

                    TextInput::make('widgets.hero_banner.button_text')
                        ->label('CTA Button Text')
                        ->placeholder('EXPLORE NOW')
                        ->helperText('Banner এর নিচের button এর text।'),

                    TextInput::make('widgets.hero_banner.link')
                        ->label('Banner / Button Link')
                        ->url(),

                    ColorPicker::make('widgets.hero_banner.color')
                        ->label('Text / Accent Color'),

                    FileUpload::make('widgets.hero_banner.image')
                        ->label('Hero Banner Background Image')
                        ->image()
                        ->directory('shops/hero-banners')
                        ->maxSize(8192)
                        ->columnSpanFull()
                        ->helperText('✅ Recommended: 1600×600px। না দিলে shop এর general banner ব্যবহার হবে।')
                        ->saveUploadedFileUsing(function ($file) {
                            try {
                                return (new \App\Services\ImageOptimizer())->optimize($file, 'shops/hero-banners', 'hero_banner');
                            } catch (\Exception $e) {
                                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                                $file->storeAs('shops/hero-banners', $filename, 'public');
                                return 'shops/hero-banners/' . $filename;
                            }
                        }),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(false),

            // ─── Search Bar ───────────────────────────────────────
            Section::make('🔍 Search Bar Widget')
                ->description('Header এ search bar এর settings।')
                ->schema([
                    Toggle::make('widgets.search_bar.active')
                        ->label('Enable Search Bar')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->columnSpanFull(),

                    TextInput::make('widgets.search_bar.text')
                        ->label('Search Placeholder Text')
                        ->placeholder('Search for products...'),

                    ColorPicker::make('widgets.search_bar.color')
                        ->label('Search Button Color'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // ─── Category Filter ──────────────────────────────────
            Section::make('📂 Category Filter Widget')
                ->description('Homepage এ category tab/filter section।')
                ->schema([
                    Toggle::make('widgets.category_filter.active')
                        ->label('Enable Category Filter')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->columnSpanFull(),

                    TextInput::make('widgets.category_filter.text')
                        ->label('Section Title')
                        ->placeholder('Categories'),

                    ColorPicker::make('widgets.category_filter.color')
                        ->label('Active Tab Color'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // ─── Flash Sale / Featured ────────────────────────────
            Section::make('🔥 Featured / Flash Sale Widget')
                ->description('Homepage এ featured products বা flash sale section।')
                ->schema([
                    Toggle::make('widgets.flash_sale.active')
                        ->label('Enable Featured Section')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->columnSpanFull(),

                    TextInput::make('widgets.flash_sale.text')
                        ->label('Section Title')
                        ->placeholder('Featured Products'),

                    TextInput::make('widgets.flash_sale.link')
                        ->label('"View All" Link')
                        ->url(),

                    ColorPicker::make('widgets.flash_sale.color')
                        ->label('Section Badge Color')
                        ->default('#ef4444'),
                ])
                ->columns(3)
                ->collapsible()
                ->collapsed(),

            // ─── Floating Chat ────────────────────────────────────
            Section::make('💬 Floating Chat Widget')
                ->description('Shop এ floating chat button এর settings।')
                ->schema([
                    Toggle::make('widgets.floating_chat.active')
                        ->label('Enable Floating Chat Button')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->columnSpanFull(),

                    TextInput::make('widgets.floating_chat.link')
                        ->label('Chat Link Override (Optional)')
                        ->url()
                        ->helperText('খালি রাখলে configured WhatsApp/Messenger ব্যবহার হবে।'),

                    ColorPicker::make('widgets.floating_chat.color')
                        ->label('Chat Icon Color')
                        ->default('#25D366'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // ─── Trust Bar (Daraz theme) ──────────────────────────
            Section::make('🏅 Trust Bar Widget')
                ->description('Homepage এর trust strip — Daraz, Shwapno theme এ দেখাবে।')
                ->schema([
                    Toggle::make('widgets.trust_bar.active')
                        ->label('Enable Trust Bar')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->columnSpanFull(),

                    Repeater::make('widgets.trust_bar.items')
                        ->label('Trust Items')
                        ->schema([
                            TextInput::make('icon')
                                ->label('Font Awesome Icon')
                                ->placeholder('fas fa-check-circle'),
                            TextInput::make('text')
                                ->label('Text')
                                ->placeholder('১০০% অরিজিনাল পণ্য'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('+ Add Trust Item')
                        ->columnSpanFull()
                        ->helperText('খালি রাখলে default 4টি trust item দেখাবে।'),
                ])
                ->columns(1)
                ->collapsible()
                ->collapsed(),

            // ─── Products Section ─────────────────────────────────
            Section::make('🛍️ Products Section Widget')
                ->description('Homepage এ product grid এর title ও settings।')
                ->schema([
                    TextInput::make('widgets.products_section.title')
                        ->label('Product Grid Section Title')
                        ->placeholder('সকল পণ্য')
                        ->helperText('Homepage এ product grid এর উপরে যে title দেখাবে।')
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->collapsible()
                ->collapsed(),

            // ─── Location Picker ──────────────────────────────────
            Section::make('📍 Location Picker Widget')
                ->description('Shwapno theme এর "Deliver to" location selector।')
                ->schema([
                    Toggle::make('widgets.location_picker.active')
                        ->label('Enable Location Picker')
                        ->default(false)
                        ->onColor('success')
                        ->inline(false)
                        ->columnSpanFull(),

                    TextInput::make('widgets.location_picker.text')
                        ->label('Button Text')
                        ->placeholder('Select delivery location'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // ─── Stock Notify ─────────────────────────────────────
            Section::make('🔔 Stock Notify Widget')
                ->description('Out-of-stock product এ "Notify me" button।')
                ->schema([
                    Toggle::make('widgets.stock_notify.active')
                        ->label('Enable Stock Notify Button')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),

                    Toggle::make('widgets.top_help_links.active')
                        ->label('Show Top Help Links')
                        ->helperText('Header এ Help, Track Order link দেখাবে (Shwapno theme)।')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // ─── Loyalty Program ──────────────────────────────────
            Section::make('🏆 Loyalty Points Widget')
                ->description('Product page এ "কিনলে X points পাবেন" badge দেখাবে।')
                ->schema([
                    Toggle::make('widgets.loyalty.active')
                        ->label('Enable Loyalty Points Badge')
                        ->default(false)
                        ->onColor('success')
                        ->inline(false),

                    TextInput::make('widgets.loyalty.rate')
                        ->label('Points Rate (%)')
                        ->numeric()
                        ->default(1)
                        ->helperText('Product price এর কত % points পাবে। e.g. 1 = 1 point per 100৳।'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ];
    }
}
