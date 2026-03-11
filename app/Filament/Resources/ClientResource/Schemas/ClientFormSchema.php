<?php

namespace App\Filament\Resources\ClientResource\Schemas;

use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class ClientFormSchema
{
    public static function schema(): array
    {
        $isAdmin = fn () => auth()->id() === 1;
        $isNotAdmin = fn () => auth()->id() !== 1;

        return [
            // --- Section 1: Subscription Plan (Admin Only) ---
            Section::make('Subscription Plan')
                ->description('User subscription & limitations control.')
                ->icon('heroicon-m-credit-card')
                ->collapsible()
                ->schema([
                    Select::make('plan_id')
                        ->label('Assigned Plan')
                        ->relationship('plan', 'name')
                        ->preload()
                        ->searchable()
                        ->required($isAdmin)
                        ->disabled($isNotAdmin)
                        ->dehydrated($isAdmin),

                    DateTimePicker::make('plan_ends_at')
                        ->label('Plan Expiry Date')
                        ->default(now()->addMonth())
                        ->required($isAdmin)
                        ->disabled($isNotAdmin)
                        ->dehydrated($isAdmin),
                ])
                ->columns(['default' => 1, 'sm' => 2])
                ->visible($isAdmin),

            // --- Section 2: Shop Configuration (Tabs) ---
            Group::make()->schema([
                Tabs::make('Shop Configuration')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Basic Info')->icon('heroicon-m-information-circle')->schema(self::basicInfo()),
                        Tab::make('Storefront')->icon('heroicon-m-paint-brush')->schema(self::storefront()),
                        Tab::make('Domain & SEO')->icon('heroicon-m-globe-alt')->schema(self::domainSeo()),
                        Tab::make('AI Brain & Automation')->icon('heroicon-m-cpu-chip')->schema(self::aiBrain()),
                        Tab::make('Logistics')->icon('heroicon-m-truck')->schema(self::logistics()),
                        Tab::make('Courier API')->icon('heroicon-m-archive-box-arrow-down')->schema(self::courierApi()),
                        Tab::make('Integrations & Social')->icon('heroicon-m-share')->schema(self::integrations()),

                        // 💬 Tab 8: Inbox Automation
                        Tab::make('Inbox Automation')
                            ->icon('heroicon-m-chat-bubble-left-right')
                            ->schema([
                                Section::make('AI Comment & Inbox Automation')
                                    ->description('ফেসবুক পেইজের কমেন্টে অটো-রিপ্লাই এবং ইনবক্স মেসেজ সেটআপ করুন।')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->schema([
                                        Group::make()->schema([
                                            Toggle::make('auto_comment_reply')
                                                ->label('Auto Comment Reply')
                                                ->helperText('AI নিজে থেকে কাস্টমারের কমেন্টের নিচে রিপ্লাই দিবে।')
                                                ->default(true),

                                            Toggle::make('auto_private_reply')
                                                ->label('Auto Inbox Message (PM)')
                                                ->helperText('কমেন্টকারীকে AI সরাসরি মেসেঞ্জারে মেসেজ পাঠাবে।')
                                                ->default(true),
                                        ])->columns(2),
                                    ]),
                                
                                Toggle::make('auto_status_update_msg')
                                    ->label('Auto Order Status SMS (Messenger/IG)')
                                    ->helperText('ড্যাশবোর্ড থেকে অর্ডারের স্ট্যাটাস পরিবর্তন করলে কাস্টমার অটোমেটিক মেসেজ পাবে।')
                                    ->default(true),
                            ]),

                        // 🔄 Tab 9: Store Sync (WooCommerce/Shopify)
                        Tab::make('Store Sync')
                            ->icon('heroicon-m-arrow-path-rounded-square')
                            ->schema([
                                Section::make('WooCommerce Sync (WordPress)')
                                    ->description('আপনার ওয়ার্ডপ্রেস ওয়েবসাইটের প্রোডাক্ট এক ক্লিকে এখানে ইমপোর্ট করুন।')
                                    ->collapsed()
                                    ->schema([
                                        TextInput::make('wc_store_url')->label('Store URL')->url(),
                                        TextInput::make('wc_consumer_key')->label('Consumer Key')->password()->revealable(),
                                        TextInput::make('wc_consumer_secret')->label('Consumer Secret')->password()->revealable(),
                                    ])->columns(3),

                                Section::make('Shopify Sync')
                                    ->description('আপনার শপিফাই স্টোরের প্রোডাক্ট ইমপোর্ট করুন।')
                                    ->collapsed()
                                    ->schema([
                                        TextInput::make('shopify_store_url')->label('Shopify Store Domain')->placeholder('your-store.myshopify.com')->url(),
                                        TextInput::make('shopify_access_token')->label('Admin API Access Token')->password()->revealable(),
                                    ])->columns(2),


                                        TextInput::make('api_token')
                                    ->label('Webhook API Token (WooCommerce/Shopify)')
                                    ->readOnly()
                                    ->suffixAction(
                                        Action::make('copy_token')
                                            ->icon('heroicon-m-clipboard')
                                            ->color('success')
                                            ->action(function ($livewire, $state) {
                                                $livewire->js("window.navigator.clipboard.writeText('{$state}')");
                                                Notification::make()->title('Token copied to clipboard!')->success()->send();
                                            })
                                    )
                                    ->helperText('Ei token ti WordPress er webhook delivery URL e ?api_key= er pore boshaben.'),
                            ]),

                        // 🟢 Tab 10: WhatsApp Integration
                        Tab::make('WhatsApp API')
                            ->icon('heroicon-m-chat-bubble-oval-left-ellipsis')
                            ->schema([
                                Toggle::make('is_whatsapp_active')
                                    ->label('Enable WhatsApp AI Bot')
                                    ->helperText('আপনার কাস্টমারদের হোয়াটসঅ্যাপে অটোমেটিক রিপ্লাই দেওয়ার জন্য এটি চালু করুন।')
                                    ->onColor('success')
                                    ->live(),

                                Radio::make('whatsapp_type')
                                    ->label('Select Connection Method')
                                    ->options([
                                        'unofficial' => '📱 QR Code Scan (Free & Easy for Small Business)',
                                        'official' => '🏢 Official Meta Cloud API (For Verified Business)',
                                    ])
                                    ->descriptions([
                                        'unofficial' => 'আপনার ফোন থেকে হোয়াটসঅ্যাপ ওয়েব স্ক্যান করে কানেক্ট করুন। কোনো বিজনেস ভেরিফিকেশন লাগবে না।',
                                        'official' => 'ফেসবুক ডেভেলপার প্যানেল থেকে টোকেন এনে বসান। ১০০% সিকিউর, তবে প্রতি মেসেজে মেটাকে পে করতে হবে।',
                                    ])
                                    ->visible(fn (Get $get) => $get('is_whatsapp_active'))
                                    ->live()
                                    ->required(fn (Get $get) => $get('is_whatsapp_active')),

                                // 🟢 Unofficial Setup (QR Code)
                                Section::make('QR Code Setup (Device Link)')
                                    ->visible(fn (Get $get) => $get('whatsapp_type') === 'unofficial' && $get('is_whatsapp_active'))
                                    ->schema([
                                        Placeholder::make('qr_note')
                                            ->label('Status')
                                            ->content(fn ($record) => $record && $record->wa_status === 'connected' 
                                                ? new HtmlString('<span class="text-green-600 font-bold">✅ WhatsApp is Connected! AI is ready to reply.</span>') 
                                                : new HtmlString('<span class="text-red-500 font-bold">❌ Disconnected. Please connect your device.</span>')
                                            ),
                                            
                                        Actions::make([
                                            Action::make('generate_qr')
                                                ->label('Generate QR Code')
                                                ->icon('heroicon-o-qr-code')
                                                ->color('info')
                                                ->action(function ($record, Set $set) {
                                                    if (!$record) {
                                                        Notification::make()->title('দয়া করে আগে শপটি Save করুন!')->warning()->send();
                                                        return;
                                                    }
                                                    try {
                                                        $instanceId = 'client_' . $record->id;
                                                        $response = Http::post('http://127.0.0.1:3001/api/generate-qr', ['instance_id' => $instanceId]);
                                                        if ($response->successful()) {
                                                            $data = $response->json();
                                                            if (isset($data['status']) && $data['status'] === 'connected') {
                                                                $record->update(['wa_status' => 'connected', 'wa_instance_id' => $instanceId]);
                                                                Notification::make()->title('Already Connected!')->success()->send();
                                                            } elseif (isset($data['qr_code'])) {
                                                                $record->update(['wa_instance_id' => $instanceId]);
                                                                $set('generated_qr_code', $data['qr_code']);
                                                                Notification::make()->title('QR Code Generated. Please Scan!')->success()->send();
                                                            }
                                                        } else {
                                                            Notification::make()->title('Failed to get QR Code.')->danger()->send();
                                                        }
                                                    } catch (\Exception $e) {
                                                        Notification::make()->title('Error: Node Server is not running!')->danger()->send();
                                                    }
                                                })
                                                ->hidden(fn ($record) => $record && $record->wa_status === 'connected'),
                                                
                                            // 🔥 নতুন Disconnect বাটন (With Node.js Logout API)
                                            Action::make('disconnect_wa')
                                                ->label('Disconnect & Rescan')
                                                ->icon('heroicon-o-x-circle')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->action(function ($record, Set $set) {
                                                    if ($record) {
                                                        // ১. Node সার্ভারকে সেশন ডিলিট করার নির্দেশ দেওয়া
                                                        try {
                                                            $instanceId = $record->wa_instance_id ?? ('client_' . $record->id);
                                                            Http::post('http://127.0.0.1:3001/api/disconnect', [
                                                                'instance_id' => $instanceId
                                                            ]);
                                                        } catch (\Exception $e) {
                                                            // Node Server Offline থাকলেও সমস্যা নেই
                                                        }
                                                        
                                                        // ২. ডাটাবেস আপডেট করা
                                                        $record->update(['wa_status' => 'disconnected', 'wa_instance_id' => null]);
                                                        $set('generated_qr_code', null);
                                                        
                                                        Notification::make()->title('Disconnected successfully! You can now generate a new QR.')->warning()->send();
                                                    }
                                                })
                                                ->visible(fn ($record) => $record && $record->wa_status === 'connected')
                                        ]),
                                        
                                        Hidden::make('generated_qr_code')->dehydrated(false),
                            
                                        Placeholder::make('qr_display')
                                            ->label('Scan this QR Code using WhatsApp')
                                            ->visible(fn (Get $get) => $get('generated_qr_code') !== null)
                                            ->content(fn (Get $get) => new HtmlString('
                                                <div class="text-center bg-gray-50 p-6 rounded-2xl border border-gray-200 inline-block w-full max-w-sm">
                                                    <img src="' . $get('generated_qr_code') . '" style="width: 250px; height: 250px; margin: 0 auto; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);" />
                                                    <p class="text-sm text-gray-600 font-bold mt-4 animate-pulse">⏳ স্ক্যান করার জন্য অপেক্ষা করছি...</p>
                                                    <p class="text-xs text-gray-400 mt-1">আপনার মোবাইল থেকে Linked Devices এ গিয়ে স্ক্যান করুন।</p>
                                                </div>
                                            ')),
                                    ]),

                                // 🟢 Official Setup (Meta API)
                                Section::make('Official Meta API Setup')
                                    ->visible(fn (Get $get) => $get('whatsapp_type') === 'official' && $get('is_whatsapp_active'))
                                    ->schema([
                                        TextInput::make('wa_phone_number_id')
                                            ->label('Phone Number ID')
                                            ->placeholder('E.g. 102345678901234')
                                            ->required(fn (Get $get) => $get('whatsapp_type') === 'official'),
                                            
                                        Textarea::make('wa_access_token')
                                            ->label('Permanent Access Token')
                                            ->placeholder('E.g. EAAGm0... ')
                                            ->rows(3)
                                            ->required(fn (Get $get) => $get('whatsapp_type') === 'official'),
                                    ])->columns(2),

                            
                            ]),
                    ])
                    ->columnSpanFull(),
            ])->columnSpanFull(),
        ];
    }

    private static function basicInfo(): array
    {
        return [
            Hidden::make('user_id')->default(auth()->id()),
            Section::make('Identity')->schema([
                TextInput::make('shop_name')
                    ->label('Shop Name')
                    ->placeholder('Eg. Fashion BD')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn ($state, callable $set, $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                
                TextInput::make('slug')
                    ->label('Shop URL')
                    ->prefix(config('app.url') . '/shop/')
                    ->required()
                    ->live(onBlur: true)
                    ->unique(Client::class, 'slug', ignoreRecord: true)
                    ->helperText('Unique link for your shop. You can customize it!'),    
            ])->columns(2),

            Section::make('Contact Details')->schema([
                TextInput::make('phone')
                    ->label('Support Phone')
                    ->tel()
                    ->prefixIcon('heroicon-m-phone')
                    ->placeholder('017XXXXXXXX'),
                
                Textarea::make('address')
                    ->label('Shop Address')
                    ->rows(2)
                    ->placeholder('Full address for invoice...'),
            ])->columns(2),

            ToggleButtons::make('status')
                ->label('Shop Status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->colors(['active' => 'success', 'inactive' => 'danger'])
                ->icons(['active' => 'heroicon-o-check-circle', 'inactive' => 'heroicon-o-x-circle'])
                ->default('active')
                ->inline()
                ->visible(fn () => auth()->id() === 1),
        ];
    }

    private static function storefront(): array
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
                ColorPicker::make('primary_color')
                    ->label('Brand Color')
                    ->default('#4f46e5')
                    ->helperText('This color will be used for buttons and links.'),
                
                TextInput::make('announcement_text')
                    ->label('Announcement Bar')
                    ->placeholder('🎉 Eid Sale is Live! Get 10% Off.')
                    ->helperText('Shows at the top of your shop header.'),
            ])->columns(2),
        ];
    }
