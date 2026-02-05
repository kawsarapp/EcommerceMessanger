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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'Shop Management';

    /**
     * এডমিন কন্ট্রোল: শুধুমাত্র ইউজার আইডি ১ (আপনি) তৈরি/এডিট/ডিলিট করতে পারবেন।
     */
    public static function canCreate(): bool
    {
        return auth()->id() === 1;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->id() === 1;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->id() === 1;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                            
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('slug')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products') // এই ক্যাটাগরিতে কয়টি প্রোডাক্ট আছে তা দেখাবে
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            
            ->actions([
                // এডমিন (ID 1) হলে এডিট বাটন, বাকিদের জন্য ভিউ বাটন
                auth()->id() === 1 
                    ? Tables\Actions\EditAction::make() 
                    : Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions(
                // এখানে সরাসরি কন্ডিশনাল অ্যারে ব্যবহার করা হয়েছে
                auth()->id() === 1 
                    ? [
                        Tables\Actions\BulkActionGroup::make([
                            Tables\Actions\DeleteBulkAction::make(),
                        ]),
                    ] 
                    : [] // এডমিন না হলে খালি অ্যারে
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}