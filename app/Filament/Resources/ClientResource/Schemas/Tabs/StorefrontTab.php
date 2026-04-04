<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;

/**
 * StorefrontTab — Footer Content Manager
 * 
 * এই tab এ শুধু Footer এর সব settings আছে।
 * Header → StorefrontHeaderTab
 * Body/Display → StorefrontBodyTab
 * Widgets → StorefrontWidgetsTab
 * Footer → এই file (StorefrontTab)
 */
class StorefrontTab
{
    public static function schema(): array
    {
        return [
            // ─── Brand & Contact ──────────────────────────────────
            Section::make('🏢 Brand Info & Contact')
                ->description('Footer এর brand column এ যা দেখাবে।')
                ->schema([
                    Textarea::make('widgets.footer.brand_description')
                        ->label('Footer Brand Description')
                        ->placeholder('আমরা সেরা মানের পণ্য দ্রুত ডেলিভারি করি। সারা দেশে shipping সুবিধা।')
                        ->rows(3)
                        ->helperText('না দিলে shop এর main description ব্যবহার হবে।')
                        ->columnSpanFull(),

                    TextInput::make('widgets.footer.contact_title')
                        ->label('Contact Section Heading')
                        ->placeholder('যোগাযোগ করুন'),

                    TextInput::make('widgets.delivery_time.text')
                        ->label('Delivery Time Info')
                        ->placeholder('Same day delivery within Dhaka')
                        ->helperText('Product page ও footer এ delivery info দেখাবে।'),
                ])
                ->columns(2),

            // ─── Footer Menu Headings ─────────────────────────────
            Section::make('📋 Footer Menu Column Headings')
                ->description('Footer এ ৩টি menu column এর heading। Menu items গুলো "Menu Manager" থেকে যোগ করুন।')
                ->schema([
                    TextInput::make('widgets.footer.menu1_title')
                        ->label('Column 1 Heading')
                        ->placeholder('Information'),

                    TextInput::make('widgets.footer.menu2_title')
                        ->label('Column 2 Heading')
                        ->placeholder('Customer Service'),

                    TextInput::make('widgets.footer.menu3_title')
                        ->label('Column 3 Heading')
                        ->placeholder('Quick Links'),
                ])
                ->columns(3),

            // ─── Display Options ──────────────────────────────────
            Section::make('⚙️ Footer Display Options')
                ->description('Footer এ কোন sections দেখাবে তা নিয়ন্ত্রণ করুন।')
                ->schema([
                    Toggle::make('widgets.footer.show_payment')
                        ->label('Show Payment Methods in Footer')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->helperText('Footer এ COD, bKash, SSL ইত্যাদি payment badge দেখাবে।'),

                    Toggle::make('widgets.footer.show_social')
                        ->label('Show Social Media Icons in Footer')
                        ->default(true)
                        ->onColor('success')
                        ->inline(false)
                        ->helperText('Social links এর URL "Header & Brand" সেকশনে দিন।'),
                ])
                ->columns(2),

            // ─── Copyright ────────────────────────────────────────
            Section::make('©️ Copyright & Quick Links')
                ->description('Footer এর সবার নিচে copyright strip এ যা দেখাবে।')
                ->schema([
                    Textarea::make('footer_text')
                        ->label('Copyright / Bottom Text')
                        ->placeholder('© 2026 আপনার শপের নাম। সর্বস্বত্ব সংরক্ষিত।')
                        ->helperText('খালি রাখলে automatic copyright text দেখাবে।')
                        ->rows(2)
                        ->columnSpanFull(),

                    Repeater::make('footer_links')
                        ->label('Bottom Quick Links')
                        ->schema([
                            TextInput::make('title')
                                ->label('Link Title')
                                ->required()
                                ->placeholder('Privacy Policy'),
                            TextInput::make('url')
                                ->label('Link URL')
                                ->url()
                                ->required()
                                ->placeholder('https://...'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->addActionLabel('+ Add Quick Link')
                        ->helperText('Privacy Policy, Terms & Conditions ইত্যাদি এখানে যোগ করুন।')
                        ->columnSpanFull(),
                ])
                ->columns(1),

            // ─── Daraz Theme Footer Extra ─────────────────────────
            Section::make('🛍️ Daraz Theme Footer Note')
                ->description('Daraz theme এর footer description আলাদাভাবে customize করতে চাইলে।')
                ->schema([
                    Textarea::make('widgets.footer.description')
                        ->label('Daraz Theme Footer Description')
                        ->rows(2)
                        ->placeholder('১০০% আসল পণ্য। সারা দেশে ডেলিভারি। ২৪/৭ কাস্টমার সাপোর্ট।')
                        ->helperText('Daraz theme এ ব্যবহার হবে। অন্য theme এ brand_description ব্যবহার হয়।')
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->collapsible()
                ->collapsed(),
        ];
    }
}