private static function domainSeo(): array
    {
        return [
            Section::make('Custom Domain Setup')
                ->description('আপনার শপের জন্য নিজস্ব ডোমেইন (e.g. yourbrand.com) সেটআপ করুন।')
                ->icon('heroicon-m-globe-alt')
                ->schema([
                    TextInput::make('custom_domain')
                        ->label('Your Domain Name')
                        ->placeholder('yourbrand.com (without https://)')
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->unique(Client::class, 'custom_domain', ignoreRecord: true)
                        ->suffixAction(
                            Action::make('verify_domain')
                                ->icon('heroicon-m-check-badge')
                                ->color('success')
                                ->label('Verify Setup')
                                ->action(function ($state, $livewire) {
                                    if (!$state) {
                                        Notification::make()->title('Please enter a domain first.')->warning()->send();
                                        return;
                                    }
                                    
                                    $domain = preg_replace('/^https?:\/\//', '', $state);
                                    $domain = trim($domain, '/');
                                    
                                    try {
                                        // 🔥 FIX: Cloudflare এর বদলে আসল cPanel IP এবং CNAME চেক করা
                                        $realServerIp = '198.38.91.154'; 
                                        $mainDomain = 'asianhost.net';
                                        
                                        $recordsA = dns_get_record($domain, DNS_A);
                                        $recordsCNAME = dns_get_record($domain, DNS_CNAME);
                                        
                                        $isMatched = false;

                                        // ১. CNAME চেক
                                        foreach ($recordsCNAME as $record) {
                                            if (isset($record['target']) && $record['target'] === $mainDomain) {
                                                $isMatched = true; break;
                                            }
                                        }

                                        // ২. A Record (আসল IP) চেক
                                        if (!$isMatched) {
                                            foreach ($recordsA as $record) {
                                                if (isset($record['ip']) && $record['ip'] === $realServerIp) {
                                                    $isMatched = true; break;
                                                }
                                            }
                                        }

                                        if ($isMatched) {
                                            Notification::make()->title('✅ Domain Verified!')->body('DNS record is perfectly pointing to our server.')->success()->send();
                                        } else {
                                            Notification::make()->title('❌ DNS Not Matched!')->body("Please point your domain to IP: {$realServerIp} or add a CNAME for {$mainDomain}.")->danger()->send();
                                        }
                                    } catch (\Exception $e) {
                                        Notification::make()->title('❌ Verification Failed')->body('Could not check DNS records.')->danger()->send();
                                    }
                                })
                        ),

                    Placeholder::make('dns_instructions')
                        ->label('DNS Setup Instructions (অবশ্যই করণীয়)')
                        ->content(function () {
                            // 🔥 FIX: হার্ডকোডেড আসল সার্ভার IP
                            $realServerIp = '198.38.91.154';
                            return new HtmlString('
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-sm text-gray-800 shadow-sm mt-2">
                                    <p class="mb-3 font-bold text-blue-800"><i class="fas fa-info-circle"></i> ডোমেইন কানেক্ট করার নিয়ম:</p>
                                    <p class="mb-4">আপনার ডোমেইন কন্ট্রোল প্যানেলে (যেমন: Cloudflare, Namecheap) গিয়ে DNS Settings থেকে নিচের <strong>A Record</strong> অথবা <strong>CNAME Record</strong> যুক্ত করুন:</p>
                                    
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
                                            <thead>
                                                <tr class="bg-gray-100 text-gray-700">
                                                    <th class="border-b p-3 font-bold">Type</th>
                                                    <th class="border-b p-3 font-bold">Name / Host</th>
                                                    <th class="border-b p-3 font-bold">Value / Target</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="border-b p-3 font-bold text-blue-600">A Record</td>
                                                    <td class="border-b p-3">@ (বা আপনার ডোমেইন নাম)</td>
                                                    <td class="border-b p-3 font-mono font-bold text-green-600">' . $realServerIp . '</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-center text-xs text-gray-400 py-1 bg-gray-50">অথবা (যেকোনো একটি ব্যবহার করুন)</td>
                                                </tr>
                                                <tr>
                                                    <td class="border-b p-3 font-bold text-blue-600">CNAME</td>
                                                    <td class="border-b p-3">www (বা সাবডোমেইন)</td>
                                                    <td class="border-b p-3 font-mono font-bold text-green-600">asianhost.net</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="mt-4 text-xs text-red-500 font-bold">* Cloudflare ব্যবহার করলে প্রথমে "Proxy Status" বন্ধ (DNS Only) করে Verify করুন।</p>
                                </div>
                            ');
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('SEO & Analytics')->schema([
                TextInput::make('meta_title')
                    ->label('Meta Title')
                    ->placeholder('Best Online Shop in BD')
                    ->maxLength(60),
                TextInput::make('pixel_id')
                    ->label('Facebook Pixel ID')
                    ->numeric(),
                Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ];
    }
    
    private static function aiBrain(): array
    {
        return [
            Section::make('Knowledge Base')
                ->description('দোকানের নিয়মকানুন এখানে লিখুন। AI এটি পড়েই কাস্টমারকে উত্তর দিবে।')
                ->schema([
                    Textarea::make('knowledge_base')
                        ->label('Shop Policies & FAQs')
                        ->placeholder("উদাহরণ:\n১. ডেলিভারি চার্জ ৮০ টাকা।\n২. রিটার্ন পলিসি নেই।\n৩. শুক্রবার বন্ধ।")
                        ->rows(6),
                ]),

            Section::make('Bot Personality')
                ->description('Advanced: AI behavior control.')
                ->collapsed()
                ->schema([
                    Textarea::make('custom_prompt')
                        ->label('Salesman Personality')
                        ->placeholder("তুমি একজন ভদ্র সেলসম্যান। কাস্টমারকে 'স্যার' বলে সম্বোধন করবে...")
                        ->rows(3),
                ]),

            Section::make('Abandoned Cart Automation')
                ->description('অসম্পূর্ণ অর্ডারগুলো রিকভার করতে এআই রিমাইন্ডার সেটআপ করুন।')
                ->schema([
                    Toggle::make('is_reminder_active')
                        ->label('Enable AI Follow-up')
                        ->onColor('success')
                        ->offColor('danger')
                        ->inline(false),
                    
                    Select::make('reminder_delay_hours')
                        ->label('Send Reminder After')
                        ->options([1 => '1 Hour', 2 => '2 Hours', 6 => '6 Hours', 12 => '12 Hours', 24 => '24 Hours'])
                        ->default(2)
                        ->required()
                        ->visible(fn (callable $get) => $get('is_reminder_active')),
                ])->columns(2),

            Section::make('Post-Purchase Auto Review')
                ->description('অর্ডার ডেলিভারি হওয়ার পর কাস্টমারের কাছ থেকে অটোমেটিক রিভিউ সংগ্রহ করুন।')
                ->schema([
                    Toggle::make('is_review_collection_active')
                        ->label('Enable Auto Review Request')
                        ->default(true),
                    
                    Select::make('review_delay_days')
                        ->label('Ask for review after (Days)')
                        ->options([1 => '1 Day', 2 => '2 Days', 3 => '3 Days', 5 => '5 Days', 7 => '7 Days'])
                        ->default(3)
                        ->required()
                        ->visible(fn (callable $get) => $get('is_review_collection_active')),
                ])->columns(2),
        ];
    }

    private static function logistics(): array
    {
        return [
            Section::make('Delivery Fees')->schema([
                TextInput::make('delivery_charge_inside')
                    ->label('Inside Dhaka')
                    ->numeric()
                    ->prefix('৳')
                    ->default(80)
                    ->required(),
                
                TextInput::make('delivery_charge_outside')
                    ->label('Outside Dhaka')
                    ->numeric()
                    ->prefix('৳')
                    ->default(150)
                    ->required(),
            ])->columns(2),
        ];
    }

    private static function courierApi(): array
    {
        return [
            Section::make('📖 কুরিয়ার এপিআই নির্দেশিকা (Help Note)')
                ->description('অটোমেটিক পার্সেল এন্ট্রি এবং স্ট্যাটাস আপডেটের জন্য নিচের নিয়মগুলো মেনে চলুন।')
                ->schema([
                    Placeholder::make('instruction')
                        ->label('')
                        ->content(new HtmlString('
                            <ul class="list-disc pl-5 text-sm text-gray-600 bg-gray-50 p-4 rounded-lg">
                                <li><strong>API Key:</strong> আপনার কুরিয়ার প্যানেল (Steadfast/Pathao) থেকে API Key কপি করে নিচের ফর্মে বসান।</li>
                                <li><strong>অটো স্ট্যাটাস আপডেট:</strong> কুরিয়ার যখন পার্সেল ডেলিভারি করবে, ড্যাশবোর্ডে স্ট্যাটাস নিজে থেকেই আপডেট হওয়ার জন্য নিচের Webhook URL টি আপনার কুরিয়ার প্যানেলের Webhook সেটিংসে বসান।</li>
                                <li class="text-red-500"><strong>সতর্কতা:</strong> আপনার Webhook URL টি কাউকে শেয়ার করবেন না। এটি শুধু আপনার দোকানের জন্যই তৈরি করা হয়েছে।</li>
                            </ul>
                        ')),
                    
                    TextInput::make('webhook_url_display')
                        ->label('Your Unique Steadfast Webhook URL')
                        ->default(fn ($record) => $record ? url("/api/webhook/courier/{$record->id}/steadfast") : 'দোকান সেভ করার পর URL তৈরি হবে')
                        ->readOnly()
                        ->suffixAction(
                            Action::make('copy')
                                ->icon('heroicon-m-clipboard')
                                ->action(fn ($livewire, $state) => $livewire->js("window.navigator.clipboard.writeText('{$state}')"))
                        ),
                ]),

            Section::make('Default Courier')
                ->description('অর্ডার শিপমেন্টের জন্য ডিফল্ট কুরিয়ার সিলেক্ট করুন।')
                ->schema([
                    Select::make('default_courier')
                        ->options([
                            'steadfast' => 'Steadfast Courier',
                            'pathao' => 'Pathao Courier',
                            'redx' => 'RedX Courier',
                        ])
                        ->placeholder('Select your preferred courier')
                        ->columnSpanFull(),
                ]),

            Section::make('Steadfast Setup')
                ->collapsed()
                ->icon('heroicon-o-key')
                ->schema([
                    TextInput::make('steadfast_api_key')
                        ->label('Api Key')
                        ->password()
                        ->revealable(),
                    TextInput::make('steadfast_secret_key')
                        ->label('Secret Key')
                        ->password()
                        ->revealable(),
                ])->columns(2),

            Section::make('Pathao Setup')
                ->collapsed()
                ->icon('heroicon-o-key')
                ->schema([
                    TextInput::make('pathao_api_key')
                        ->label('Client ID / API Key')
                        ->password()
                        ->revealable(),
                    TextInput::make('pathao_store_id')
                        ->label('Store ID'),
                ])->columns(2),

            Section::make('RedX Setup')
                ->collapsed()
                ->icon('heroicon-o-key')
                ->schema([
                    TextInput::make('redx_api_token')
                        ->label('API Access Token')
                        ->password()
                        ->revealable()
                        ->columnSpanFull(),
                ]),
        ];
    }

    private static function integrations(): array
    {
        return [
            Section::make('Omnichannel Chatbot Integrations')
                ->description('আপনার শপের মেসেজগুলো কোন কোন প্ল্যাটফর্ম থেকে AI হ্যান্ডেল করবে তা সেটআপ করুন।')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->collapsible()
                ->schema([
                    Group::make([
                        Placeholder::make('messenger_info')
                            ->label('🔵 Facebook Messenger')
                            ->content('ফেসবুক মেসেঞ্জার অটোমেটিক্যালি কানেক্টেড আছে (OAuth এর মাধ্যমে)।'),
                        TextInput::make('fb_page_id')
                            ->label('Facebook Page ID')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(1),

                    Group::make([
                        Toggle::make('is_instagram_active')
                            ->label('🟣 Enable Instagram AI')
                            ->helperText('ইনস্টাগ্রাম ডিএম (DM) এর জন্য চ্যাটবট চালু করুন।')
                            ->onColor('success')
                            ->offColor('gray')
                            ->live()
                            ->inline(false),
                        TextInput::make('ig_account_id')
                            ->label('Instagram Account ID')
                            ->placeholder('e.g., 178414000000000')
                            ->prefixIcon('heroicon-o-camera')
                            ->visible(fn (Get $get): bool => $get('is_instagram_active'))
                            ->required(fn (Get $get): bool => $get('is_instagram_active')),
                    ])->columns(1),

                    Group::make([
                        Toggle::make('is_telegram_active')
                            ->label('✈️ Enable Telegram AI')
                            ->helperText('Telegram Bot e customer der jonno AI chalu korun.')
                            ->onColor('success')
                            ->offColor('gray')
                            ->inline(false),
                        TextInput::make('telegram_bot_token')
                            ->label('Telegram Bot Token')
                            ->placeholder('e.g., 123456:ABC...')
                            ->password()
                            ->revealable()
                            ->prefixIcon('heroicon-o-key'),
                    ])->columns(1),
                ])->columns(2),

            Section::make('Social Media Links')
                ->description('লিংক দিলে ফুটারে আইকন দেখাবে।')
                ->schema([
                    TextInput::make('social_facebook')
                        ->label('Facebook Page URL')
                        ->prefixIcon('heroicon-m-globe-alt'),
                    TextInput::make('social_instagram')
                        ->label('Instagram Profile URL')
                        ->prefixIcon('heroicon-m-camera'),
                    TextInput::make('social_youtube')
                        ->label('YouTube Channel URL')
                        ->prefixIcon('heroicon-m-play'),
                ])->columns(2),

            Section::make('Facebook Connection')->schema([
                Placeholder::make('fb_status')
                    ->label('Status')
                    ->content(fn ($record) => $record && $record->fb_page_id
                        ? new HtmlString('<span class="text-green-600 font-bold flex items-center gap-1">✅ Connected to Page ID: ' . $record->fb_page_id . '</span>')
                        : new HtmlString('<span class="text-gray-500">❌ Not Connected</span>')),
                
                Actions::make([
                    Action::make('connect_facebook')
                        ->label('Connect with Facebook')
                        ->url(fn ($record) => route('auth.facebook', ['client_id' => $record->id]))
                        ->color('info')
                        ->visible(fn ($record) => !$record->fb_page_id),
                    Action::make('disconnect_facebook')
                        ->label('Disconnect Page')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['fb_page_id' => null, 'fb_page_token' => null]))
                        ->visible(fn ($record) => $record->fb_page_id),
                ]),

                Section::make('Advanced Manual Setup')
                    ->collapsed()
                    ->schema([
                        TextInput::make('fb_verify_token')
                            ->label('Webhook Token')
                            ->readOnly()
                            ->suffixActions([
                                Action::make('regenerate')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(fn ($set) => $set('fb_verify_token', Str::random(40))),
                                Action::make('copy')
                                    ->icon('heroicon-m-clipboard')
                                    ->action(fn ($livewire, $state) => $livewire->js("window.navigator.clipboard.writeText('{$state}')")),
                            ]),
                        TextInput::make('fb_page_id')
                            ->label('Page ID (Manual)')
                            ->numeric(),
                        Textarea::make('fb_page_token')
                            ->label('Access Token')
                            ->rows(2),
                    ]),
            ]),

            Section::make('Telegram Notification')
                ->description('Get order alerts on Telegram.')
                ->collapsed()
                ->schema([
                    Placeholder::make('tutorial')
                        ->label('')
                        ->content(new HtmlString('<div class="text-sm text-gray-600 bg-gray-50 p-2 rounded">Step 1: Create bot on @BotFather.<br>Step 2: Get Token & Chat ID from @userinfobot.</div>')),
                    TextInput::make('telegram_chat_id')
                        ->label('Chat ID'),
                    Actions::make([
                        Action::make('test_telegram')
                            ->label('Test Message')
                            ->color('success')
                            ->icon('heroicon-m-paper-airplane')
                            ->action(function (Get $get) {
                                $token = $get('telegram_bot_token');
                                $chatId = $get('telegram_chat_id');
                                if (!$token || !$chatId) {
                                    Notification::make()->title('Missing Info')->danger()->send();
                                    return;
                                }
                                try {
                                    Http::post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chatId, 'text' => "✅ Test Successful!"]);
                                    Notification::make()->title('Sent! Check Telegram.')->success()->send();
                                } catch (\Exception $e) {
                                    Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                                }
                            }),
                    ]),
                ]),
        ];
    }
}