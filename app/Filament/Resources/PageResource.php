<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
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
                
                // 🔥 URL GENERATION FIX
                Tables\Actions\Action::make('View')
                    ->icon('heroicon-m-eye')
                    ->url(function (Page $record) {
                        $client = $record->client;
                        
                        // যদি কাস্টম ডোমেইন থাকে
                        if ($client->custom_domain) {
                            return "https://{$client->custom_domain}/{$record->slug}";
                        }

                        // যদি মেইন ডোমেইন (সাব-পাথ) হয়
                        return route('shop.page.slug', [
                            'slug' => $client->slug, 
                            'pageSlug' => $record->slug
                        ]);
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
