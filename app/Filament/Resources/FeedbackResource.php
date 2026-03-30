<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbackResource\Pages;
use App\Models\Feedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationGroup = '💬 Communication';
    protected static ?string $navigationLabel = 'Feedback & Support';
    protected static ?int $navigationSort = 5;

    // Unread open tickets badge
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'open')->count();
        return $count > 0 ? (string) $count : null;
    }
    public static function getNavigationBadgeColor(): ?string { return 'danger'; }

    // ── Access ─────────────────────────────────────────────
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        // Sellers can view their own tickets; staff cannot
        return !$user->isStaff() && (bool) $user->client;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isSuperAdmin() || $user->isStaff()) return false;
        return (bool) $user->client;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        // Seller can edit only their own OPEN tickets
        return !$user->isStaff()
            && $record->client_id === $user->client?->id
            && $record->status === 'open';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    // ── Query isolation ────────────────────────────────────
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if ($user?->isSuperAdmin()) return parent::getEloquentQuery();
        $clientId = $user?->client?->id;
        return parent::getEloquentQuery()->where('client_id', $clientId);
    }

    // ── Form ───────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin();

        return $form->schema([
            Forms\Components\Section::make('Feedback / Support Ticket')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Feedback Type')
                        ->options([
                            'bug'       => '🐛 Bug Report',
                            'feature'   => '💡 Feature Request',
                            'complaint' => '⚠️ Complaint',
                            'general'   => '💬 General Feedback',
                        ])
                        ->required()
                        ->disabled($isSuperAdmin),

                    Forms\Components\TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->disabled($isSuperAdmin),

                    Forms\Components\Textarea::make('message')
                        ->required()
                        ->rows(5)
                        ->disabled($isSuperAdmin)
                        ->columnSpanFull(),
                ])->columns(['default' => 2]),

            Forms\Components\Section::make('Admin Reply')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'open'        => '🔴 Open',
                            'in_progress' => '🟡 In Progress',
                            'resolved'    => '🟢 Resolved',
                            'closed'      => '⚫ Closed',
                        ])
                        ->required()
                        ->disabled(! $isSuperAdmin),

                    Forms\Components\Textarea::make('admin_reply')
                        ->label(fn () => $isSuperAdmin ? 'Reply to Seller' : 'Message from Admin')
                        ->rows(4)
                        ->columnSpanFull()
                        ->disabled(! $isSuperAdmin)
                        ->placeholder(fn () => $isSuperAdmin ? 'Write your reply...' : 'Waiting for admin response...'),
                ])
                ->columns(['default' => 1]),
        ]);
    }

    // ── Table ──────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        $isSuperAdmin = auth()->user()?->isSuperAdmin();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.shop_name')
                    ->label('Shop')
                    ->searchable()
                    ->sortable()
                    ->visible($isSuperAdmin),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'bug'       => 'danger',
                        'feature'   => 'info',
                        'complaint' => 'warning',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(45)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'open'        => 'danger',
                        'in_progress' => 'warning',
                        'resolved'    => 'success',
                        'closed'      => 'gray',
                        default       => 'gray',
                    }),

                Tables\Columns\IconColumn::make('admin_reply')
                    ->label('Replied?')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => filled($record->admin_reply)),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open'        => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved'    => 'Resolved',
                        'closed'      => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'bug'       => 'Bug',
                        'feature'   => 'Feature',
                        'complaint' => 'Complaint',
                        'general'   => 'General',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(fn () => auth()->user()?->isSuperAdmin() ? 'Reply' : 'Edit')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions(
                $isSuperAdmin
                    ? [Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ])]
                    : []
            );
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFeedback::route('/'),
            'create' => Pages\CreateFeedback::route('/create'),
            'edit'   => Pages\EditFeedback::route('/{record}/edit'),
        ];
    }
}
