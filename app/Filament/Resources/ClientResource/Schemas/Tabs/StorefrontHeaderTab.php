<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Services\ImageOptimizer;

class StorefrontHeaderTab
{
    public static function schema(): array
    {
        $themes = [
            'default'     => ['label' => 'Default Classic',     'desc' => 'Standard E-commerce',          'emoji' => '🏪'],
            'modern'      => ['label' => 'Modern Minimal',      'desc' => 'Clean & Fast',                  'emoji' => '✨'],
            'fashion'     => ['label' => 'Fashion Pro',         'desc' => 'Apparel & Clothing',            'emoji' => '👗'],
            'electronics' => ['label' => 'Tech & Gadgets',      'desc' => 'Electronics (Dark Mode)',       'emoji' => '💻'],
            'grocery'     => ['label' => 'Supermarket',         'desc' => 'Grocery & Daily Needs',         'emoji' => '🛒'],
            'luxury'      => ['label' => 'Premium Luxury',      'desc' => 'Jewelry & Watches',             'emoji' => '💎'],
            'kids'        => ['label' => 'Kids Corner',         'desc' => 'Toys & Baby Products',          'emoji' => '🧸'],
            'premium'     => ['label' => 'Ultra Premium',       'desc' => 'Glassmorphism & Vibrant',       'emoji' => '🌟'],
            'bdshop'      => ['label' => 'BD Shop',             'desc' => 'BDShop / Daraz Style',          'emoji' => '🇧🇩'],
            'bdpro'       => ['label' => 'BD Pro',              'desc' => 'Original BDShop Style',         'emoji' => '🔵'],
            'shwapno'     => ['label' => 'Shwapno',             'desc' => 'Grocery/Supermarket Style',     'emoji' => '🛍️'],
            'shoppers'    => ['label' => 'Shoppers',            'desc' => 'Cosmetics & Retail',            'emoji' => '💄'],
            'athletic'    => ['label' => 'Athletic',            'desc' => 'High Energy & Performance',     'emoji' => '⚡'],
            'daraz'       => ['label' => 'Daraz Classic',       'desc' => 'Marketplace Inspired',          'emoji' => '🛒'],
            'pikabo'      => ['label' => 'Pikabo',              'desc' => 'Pickaboo Style',                'emoji' => '📱'],
            'vegist'      => ['label' => 'Vegist',              'desc' => 'Organic & Supermarket',         'emoji' => '🌿'],
        ];

        $themeOptions = [];
        foreach ($themes as $key => $info) {
            $themeOptions[$key] = "{$info['emoji']} {$info['label']} — {$info['desc']}";
        }

        return [
            // ─── Visual Identity ─────────────────────────────────
            Section::make('🖼️ Visual Identity')
                ->description('Shop এর logo ও cover banner আপলোড করুন। এগুলো সব theme এ দেখাবে।')
                ->schema([
                    FileUpload::make('logo')
                        ->label('Shop Logo')
                        ->image()
                        ->avatar()
                        ->directory('shops/logos')
                        ->maxSize(3072)
                        ->helperText('✅ Square format — 200×200px recommended. PNG/JPG, max 3MB.')
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
                        ->label('Cover Banner')
                        ->image()
                        ->directory('shops/banners')
                        ->maxSize(8192)
                        ->helperText('✅ Wide format — 1600×600px recommended. JPG/WebP, max 8MB.')
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
                ])
                ->columns(2),

            // ─── Theme Selection ─────────────────────────────────
            Section::make('🎨 Theme & Brand Colors')
                ->description('আপনার shop এর theme এবং ব্র্যান্ড রং বেছে নিন।')
                ->schema([
                    Select::make('theme_name')
                        ->label('Storefront Theme')
                        ->options($themeOptions)
                        ->default('default')
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->helperText('🎨 Theme পরিবর্তন করলে সাথে সাথে shop এর layout change হয়।')
                        ->columnSpanFull(),

                    ColorPicker::make('primary_color')
                        ->label('Primary Brand Color')
                        ->default('#4f46e5')
                        ->helperText('Buttons, links, highlights — সব জায়গায় এই রং ব্যবহার হবে।'),

                    ColorPicker::make('secondary_color')
                        ->label('Secondary / Accent Color')
                        ->helperText('Gradient ও secondary highlights এ ব্যবহার হবে। (Optional)')
                        ->nullable(),

                    ColorPicker::make('bg_color')
                        ->label('Page Background Color')
                        ->helperText('পুরো page এর background রং। খালি রাখলে theme default ব্যবহার হবে।')
                        ->nullable(),

                    TextInput::make('tagline')
                        ->label('Shop Tagline')
                        ->placeholder('দ্রুত ডেলিভারি, আসল পণ্য, সেরা দাম।')
                        ->helperText('Footer ও SEO তে দেখাবে।'),
                ])
                ->columns(2),

            // ─── Header & Announcement ───────────────────────────
            Section::make('📢 Header Announcement & Top Bar')
                ->description('Shop এর header এ announcement বার ও topbar text দেখানোর জন্য।')
                ->schema([
                    TextInput::make('announcement_text')
                        ->label('🔔 Announcement Bar Text')
                        ->placeholder('🎉 Eid Sale — সব পণ্যে ২০% ছাড়! সীমিত সময়ের জন্য।')
                        ->helperText('Header এর একদম উপরে একটি strip-এ দেখাবে। সব theme এ কাজ করে।')
                        ->columnSpanFull(),

                    TextInput::make('topbar_text')
                        ->label('🔢 Topbar Left Text')
                        ->placeholder('স্বাগতম! সব পণ্যে ফ্রি শিপিং সুবিধা।')
                        ->helperText('BDPro, Vegist, Shoppers theme এ topbar এ বাম পাশে দেখাবে।')
                        ->columnSpanFull(),
                ])
                ->columns(1),

            // ─── Shop Description ─────────────────────────────────
            Section::make('🏷️ Shop Description & Social Media')
                ->description('Shop সম্পর্কে তথ্য এবং social media links। Footer ও SEO তে ব্যবহার হবে।')
                ->schema([
                    Textarea::make('description')
                        ->label('Shop Description')
                        ->placeholder('আপনার শপের সম্পর্কে সংক্ষিপ্ত বিবরণ লিখুন...')
                        ->helperText('Footer, SEO এবং product page এ দেখাবে।')
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('youtube_url')
                        ->label('YouTube URL')
                        ->url()
                        ->placeholder('https://youtube.com/@yourshop')
                        ->helperText('Footer ও social icons এ দেখাবে।'),

                    TextInput::make('tiktok_url')
                        ->label('TikTok URL')
                        ->url()
                        ->placeholder('https://tiktok.com/@yourshop')
                        ->helperText('Daraz ও অন্যান্য theme এ social icons এ দেখাবে।'),

                    TextInput::make('widgets.office_hours.text')
                        ->label('Office Hours')
                        ->placeholder('Everyday 10AM - 9PM')
                        ->helperText('BDPro, Shoppers theme এ contact section এ দেখাবে।'),
                ])
                ->columns(2),
        ];
    }
}
