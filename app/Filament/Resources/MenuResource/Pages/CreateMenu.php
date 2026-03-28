<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            // client_id comes from the 'Assign to Shop' Select field in the form
            return $data;
        }

        // Regular seller — always inject their own client_id
        $data['client_id'] = $user->client?->id ?? $user->client_id;

        return $data;
    }
}
