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
use Illuminate\Database\Eloquent\Model;

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
        $user = auth()->user();
        
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
        return $query->where('client_id', $clientId);
    }

    /**
     * মেনুতে পেন্ডিং অর্ডারের সংখ্যা দেখানোর জন্য ব্যাজ
     */
    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::where('order_status', 'processing');
        $user = auth()->user();
        
        if (!$user?->isSuperAdmin()) {
            $clientId = $user?->client ? $user->client->id : ($user?->client_id ?? null);
            $query->where('client_id', $clientId);
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

    // ==========================================
    // 🔒 PERMISSION LOGIC
    // ==========================================

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('view_orders');
        }

        return $user->client && $user->client->hasActivePlan();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;
        
        if ($user->isStaff()) {
            return $user->hasStaffPermission('edit_orders'); // Assuming creating order is part of editing
        }

        $client = $user->client;
        if (!$client || !$client->hasActivePlan()) return false;

        return !$client->hasReachedOrderLimit();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('edit_orders') && $user->client_id === $record->client_id;
        }

        return $user->client && $user->client->id === $record->client_id && $user->client->hasActivePlan();
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        if ($user->isStaff()) {
            return $user->hasStaffPermission('delete_orders') && $user->client_id === $record->client_id;
        }

        return $user->client && $user->client->id === $record->client_id;
    }
}