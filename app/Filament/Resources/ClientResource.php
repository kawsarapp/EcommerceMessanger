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

    public static function getNavigationBadge(): ?string
    {
        return auth()->id() === 1 ? (string) static::getModel()::count() : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['shop_name', 'slug', 'fb_page_id'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

                Forms\Components\Group::make()
                    ->schema([
                        Tabs::make('Shop Configuration')
                            ->persistTabInQueryString()
                            ->tabs([
                                
                                // ১. সাধারণ তথ্য
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

                                        Placeholder::make('webhook_status')
                                            ->label('Connection Status')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new HtmlString('<span class="text-gray-500 italic text-sm">Save to generate status</span>');
                                                }

                                                $isVerified = (bool) $record->webhook_verified_at;
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

                                // ২. এআই কনফিগারেশন
                                Tabs\Tab::make('AI & Chatbot')
                                    ->icon('heroicon-m-cpu-chip')
                                    ->schema([
                                        Section::make('Bot Personality')
                                            ->description('Instruct the AI on how to behave.')
                                            ->schema([
                                                Textarea::make('custom_prompt')
                                                    ->label('System Instruction')
                                                    ->placeholder("Example:\n- Always be polite.\n- Address user as 'Brother'.")
                                                    ->rows(6)
                                                    ->maxLength(2000)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                // ৩. লজিস্টিকস
                                Tabs\Tab::make('Logistics')
                                    ->icon('heroicon-m-truck')
                                    ->schema([
                                        Section::make('Delivery Charges')
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
                                            ])->columns(['default' => 1, 'sm' => 2]),
                                    ]),

                                // ৪. মেটা (ফেসবুক) ইন্টিগ্রেশন
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

                                // 🔥 ৫. টেলিগ্রাম ইন্টিগ্রেশন (IMPROVED with VERIFICATION)
                                Tabs\Tab::make('Telegram Integration')
                                    ->icon('heroicon-m-paper-airplane')
                                    ->schema([
                                        Section::make('Instructions (কিভাবে পাবেন?)')
                                            ->description('Follow these steps to connect your Telegram.')
                                            ->schema([
                                                Placeholder::make('tutorial')
                                                    ->label('')
                                                    ->content(new HtmlString('
                                                        <div class="text-sm text-gray-600 space-y-3 bg-gray-50 p-4 rounded-lg border">
                                                            <p class="font-bold text-primary-600">📌 How to connect Telegram?</p>
                                                            <ul class="list-disc ml-4 space-y-1">
                                                                <li><strong>Option A: Create New Bot</strong> - Go to <code>@BotFather</code> → Type <code>/newbot</code> → Follow steps → Copy Token.</li>
                                                                <li><strong>Option B: Use Existing Bot</strong> - Go to <code>@BotFather</code> → Type <code>/mybots</code> → Select bot → API Token.</li>
                                                            </ul>
                                                            <div class="mt-2 pt-2 border-t border-gray-200">
                                                                <p><strong>Step 2: Get Chat ID</strong> - Search <code>@userinfobot</code> → Click Start → Copy ID.</p>
                                                            </div>
                                                            <p class="text-red-500 font-bold mt-2">⚠️ Must Do: Search your bot on Telegram & click START button.</p>
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
                                                    ->helperText('Paste the token from BotFather here.'),

                                                TextInput::make('telegram_chat_id')
                                                    ->label('Admin Chat ID')
                                                    ->placeholder('123456789')
                                                    ->helperText('Paste your ID from @userinfobot here.'),

                                                // 🔥 ভেরিফাই এবং কানেক্ট বাটন
                                                Actions::make([
                                                    Actions\Action::make('connect_telegram')
                                                        ->label('Verify & Connect')
                                                        ->icon('heroicon-m-check-badge')
                                                        ->color('success')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Test Connection')
                                                        ->modalDescription('We will send a test message to your Telegram to verify credentials.')
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
                                                                    'text' => "✅ **Connection Successful!**\nYour shop is now connected to this bot.",
                                                                    'parse_mode' => 'Markdown'
                                                                ]);

                                                                if (!$testMsg->successful()) {
                                                                    Notification::make()
                                                                        ->title('Verification Failed!')
                                                                        ->body('Could not send message. Check Chat ID or ensure you started the bot.')
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

                                                            // 3. ওয়েবহুক সেট করা
                                                            $webhookUrl = "https://asianhost.net/telegram/webhook/" . $token;
                                                            
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

                TextColumn::make('webhook_verified_at')
                    ->label('Webhook')
                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Pending')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-badge' : 'heroicon-m-clock'),

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