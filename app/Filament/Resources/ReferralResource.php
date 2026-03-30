<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Referral;
use App\Models\Client;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = '👥 Customers & Reviews';
    protected static ?string $navigationLabel = 'Referrals';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_referral');
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
                Tables\Columns\TextColumn::make('sender_id')->label('Referrer ID')->searchable(),
                Tables\Columns\TextColumn::make('referral_code')->label('Code')
                    ->badge()->color('primary')->copyable(),
                Tables\Columns\TextColumn::make('referred_sender_id')->label('Referred By')->placeholder('—'),
                Tables\Columns\TextColumn::make('reward_amount')->label('Reward')->prefix('৳'),
                Tables\Columns\TextColumn::make('discount_amount')->label('Discount Given')->prefix('৳'),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'pending',
                    'success' => 'completed',
                    'danger'  => 'expired',
                ]),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->date('d M Y'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReferrals::route('/')];
    }
}
