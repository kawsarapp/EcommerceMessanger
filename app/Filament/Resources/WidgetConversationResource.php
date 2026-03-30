<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WidgetConversationResource\Pages;
use App\Models\Client;
use App\Models\OrderSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WidgetConversationResource extends Resource
{
    protected static ?string $model = OrderSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = '💬 Communication';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Widget Conversations';
    protected static ?string $slug            = 'widget-conversations';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('platform', 'widget')
            ->latest('last_interacted_at');

        // Sellers only see their own conversations
        if (!auth()->user()?->isSuperAdmin()) {
            $clientId = Client::where('user_id', auth()->id())->value('id');
            $query->where('client_id', $clientId);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender_id')
                    ->label('Visitor ID')
                    ->formatStateUsing(fn($state) => '🌐 ' . str_replace('widget_', '', $state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer_info.name')
                    ->label('Name')
                    ->weight('bold')
                    ->default('Anonymous')
                    ->description(fn($record) => $record->customer_info['phone'] ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('customer_info->name', 'like', "%{$search}%")
                                     ->orWhere('customer_info->phone', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('client.shop_name')
                    ->label('Shop')
                    ->visible(fn() => auth()->user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'idle',
                        'danger'  => 'closed',
                    ]),

                Tables\Columns\IconColumn::make('is_human_agent_active')
                    ->label('Human Agent')
                    ->boolean()
                    ->trueIcon('heroicon-s-user')
                    ->falseIcon('heroicon-s-cpu-chip')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('last_interacted_at')
                    ->label('Last Active')
                    ->since()
                    ->tooltip(fn($record) => $record->last_interacted_at ? $record->last_interacted_at->format('d M y, h:i A') : null)
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_chat')
                    ->label('Open Chat')
                    ->icon('heroicon-o-chat-bubble-oval-left')
                    ->color('primary')
                    ->url(fn(OrderSession $record) => static::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('toggle_agent')
                    ->label(fn(OrderSession $r) => $r->is_human_agent_active ? 'Hand to AI' : 'Take Over')
                    ->icon(fn(OrderSession $r) => $r->is_human_agent_active ? 'heroicon-o-cpu-chip' : 'heroicon-o-user')
                    ->color(fn(OrderSession $r) => $r->is_human_agent_active ? 'gray' : 'warning')
                    ->action(function (OrderSession $record) {
                        $record->update(['is_human_agent_active' => !$record->is_human_agent_active]);
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'idle'   => 'Idle',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\TernaryFilter::make('is_human_agent_active')
                    ->label('Human Agent')
                    ->trueLabel('Human Only')
                    ->falseLabel('AI Only')
                    ->placeholder('All'),
            ])
            ->searchable()
            ->defaultSort('last_interacted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWidgetConversations::route('/'),
            'view'  => Pages\ViewWidgetConversation::route('/{record}/view'),
        ];
    }
}
