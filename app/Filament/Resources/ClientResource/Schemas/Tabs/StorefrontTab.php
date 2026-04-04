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
                        'bdpro'       => '🔵 BD Pro (Original BDShop Replica)',
                        'shwapno'     => '🛒 Shwapno (Grocery/Supermarket Style)',
                        'shoppers'    => '💄 Shoppers (Cosmetics/Retail Style)',
                        'athletic'    => '⚡ Athletic (High Energy & Performance)',
                        'daraz'       => '🛍️ Daraz Classic (Marketplace Look)',
                        'pikabo'      => '📱 Pikabo (Pickaboo Style)',
                        'vegist'      => '🌿 Vegist (Organic & Supermarket)',
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

                TextInput::make('topbar_text')
                    ->label('Topbar Text (Left Side)')
                    ->placeholder('স্বাগতম! সব পণ্যে ফ্রি শিপিং সুবিধা।')
                    ->helperText('BDPro, Vegist, Shoppers থিমে topbar-এ বাম পাশে দেখাবে।')
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Shop Info & Social Links')->schema([
                Textarea::make('description')
                    ->label('Shop Description')
                    ->placeholder('আপনার শপের সম্পর্কে সংক্ষিপ্ত বিবরণ লিখুন...')
                    ->helperText('Footer, SEO meta description এবং SEO section-এ দেখাবে।')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('tagline')
                    ->label('Shop Tagline')
                    ->placeholder('দ্রুত ডেলিভারি, আসল পণ্য, সেরা দাম।')
                    ->helperText('Footer copyright section-এ দেখাবে।'),

                TextInput::make('youtube_url')
                    ->label('YouTube URL')
                    ->url()
                    ->placeholder('https://youtube.com/@yourshop')
                    ->helperText('Footer ও social icons-এ দেখাবে।'),

                TextInput::make('tiktok_url')
                    ->label('TikTok URL')
                    ->url()
                    ->placeholder('https://tiktok.com/@yourshop')
                    ->helperText('Daraz ও অন্যান্য থিমে দেখাবে।'),

                TextInput::make('widgets.office_hours.text')
                    ->label('Office Hours / Contact Hours')
                    ->placeholder('Everyday 10AM - 9PM')
                    ->helperText('BDPro, Shoppers থিমে contact section-এ দেখাবে।'),
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
                
            Section::make('🦶 Footer Content Manager')
                ->description('সব থিমের Footer এখান থেকে dynamically কাস্টমাইজ করুন। Menu গুলো Menu Manager থেকে সেট করুন।')
                ->schema([

                    Section::make('Brand & Contact Info')
                        ->description('Footer-এর বাম কলামে দেখাবে।')
                        ->schema([
                            Textarea::make('widgets.footer.brand_description')
                                ->label('Footer Brand Description')
                                ->placeholder('আমরা সেরা মানের পণ্য দ্রুত ডেলিভারি করি। সারা দেশে শিপিং সুবিধা।')
                                ->rows(3)
                                ->helperText('না দিলে shop description ব্যবহার হবে।')
                                ->columnSpanFull(),
                        ])->columns(1)->collapsible(),

                    Section::make('Footer Menu Column Headings')
                        ->description('Footer-এ ৩টি menu column heading কাস্টম করুন (menu items Menu Manager থেকে যোগ করুন।)')
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
                        ])->columns(3)->collapsible(),

                    Section::make('Delivery & Miscellaneous')
                        ->schema([
                            TextInput::make('widgets.delivery_time.text')
                                ->label('Delivery Time Text')
                                ->placeholder('Same day delivery within Dhaka')
                                ->helperText('Product page ও footer-এ delivery info দেখাবে।'),
                            TextInput::make('widgets.footer.contact_title')
                                ->label('Contact Section Heading')
                                ->placeholder('যোগাযোগ করুন'),
                        ])->columns(2)->collapsible(),

                    Section::make('Footer Display Options')
                        ->schema([
                            Toggle::make('widgets.footer.show_payment')
                                ->label('Show Payment Methods in Footer')
                                ->default(true)
                                ->onColor('success')
                                ->helperText('Footer-এ COD, bKash, SSL ইত্যাদি payment badge দেখাবে।'),
                            Toggle::make('widgets.footer.show_social')
                                ->label('Show Social Media Icons in Footer')
                                ->default(true)
                                ->onColor('success')
                                ->helperText('Social links section-এর URL গুলো "Shop Info & Social Links" থেকে দিন।'),
                        ])->columns(2)->collapsible(),

                    Section::make('Copyright & Quick Links')
                        ->schema([
                            Textarea::make('footer_text')
                                ->label('Copyright / Bottom Text')
                                ->placeholder('© 2026 আপনার শপের নাম। সর্বস্বত্ব সংরক্ষিত।')
                                ->helperText('খালি রাখলে automatic copyright text দেখাবে।')
                                ->rows(2)
                                ->columnSpanFull(),
                            Repeater::make('footer_links')
                                ->label('Bottom Quick Links (copyright bar-এ)')
                                ->schema([
                                    TextInput::make('title')->label('Link Title')->required()->placeholder('Privacy Policy'),
                                    TextInput::make('url')->label('Link URL')->url()->required()->placeholder('https://...'),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->addActionLabel('+ Add Quick Link')
                                ->helperText('Privacy Policy, Terms & Conditions ইত্যাদি এখানে যোগ করুন।')
                                ->columnSpanFull(),
                        ])->columns(1)->collapsible(),

                    Section::make('🧩 Trust Badges (Dynamic Items)')
                        ->description('Homepage-এ trust badge section-এর items কাস্টম করুন। খালি রাখলে default 4টি badge দেখাবে।')
                        ->schema([
                            Repeater::make('widgets.trust_badges.items')
                                ->label('Trust Badge Items')
                                ->schema([
                                    TextInput::make('icon')
                                        ->label('Font Awesome Icon')
                                        ->placeholder('fa-truck')
                                        ->helperText('e.g. fa-truck, fa-shield-alt, fa-headset, fa-wallet'),
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
                        ])->columns(1)->collapsible(),

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
                            TextInput::make('widgets.hero_banner.text')
                                ->label('Banner Overlay Text')
                                ->placeholder('BANNER OVERLAY TEXT')
                                ->helperText('ব্যানারের ডান পাশে বড় হরফে যে text দেখাবে।'),
                            TextInput::make('widgets.hero_banner.button_text')
                                ->label('Button Text')
                                ->placeholder('EXPLORE NOW')
                                ->helperText('ব্যানারের নিচের button এর text।'),
                            TextInput::make('widgets.hero_banner.link')->label('Banner/Button Link')->url(),
                            ColorPicker::make('widgets.hero_banner.color')->label('Text/Accent Color'),
                            FileUpload::make('widgets.hero_banner.image')
                                ->label('Hero Banner Background Image')
                                ->image()
                                ->directory('shops/hero-banners')
                                ->maxSize(8192)
                                ->columnSpanFull()
                                ->helperText('📐 Recommended: 1600×600px. এই image টি hero banner এর background এ দেখাবে। না দিলে shop এর general banner ব্যবহার হবে।')
                                ->saveUploadedFileUsing(function ($file) {
                                    try {
                                        return (new \App\Services\ImageOptimizer())->optimize($file, 'shops/hero-banners', 'hero_banner');
                                    } catch (\Exception $e) {
                                        $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                                        $file->storeAs('shops/hero-banners', $filename, 'public');
                                        return 'shops/hero-banners/' . $filename;
                                    }
                                }),
                        ])->columns(2)->collapsible(),

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

                    Section::make('🏅 Trust Bar (Daraz Theme)')
                        ->description('Homepage এর trust strip এর text গুলো customize করুন।')
                        ->schema([
                            Toggle::make('widgets.trust_bar.active')
                                ->label('Enable Trust Bar')
                                ->default(true)
                                ->columnSpanFull()
                                ->onColor('success'),
                            Repeater::make('widgets.trust_bar.items')
                                ->label('Trust Items')
                                ->schema([
                                    TextInput::make('icon')->label('Font Awesome Icon Class')->placeholder('fas fa-check-circle'),
                                    TextInput::make('text')->label('Text')->placeholder('১০০% অরিজিনাল পণ্য'),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->addActionLabel('Add Trust Item')
                                ->columnSpanFull()
                                ->helperText('খালি রাখলে default 4টি trust item দেখাবে।'),
                        ])->columns(1)->collapsible(),

                    Section::make('🛍️ Products Section (Daraz Theme)')
                        ->description('Homepage এ product grid এর title customize করুন।')
                        ->schema([
                            TextInput::make('widgets.products_section.title')
                                ->label('Section Title')
                                ->placeholder('সকল পণ্য')
                                ->helperText('Homepage এ product grid এর উপরে যে title দেখাবে।'),
                        ])->columns(1)->collapsible(),

                    Section::make('🔗 Footer Customization (Daraz Theme)')
                        ->description('Footer এ description text ও contact section heading customize করুন।')
                        ->schema([
                            Textarea::make('widgets.footer.description')
                                ->label('Footer Description Text')
                                ->rows(2)
                                ->placeholder('১০০% আসল পণ্য। সারা দেশে ডেলিভারি। ২৪/৭ কাস্টমার সাপোর্ট।')
                                ->columnSpanFull(),
                            TextInput::make('widgets.footer.contact_title')
                                ->label('Contact Section Heading')
                                ->placeholder('যোগাযোগ'),
                        ])->columns(2)->collapsible(),
                ]),
        ];
    }
}