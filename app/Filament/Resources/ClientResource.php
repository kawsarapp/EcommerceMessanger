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
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
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

    // [UX] গ্লোবাল সার্চ (যেকোনো জায়গা থেকে শপ খোঁজা যাবে)
    public static function getGloballySearchableAttributes(): array
    {
        return ['shop_name', 'slug', 'fb_page_id'];
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
                    ->columns(['default' => 1, 'sm' => 2]) // রেস্পন্সিভ কলাম
                    ->visible(fn () => auth()->id() === 1),

                // --- সেকশন ২: শপ কনফিগারেশন ---
                Forms\Components\Group::make()
                    ->schema([
                        Tabs::make('Shop Configuration')
                            ->persistTabInQueryString() // রিফ্রেশ দিলেও ট্যাব হারাবে না
                            ->tabs([
                                
                                // ১. সাধারণ তথ্য (General Info)
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

                                        // Webhook Token with UI Enhancements
                                        TextInput::make('fb_verify_token')
                                            ->label('Webhook Verify Token')
                                            ->helperText('Keep this token secret. Used for Facebook verification.')
                                            ->default(fn () => Str::random(40))
                                            ->readOnly()
                                            ->required()
                                            ->suffixActions([
                                                Action::make('regenerate')
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->color('warning')
                                                    ->tooltip('Regenerate Token')
                                                    ->requiresConfirmation()
                                                    ->action(fn ($set) => $set('fb_verify_token', Str::random(40))),

                                                Action::make('copy')
                                                    ->icon('heroicon-m-clipboard')
                                                    ->color('gray')
                                                    ->tooltip('Copy Token')
                                                    ->action(function ($livewire, $state) {
                                                        $livewire->js("window.navigator.clipboard.writeText('{$state}')");
                                                        Notification::make()->title('Copied!')->success()->send();
                                                    }),
                                            ]),

                                        // ✅ [FIXED & OPTIMIZED] Webhook Status Logic
                                        Placeholder::make('webhook_status')
                                            ->label('Connection Status')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new HtmlString('<span class="text-gray-500 italic text-sm">Save to generate status</span>');
                                                }

                                                $isVerified = (bool) $record->webhook_verified_at;
                                                
                                                // Tailwind Classes for better UI
                                                $class = $isVerified 
                                                    ? 'bg-green-100 text-green-700 border-green-200' 
                                                    : 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                                
                                                $icon = $isVerified ? '✅' : '⏳';
                                                $text = $isVerified 
                                                    ? 'Verified by Facebook (' . $record->webhook_verified_at->diffForHumans() . ')' 
                                                    : 'Pending Verification';

                                                return new HtmlString("
                                                    <div class='px-3 py-1.5 rounded-lg border {$class} inline-flex items-center gap-2 text-sm font-medium'>
                                                        <span>{$icon}</span> <span>{$text}</span>
                                                    </div>
                                                ");
                                            }),
                                    
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
                                    ]),

                                // ২. এআই কনফিগারেশন (AI & Chatbot) - 🔥 UPGRADED
                                Tabs\Tab::make('AI & Chatbot')
                                    ->icon('heroicon-m-cpu-chip')
                                    ->schema([
                                        
                                        // 🔥 Knowledge Base Section (New Feature)
                                        Section::make('Knowledge Base (AI-এর মগজ)')
                                            ->description('দোকানের পলিসি, রিটার্ন রুলস বা অফার ডিটেইলস এখানে লিখুন। AI এটি পড়ে উত্তর দিবে।')
                                            ->icon('heroicon-m-book-open')
                                            ->schema([
                                                Textarea::make('knowledge_base')
                                                    ->label('Shop Policies & FAQs')
                                                    ->placeholder("উদাহরণ:\n১. ডেলিভারি চার্জ ঢাকার মধ্যে ৮০ টাকা।\n২. কোনো রিটার্ন পলিসি নেই।\n৩. শুক্রবার বন্ধ থাকে।")
                                                    ->rows(5)
                                                    ->helperText('AI এই তথ্যগুলো ব্যবহার করে কাস্টমারের প্রশ্নের উত্তর দিবে।'),
                                            ]),

                                        // 🔥 Bot Personality
                                        Section::make('Bot Personality & Instructions')
                                            ->description('AI কাস্টমারের সাথে কীভাবে আচরণ করবে তা নির্ধারণ করুন।')
                                            ->icon('heroicon-m-face-smile')
                                            ->collapsed()
                                            ->schema([
                                                Textarea::make('custom_prompt')
                                                    ->label('Custom Salesman Prompt')
                                                    ->placeholder("তুমি একজন দক্ষ সেলসম্যান। কাস্টমারকে 'স্যার' বলে সম্বোধন করবে...")
                                                    ->rows(6)
                                                    ->maxLength(2000)
                                                    ->helperText('Advanced users only. Leave blank to use the default professional salesman persona.'),
                                            ]),
                                    ]),

                                // ৩. লজিস্টিকস (Logistics)
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
                                                    ->minValue(0)
                                                    ->required(),

                                                TextInput::make('delivery_charge_outside')
                                                    ->label('Outside Dhaka')
                                                    ->numeric()
                                                    ->prefix('৳')
                                                    ->default(150)
                                                    ->minValue(0)
                                                    ->required(),
                                            ])->columns(['default' => 1, 'sm' => 2]), // মোবাইল রেস্পন্সিভ
                                    ]),

                                // ৪. মেটা (ফেসবুক) ইন্টিগ্রেশন (Meta Integration)
                                Tabs\Tab::make('Meta Integration')
                                    ->icon('heroicon-m-link')
                                    ->schema([
                                        Actions::make([
                                            Actions\Action::make('connect_facebook')
                                                ->label('Connect with Facebook')
                                                ->icon('heroicon-m-globe-alt')
                                                ->color('info')
                                                ->url(fn ($record) => route('auth.facebook', ['client_id' => $record->id]))
                                                ->openUrlInNewTab(false)
                                                ->visible(fn ($record) => !$record->fb_page_id),

                                            Actions\Action::make('disconnect_facebook')
                                                ->label('Disconnect Page')
                                                ->icon('heroicon-m-trash')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->action(fn ($record) => $record->update([
                                                    'fb_page_id' => null, 
                                                    'fb_page_token' => null, 
                                                    'webhook_verified_at' => null
                                                ]))
                                                ->visible(fn ($record) => $record->fb_page_id),
                                        ])->columnSpanFull(),

                                        Section::make('Manual Configuration (Advanced)')
                                            ->description('Use these only if automatic connection fails.')
                                            ->collapsed()
                                            ->schema([
                                                TextInput::make('fb_page_id')
                                                    ->label('Facebook Page ID')
                                                    ->numeric()
                                                    ->unique(Client::class, 'fb_page_id', ignoreRecord: true),
                                                
                                                Textarea::make('fb_page_token')
                                                    ->label('Page Access Token')
                                                    ->rows(2),

                                                // App Secret Field (Security Upgrade)
                                                TextInput::make('fb_app_secret')
                                                    ->label('App Secret')
                                                    ->password()
                                                    ->revealable()
                                                    ->helperText('Used for webhook signature verification (Highly Recommended).'),

                                                Actions::make([
                                                    Actions\Action::make('test_connection')
                                                        ->label('Test Manual Connection')
                                                        ->icon('heroicon-m-signal')
                                                        ->action(function ($get) {
                                                            $pageId = $get('fb_page_id');
                                                            $token = $get('fb_page_token');

                                                            if (!$pageId || !$token) {
                                                                Notification::make()->title('Missing Info')->warning()->send();
                                                                return;
                                                            }
                                                            try {
                                                                $response = Http::get("https://graph.facebook.com/v19.0/{$pageId}", [
                                                                    'fields' => 'name', 'access_token' => $token,
                                                                ]);
                                                                if ($response->successful()) {
                                                                    Notification::make()->title('Success!')->body("Page: {$response->json()['name']}")->success()->send();
                                                                } else {
                                                                    Notification::make()->title('Failed!')->body($response->json()['error']['message'] ?? 'Error')->danger()->send();
                                                                }
                                                            } catch (\Exception $e) {
                                                                Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                                                            }
                                                        })
                                                ]),
                                            ]),
                                    ]),

                                // 🔥 ৫. টেলিগ্রাম ইন্টিগ্রেশন (Telegram Integration - SAAS FEATURE)
                                Tabs\Tab::make('Telegram Integration')
                                    ->icon('heroicon-m-paper-airplane')
                                    ->schema([
                                        Section::make('Instructions (কিভাবে কানেক্ট করবেন?)')
                                            ->description('অর্ডারের নোটিফিকেশন পেতে নিচের ধাপগুলো অনুসরণ করুন।')
                                            ->schema([
                                                Placeholder::make('tutorial')
                                                    ->label('')
                                                    ->content(new HtmlString('
                                                        <div class="text-sm text-gray-600 space-y-3 bg-gray-50 p-4 rounded-lg border">
                                                            <p class="font-bold text-primary-600">📌 Telegram Setup Guide:</p>
                                                            <ul class="list-disc ml-4 space-y-1">
                                                                <li><strong>ধাপ ১:</strong> টেলিগ্রামে <code>@BotFather</code> সার্চ করুন এবং একটি নতুন বট খুলুন।</li>
                                                                <li><strong>ধাপ ২:</strong> পাওয়া <strong>API Token</strong> টি নিচের "Bot Token" বক্সে দিন।</li>
                                                                <li><strong>ধাপ ৩:</strong> টেলিগ্রামে <code>@userinfobot</code> সার্চ করে আপনার <strong>Chat ID</strong> বের করুন এবং নিচের বক্সে দিন।</li>
                                                            </ul>
                                                            <p class="text-red-500 font-bold mt-2">⚠️ জরুরী: সেভ করার আগে আপনার বটের চ্যাটে গিয়ে START বাটন চাপতে ভুলবেন না!</p>
                                                        </div>
                                                    ')),
                                            ]),

                                        Section::make('Bot Configuration')
                                            ->schema([
                                                TextInput::make('telegram_bot_token')
                                                    ->label('Bot Token')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11')
                                                    ->helperText('BotFather থেকে পাওয়া টোকেন এখানে দিন।'),

                                                TextInput::make('telegram_chat_id')
                                                    ->label('Admin Chat ID')
                                                    ->placeholder('123456789')
                                                    ->helperText('আপনার বা গ্রুপের চ্যাট আইডি।'),

                                                // 🔥 ভেরিফাই এবং কানেক্ট বাটন (Smart Verify)
                                                Actions::make([
                                                    Actions\Action::make('connect_telegram')
                                                        ->label('Verify & Connect')
                                                        ->icon('heroicon-m-check-badge')
                                                        ->color('success')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Test Connection')
                                                        ->modalDescription('আমরা আপনার টেলিগ্রামে একটি টেস্ট মেসেজ পাঠাব।')
                                                        ->action(function ($get, $record) {
                                                            $token = $get('telegram_bot_token');
                                                            $chatId = $get('telegram_chat_id');
                                                            
                                                            if (!$token || !$chatId) {
                                                                Notification::make()->title('Error')->body('Please enter Bot Token AND Chat ID first.')->danger()->send();
                                                                return;
                                                            }

                                                            // 1. টেস্ট মেসেজ পাঠানো (Verification)
                                                            try {
                                                                $testMsg = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                                                                    'chat_id' => $chatId,
                                                                    'text' => "✅ **Connection Successful!**\nShop: " . ($record->shop_name ?? 'Unknown') . " is now connected.",
                                                                    'parse_mode' => 'Markdown'
                                                                ]);

                                                                if (!$testMsg->successful()) {
                                                                    Notification::make()
                                                                        ->title('Verification Failed!')
                                                                        ->body('মেসেজ পাঠানো যায়নি। দয়া করে দেখুন আপনি বট Start করেছেন কিনা বা চ্যাট আইডি সঠিক কিনা।')
                                                                        ->danger()
                                                                        ->send();
                                                                    return; // ডাটা ভুল হলে এখানেই থামবে
                                                                }

                                                            } catch (\Exception $e) {
                                                                Notification::make()->title('Network Error')->body($e->getMessage())->danger()->send();
                                                                return;
                                                            }

                                                            // 2. ভেরিফিকেশন সফল হলে ডাটাবেসে সেভ করা
                                                            if ($record) {
                                                                $record->update([
                                                                    'telegram_bot_token' => $token,
                                                                    'telegram_chat_id' => $chatId,
                                                                ]);
                                                            }

                                                            // 3. ওয়েবহুক সেট করা (Automatic)
                                                            $webhookUrl = config('app.url') . "/telegram/webhook/" . $token;
                                                            
                                                            try {
                                                                $response = Http::get("https://api.telegram.org/bot{$token}/setWebhook?url={$webhookUrl}");
                                                                
                                                                if ($response->successful() && $response->json()['ok']) {
                                                                    Notification::make()
                                                                        ->title('Connected & Verified!')
                                                                        ->body('Telegram Bot is active and saved successfully.')
                                                                        ->success()
                                                                        ->send();
                                                                } else {
                                                                    Notification::make()
                                                                        ->title('Webhook Failed')
                                                                        ->body($response->json()['description'] ?? 'Unknown Error')
                                                                        ->warning()
                                                                        ->send();
                                                                }
                                                            } catch (\Exception $e) {
                                                                Notification::make()->title('Webhook Error')->body($e->getMessage())->danger()->send();
                                                            }
                                                        })
                                                ])->columnSpanFull(),
                                            ])->columns(2),
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
                TextColumn::make('shop_name')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),
                    
                TextColumn::make('slug')
                    ->icon('heroicon-m-link')
                    ->color('primary')
                    ->copyable()
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('plan.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pro', 'Premium' => 'warning',
                        'Basic' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                // Status Badge for Webhook
                TextColumn::make('webhook_verified_at')
                    ->label('FB Webhook')
                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Pending')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-badge' : 'heroicon-m-clock'),

                // Telegram Status Badge [NEW]
                TextColumn::make('telegram_bot_token')
                    ->label('Telegram')
                    ->formatStateUsing(fn ($state) => $state ? 'Connected' : 'Not Connected')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->icon(fn ($state) => $state ? 'heroicon-m-paper-airplane' : 'heroicon-m-x-circle'),

                ToggleColumn::make('status')
                    ->label('Active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->visible(fn () => auth()->id() === 1),

                TextColumn::make('created_at')
                    ->dateTime('d M, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Visit')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Client $record) => url('/shop/' . $record->slug))
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