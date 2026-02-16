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
use Filament\Forms\Components\FileUpload; // ✅ Logo/Banner এর জন্য
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ImageColumn; // ✅ Table এ লোগো দেখার জন্য
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

    // [UX] গ্লোবাল সার্চ (Domain, Name, Slug দিয়ে খোঁজা যাবে)
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

                // --- সেকশন ২: শপ কনফিগারেশন (All Tabs) ---
                Forms\Components\Group::make()
                    ->schema([
                        Tabs::make('Shop Configuration')
                            ->persistTabInQueryString() // রিফ্রেশ দিলেও ট্যাব হারাবে না
                            ->tabs([
                                
                                // 🏠 Tab 1: General Info & Contact
                                Tabs\Tab::make('General Info')
                                    ->icon('heroicon-m-information-circle')
                                    ->schema([
                                        Hidden::make('user_id')->default(auth()->id()), 

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
                                            ->label('Shop URL Slug')
                                            ->prefix(config('app.url') . '/shop/')
                                            ->required()
                                            ->unique(Client::class, 'slug', ignoreRecord: true)
                                            ->disabled(fn ($operation) => $operation !== 'create')
                                            ->dehydrated()
                                            ->helperText('Unique link for the shop.'),

                                        // 🔥 Contact Info
                                        TextInput::make('phone')
                                            ->label('Support Phone')
                                            ->tel()
                                            ->prefixIcon('heroicon-m-phone'),

                                        Textarea::make('address')
                                            ->label('Shop Address')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        
                                        // Status Control
                                        ToggleButtons::make('status')
                                            ->label('Shop Status')
                                            ->options([
                                                'active' => 'Active',
                                                'inactive' => 'Inactive',
                                            ])
                                            ->colors([
                                                'active' => 'success',
                                                'inactive' => 'danger',
                                            ])
                                            ->icons([
                                                'active' => 'heroicon-o-check-circle',
                                                'inactive' => 'heroicon-o-x-circle',
                                            ])
                                            ->default('active')
                                            ->inline()
                                            ->visible(fn () => auth()->id() === 1),
                                    ])->columns(2),

                                // 🎨 Tab 2: Storefront & Branding (Logo/Domain)
                                Tabs\Tab::make('Storefront & Branding')
                                    ->icon('heroicon-m-paint-brush')
                                    ->schema([
                                        Section::make('Custom Domain')
                                            ->description('Connect your own domain (e.g. www.brand.com)')
                                            ->schema([
                                                TextInput::make('custom_domain')
                                                    ->label('Domain Name')
                                                    ->placeholder('www.yourbrand.com')
                                                    ->prefixIcon('heroicon-m-globe-alt')
                                                    ->helperText('Point A Record to Server IP. Do not add http/https.')
                                                    ->unique(Client::class, 'custom_domain', ignoreRecord: true),
                                            ]),

                                        Section::make('Visuals')
                                            ->schema([
                                                FileUpload::make('logo')
                                                    ->label('Shop Logo')
                                                    ->image()
                                                    ->avatar()
                                                    ->directory('shops/logos')
                                                    ->maxSize(2048), // 2MB

                                                FileUpload::make('banner')
                                                    ->label('Shop Banner (Cover)')
                                                    ->image()
                                                    ->directory('shops/banners')
                                                    ->maxSize(5120) // 5MB
                                                    ->columnSpanFull(),
                                            ])->columns(2),
                                    ]),

                                // 🔍 Tab 3: SEO & Marketing
                                Tabs\Tab::make('SEO Settings')
                                    ->icon('heroicon-m-magnifying-glass')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->placeholder('Best Online Shop in BD')
                                            ->maxLength(60),

                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->placeholder('Short description for Google search results...')
                                            ->rows(3)
                                            ->maxLength(160),
                                    ]),

                                // 🤖 Tab 4: AI & Automation
                                Tabs\Tab::make('AI & Chatbot')
                                    ->icon('heroicon-m-cpu-chip')
                                    ->schema([
                                        Section::make('Knowledge Base (AI-এর মগজ)')
                                            ->description('দোকানের পলিসি, রিটার্ন রুলস বা অফার ডিটেইলস এখানে লিখুন। AI এটি পড়ে উত্তর দিবে।')
                                            ->icon('heroicon-m-book-open')
                                            ->schema([
                                                Textarea::make('knowledge_base')
                                                    ->label('Shop Policies & FAQs')
                                                    ->placeholder("উদাহরণ:\n১. ডেলিভারি চার্জ ঢাকার মধ্যে ৮০ টাকা।\n২. কোনো রিটার্ন পলিসি নেই।\n৩. শুক্রবার বন্ধ থাকে।")
                                                    ->rows(5)
                                                    ->helperText('AI এই তথ্যগুলো ব্যবহার করে কাস্টমারের প্রশ্নের উত্তর দিবে।'),
                                            ]),

                                        Section::make('Bot Personality')
                                            ->description('AI কাস্টমারের সাথে কীভাবে আচরণ করবে তা নির্ধারণ করুন।')
                                            ->icon('heroicon-m-face-smile')
                                            ->collapsed()
                                            ->schema([
                                                Textarea::make('custom_prompt')
                                                    ->label('Custom Salesman Prompt')
                                                    ->placeholder("তুমি একজন দক্ষ সেলসম্যান। কাস্টমারকে 'স্যার' বলে সম্বোধন করবে...")
                                                    ->rows(4)
                                                    ->helperText('Advanced users only. Leave blank to use default.'),
                                            ]),
                                    ]),

                                // 🚚 Tab 5: Logistics
                                Tabs\Tab::make('Logistics')
                                    ->icon('heroicon-m-truck')
                                    ->schema([
                                        Section::make('Delivery Charges')
                                            ->description('Shipping costs for orders.')
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

                                // 🔗 Tab 6: Integrations (FB & Telegram)
                                Tabs\Tab::make('Integrations')
                                    ->icon('heroicon-m-link')
                                    ->schema([
                                        Section::make('Facebook & Messenger')
                                            ->schema([
                                                TextInput::make('fb_verify_token')
                                                    ->label('Webhook Token')
                                                    ->default(fn () => Str::random(40))
                                                    ->readOnly()
                                                    ->suffixActions([
                                                        Action::make('regenerate')
                                                            ->icon('heroicon-m-arrow-path')
                                                            ->color('warning')
                                                            ->requiresConfirmation()
                                                            ->action(fn ($set) => $set('fb_verify_token', Str::random(40))),
                                                        Action::make('copy')
                                                            ->icon('heroicon-m-clipboard')
                                                            ->action(fn ($livewire, $state) => $livewire->js("window.navigator.clipboard.writeText('{$state}')")),
                                                    ]),
                                                
                                                Placeholder::make('fb_status')
                                                    ->label('Status')
                                                    ->content(fn ($record) => $record && $record->fb_page_id ? '✅ Connected' : '❌ Not Connected'),

                                                Actions::make([
                                                    Action::make('connect_facebook')
                                                        ->label('Connect Facebook')
                                                        ->url(fn ($record) => route('auth.facebook', ['client_id' => $record->id]))
                                                        ->visible(fn ($record) => !$record->fb_page_id),
                                                    
                                                    Action::make('disconnect_facebook')
                                                        ->label('Disconnect')
                                                        ->color('danger')
                                                        ->requiresConfirmation()
                                                        ->action(fn ($record) => $record->update(['fb_page_id' => null]))
                                                        ->visible(fn ($record) => $record->fb_page_id),
                                                ]),
                                                
                                                Section::make('Manual Config (Advanced)')
                                                    ->collapsed()
                                                    ->schema([
                                                        TextInput::make('fb_page_id')->label('Facebook Page ID')->numeric(),
                                                        Textarea::make('fb_page_token')->label('Access Token')->rows(2),
                                                        TextInput::make('fb_app_secret')->label('App Secret')->password()->revealable(),
                                                        Actions::make([
                                                            Action::make('test_connection')
                                                                ->label('Test Manual Connection')
                                                                ->icon('heroicon-m-signal')
                                                                ->action(function ($get) {
                                                                    // Simple Test Logic
                                                                    if (!$get('fb_page_id') || !$get('fb_page_token')) {
                                                                        Notification::make()->title('Missing Info')->warning()->send();
                                                                        return;
                                                                    }
                                                                    Notification::make()->title('Test Request Sent')->success()->send();
                                                                })
                                                        ]),
                                                    ]),
                                            ]),

                                        Section::make('Telegram Notification')
                                            ->collapsed()
                                            ->schema([
                                                Placeholder::make('tutorial')
                                                    ->label('')
                                                    ->content(new HtmlString('<div class="text-sm text-gray-600">1. @BotFather থেকে টোকেন নিন।<br>2. @userinfobot থেকে চ্যাট আইডি নিন।</div>')),

                                                TextInput::make('telegram_bot_token')
                                                    ->label('Bot Token')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('12345:ABC-DEF...'),

                                                TextInput::make('telegram_chat_id')
                                                    ->label('Chat ID')
                                                    ->placeholder('123456789'),

                                                Actions::make([
                                                    Action::make('connect_telegram')
                                                        ->label('Test Connection')
                                                        ->icon('heroicon-m-paper-airplane')
                                                        ->color('success')
                                                        ->action(function ($get) {
                                                            $token = $get('telegram_bot_token');
                                                            $chatId = $get('telegram_chat_id');
                                                            
                                                            if (!$token || !$chatId) {
                                                                Notification::make()->title('Error')->body('Token & Chat ID required.')->danger()->send();
                                                                return;
                                                            }

                                                            try {
                                                                $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                                                                    'chat_id' => $chatId,
                                                                    'text' => "✅ **Test Success!** Your shop is connected."
                                                                ]);

                                                                if ($response->successful()) {
                                                                    Notification::make()->title('Connected!')->success()->send();
                                                                } else {
                                                                    Notification::make()->title('Failed')->body('Check token/chat ID or start the bot.')->danger()->send();
                                                                }
                                                            } catch (\Exception $e) {
                                                                Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                                                            }
                                                        })
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
                ImageColumn::make('logo') // 🔥 Logo in Table
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-shop.png'))
                    ->label('Logo'),

                TextColumn::make('shop_name')
                    ->searchable()
                    ->weight('bold')
                    ->sortable()
                    ->description(fn (Client $record) => $record->custom_domain ?: $record->slug), // 🔥 Domain Show
                    
                TextColumn::make('plan.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pro', 'Premium' => 'warning',
                        'Basic' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('telegram_bot_token')
                    ->label('Telegram')
                    ->formatStateUsing(fn ($state) => $state ? 'Connected' : 'Pending')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                // Webhook Status
                TextColumn::make('webhook_verified_at')
                    ->label('FB Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Pending')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger'),

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
                Tables\Actions\Action::make('Visit')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    // 🔥 Smart URL Logic
                    ->url(fn (Client $record) => $record->custom_domain ? "https://{$record->custom_domain}" : url('/shop/' . $record->slug))
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