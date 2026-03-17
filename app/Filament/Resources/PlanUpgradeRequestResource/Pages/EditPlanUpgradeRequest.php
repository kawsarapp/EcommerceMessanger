<?php

namespace App\Filament\Resources\PlanUpgradeRequestResource\Pages;

use App\Filament\Resources\PlanUpgradeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanUpgradeRequest extends EditRecord
{
    protected static string $resource = PlanUpgradeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            $data['reviewed_by'] = $user->id;
            $data['reviewed_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // If approved, update the client's plan
        $record = $this->record;
        if ($record->status === 'approved' && $record->requested_plan_id) {
            $client = $record->client;
            if ($client) {
                // Determine new expiry date (e.g. 1 month from now or keep existing if desired)
                // For now, let's keep it simple: just update the plan and reset the expiry to 30 days from now
                $client->update([
                    'plan_id' => $record->requested_plan_id,
                    'plan_ends_at' => now()->addDays(30),
                    'status' => 'active', // Ensure active status
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
