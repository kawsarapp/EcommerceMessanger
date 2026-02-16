<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload; 
use Filament\Forms\Components\ColorPicker; // ✅ New Feature
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
    protected static ?int $navigationSort = 1;

    // [UX] ড্যাশবোর্ডে ব্যাজ (শুধুমাত্র সুপার অ্যাডমিনের জন্য)
    public static function getNavigationBadge(): ?string
    {
        return auth()->id() === 1 ? (string) static::getModel()::count() : null;
    }

    // [UX] গ্লোবাল সার্চ
    public static function getGloballySearchableAttributes(): array
    {
        return ['shop_name', 'slug', 'fb_page_id', 'custom_domain', 'phone'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- সেকশন ১: সাবস্ক্রিপশন প্ল্যান (Admin Only) ---
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
                            ->required(fn () => auth()->id() === 1)
                            ->disabled(fn () => auth()->id() !== 1)
                            ->dehydrated(fn () => auth()->id() === 1), 

                        DateTimePicker::make('plan_ends_at')
                            ->label('Plan Expiry Date')
                            ->default(now()->addMonth())
                            ->required(fn () => auth()->id() === 1)
                            ->disabled(fn () => auth()->id() !== 1)
                            ->dehydrated(fn () => auth()->id() === 1),
                    ])
                    ->columns(['default' => 1, 'sm' => 2])
                    ->visible(fn () => auth()->id() === 1),

                // --- সেকশন ২: শপ কনফিগারেশন ---
                Forms\Components\Group::make()
                    ->schema([
                        Tabs::make('Shop Configuration')
                            ->persistTabInQueryString()
                            ->tabs([
                                
                                // 🏠 Tab 1: General Info
                                Tabs\Tab::make('Basic Info')
                                    ->icon('heroicon-m-information-circle')
                                    ->schema([
                                        Hidden::make('user_id')->default(auth()->id()), 

                                        Section::make('Identity')
                                            ->schema([
                                                TextInput::make('shop_name')
                                                    ->label('Shop Name')
                                                    ->placeholder('E.g. Fashion BD')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->maxLength(255)
                                                    ->afterStateUpdated(fn ($state, callable $set, $operation) => 
                                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                                                    ),
                                            
                                                TextInput::make('slug')
                                                    ->label('Shop URL')
                                                    ->prefix(config('app.url') . '/shop/')
                                                    ->required()
                                                    ->unique(Client::class, 'slug', ignoreRecord: true)
                                                    ->disabled(fn ($operation) => $operation !== 'create')
                                                    ->dehydrated()
                                                    ->helperText('Unique link for your shop.'),
                                            ])->columns(2),

                                        Section::make('Contact Details')
                                            ->schema([
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
                                    ]),

                                // 🎨 Tab 2: Branding & Design (New Features Added)
                                Tabs\Tab::make('Storefront')
                                    ->icon('heroicon-m-paint-brush')
                                    ->schema([
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

                                        Section::make('Theme & Announcements') // 🔥 New Feature
                                            ->schema([
                                                ColorPicker::make('primary_color')
                                                    ->label('Brand Color')
                                                    ->default('#4f46e5')
                                                    ->helperText('This color will be used for buttons and links.'),

                                                TextInput::make('announcement_text')
                                                    ->label('Announcement Bar')
                                                    ->placeholder('🎉 Eid Sale is Live! Get 10% Off.')
                                                    ->helperText('Shows at the top of your shop header.'),
                                            ])->columns(2),
                                    ]),

                                // 🌐 Tab 3: Domain & SEO
                                Tabs\Tab::make('Domain & SEO')
                                    ->icon('heroicon-m-globe-alt')
                                    ->schema([
                                        Section::make('Custom Domain')
                                            ->description('Connect your own domain (e.g. www.brand.com)')
                                            ->schema([
                                                TextInput::make('custom_domain')
                                                    ->label('Your Domain Name')
                                                    ->placeholder('www.yourbrand.com')
                                                    ->prefixIcon('heroicon-m-globe-alt')
                                                    ->helperText(new HtmlString('<strong>Setup:</strong> Point your domain\'s <code>A Record</code> to our server IP.'))
                                                    ->unique(Client::class, 'custom_domain', ignoreRecord: true),
                                            ]),

                                        Section::make('SEO & Analytics')
                                            ->schema([
                                                TextInput::make('meta_title')
                                                    ->label('Meta Title')
                                                    ->placeholder('Best Online Shop in BD')
                                                    ->maxLength(60),

                                                TextInput::make('pixel_id') // 🔥 New Feature
                                                    ->label('Facebook Pixel ID')
                                                    ->placeholder('1234567890')
                                                    ->numeric(),

                                                Textarea::make('meta_description')
                                                    ->label('Meta Description')
                                                    ->placeholder('Short description for Google search...')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ])->columns(2),
                                    ]),

                                // 🤖 Tab 4: AI Brain
                                Tabs\Tab::make('AI Brain')
                                    ->icon('heroicon-m-cpu-chip')
                                    ->schema([
                                        Section::make('Knowledge Base')
                                            ->description('দোকানের নিয়মকানুন এখানে লিখুন। AI এটি পড়েই কাস্টমারকে উত্তর দিবে।')
                                            ->schema([
                                                Textarea::make('knowledge_base')
                                                    ->label('Shop Policies & FAQs')
                                                    ->placeholder("উদাহরণ:\n১. ডেলিভারি চার্জ ৮০ টাকা।\n২. রিটার্ন পলিসি নেই।\n৩. শুক্রবার বন্ধ।")
                                                    ->rows(6),
                                            ]),
                                        
                                        // ✅ FIXED: Textarea থেকে collapsed() সরিয়ে Section এ দেওয়া হয়েছে
                                        Section::make('Bot Personality')
                                            ->description('Advanced: AI behavior control.')
                                            ->collapsed()
                                            ->schema([
                                                Textarea::make('custom_prompt')
                                                    ->label('Salesman Personality')
                                                    ->placeholder("তুমি একজন ভদ্র সেলসম্যান। কাস্টমারকে 'স্যার' বলে সম্বোধন করবে...")
                                                    ->rows(3),
                                            ]),
                                    ]),

                                // 🚚 Tab 5: Logistics
                                Tabs\Tab::make('Logistics')
                                    ->icon('heroicon-m-truck')
                                    ->schema([
                                        Section::make('Delivery Fees')
                                            ->schema([
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
                                    ]),

                                // 🔗 Tab 6: Integrations
                                    Tabs\Tab::make('Integrations & Social')
                            ->icon('heroicon-m-share')
                            ->schema([
                                // নতুন সোশ্যাল মিডিয়া সেকশন
                                Section::make('Social Media Links')
                                    ->description('লিংক দিলে ফুটারে আইকন দেখাবে।')
                                    ->schema([
                                        TextInput::make('social_facebook')
                                            ->label('Facebook Page URL')
                                            ->prefixIcon('heroicon-m-globe-alt')
                                            ->placeholder('https://facebook.com/your-page'),
                                        
                                        TextInput::make('social_instagram')
                                            ->label('Instagram Profile URL')
                                            ->prefixIcon('heroicon-m-camera')
                                            ->placeholder('https://instagram.com/your-brand'),

                                        TextInput::make('social_youtube')
                                            ->label('YouTube Channel URL')
                                            ->prefixIcon('heroicon-m-play')
                                            ->placeholder('https://youtube.com/@channel'),
                                    ])->columns(2),

                                        Section::make('Facebook Connection')
                                            ->schema([
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
                                                        TextInput::make('fb_page_id')->label('Page ID')->numeric(),
                                                        Textarea::make('fb_page_token')->label('Access Token')->rows(2),
                                                    ]),
                                            ]),

                                        Section::make('Telegram Notification')
                                            ->description('Get order alerts on Telegram.')
                                            ->collapsed()
                                            ->schema([
                                                Placeholder::make('tutorial')
                                                    ->label('')
                                                    ->content(new HtmlString('<div class="text-sm text-gray-600 bg-gray-50 p-2 rounded">Step 1: Create bot on @BotFather.<br>Step 2: Get Token & Chat ID from @userinfobot.</div>')),

                                                TextInput::make('telegram_bot_token')->label('Bot Token')->password()->revealable(),
                                                TextInput::make('telegram_chat_id')->label('Chat ID'),
                                                
                                                Actions::make([
                                                    Action::make('test_telegram')
                                                        ->label('Test Message')
                                                        ->color('success')
                                                        ->icon('heroicon-m-paper-airplane')
                                                        ->action(function ($get) {
                                                            $token = $get('telegram_bot_token');
                                                            $chatId = $get('telegram_chat_id');
                                                            if (!$token || !$chatId) {
                                                                Notification::make()->title('Missing Info')->danger()->send();
                                                                return;
                                                            }
                                                            try {
                                                                Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                                                                    'chat_id' => $chatId, 'text' => "✅ Test Successful!"
                                                                ]);
                                                                Notification::make()->title('Sent! Check Telegram.')->success()->send();
                                                            } catch (\Exception $e) {
                                                                Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                                                            }
                                                        }),
                                                ]),
                                            ]),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-shop.png'))
                    ->label('Logo'),

                TextColumn::make('shop_name')
                    ->searchable()
                    ->weight('bold')
                    ->sortable()
                    ->description(fn (Client $record) => $record->custom_domain ?: $record->slug),
                    
                TextColumn::make('plan.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pro', 'Premium' => 'warning',
                        'Basic' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('telegram_bot_token')
                    ->label('Telegram')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Setup Needed')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('webhook_verified_at')
                    ->label('Facebook')
                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Pending')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-badge' : 'heroicon-m-clock'),

                ToggleColumn::make('status')
                    ->label('Active')
                    ->visible(fn () => auth()->id() === 1),

                TextColumn::make('created_at')
                    ->dateTime('d M, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Visit Shop')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Client $record) => $record->custom_domain ? "https://{$record->custom_domain}" : route('shop.show', $record->slug))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->id() === 1) return $query;
        return $query->where('user_id', auth()->id());
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool 
    { 
        return false; 
    } 
    
    public static function canDelete(Model $record): bool 
    { 
        return auth()->id() === 1; 
    }
    
    public static function canEdit(Model $record): bool
    {
        return auth()->id() === 1 || $record->user_id === auth()->id();
    }
}