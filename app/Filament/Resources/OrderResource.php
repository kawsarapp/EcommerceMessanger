<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Schemas\OrderFormSchema;
use App\Filament\Resources\OrderResource\Schemas\OrderTableSchema;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Shop Management';

    /**
     * ডাটা আইসোলেশন: ক্লায়েন্ট শুধুমাত্র নিজের অর্ডার দেখবে।
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

    /**
     * মেনুতে পেন্ডিং অর্ডারের সংখ্যা দেখানোর জন্য ব্যাজ
     */
    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::where('order_status', 'processing');
        if (!auth()->user()?->isSuperAdmin()) {
            $query->whereHas('client', fn($q) => $q->where('user_id', auth()->id()));
        }
        return $query->count() ?: null;
    }

    // 🚀 Schema গুলো আলাদা ক্লাস থেকে কল করা হচ্ছে
    public static function form(Form $form): Form
    {
        return $form->schema(OrderFormSchema::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(OrderTableSchema::columns())
            ->defaultSort('created_at', 'desc')
            ->filters(OrderTableSchema::filters())
            ->actions(OrderTableSchema::actions())
            ->bulkActions(OrderTableSchema::bulkActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}