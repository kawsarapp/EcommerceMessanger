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

    // সেলার শুধু নিজের শপের কমেন্ট দেখবে
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (auth()->id() === 1) return $query;
        return $query->where('client_id', auth()->user()->client->id ?? null);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'facebook' => 'info',
                        'instagram' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('sender_name')
                    ->label('Customer')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('comment_text')
                    ->label('Comment')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'auto_replied' => 'success',
                        'manual_replied' => 'info',
                        'ignored' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // প্ল্যাটফর্ম ফিল্টার (ফেসবুক নাকি ইনস্টাগ্রাম)
                SelectFilter::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                    ]),

                // টাইম ফিল্টার (১ ঘণ্টা, ৩ ঘণ্টা, ১ দিন)
                SelectFilter::make('time_filter')
                    ->label('Filter by Time')
                    ->options([
                        '1_hour' => 'Last 1 Hour',
                        '3_hours' => 'Last 3 Hours',
                        '24_hours' => 'Last 24 Hours',
                        '7_days' => 'Last 7 Days',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === '1_hour') {
                            $query->where('created_at', '>=', Carbon::now()->subHour());
                        } elseif ($data['value'] === '3_hours') {
                            $query->where('created_at', '>=', Carbon::now()->subHours(3));
                        } elseif ($data['value'] === '24_hours') {
                            $query->where('created_at', '>=', Carbon::now()->subDay());
                        } elseif ($data['value'] === '7_days') {
                            $query->where('created_at', '>=', Carbon::now()->subDays(7));
                        }
                    }),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'auto_replied' => 'Auto Replied (AI)',
                        'manual_replied' => 'Manual Replied',
                        'ignored' => 'Ignored (Not Sales)',
                    ]),
            ])
            ->actions([
                // ম্যানুয়াল রিপ্লাই বাটন
                Tables\Actions\Action::make('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Textarea::make('manual_reply_text')
                            ->label('Write your reply')
                            ->required()
                    ])
                    ->action(function (SocialComment $record, array $data) {
                        $token = $record->client->fb_page_token;
                        
                        // প্ল্যাটফর্ম অনুযায়ী Facebook বা Instagram এর সঠিক API URL সেট করা
                        $endpoint = $record->platform === 'instagram' 
                            ? "https://graph.facebook.com/v19.0/{$record->comment_id}/replies"
                            : "https://graph.facebook.com/v19.0/{$record->comment_id}/comments";

                        // API তে রিপ্লাই পাঠানো
                        $response = Http::post($endpoint, [
                            'message' => $data['manual_reply_text'],
                            'access_token' => $token
                        ]);

                        if ($response->successful()) {
                            $record->update([
                                'status' => 'manual_replied',
                                'reply_text' => $data['manual_reply_text']
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Reply Sent Successfully')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to send reply')
                                ->danger()
                                ->send();
                        }
                    })
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialComments::route('/'),
        ];
    }
    
    // ম্যানুয়ালি ক্রিয়েট অফ করে রাখা হলো
    public static function canCreate(): bool { return false; }
}