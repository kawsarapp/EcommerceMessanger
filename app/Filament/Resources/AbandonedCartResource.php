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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbandonedCartResource extends Resource
{
    protected static ?string $model = OrderSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-x-mark';
    protected static ?string $navigationLabel = 'Abandoned Carts';
    protected static ?string $navigationGroup = '🛒 Sales & Orders';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'abandoned-carts';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        $client = $user->client;
        if (!$client) return false;

        // canAccessFeature() checks admin override first, then plan
        if (!$client->canAccessFeature('allow_abandoned_cart')) return false;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_abandoned');
        }

        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        // শুধু অসম্পূর্ণ সেশনগুলো দেখাবে
        $query->where('customer_info->step', '!=', 'completed');

        if ($user?->isSuperAdmin()) {
            return $query;
        }

        $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
        return $query->where('client_id', $clientId);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->state(fn (OrderSession $record) => $record->customer_info['name'] ?? 'Unknown Guest')
                    ->description(fn (OrderSession $record) => ($record->customer_info['phone'] ?? 'No Phone') . ' • ID: ' . $record->sender_id)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('customer_info->name', 'like', "%{$search}%")
                                     ->orWhere('sender_id', 'like', "%{$search}%");
                    })
                    ->weight('bold'),

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

                TextColumn::make('platform')
                    ->label('Channel')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'whatsapp'  => 'success',
                        'messenger' => 'info',
                        'instagram' => 'warning',
                        'telegram'  => 'primary',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'whatsapp'  => '📱 WhatsApp',
                        'messenger' => '💬 Messenger',
                        'instagram' => '📸 Instagram',
                        'telegram'  => '✈️ Telegram',
                        default     => ucfirst($state ?? 'Unknown'),
                    }),

                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at->format('d M, h:i A'))
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reminder_status')
                    ->label('Reminder Status')
                    ->options([
                        'pending'   => 'Pending',
                        'sent'      => 'Reminder Sent',
                        'recovered' => 'Recovered (Ordered)',
                        'ignored'   => 'Ignored / Lost',
                    ]),
                Tables\Filters\SelectFilter::make('platform')
                    ->label('Channel')
                    ->options([
                        'whatsapp'  => '📱 WhatsApp',
                        'messenger' => '💬 Messenger',
                        'instagram' => '📸 Instagram',
                        'telegram'  => '✈️ Telegram',
                    ]),
            ])
            ->actions([
                
                // 🔥 FEATURE 1: View Chat History (কাস্টমার কোথায় আটকেছে তা পড়ার জন্য)
                Tables\Actions\Action::make('view_history')
                    ->label('Read Chat')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->modalHeading('Customer Conversation History')
                    ->modalSubmitAction(false) // No submit button needed
                    ->modalCancelActionLabel('Close')
                    ->form([
                        Placeholder::make('history')
                            ->label('')
                            ->content(function (OrderSession $record) {
                                $history = $record->customer_info['history'] ?? [];
                                if (empty($history)) return new HtmlString('<p class="text-gray-500">No chat history available.</p>');
                                
                                $html = '<div class="space-y-3 max-h-96 overflow-y-auto p-2">';
                                foreach(array_slice($history, -15) as $chat) { // শেষের ১৫টি মেসেজ দেখাবে
                                    if(!empty($chat['user'])) {
                                        $html .= '<div class="bg-gray-100 border border-gray-200 p-3 rounded-lg text-sm text-gray-800"><span class="font-bold text-gray-600">Customer:</span><br> ' . nl2br(htmlspecialchars($chat['user'])) . '</div>';
                                    }
                                    if(!empty($chat['ai'])) {
                                        $html .= '<div class="bg-blue-50 border border-blue-100 p-3 rounded-lg text-sm text-blue-900"><span class="font-bold text-blue-600">AI Bot:</span><br> ' . nl2br(htmlspecialchars($chat['ai'])) . '</div>';
                                    }
                                }
                                $html .= '</div>';
                                return new HtmlString($html);
                            })
                    ]),

                // 🔥 FEATURE 2: Custom Reminder Message
                Tables\Actions\Action::make('send_reminder')
                    ->label('Send Push')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->modalHeading('Send Custom Reminder')
                    ->modalDescription('আপনি চাইলে এই মেসেজটি এডিট করে কাস্টমারকে স্পেশাল ডিসকাউন্ট অফার করতে পারেন।')
                    ->visible(fn (OrderSession $record) => !in_array($record->reminder_status, ['recovered', 'ignored']))
                    ->form(function (OrderSession $record) {
                        $pid = $record->customer_info['product_id'] ?? null;
                        $productName = "আপনার পছন্দের প্রোডাক্টটি";
                        if ($pid) {
                            $product = Product::find($pid);
                            if ($product) $productName = "'" . $product->name . "'";
                        }

                        $defaultMessage = "হ্যালো! 👋\nআপনি {$productName} দেখছিলেন, কিন্তু অর্ডারটি সম্পূর্ণ করেননি। প্রোডাক্টটি স্টক আউট হওয়ার আগেই অর্ডার কনফার্ম করতে চাইলে আমাকে জানাতে পারেন। কোনো সাহায্য লাগবে কি? 😊";

                        return [
                            Textarea::make('custom_message')
                                ->label('Message to Send')
                                ->default($defaultMessage)
                                ->required()
                                ->rows(4),
                        ];
                    })
                    ->action(function (OrderSession $record, array $data) {
                        $client   = $record->client;
                        $platform = $record->platform ?? 'messenger';
                        $message  = $data['custom_message'];
                        $senderId = $record->sender_id;

                        try {
                            switch ($platform) {
                                case 'whatsapp':
                                    $waApiUrl = config('services.whatsapp.api_url');
                                    if (!$client->wa_instance_id || !$waApiUrl) {
                                        Notification::make()->title('WhatsApp Not Connected')->body('এই শপের WhatsApp Instance ID কনফিগার করা নেই।')->danger()->send();
                                        return;
                                    }
                                    $response = Http::timeout(15)->post($waApiUrl . '/api/send-message', [
                                        'instance_id' => $client->wa_instance_id,
                                        'to'          => $senderId,
                                        'message'     => $message,
                                    ]);
                                    if ($response->status() >= 400) {
                                        throw new \Exception('WhatsApp API error: ' . $response->body());
                                    }
                                    Log::info("✅ Manual WA Reminder sent to {$senderId}");
                                    break;

                                case 'telegram':
                                    if (!$client->telegram_bot_token) {
                                        Notification::make()->title('Telegram Not Connected')->body('এই শপের Telegram Bot Token কনফিগার করা নেই।')->danger()->send();
                                        return;
                                    }
                                    Http::post("https://api.telegram.org/bot{$client->telegram_bot_token}/sendMessage", [
                                        'chat_id' => $senderId,
                                        'text'    => $message,
                                    ]);
                                    Log::info("✅ Manual Telegram Reminder sent to {$senderId}");
                                    break;

                                case 'instagram':
                                case 'messenger':
                                default:
                                    if (!$client->fb_page_token) {
                                        Notification::make()->title('Facebook Page Not Connected')->body('এই শপের Facebook Page Token কনফিগার করা নেই।')->danger()->send();
                                        return;
                                    }
                                    app(MessengerResponseService::class)->sendMessengerMessage($senderId, $message, $client->fb_page_token);
                                    Log::info("✅ Manual Messenger/Instagram Reminder sent to {$senderId}");
                                    break;
                            }

                            $record->update([
                                'reminder_status'    => 'sent',
                                'last_interacted_at' => now(),
                            ]);

                            Notification::make()->title('✅ Reminder Sent via ' . ucfirst($platform) . '!')->success()->send();

                        } catch (\Exception $e) {
                            Log::error("❌ Manual reminder failed [{$platform}] to {$senderId}: " . $e->getMessage());
                            Notification::make()->title('Failed to send reminder')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // 🔥 FEATURE 3: Mark as Ignored (লিস্ট ক্লিন রাখার জন্য)
                Tables\Actions\Action::make('mark_ignored')
                    ->label('Ignore')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Ignored / Lost')
                    ->modalDescription('এই কাস্টমার কি অর্ডার করতে আগ্রহী নয়? এটি ইগনোরড হিসেবে মার্ক করলে আর রিমাইন্ডার যাবে না।')
                    ->visible(fn (OrderSession $record) => !in_array($record->reminder_status, ['recovered', 'ignored']))
                    ->action(function (OrderSession $record) {
                        $record->update(['reminder_status' => 'ignored']);
                        Notification::make()->title('Marked as Ignored')->success()->send();
                    }),

                // 🗑️ Delete
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->searchable();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbandonedCarts::route('/'),
        ];
    }
    
    public static function canCreate(): bool { return false; }
}