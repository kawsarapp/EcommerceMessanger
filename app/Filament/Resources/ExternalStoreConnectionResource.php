<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExternalStoreConnectionResource\Pages;
use App\Models\Client;
use App\Models\ExternalStoreConnection;
use App\Services\Store\StoreDriverFactory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExternalStoreConnectionResource extends Resource
{
    protected static ?string $model = ExternalStoreConnection::class;
    protected static ?string $navigationIcon  = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Platform';
    protected static ?string $navigationLabel = 'Store Connections';
    protected static ?int    $navigationSort  = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin()
            || Client::where('user_id', auth()->id())->exists();
    }

    public static function form(Form $form): Form
    {
        $isAdmin = auth()->user()?->isSuperAdmin();

        return $form->schema([
            Forms\Components\Section::make('🔌 Store Connection Setup')
                ->description('আপনার WordPress/WooCommerce site এ NeuralCart Plugin install করুন, তারপর এখানে connect করুন।')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        // Admin only: which client
                        Forms\Components\Select::make('client_id')
                            ->label('Shop / Client')
                            ->relationship('client', 'shop_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn() => $isAdmin),

                        Forms\Components\Select::make('platform')
                            ->label('Platform')
                            ->options([
                                'wordpress' => '🔵 WordPress / WooCommerce',
                                'custom'    => '🟠 Custom REST API',
                                'shopify'   => '🟢 Shopify (Coming Soon)',
                            ])
                            ->default('wordpress')
                            ->required()
                            ->helperText('আপনার site কোন platform এ?'),

                        Forms\Components\TextInput::make('endpoint_url')
                            ->label('Plugin API Base URL')
                            ->placeholder('https://yoursite.com/wp-json/neuralcart/v1')
                            ->url()
                            ->required()
                            ->columnSpanFull()
                            ->helperText('WordPress Plugin install এর পরে এই URL পাবেন'),

                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->placeholder('nc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                            ->required()
                            ->helperText('WordPress Plugin Settings থেকে copy করুন'),

                        Forms\Components\TextInput::make('api_secret')
                            ->label('API Secret (Optional)')
                            ->password()
                            ->revealable()
                            ->helperText('Webhook signature verification এর জন্য'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Connection Active')
                            ->default(true)
                            ->onColor('success'),
                    ]),
                ]),

            Forms\Components\Section::make('📋 How to Connect')
                ->description('Steps to install NeuralCart Plugin on WordPress')
                ->schema([
                    Forms\Components\Placeholder::make('instructions')
                        ->content(new \Illuminate\Support\HtmlString('
                            <ol style="list-style:decimal;padding-left:20px;line-height:2;">
                                <li><strong>Download</strong> the NeuralCart plugin from your dashboard</li>
                                <li>Go to WordPress Admin → Plugins → Add New → Upload Plugin</li>
                                <li>Activate the plugin</li>
                                <li>Go to <strong>Settings → NeuralCart</strong></li>
                                <li>Copy the <strong>API Key</strong> and <strong>Plugin API URL</strong></li>
                                <li>Paste them above and click Save</li>
                                <li>Click <strong>"Test Connection"</strong> to verify</li>
                            </ol>
                        '))
                        ->columnSpanFull(),
                ])->collapsible()->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (!auth()->user()?->isSuperAdmin()) {
                    $clientId = Client::where('user_id', auth()->id())->value('id');
                    $query->where('client_id', $clientId);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('client.shop_name')
                    ->label('Shop')
                    ->searchable()
                    ->visible(fn() => auth()->user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Platform')
                    ->formatStateUsing(fn($state) => match($state) {
                        'wordpress' => '🔵 WordPress',
                        'shopify'   => '🟢 Shopify',
                        'custom'    => '🟠 Custom API',
                        default     => $state,
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('endpoint_url')
                    ->label('Endpoint')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->endpoint_url),

                Tables\Columns\IconColumn::make('last_test_passed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_tested_at')
                    ->label('Last Tested')
                    ->since()
                    ->placeholder('Never'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onColor('success'),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (ExternalStoreConnection $record) {
                        $driver = new \App\Services\Store\Drivers\ExternalApiDriver($record->client_id, $record);
                        $result = $driver->testConnection();

                        $record->update([
                            'last_tested_at'   => now(),
                            'last_test_passed' => $result['success'],
                            'last_test_error'  => $result['success'] ? null : $result['message'],
                        ]);

                        if ($result['success']) {
                            Notification::make()->success()->title('Connected!')->body($result['message'])->send();
                        } else {
                            Notification::make()->danger()->title('Connection Failed')->body($result['message'])->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExternalStoreConnections::route('/'),
            'create' => Pages\CreateExternalStoreConnection::route('/create'),
            'edit'   => Pages\EditExternalStoreConnection::route('/{record}/edit'),
        ];
    }
}
