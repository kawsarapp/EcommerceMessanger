<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Set;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    // অন্যান্য রিসোর্সের সাথে মিলিয়ে একই গ্রুপে রাখা হলো
    protected static ?string $navigationGroup = 'Shop Management'; 

    /**
     * 🔥 ডাটা আইসোলেশন: ক্লায়েন্ট শুধুমাত্র নিজের পেজ দেখবে।
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
                Forms\Components\Section::make('Page Details')
                    ->schema([
                        // Hidden Field: নিজে থেকেই ইউজারের Client ID সেভ করবে
                        Forms\Components\Hidden::make('client_id')
                            ->default(fn () => auth()->user()->client?->id ?? 1)
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        // (নোট: আপনার ডাটাবেসে পেজের কন্টেন্টের কলামের নাম যদি 'content' না হয়ে 'body' বা অন্য কিছু হয়, তবে শুধু নিচের 'content' লেখাটি চেঞ্জ করে দিবেন)
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

   public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M, Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                // 🔥 URL GENERATION FIX (আপনার আগের কোডটি রাখা হয়েছে)
                Tables\Actions\Action::make('View')
                    ->icon('heroicon-m-eye')
                    ->url(function (Page $record) {
                        $client = $record->client;
                        
                        // যদি কাস্টম ডোমেইন থাকে
                        if ($client && $client->custom_domain) {
                            return "https://{$client->custom_domain}/{$record->slug}";
                        }

                        // যদি মেইন ডোমেইন (সাব-পাথ) হয়
                        if ($client) {
                            return route('shop.page.slug', [
                                'slug' => $client->slug, 
                                'pageSlug' => $record->slug
                            ]);
                        }

                        return '#'; // Fallback
                    })
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}