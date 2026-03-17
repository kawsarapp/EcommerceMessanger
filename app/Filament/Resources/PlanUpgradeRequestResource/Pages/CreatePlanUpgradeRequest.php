<?php

namespace App\Filament\Resources\PlanUpgradeRequestResource\Pages;

use App\Filament\Resources\PlanUpgradeRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlanUpgradeRequest extends CreateRecord
{
    protected static string $resource = PlanUpgradeRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $client = $user->client;
        $data['client_id']       = $client->id;
        $data['current_plan_id'] = $client->plan_id;
        $data['status']          = 'pending';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
