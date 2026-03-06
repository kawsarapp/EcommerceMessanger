<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbandonedCartResource\Pages;
use App\Models\OrderSession;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use App\Services\Messenger\MessengerResponseService;

class AbandonedCartResource extends Resource
{
    protected static ?string $model = OrderSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Abandoned Carts';
    protected static ?string $navigationGroup = 'Shop Management';
    protected static ?string $slug = 'abandoned-carts';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // 🔥 FIX: OrderService 'step' কে 'completed' করে, তাই JSON চেক করতে হবে
        $query->where('customer_info->step', '!=', 'completed');

        if (auth()->id() === 1) {
            return $query;
        }

        return $query->where('client_id', auth()->user()->client->id ?? null);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->state(fn (OrderSession $record) => $record->customer_info['name'] ?? 'Unknown')
                    ->description(fn (OrderSession $record) => $record->sender_id)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('customer_info->name', 'like', "%{$search}%")
                                     ->orWhere('sender_id', 'like', "%{$search}%");
                    })
                    ->weight('bold'),

                TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->state(fn (OrderSession $record) => $record->customer_info['phone'] ?? 'N/A')
                    ->icon('heroicon-m-phone'),

                TextColumn::make('product_name')
                    ->label('Stuck Product')
                    ->state(function (OrderSession $record) {
                        $pid = $record->customer_info['product_id'] ?? null;
                        return $pid ? Product::find($pid)?->name : 'Browsing Phase';
                    })
                    ->limit(25)
                    ->color('info'),

                TextColumn::make('customer_info.step')
                    ->label('Stuck At Step')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'start' => 'gray',
                        'select_variant' => 'warning',
                        'collect_info' => 'danger',
                        'confirm_order' => 'info',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                TextColumn::make('reminder_status')
                    ->label('Reminder')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending', null => 'gray',
                        'sent' => 'warning',
                        'recovered' => 'success',
                        'ignored' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'Pending')),

                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reminder_status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Reminder Sent',
                        'recovered' => 'Recovered (Ordered)',
                    ]),
            ])
            ->actions([
                // 🔥 NEW: Manual Send Reminder Action
                Tables\Actions\Action::make('send_reminder')
                    ->label('Send Push')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Manual Reminder')
                    ->modalDescription('আপনি কি এই কাস্টমারকে মেসেঞ্জারে রিমাইন্ডার মেসেজ পাঠাতে চান?')
                    ->visible(fn (OrderSession $record) => ($record->reminder_status ?? 'pending') !== 'recovered')
                    ->action(function (OrderSession $record) {
                        $client = $record->client;
                        if (!$client || !$client->fb_page_token) {
                            Notification::make()->title('Facebook Page Not Connected')->danger()->send();
                            return;
                        }

                        $pid = $record->customer_info['product_id'] ?? null;
                        $productName = "আপনার পছন্দের প্রোডাক্টটি";
                        if ($pid) {
                            $product = Product::find($pid);
                            if ($product) $productName = "'" . $product->name . "'";
                        }

                        $message = "হ্যালো! 👋\nআপনি {$productName} দেখছিলেন, কিন্তু অর্ডারটি সম্পূর্ণ করেননি। প্রোডাক্টটি স্টক আউট হওয়ার আগেই অর্ডার কনফার্ম করতে চাইলে আমাকে জানাতে পারেন। কোনো সাহায্য লাগবে কি? 😊";

                        try {
                            app(MessengerResponseService::class)->sendMessengerMessage($record->sender_id, $message, $client->fb_page_token);
                            
                            $record->update([
                                'reminder_status' => 'sent',
                                'last_interacted_at' => now(),
                            ]);

                            Notification::make()->title('Reminder Sent Successfully!')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Failed to send reminder')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbandonedCarts::route('/'),
        ];
    }
    
    public static function canCreate(): bool { return false; }
}