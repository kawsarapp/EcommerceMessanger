<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanUpgradeRequestResource\Pages;
use App\Models\PlanUpgradeRequest;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PlanUpgradeRequestResource extends Resource
{
    protected static ?string $model = PlanUpgradeRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Billing & Plans';
    protected static ?string $navigationLabel = 'Upgrade Requests';
    protected static ?int $navigationSort = 2;

    // Pending badge count on sidebar
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }
    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    // ── Access ─────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        // Sellers can see their own requests
        return !$user->isStaff() && $user->client && $user->client->hasActivePlan();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isSuperAdmin() || $user->isStaff()) return false;
        // Only seller can submit upgrade request
        return (bool) $user->client;
    }

    public static function canEdit(Model $record): bool
    {
        // Only super admin can review (approve/reject)
        return auth()->user()?->isSuperAdmin() ?? false;
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
            Forms\Components\Section::make('Upgrade Request Details')
                ->schema([
                    // Seller fills this part
                    Forms\Components\Select::make('requested_plan_id')
                        ->label('Plan I want to upgrade to')
                        ->options(Plan::where('is_active', true)->orderBy('price')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->disabled($isSuperAdmin),

                    Forms\Components\Textarea::make('note')
                        ->label('Why do you want to upgrade? (optional)')
                        ->rows(3)
                        ->disabled($isSuperAdmin),

                    // Super Admin fills this part
                    Forms\Components\Select::make('status')
                        ->label('Decision')
                        ->options([
                            'pending'  => '⏳ Pending',
                            'approved' => '✅ Approved',
                            'rejected' => '❌ Rejected',
                        ])
                        ->required()
                        ->visible($isSuperAdmin),

                    Forms\Components\Textarea::make('admin_reply')
                        ->label('Admin Reply / Reason')
                        ->rows(3)
                        ->visible($isSuperAdmin),
                ])->columns(['default' => 1]),

            // Info panel for super admin
            Forms\Components\Section::make('Request Info')
                ->schema([
                    Forms\Components\Placeholder::make('client_name')
                        ->label('Seller / Shop')
                        ->content(fn ($record) => $record?->client?->shop_name ?? '—'),

                    Forms\Components\Placeholder::make('current_plan_name')
                        ->label('Current Plan')
                        ->content(fn ($record) => $record?->currentPlan?->name ?? 'No Plan'),

                    Forms\Components\Placeholder::make('requested_plan_name')
                        ->label('Requested Plan')
                        ->content(fn ($record) => $record?->requestedPlan?->name ?? '—'),

                    Forms\Components\Placeholder::make('created_at')
                        ->label('Submitted At')
                        ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '—'),
                ])
                ->columns(['default' => 2])
                ->visible($isSuperAdmin)
                ->hiddenOn('create'),
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
                    ->visible($isSuperAdmin),

                Tables\Columns\TextColumn::make('currentPlan.name')
                    ->label('From Plan')
                    ->default('No Plan')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('requestedPlan.name')
                    ->label('To Plan')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Review')->icon('heroicon-o-pencil-square')
                    ->visible($isSuperAdmin),
                Tables\Actions\ViewAction::make()->visible(!$isSuperAdmin),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlanUpgradeRequests::route('/'),
            'create' => Pages\CreatePlanUpgradeRequest::route('/create'),
            'edit'   => Pages\EditPlanUpgradeRequest::route('/{record}/edit'),
        ];
    }
}
