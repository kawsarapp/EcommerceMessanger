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

            Section::make('Advanced Tracking & Analytics')
                ->description('আপনার ওয়েবসাইটের ট্রাফিক এবং সেলস নিখুঁতভাবে ট্র্যাক করতে Facebook, Google এবং Tiktok এর API টোকেনগুলো यहाँ বসান। DataLayer এবং Server-side Tracking স্বয়ংক্রিয়ভাবে কাজ করবে।')
                ->icon('heroicon-o-presentation-chart-line')
                ->collapsible()
                ->schema([
                    Group::make()->schema([
                        Placeholder::make('meta_title')->label('🔵 Facebook / Meta Tracking'),
                        TextInput::make('fb_pixel_id')
                            ->label('Facebook Pixel ID')
                            ->placeholder('e.g. 293847293847293')
                            ->helperText('Legacy Client-Side Pixel. Leave blank if using CAPI exclusively.'),
                        TextInput::make('tracking_settings.fb_capi_token')
                            ->label('Conversion API Access Token')
                            ->password()
                            ->revealable()
                            ->placeholder('e.g. EAAGm0PX...'),
                    ])->columns(2),

                    Group::make()->schema([
                        Placeholder::make('google_title')->label('🔴 Google Analytics & GTM'),
                        TextInput::make('tracking_settings.gtm_id')
                            ->label('Google Tag Manager ID')
                            ->placeholder('e.g. GTM-XXXXXXX')
                            ->helperText('Deploy native DataLayer immediately.'),
                        TextInput::make('tracking_settings.ga4_measurement_id')
                            ->label('GA4 Measurement ID')
                            ->placeholder('e.g. G-XXXXXXX'),
                        TextInput::make('tracking_settings.ga4_api_secret')
                            ->label('GA4 Measurement API Secret')
                            ->placeholder('Needed for Server-Side events'),
                    ])->columns(3),

                    Group::make()->schema([
                        Placeholder::make('tiktok_title')->label('⚫ TikTok Tracking'),
                        TextInput::make('tracking_settings.tiktok_pixel_id')
                            ->label('TikTok Pixel ID')
                            ->placeholder('e.g. CXXXXXX')
                            ->columnSpan(1),
                    ])->columns(2),

                    Group::make()->schema([
                        Placeholder::make('tools_title')->label('🛠️ Utilities & Heatmaps'),
                        TextInput::make('tracking_settings.search_console_tag')
                            ->label('Google Search Console Meta Tag')
                            ->placeholder('e.g. sdfjkldsflkjewr3243...'),
                        TextInput::make('tracking_settings.microsoft_clarity_id')
                            ->label('Microsoft Clarity Project ID')
                            ->placeholder('e.g. jfksdf32..'),
                    ])->columns(2),
                ]),

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

            // ====================================================================
            // 🔌 WEBSITE / EXTERNAL CONNECTOR (SaaS Integration Hub)
            // ====================================================================
            Section::make('🔌 Website & External Connector')
                ->description('আপনার API Key দিয়ে যেকোনো website থেকে AI Chatbot ও Product Sync করুন।')
                ->icon('heroicon-o-code-bracket')
                ->collapsible()
                ->collapsed()
                ->schema([

                    // ── API Key ──────────────────────────────────────────────────
                    TextInput::make('api_token')
                        ->label('🔑 Your API Key')
                        ->readOnly()
                        ->helperText('এই Key দিয়ে WordPress, Shopify বা যেকোনো website আপনার shop-এর সাথে connect করতে পারবে।')
                        ->suffixActions([
                            Action::make('copy_api_key')
                                ->icon('heroicon-m-clipboard')
                                ->tooltip('Copy API Key')
                                ->action(function ($record) {
                                    Notification::make()->title('✅ API Key Copied!')->success()->send();
                                }),
                            Action::make('regenerate_api_key')
                                ->icon('heroicon-m-arrow-path')
                                ->tooltip('Regenerate API Key')
                                ->color('warning')
                                ->requiresConfirmation()
                                ->modalHeading('Regenerate API Key?')
                                ->modalDescription('পুরানো API Key অবৈধ হয়ে যাবে। আগে connected সব website তে নতুন key update করতে হবে।')
                                ->action(function ($record) {
                                    if ($record) {
                                        $record->update(['api_token' => \Illuminate\Support\Str::random(40)]);
                                        Notification::make()->title('✅ নতুন API Key তৈরি হয়েছে!')->warning()->send();
                                    }
                                }),
                        ]),

                    // ── JS Snippet Preview ───────────────────────────────────────
                    Placeholder::make('js_snippet_info')
                        ->label('📋 Chatbot Embed Snippet')
                        ->content(fn ($record) => $record
                            ? new HtmlString('
                                <div class="bg-slate-900 rounded-xl p-4 font-mono text-xs text-green-400 overflow-x-auto leading-relaxed">&lt;!-- AI Commerce Bot | Paste before &lt;/body&gt; --&gt;
&lt;script&gt;
(function() {
  window.AICB_CONFIG = {
    apiKey: "' . $record->api_token . '",
    shopName: "' . e($record->shop_name) . '",
    baseUrl: "' . config('app.url') . '",
    position: "bottom-right"
  };
  var s = document.createElement("script");
  s.src = "' . config('app.url') . '/js/chatbot-widget.js";
  document.head.appendChild(s);
})();
&lt;/script&gt;</div>
                                <p class="text-xs text-gray-500 mt-2">👆 এই snippet টি আপনার website-এর <strong>&lt;/body&gt;</strong> tag-এর আগে paste করুন।</p>
                                <a href="' . config('app.url') . '/api/connector/verify?api_key=' . $record->api_token . '" target="_blank"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline mt-2 block">
                                    🔗 Connection Test করুন ↗
                                </a>'
                            )
                            : new HtmlString('<p class="text-gray-400 text-sm">Shop save করার পর snippet দেখাবে।</p>')),

                    // ── API Reference ────────────────────────────────────────────
                    Placeholder::make('api_endpoints_ref')
                        ->label('🛠️ Developer API Endpoints')
                        ->content(fn ($record) => new HtmlString('
                            <div class="space-y-2 text-xs">
                                <div class="flex items-start gap-3 bg-slate-50 border border-slate-100 rounded-lg p-2.5">
                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 font-bold rounded text-[10px] shrink-0 mt-0.5">GET</span>
                                    <div>
                                        <code class="text-slate-800 font-mono">/api/connector/verify</code>
                                        <p class="text-gray-500 mt-0.5">API Key সঠিক কিনা test করুন</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 bg-slate-50 border border-slate-100 rounded-lg p-2.5">
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 font-bold rounded text-[10px] shrink-0 mt-0.5">POST</span>
                                    <div>
                                        <code class="text-slate-800 font-mono">/api/connector/sync-products</code>
                                        <p class="text-gray-500 mt-0.5">WooCommerce / Shopify / Custom store থেকে product import</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 bg-slate-50 border border-slate-100 rounded-lg p-2.5">
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 font-bold rounded text-[10px] shrink-0 mt-0.5">POST</span>
                                    <div>
                                        <code class="text-slate-800 font-mono">/api/v1/chat/widget</code>
                                        <p class="text-gray-500 mt-0.5">Embedded chatbot widget-এর real-time AI chat</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 bg-slate-50 border border-slate-100 rounded-lg p-2.5">
                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 font-bold rounded text-[10px] shrink-0 mt-0.5">POST</span>
                                    <div>
                                        <code class="text-slate-800 font-mono">/api/v1/import-products</code>
                                        <p class="text-gray-500 mt-0.5">Bulk product import (WooCommerce webhook compatible)</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 mt-3 bg-amber-50 border border-amber-100 rounded p-2">
                                    🔐 সব API call-এ header পাঠান: <code class="bg-amber-100 px-1 rounded font-mono">X-Api-Key: YOUR_API_KEY</code>
                                </p>
                            </div>')),
                ]),

            // ══════════════════════════════════════════════════════════════════
            // DEVELOPER SDK DOWNLOADS
            // ══════════════════════════════════════════════════════════════════
            Section::make('🧩 Developer SDK & Integration')
                ->description('Laravel, Node.js, Next.js বা যেকোনো platform থেকে সহজে connect করতে এই SDK গুলো ব্যবহার করুন।')
                ->icon('heroicon-o-code-bracket')
                ->collapsible()
                ->schema([
                    // ── SDK Download Cards ────────────────────────────────────
                    Placeholder::make('sdk_downloads')
                        ->label('📦 SDK Download করুন')
                        ->content(fn () => new HtmlString('
                            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">

                                <a href="' . config('app.url') . '/sdks/laravel/AiCommerceBot.php" download
                                    class="flex flex-col items-center gap-2 bg-white border-2 border-indigo-100 hover:border-indigo-400 rounded-xl p-4 text-center transition-all group">
                                    <span class="text-3xl">🐘</span>
                                    <strong class="text-sm text-slate-700 group-hover:text-indigo-600">Laravel SDK</strong>
                                    <span class="text-xs text-gray-400">AiCommerceBot.php</span>
                                    <span class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">PHP</span>
                                </a>

                                <a href="' . config('app.url') . '/sdks/nodejs/aiCommerceBot.js" download
                                    class="flex flex-col items-center gap-2 bg-white border-2 border-green-100 hover:border-green-400 rounded-xl p-4 text-center transition-all group">
                                    <span class="text-3xl">🟢</span>
                                    <strong class="text-sm text-slate-700 group-hover:text-green-600">Node.js SDK</strong>
                                    <span class="text-xs text-gray-400">aiCommerceBot.js</span>
                                    <span class="text-[10px] bg-green-50 text-green-600 px-2 py-0.5 rounded-full">JS / Express</span>
                                </a>

                                <a href="' . config('app.url') . '/sdks/nodejs/aiCommerceBot.ts" download
                                    class="flex flex-col items-center gap-2 bg-white border-2 border-blue-100 hover:border-blue-400 rounded-xl p-4 text-center transition-all group">
                                    <span class="text-3xl">🔷</span>
                                    <strong class="text-sm text-slate-700 group-hover:text-blue-600">Next.js SDK</strong>
                                    <span class="text-xs text-gray-400">aiCommerceBot.ts</span>
                                    <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">TypeScript</span>
                                </a>

                                <a href="' . config('app.url') . '/api/v1/wordpress-plugin/download"
                                    class="flex flex-col items-center gap-2 bg-white border-2 border-purple-100 hover:border-purple-400 rounded-xl p-4 text-center transition-all group">
                                    <span class="text-3xl">🔌</span>
                                    <strong class="text-sm text-slate-700 group-hover:text-purple-600">WordPress Plugin</strong>
                                    <span class="text-xs text-gray-400">ai-commerce-bot.zip</span>
                                    <span class="text-[10px] bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full">WooCommerce</span>
                                </a>

                            </div>

                            <div class="mt-4">
                                <a href="' . config('app.url') . '/sdks/INTEGRATION.md" target="_blank"
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-lg transition-all">
                                    📖 Full Integration Guide দেখুন ↗
                                </a>
                            </div>
                        ')),

                    // ── Feature Summary ───────────────────────────────────────
                    Placeholder::make('sdk_features')
                        ->label('🎁 Connect করার পর যা যা পাবেন')
                        ->content(fn () => new HtmlString('
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <div class="flex items-start gap-2 bg-emerald-50 border border-emerald-100 rounded-lg p-3">
                                    <span class="text-lg shrink-0">🤖</span>
                                    <div>
                                        <strong class="text-emerald-800">AI Chatbot Widget</strong>
                                        <p class="text-xs text-gray-500 mt-0.5">Floating chatbot — Bangla, English, Banglish সব ভাষায়</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-lg p-3">
                                    <span class="text-lg shrink-0">📦</span>
                                    <div>
                                        <strong class="text-blue-800">Product AI Search</strong>
                                        <p class="text-xs text-gray-500 mt-0.5">Real database থেকে product খুঁজে উত্তর দেবে</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-orange-50 border border-orange-100 rounded-lg p-3">
                                    <span class="text-lg shrink-0">🛒</span>
                                    <div>
                                        <strong class="text-orange-800">Order Assistant</strong>
                                        <p class="text-xs text-gray-500 mt-0.5">AI chatbot-এ order নিয়ে সরাসরি WooCommerce-এ create করে</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-pink-50 border border-pink-100 rounded-lg p-3">
                                    <span class="text-lg shrink-0">📸</span>
                                    <div>
                                        <strong class="text-pink-800">Image & Voice Search</strong>
                                        <p class="text-xs text-gray-500 mt-0.5">ছবি পাঠিয়ে product খুঁজবে, voice note-ও বুঝবে</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-teal-50 border border-teal-100 rounded-lg p-3">
                                    <span class="text-lg shrink-0">🟢</span>
                                    <div>
                                        <strong class="text-teal-800">Live Chat Handover</strong>
                                        <p class="text-xs text-gray-500 mt-0.5">Customer চাইলে human agent-এ switch করতে পারবে</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-violet-50 border border-violet-100 rounded-lg p-3">
                                    <span class="text-lg shrink-0">🔔</span>
                                    <div>
                                        <strong class="text-violet-800">Notifications</strong>
                                        <p class="text-xs text-gray-500 mt-0.5">নতুন order বা chat-এ Telegram/Email alert পাবেন</p>
                                    </div>
                                </div>
                            </div>
                        ')),

                    // ── Quick Connection Guide ────────────────────────────────
                    Placeholder::make('quick_connect_guide')
                        ->label('⚡ Quick Connect (যেকোনো website)')
                        ->columnSpanFull()
                        ->content(fn ($record) => new HtmlString('
                            <div class="space-y-3">
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Step 1 — .env file-এ যোগ করুন</p>
                                    <div class="bg-slate-900 rounded-lg p-3 font-mono text-xs text-green-300">
                                        AICB_API_KEY=<span class="text-yellow-300">' . ($record?->api_token ?? 'your_api_key_here') . '</span><br>
                                        AICB_BASE_URL=<span class="text-yellow-300">' . config('app.url') . '</span>
                                    </div>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Step 2 — SDK ফাইলটি আপনার project-এ রাখুন</p>
                                    <div class="grid grid-cols-3 gap-2 text-xs">
                                        <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-2 text-center">
                                            <p class="font-semibold text-indigo-700">Laravel</p>
                                            <code class="text-[10px] text-gray-600">app/Services/AiCommerceBot.php</code>
                                        </div>
                                        <div class="bg-green-50 border border-green-100 rounded-lg p-2 text-center">
                                            <p class="font-semibold text-green-700">Node.js</p>
                                            <code class="text-[10px] text-gray-600">lib/aiCommerceBot.js</code>
                                        </div>
                                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-2 text-center">
                                            <p class="font-semibold text-blue-700">Next.js</p>
                                            <code class="text-[10px] text-gray-600">lib/aiCommerceBot.ts</code>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Step 3 — Chatbot Widget যোগ করুন</p>
                                    <div class="bg-slate-900 rounded-lg p-3 font-mono text-xs">
                                        <span class="text-purple-300">// Laravel Blade (before &lt;/body&gt;)</span><br>
                                        <span class="text-yellow-300">{!! App\Services\AiCommerceBot::embedScript() !!}</span><br><br>
                                        <span class="text-purple-300">// Next.js layout.tsx</span><br>
                                        <span class="text-green-300">&lt;div dangerouslySetInnerHTML=</span><span class="text-blue-300">{{ __html: getEmbedSnippet() }}</span><span class="text-green-300"> /&gt;</span>
                                    </div>
                                </div>
                            </div>
                        ')),
                ]),
        ];
    }
}
