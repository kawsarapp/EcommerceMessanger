<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class IntegrationsTab
{
    public static function schema(): array
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

            Section::make('Tracking & Analytics')
                ->description('ফেসবুক পিক্সেল বা অন্যান্য ট্র্যাকিং টুল সেটআপ করুন।')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    TextInput::make('fb_pixel_id')
                        ->label('Facebook Pixel ID')
                        ->placeholder('e.g., 293847293847293')
                        ->helperText('Your Meta Pixel ID for tracking conversions and page views.')
                        ->prefixIcon('heroicon-o-code-bracket-square'),
                ])->columns(1),

            Section::make('Facebook Connection')->schema([
                Placeholder::make('fb_status')
                    ->label('Status')
                    ->content(fn ($record) => $record && $record->fb_page_id
                        ? new HtmlString('<span class="text-green-600 font-bold flex items-center gap-1">✅ Connected to Page ID: ' . $record->fb_page_id . '</span>')
                        : new HtmlString('<span class="text-gray-500">❌ Not Connected</span>')),
                
                Actions::make([
                    Action::make('connect_facebook')
                        ->label('Connect with Facebook')
                        ->url(fn ($record) => $record ? route('auth.facebook', ['client_id' => $record->id]) : '#')
                        ->color('info')
                        ->visible(fn ($record) => !$record || !$record->fb_page_id)
                        ->disabled(fn ($record) => !$record), // Disable link if client is not created yet
                    Action::make('disconnect_facebook')
                        ->label('Disconnect Page')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record?->update(['fb_page_id' => null, 'fb_page_token' => null]))
                        ->visible(fn ($record) => $record && $record->fb_page_id),
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