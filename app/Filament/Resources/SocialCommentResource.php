<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialCommentResource\Pages;
use App\Models\SocialComment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SocialCommentResource extends Resource
{
    protected static ?string $model = SocialComment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'Social Comments';
    protected static ?string $navigationGroup = 'Shop Management';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        $client = $user->client;
        if (!$client) return false;

        // canAccessFeature() checks admin override first, then plan
        if (!$client->canAccessFeature('allow_facebook_messenger')) return false;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_customers');
        }

        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

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
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'facebook'  => 'info',
                        'instagram' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'facebook'  => '📘 Facebook',
                        'instagram' => '📸 Instagram',
                        default     => ucfirst($state),
                    }),

                TextColumn::make('sender_name')
                    ->label('Customer')
                    ->weight('bold')
                    ->description(fn (SocialComment $record) => $record->sender_id ? 'ID: ' . $record->sender_id : null)
                    ->searchable(),

                TextColumn::make('comment_text')
                    ->label('Comment')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('reply_text')
                    ->label('Reply Sent')
                    ->limit(50)
                    ->color('success')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'        => 'warning',
                        'auto_replied'   => 'success',
                        'manual_replied' => 'info',
                        'ignored'        => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->filters([
                // প্ল্যাটফর্ম ফিল্টার
                SelectFilter::make('platform')
                    ->options([
                        'facebook'  => '📘 Facebook',
                        'instagram' => '📸 Instagram',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'pending'        => 'Pending',
                        'auto_replied'   => 'Auto Replied (AI)',
                        'manual_replied' => 'Manual Replied',
                        'ignored'        => 'Ignored (Not Sales)',
                    ]),

                // টাইম ফিল্টার
                SelectFilter::make('time_filter')
                    ->label('Filter by Time')
                    ->options([
                        '1_hour'  => 'Last 1 Hour',
                        '3_hours' => 'Last 3 Hours',
                        '24_hours'=> 'Last 24 Hours',
                        '7_days'  => 'Last 7 Days',
                    ])
                    ->query(function (Builder $query, array $data) {
                        match ($data['value'] ?? null) {
                            '1_hour'   => $query->where('created_at', '>=', Carbon::now()->subHour()),
                            '3_hours'  => $query->where('created_at', '>=', Carbon::now()->subHours(3)),
                            '24_hours' => $query->where('created_at', '>=', Carbon::now()->subDay()),
                            '7_days'   => $query->where('created_at', '>=', Carbon::now()->subDays(7)),
                            default    => null,
                        };
                    }),
            ])
            ->actions([
                // ✏️ Manual Reply
                Tables\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->modalHeading('Send Manual Reply')
                    ->modalDescription(fn (SocialComment $record) =>
                        $record->reply_text
                            ? '✅ Previous AI reply: "' . \Illuminate\Support\Str::limit($record->reply_text, 80) . '"'
                            : 'No previous reply sent yet.'
                    )
                    ->form(fn (SocialComment $record) => [
                        Textarea::make('manual_reply_text')
                            ->label('Your Reply')
                            ->default($record->reply_text)
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (SocialComment $record, array $data) {
                        $token = $record->client?->fb_page_token;

                        if (!$token) {
                            \Filament\Notifications\Notification::make()
                                ->title('Facebook Page Not Connected')
                                ->body('এই শপের Facebook Page Token কনফিগার করা নেই।')
                                ->danger()->send();
                            return;
                        }

                        // প্ল্যাটফর্ম অনুযায়ী সঠিক API endpoint
                        $endpoint = $record->platform === 'instagram'
                            ? "https://graph.facebook.com/v19.0/{$record->comment_id}/replies"
                            : "https://graph.facebook.com/v19.0/{$record->comment_id}/comments";

                        $response = Http::post($endpoint, [
                            'message'      => $data['manual_reply_text'],
                            'access_token' => $token,
                        ]);

                        if ($response->successful()) {
                            $record->update([
                                'status'     => 'manual_replied',
                                'reply_text' => $data['manual_reply_text'],
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Reply Sent Successfully')
                                ->success()->send();
                        } else {
                            $body = $response->json();
                            $errMsg = $body['error']['message'] ?? $response->body();
                            \Filament\Notifications\Notification::make()
                                ->title('❌ Failed to send reply')
                                ->body($errMsg)
                                ->danger()->send();
                        }
                    }),

                // 🚫 Ignore Action
                Tables\Actions\Action::make('ignore')
                    ->label('Ignore')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Ignored')
                    ->modalDescription('এই comment টি sales-related নয় বলে মার্ক করবেন?')
                    ->visible(fn (SocialComment $record) => $record->status === 'pending')
                    ->action(function (SocialComment $record) {
                        $record->update(['status' => 'ignored']);
                        \Filament\Notifications\Notification::make()
                            ->title('Marked as Ignored')
                            ->success()->send();
                    }),

                // 🗑️ Delete
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('mark_ignored_bulk')
                        ->label('Mark as Ignored')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'ignored']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialComments::route('/'),
        ];
    }
    
    public static function canCreate(): bool { return false; }
}