<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlashSaleResource\Pages;
use App\Models\FlashSale;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FlashSaleResource extends Resource
{
    protected static ?string $model = FlashSale::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = '🛍️ Products & Catalog';
    protected static ?string $navigationLabel = 'Flash Sale';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $client = Client::where('user_id', auth()->id())->first();
        if (!$client) return auth()->user()?->isSuperAdmin() ?? false;
        return $client->canAccessFeature('allow_flash_sale');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Flash Sale তৈরি করুন')->schema([
                // Admin: কোন shop এর জন্য flash sale
                Forms\Components\Select::make('client_id')
                    ->label('Shop / Client')
                    ->relationship('client', 'shop_name')
                    ->searchable()->preload()->required()
                    ->live()
                    ->visible(fn() => auth()->user()?->isSuperAdmin()),

                Forms\Components\TextInput::make('title')->label('শিরোনাম')->required()->maxLength(100),
                Forms\Components\Textarea::make('description')->label('বিবরণ')->rows(2),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('discount_type')->label('ছাড়ের ধরন')
                        ->options(['percent' => 'শতকরা (%)','fixed' => 'নির্দিষ্ট টাকা'])
                        ->default('percent')->required()->live(),
                    Forms\Components\TextInput::make('discount_percent')->label('ছাড় (%)')->numeric()->default(10)
                        ->visible(fn(Forms\Get $get) => $get('discount_type') === 'percent'),
                    Forms\Components\TextInput::make('discount_amount')->label('ছাড় (৳)')->numeric()->default(0)
                        ->visible(fn(Forms\Get $get) => $get('discount_type') === 'fixed'),
                ]),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DateTimePicker::make('starts_at')->label('শুরু')->required()->default(now()),
                    Forms\Components\DateTimePicker::make('ends_at')->label('শেষ')->required()->default(now()->addHours(24)),
                ]),
                Forms\Components\FileUpload::make('banner_image')->label('ব্যানার ছবি (Optional)')->image()->directory('flash-sales'),
                Forms\Components\Toggle::make('is_active')->label('সক্রিয়')->default(true),
            ]),

            Forms\Components\Section::make('🛍️ Flash Sale Products')
                ->description('কোন কোন পণ্য এই Flash Sale-এ থাকবে তা সিলেক্ট করুন।')
                ->schema([
                    Forms\Components\Select::make('product_ids')
                        ->label('পণ্য সিলেক্ট করুন (একাধিক)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function (Forms\Get $get) {
                            $user = auth()->user();
                            $query = \App\Models\Product::query()->where('stock_status', 'in_stock');
                            
                            if ($user?->isSuperAdmin()) {
                                $clientId = $get('client_id');
                                if ($clientId) {
                                    $query->where('client_id', $clientId);
                                }
                            } else {
                                $clientId = \App\Models\Client::where('user_id', auth()->id())->value('id');
                                $query->where('client_id', $clientId);
                            }
                            
                            return $query->pluck('name', 'id');
                        })
                        ->helperText('শুধুমাত্র স্টকে আছে এমন পণ্য দেখাচ্ছে। একাধিক পণ্য সিলেক্ট করুন।')
                        ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('title')->label('শিরোনাম')->weight('bold')->searchable(),
                Tables\Columns\TextColumn::make('discount_type')->label('ধরন')
                    ->formatStateUsing(fn($state) => $state === 'percent' ? 'শতকরা' : 'নির্দিষ্ট'),
                Tables\Columns\TextColumn::make('discount_percent')->label('ছাড়')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('product_ids')
                    ->label('পণ্য সংখ্যা')
                    ->formatStateUsing(fn($state) => is_array($state) ? count($state) . ' টি পণ্য' : (is_string($state) ? count(json_decode($state, true) ?? []) . ' টি পণ্য' : '0 টি পণ্য'))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('starts_at')->label('শুরু')->since()->tooltip(fn($record) => $record->starts_at->format('d M y, h:i A'))->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->label('শেষ')->since()->tooltip(fn($record) => $record->ends_at->format('d M y, h:i A'))->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('সক্রিয়'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->is_active) return 'বন্ধ';
                        return (method_exists($record, 'isLive') && $record->isLive()) ? '🔴 LIVE' : (now()->lt($record->starts_at) ? 'আসছে' : 'শেষ');
                    })->color(fn($state) => match(true) {
                        str_contains($state, 'LIVE') => 'success',
                        str_contains($state, 'আসছে') => 'info',
                        default => 'danger',
                    }),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFlashSales::route('/'),
            'create' => Pages\CreateFlashSale::route('/create'),
            'edit'   => Pages\EditFlashSale::route('/{record}/edit'),
        ];
    }

    // Note: mutateFormDataBeforeCreate must be in the Pages/CreateFlashSale.php, not here
}
