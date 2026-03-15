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
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
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

                // 🔥 নতুন সেকশন: Category Banner & Settings
                Forms\Components\Section::make('Category Banner & Settings')
                    ->schema([
                        Forms\Components\FileUpload::make('banner_image')
                            ->label('Category Banner (Optional)')
                            ->image()
                            ->directory('categories/banners')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('banner_link')
                            ->label('Banner Link (URL)')
                            ->url(),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Serial / Sort Order (e.g. 1, 2, 3)')
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
                    ->counts('products') // এই ক্যাটাগরিতে কয়টি প্রোডাক্ট আছে তা দেখাবে
                    ->badge(),

                // 🔥 নতুন কলাম: হোমপেজে সিরিয়াল সাজানোর জন্য (Inline Editable)
                Tables\Columns\TextInputColumn::make('sort_order')
                    ->label('Serial')
                    ->sortable(),

                // 🔥 নতুন কলাম: হাইড/শো করার জন্য (Inline Editable)
                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Visible'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            
            ->actions([
                // এডমিন হলে এডিট বাটন, বাকিদের জন্য ভিউ বাটন
                auth()->user()?->isSuperAdmin() 
                    ? Tables\Actions\EditAction::make() 
                    : Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions(
                // এখানে সরাসরি কন্ডিশনাল অ্যারে ব্যবহার করা হয়েছে
                auth()->user()?->isSuperAdmin() 
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