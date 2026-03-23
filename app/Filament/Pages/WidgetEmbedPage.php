<?php

namespace App\Filament\Pages;

use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WidgetEmbedPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-code-bracket';
    protected static ?string $navigationGroup = 'Messages';
    protected static ?string $navigationLabel = 'Widget Embed Code';
    protected static ?int    $navigationSort  = 6;
    protected static ?string $slug            = 'widget-embed';
    protected static string  $view            = 'filament.pages.widget-embed';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $client = $this->getClient();
        if ($client) {
            $this->form->fill([
                'widget_name'             => $client->widget_name     ?? $client->shop_name,
                'widget_allowed_domains'  => $client->widget_allowed_domains ?? '',
                'widget_position'         => $client->widget_position ?? 'bottom-right',
                'widget_greeting'         => $client->widget_greeting ?? '',
                'show_whatsapp_button'    => $client->show_whatsapp_button  ?? true,
                'show_messenger_button'   => $client->show_messenger_button ?? true,
                'show_ai_chat_widget'     => $client->show_ai_chat_widget   ?? true,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('🎨 Widget Settings')
                    ->description('এখানে customize করুন, তারপর embed code copy করুন।')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('widget_name')
                                ->label('Widget Display Name')
                                ->placeholder('My Shop Assistant')
                                ->helperText('Chat window এর header এ দেখাবে'),

                            Forms\Components\Select::make('widget_position')
                                ->label('Position')
                                ->options([
                                    'bottom-right' => '↘ Bottom Right (Default)',
                                    'bottom-left'  => '↙ Bottom Left',
                                ])
                                ->default('bottom-right'),

                            Forms\Components\Textarea::make('widget_greeting')
                                ->label('Greeting Message')
                                ->placeholder('আমি আপনাকে সাহায্য করতে পারি! 👋 কী খুঁজছেন?')
                                ->helperText('Chat open হলে প্রথম message')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                    ]),

                Forms\Components\Section::make('🔒 Security — Allowed Domains')
                    ->description('কোন website এ এই widget চলবে তা specify করুন। Empty রাখলে সব জায়গায় চলবে।')
                    ->schema([
                        Forms\Components\Textarea::make('widget_allowed_domains')
                            ->label('Allowed Domains (comma separated)')
                            ->placeholder('example.com, shop.example.com, myshop.com')
                            ->helperText('⚠️ এটা খালি থাকলে যেকোনো site আপনার API key use করতে পারবে। Production এ domain দিন।')
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('📱 Chat Channels — ON/OFF')
                    ->description('Shop website এ কোন chat button গুলো দেখাবে তা control করুন।')
                    ->schema([
                        Forms\Components\Toggle::make('show_whatsapp_button')
                            ->label('📗 WhatsApp Button')
                            ->helperText('WhatsApp connected থাকলে shop এ button দেখাবে')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('show_messenger_button')
                            ->label('💙 Facebook Messenger Button')
                            ->helperText('Facebook Page connected থাকলে shop এ button দেখাবে')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('show_ai_chat_widget')
                            ->label('🤖 AI Live Chat Widget')
                            ->helperText('Shop এ AI chatbot bubble দেখাবে — customer সরাসরি AI এর সাথে chat করতে পারবে')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public function save(): void
    {
        $client = $this->getClient();
        if (!$client) {
            Notification::make()->danger()->title('No client found.')->send();
            return;
        }

        $data = $this->form->getState();
        $client->update([
            'widget_name'             => $data['widget_name'],
            'widget_allowed_domains'  => $data['widget_allowed_domains'],
            'widget_position'         => $data['widget_position'],
            'widget_greeting'         => $data['widget_greeting'],
            'show_whatsapp_button'    => $data['show_whatsapp_button']  ?? true,
            'show_messenger_button'   => $data['show_messenger_button'] ?? true,
            'show_ai_chat_widget'     => $data['show_ai_chat_widget']   ?? true,
        ]);

        Notification::make()->success()->title('Widget settings saved ✅')->send();
    }

    public function getClient(): ?Client
    {
        if (auth()->user()?->isSuperAdmin()) {
            return Client::first(); // Admin: show first client as demo
        }
        return Client::where('user_id', auth()->id())->first();
    }

    public function getApiKey(): string
    {
        return $this->getClient()?->api_token ?? '—';
    }

    public function getAppUrl(): string
    {
        return rtrim(config('app.url'), '/');
    }

    public function getSnippetHead(): string
    {
        $key      = $this->getApiKey();
        $url      = $this->getAppUrl();
        $name     = $this->data['widget_name']    ?? $this->getClient()?->shop_name ?? 'Shop';
        $position = $this->data['widget_position'] ?? 'bottom-right';
        $greeting = $this->data['widget_greeting'] ?? '';

        $greetingLine = $greeting ? "\nwindow.AICB_GREETING = " . json_encode($greeting) . ";" : '';
        $posLine      = $position !== 'bottom-right' ? "\nwindow.AICB_POSITION = '{$position}';" : '';

        return <<<HTML
<!-- AI Commerce Bot Widget -->
<script>
window.AICB_KEY  = '{$key}';
window.AICB_URL  = '{$url}';
window.AICB_SHOP = {$this->toJs($name)};{$greetingLine}{$posLine}
</script>
<script src="{$url}/js/chatbot-widget.js" async></script>
<!-- End AI Commerce Bot -->
HTML;
    }

    public function getSnippetBody(): string
    {
        $key      = $this->getApiKey();
        $url      = $this->getAppUrl();
        $name     = $this->data['widget_name']    ?? $this->getClient()?->shop_name ?? 'Shop';
        $position = $this->data['widget_position'] ?? 'bottom-right';
        $greeting = $this->data['widget_greeting'] ?? '';

        $greetingLine = $greeting ? "\n  window.AICB_GREETING = " . json_encode($greeting) . ";" : '';
        $posLine      = $position !== 'bottom-right' ? "\n  window.AICB_POSITION = '{$position}';" : '';

        return <<<HTML
<!-- AI Commerce Bot Widget — Paste before </body> -->
<script>
  window.AICB_KEY  = '{$key}';
  window.AICB_URL  = '{$url}';
  window.AICB_SHOP = {$this->toJs($name)};{$greetingLine}{$posLine}
</script>
<script src="{$url}/js/chatbot-widget.js" async></script>
HTML;
    }

    private function toJs(string $val): string
    {
        return json_encode($val, JSON_UNESCAPED_UNICODE);
    }
}
