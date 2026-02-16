<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-star'; // আইকন চেঞ্জ করা হয়েছে
    
    protected static ?string $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 1;

    // 🔥 STRICT PERMISSION: Seller রা এই মেনুই দেখতে পাবে না
    public static function canViewAny(): bool { return auth()->id() === 1; }
    public static function canCreate(): bool { return auth()->id() === 1; }
    public static function canEdit(Model $record): bool { return auth()->id() === 1; }
    public static function canDelete(Model $record): bool { return auth()->id() === 1; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 🎨 Section 1: Branding & Basics
                Section::make('Identity & Pricing')
                    ->description('প্ল্যানের নাম এবং দাম নির্ধারণ করুন।')
                    ->icon('heroicon-m-swatch')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Plan Name')
                                ->placeholder('e.g. Gold Membership')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-m-tag'),

                            TextInput::make('price')
                                ->label('Monthly Price')
                                ->numeric()
                                ->prefix('৳')
                                ->required()
                                ->placeholder('0.00'),
                        ]),

                        Grid::make(2)->schema([
                            ColorPicker::make('color')
                                ->label('Theme Color')
                                ->default('#4f46e5'),

                            Toggle::make('is_featured')
                                ->label('Mark as Recommended?')
                                ->onColor('success')
                                ->helperText('Pricing পেজে এটি হাইলাইট করা থাকবে।'),
                        ]),

                        Textarea::make('description')
                            ->label('Short Description')
                            ->placeholder('Best for small businesses...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                // 🚀 Section 2: Limits & Quotas
                Section::make('Usage Limits')
                    ->description('এই প্ল্যানে ইউজার কতটুকু সুবিধা পাবে।')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('product_limit')
                                ->label('Max Products')
                                ->numeric()
                                ->default(10)
                                ->suffix('Items')
                                ->required(),

                            TextInput::make('order_limit')
                                ->label('Max Orders/Month')
                                ->numeric()
                                ->default(50)
                                ->suffix('Orders')
                                ->required(),

                            TextInput::make('ai_message_limit')
                                ->label('AI Replies')
                                ->numeric()
                                ->default(100)
                                ->suffix('Msgs')
                                ->required(),
                        ]),
                    ]),

                // ⚙️ Section 3: Status
                Section::make('Availability')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true)
                            ->helperText('বন্ধ থাকলে কেউ এই প্ল্যান কিনতে পারবে না।'),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width(40),

                TextColumn::make('name')
                    ->label('Plan Name')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (Plan $record) => $record->description ? \Illuminate\Support\Str::limit($record->description, 30) : null),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('BDT')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                // Limits Grouped
                TextColumn::make('limits_summary')
                    ->label('Limits (Prod / Ord / AI)')
                    ->default(fn (Plan $record) => "{$record->product_limit} / {$record->order_limit} / {$record->ai_message_limit}")
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-star'),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->disabled(fn () => auth()->id() !== 1), // ডবল চেক: এডমিন ছাড়া কেউ টগল করতে পারবে না

                TextColumn::make('created_at')
                    ->dateTime('d M, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('price', 'asc')
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}