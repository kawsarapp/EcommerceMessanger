<?php

namespace App\Filament\Resources\ShippingMethodResource\Pages;

use App\Filament\Resources\ShippingMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingMethod extends CreateRecord
{
    protected static string $resource = ShippingMethodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            // SuperAdmin must pick a client from the form — already filled via Select field.
            // No override needed; $data['client_id'] is already set.
            return $data;
        }

        // Regular seller — always force their own client_id regardless of form input.
        $data['client_id'] = $user->client?->id ?? $user->client_id;

        return $data;
    }
}
