<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRequestResource\Pages;
use App\Models\ReturnRequest;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = '🛒 Sales & Orders';
    protected static ?string $navigationLabel = 'Return/Refund';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_return_refund');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Return Request')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('status')->label('Status')->options([
                        'requested' => '🟡 Requested',
                        'approved'  => '🟢 Approved',
                        'rejected'  => '🔴 Rejected',
                        'returned'  => '📦 Returned',
                        'refunded'  => '💸 Refunded',
                    ])->required(),
                    Forms\Components\TextInput::make('refund_amount')->label('Refund Amount (৳)')->numeric(),
                ]),
                Forms\Components\Textarea::make('admin_note')->label('Admin Note')->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (!auth()->user()?->isSuperAdmin()) {
                    $clientId = Client::where('user_id', auth()->id())->value('id');
                    $query->where('client_id', $clientId);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('order.id')->label('Order #')->prefix('#')->searchable(),
                Tables\Columns\TextColumn::make('customer_name')->label('Customer')
                    ->weight('bold')
                    ->description(fn ($record) => $record->customer_phone)
                    ->searchable(['customer_name', 'customer_phone']),
                Tables\Columns\TextColumn::make('reason_type')->label('কারণ')
                    ->formatStateUsing(fn($state) => match($state) {
                        'defective' => 'ত্রুটিপূর্ণ', 'wrong_item' => 'ভুল আইটেম',
                        'size_issue' => 'সাইজ সমস্যা', 'not_as_described' => 'বর্ণনা অনুযায়ী নয়',
                        default => 'অন্যান্য'
                    }),
                Tables\Columns\TextColumn::make('reason')->label('বিস্তারিত')->limit(50),
                Tables\Columns\SelectColumn::make('status')->label('Status')
                    ->options([
                        'requested' => '🟡 Requested',
                        'approved'  => '🟢 Approved',
                        'rejected'  => '🔴 Rejected',
                        'returned'  => '📦 Returned',
                        'refunded'  => '💸 Refunded',
                    ]),
                Tables\Columns\TextColumn::make('refund_amount')->label('Refund')->prefix('৳')->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Date')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d M Y'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'requested' => 'Requested', 'approved' => 'Approved',
                    'rejected' => 'Rejected', 'returned' => 'Returned', 'refunded' => 'Refunded',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'edit'  => Pages\EditReturnRequest::route('/{record}/edit'),
        ];
    }
}
