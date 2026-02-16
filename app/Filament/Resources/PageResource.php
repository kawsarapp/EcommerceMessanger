<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Shop Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // অটোমেটিক Client ID সেট হবে
                Hidden::make('client_id')
                    ->default(fn() => Auth::user()->client_id ?? Auth::user()->client->id),

                Forms\Components\Section::make('Page Details')
                    ->schema([
                        TextInput::make('title')
                            ->label('Page Title')
                            ->placeholder('e.g. Return Policy')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->prefix(url('/page/'))
                            ->required()
                            ->disabled() // অটো জেনারেট হবে
                            ->dehydrated(),
                        
                        Toggle::make('is_active')
                            ->label('Publish Page')
                            ->default(true),

                        RichEditor::make('content')
                            ->label('Page Content')
                            ->required()
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('pages/images'),
                    ])->columns(2),
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Page $record) => url('/page/' . $record->slug)) // এখানে আপনার ডোমেইন লজিক বসবে
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // সুপার অ্যাডমিন সব দেখবে, সেলার শুধু তার পেজ দেখবে
        $query = parent::getEloquentQuery();
        if (Auth::id() === 1) return $query;
        
        return $query->where('client_id', Auth::user()->client->id ?? 0);
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