<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = '🛍️ Products & Catalog';
    protected static ?int $navigationSort = 2;

    // ── Access Control ─────────────────────────────────────

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        if ($user->isStaff()) return $user->hasStaffPermission('view_products');

        // Seller can view if they have an active plan
        return $user->client && $user->client->hasActivePlan();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        // Seller নিজের private categories তৈরি করতে পারবে
        return $user->client && $user->client->hasActivePlan();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        // Seller শুধু তার নিজের (private, non-global) categories edit করতে পারবে
        if ($record->is_global) return false;
        return $user->client && $record->client_id === $user->client->id;
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        // Seller শুধু তার নিজের private categories delete করতে পারবে
        if ($record->is_global) return false;
        return $user->client && $record->client_id === $user->client->id;
    }

    // ── Query Isolation ────────────────────────────────────

    /**
     * Super admin দেখবে সব।
     * Seller/Staff দেখবে: global categories + নিজের private categories।
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return parent::getEloquentQuery();
        }

        $clientId = $user?->isStaff()
            ? $user->client_id
            : $user?->client?->id;

        return parent::getEloquentQuery()->whereNested(function ($q) use ($clientId) {
            $q->where('is_global', true)
              ->orWhere('client_id', $clientId);
        });
    }

    // ── Form ───────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin();
        $clientId = $user?->client?->id;

        return $form->schema([
            Forms\Components\Section::make('Category Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    // Super Admin: assign to a client or make it global
                    Forms\Components\Toggle::make('is_global')
                        ->label('Global Category (visible to ALL sellers)')
                        ->helperText('ON করলে এই ক্যাটাগরি সকল সেলারের জন্য দেখাবে। OFF করলে শুধু selected shop এর জন্য।')
                        ->visible($isSuperAdmin)
                        ->live()
                        ->default(true),

                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Category (Sub-category তৈরি করতে)')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('খালি রাখলে এটি একটি Main Category হিসেবে তৈরি হবে।'),

                    Forms\Components\Select::make('client_id')
                        ->label('Shop / Client (for private category)')
                        ->relationship('client', 'shop_name')
                        ->searchable()
                        ->visible($isSuperAdmin)
                        ->nullable()
                        ->columnSpanFull()
                        ->hidden(fn (\Filament\Forms\Get $get) => $isSuperAdmin && $get('is_global')),

                    // Seller এর জন্য: client_id ও is_global auto-set হবে, hidden রাখা হবে
                    Forms\Components\Hidden::make('client_id')
                        ->visible(!$isSuperAdmin)
                        ->default($clientId),

                    Forms\Components\Hidden::make('is_global')
                        ->visible(!$isSuperAdmin)
                        ->default(false),

                ])->columns(2),

            Forms\Components\Section::make('Category Banner & Settings')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->label('Category Icon / Thumbnail')
                        ->helperText('Homepage category card এ এই ছবি দেখাবে। Square (1:1) ছবি দিন।')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('categories/icons')
                        ->visibility('public')
                        ->maxSize(2048),

                    Forms\Components\FileUpload::make('banner_image')
                        ->label('Category Banner (Optional)')
                        ->helperText('Category page এর top এ full-width banner দেখাবে।')
                        ->image()
                        ->directory('categories/banners')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('banner_link')
                        ->label('Banner Link (URL)')
                        ->url(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Serial / Sort Order')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('homepage_products_count')
                        ->label('Homepage Products Count')
                        ->helperText('হোমপেজে এই ক্যাটাগরি থেকে কয়টি প্রোডাক্ট দেখাবে')
                        ->numeric()
                        ->default(4)
                        ->minValue(1)
                        ->maxValue(20),

                    Forms\Components\Toggle::make('is_visible')
                        ->label('Show on Homepage')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    // ── Table ──────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $isSuperAdmin = auth()->user()?->isSuperAdmin();

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('banner_image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-category.png')),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Global badge
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Type')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record) => $record->is_global ? 'Global (all sellers)' : 'Private (seller only)')
                    ->visible($isSuperAdmin),

                Tables\Columns\TextColumn::make('client.shop_name')
                    ->label('Shop')
                    ->default('—')
                    ->visible($isSuperAdmin),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge(),

                Tables\Columns\TextInputColumn::make('sort_order')
                    ->label('Serial')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Visible'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d M, Y'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\ViewAction::make()
                    ->visible(fn ($record) => !static::canEdit($record)),
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
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}