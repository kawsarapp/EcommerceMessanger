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


class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'System Settings';

    // শুধুমাত্র এডমিন (ID 1) প্ল্যান তৈরি/এডিট/ডিলিট করতে পারবে
    public static function canCreate(): bool { return auth()->id() === 1; }
    public static function canEdit(Model $record): bool { return auth()->id() === 1; }
    public static function canDelete(Model $record): bool { return auth()->id() === 1; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->description('Set up your subscription package limits and pricing.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Plan Name')
                            ->placeholder('e.g. Premium Plan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('price')
                            ->label('Monthly Price')
                            ->numeric()
                            ->prefix('৳')
                            ->required(),

                        Forms\Components\TextInput::make('product_limit')
                            ->label('Max Products')
                            ->helperText('How many products can a client add?')
                            ->numeric()
                            ->default(10)
                            ->required(),

                        Forms\Components\TextInput::make('order_limit')
                            ->label('Max Monthly Orders')
                            ->helperText('Limit for orders per month.')
                            ->numeric()
                            ->default(50)
                            ->required(),

                        Forms\Components\TextInput::make('ai_message_limit')
                            ->label('Max AI Messages')
                            ->helperText('Total AI replies allowed per month.')
                            ->numeric()
                            ->default(100)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->fontFamily('Inter')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_limit')
                    ->label('Products')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('order_limit')
                    ->label('Orders')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('ai_message_limit')
                    ->label('AI Messages')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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