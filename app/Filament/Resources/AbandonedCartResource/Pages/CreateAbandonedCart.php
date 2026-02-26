<?php

namespace App\Filament\Resources\AbandonedCartResource\Pages;

use App\Filament\Resources\AbandonedCartResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAbandonedCart extends CreateRecord
{
    protected static string $resource = AbandonedCartResource::class;
}
