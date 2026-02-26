<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbandonedCartResource\Pages;
use App\Models\OrderSession;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class AbandonedCartResource extends Resource
{
    protected static ?string $model = OrderSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Abandoned Carts';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?string $slug = 'abandoned-carts';

    // 🔥 Data Isolation: এক সেলার শুধু নিজের ডাটা দেখবে
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // শুধু অসম্পূর্ণ সেশনগুলো দেখাবে
        $query->where('status', '!=', 'completed');

        if (auth()->id() === 1) {
            return $query;
        }

        return $query->where('client_id', auth()->user()->client->id ?? null);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender_id')
                    ->label('Customer ID (Messenger)')
                    ->searchable()
                    ->copyable()
                    ->limit(10),

                // JSON থেকে ডাটা বের করে দেখানো
                TextColumn::make('customer_info.step')
                    ->label('Stuck At Step')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'start' => 'gray',
                        'variant' => 'warning',
                        'collect_info' => 'danger',
                        'confirm' => 'info',
                        default => 'primary',
                    }),

                TextColumn::make('reminder_status')
                    ->label('Reminder Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'sent' => 'warning',
                        'recovered' => 'success',
                        'ignored' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('last_interacted_at')
                    ->label('Last Activity')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('last_interacted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reminder_status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Reminder Sent',
                        'recovered' => 'Recovered (Ordered)',
                    ]),
            ])
            ->actions([
                // সেলার চাইলে ম্যানুয়ালি এখান থেকেও ইনভয়েস বা মেসেজ চেক করতে পারে
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbandonedCarts::route('/'),
        ];
    }
    
    // সেলার নতুন করে কোনো সেশন ক্রিয়েট করতে পারবে না
    public static function canCreate(): bool { return false; }
}