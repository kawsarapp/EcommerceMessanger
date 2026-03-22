<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyPointResource\Pages;
use App\Models\LoyaltyPoint;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoyaltyPointResource extends Resource
{
    protected static ?string $model = LoyaltyPoint::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Marketing & Sales';
    protected static ?string $navigationLabel = 'Loyalty Points';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_loyalty');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Points Entry')->schema([
                // Admin only: select which shop
                Forms\Components\Select::make('client_id')
                    ->label('Shop / Client')
                    ->relationship('client', 'shop_name')
                    ->searchable()->preload()->required()
                    ->visible(fn() => auth()->user()?->isSuperAdmin()),

                Forms\Components\TextInput::make('sender_id')->label('Customer ID')->required(),
                Forms\Components\TextInput::make('customer_name')->label('নাম'),
                Forms\Components\TextInput::make('customer_phone')->label('Phone'),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('points')->label('Points (use - for deduction)')->integer()->required(),
                    Forms\Components\Select::make('type')->options([
                        'earned'   => '🟢 Earned',
                        'redeemed' => '🔴 Redeemed',
                        'bonus'    => '🌟 Bonus',
                        'expired'  => '⚫ Expired',
                    ])->required(),
                ]),
                Forms\Components\TextInput::make('note')->label('Note'),
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
                Tables\Columns\TextColumn::make('customer_name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('customer_phone')->label('Phone'),
                Tables\Columns\BadgeColumn::make('type')->colors([
                    'success' => 'earned', 'info' => 'bonus',
                    'danger'  => fn ($s) => in_array($s, ['redeemed', 'expired']),
                ]),
                Tables\Columns\TextColumn::make('points')->label('Points')
                    ->formatStateUsing(fn($s) => $s > 0 ? "+{$s}" : (string)$s)
                    ->color(fn($s) => $s > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('note')->label('Note')->limit(40),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->date('d M Y'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLoyaltyPoints::route('/'),
            'create' => Pages\CreateLoyaltyPoint::route('/create'),
        ];
    }
}
