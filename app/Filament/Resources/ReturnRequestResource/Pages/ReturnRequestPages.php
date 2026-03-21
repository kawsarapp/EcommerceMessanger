<?php

namespace App\Filament\Resources\ReturnRequestResource\Pages;

use App\Filament\Resources\ReturnRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\EditRecord;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;
}

class EditReturnRequest extends EditRecord
{
    protected static string $resource = ReturnRequestResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
