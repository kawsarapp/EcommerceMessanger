<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEndpointResource\Pages;
use App\Models\WebhookEndpoint;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WebhookEndpointResource extends Resource
{
    protected static ?string $model = WebhookEndpoint::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Developer & API';
    protected static ?string $navigationLabel = 'Zapier / Webhooks';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_webhook');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Webhook Configuration')->schema([
                Forms\Components\TextInput::make('name')->label('নাম')->required()->placeholder('My Zapier Hook'),
                Forms\Components\TextInput::make('url')->label('Webhook URL')->required()->url()->placeholder('https://hooks.zapier.com/...'),
                Forms\Components\TextInput::make('secret')->label('Secret Key (Optional)')->helperText('HMAC signature verify করতে ব্যবহার হবে'),
                Forms\Components\CheckboxList::make('events')
                    ->label('কোন Events পাঠাবেন?')
                    ->options(WebhookEndpoint::AVAILABLE_EVENTS)
                    ->required()
                    ->columns(2),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('retry_count')->label('Retry Count')->numeric()->default(3)->minValue(1)->maxValue(5),
                    Forms\Components\Toggle::make('is_active')->label('সক্রিয়')->default(true),
                ]),
            ]),
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
                Tables\Columns\TextColumn::make('name')->label('নাম')->searchable(),
                Tables\Columns\TextColumn::make('url')->label('URL')->limit(40)->url(fn($r) => $r->url),
                Tables\Columns\BadgeColumn::make('events')->getStateUsing(fn($r) => count($r->events ?? []) . ' events'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\BadgeColumn::make('last_status')->label('শেষ Status')->colors([
                    'success' => 'success', 'danger' => 'failed', 'warning' => 'pending',
                ]),
                Tables\Columns\TextColumn::make('last_triggered_at')->label('শেষবার')->since()->placeholder('কখনো না'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWebhookEndpoints::route('/'),
            'create' => Pages\CreateWebhookEndpoint::route('/create'),
            'edit'   => Pages\EditWebhookEndpoint::route('/{record}/edit'),
        ];
    }
}
