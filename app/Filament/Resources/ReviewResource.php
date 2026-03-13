<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    // আইকনটি স্টার (Star) করে দেওয়া হলো, রিভিউর সাথে মানানসই
    protected static ?string $navigationIcon = 'heroicon-o-star'; 

    protected static ?string $navigationGroup = 'Shop Management';

    /**
     * 🔥 Data Isolation: ক্লায়েন্ট শুধুমাত্র নিজের পেজ/রিভিউ দেখবে।
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // সুপার এডমিন সব দেখবে, বাকিরা শুধু নিজেরটা
        if (auth()->user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('client', function (Builder $query) {
            $query->where('user_id', auth()->id());
        });
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Details')
                    ->schema([
                        // Hidden Field: অটোমেটিক ইউজারের Client ID সেভ করবে
                        Forms\Components\Hidden::make('client_id')
                            ->default(fn () => auth()->user()->client?->id ?? 1)
                            ->required(),

                        // 🔥 FIX: Admin থেকে রিভিউ বানালে sender_id মিসিং থাকে, তাই ডিফল্ট ভ্যালু দিয়ে দেওয়া হলো
                        Forms\Components\Hidden::make('sender_id')
                            ->default(fn () => 'admin_added_' . uniqid())
                            ->required(),

                        // Product Selection
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name', function (Builder $query) {
                                if (!auth()->user()?->isSuperAdmin()) {
                                    $query->where('client_id', auth()->user()->client?->id);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Select Product'),

                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Customer Name'),

                        // Rating Dropdown
                        Forms\Components\Select::make('rating')
                            ->options([
                                1 => '1 Star ⭐',
                                2 => '2 Stars ⭐⭐',
                                3 => '3 Stars ⭐⭐⭐',
                                4 => '4 Stars ⭐⭐⭐⭐',
                                5 => '5 Stars ⭐⭐⭐⭐⭐',
                            ])
                            ->required()
                            ->label('Rating'),

                        Forms\Components\Textarea::make('comment')
                            ->required()
                            ->columnSpanFull()
                            ->label('Review Comment'),

                        Forms\Components\Toggle::make('is_visible')
                            ->label('Show on Website')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Product')->limit(30)->searchable(),
                TextColumn::make('customer_name')->label('Customer')->weight('bold')->searchable(),
                TextColumn::make('rating')->label('Rating')->formatStateUsing(fn ($state) => str_repeat('⭐', $state)),
                TextColumn::make('comment')->label('Review Comment')->wrap()->limit(50),
                ToggleColumn::make('is_visible')->label('Show on Website'), // এক ক্লিকেই হাইড/শো
                TextColumn::make('created_at')->dateTime('d M, Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}