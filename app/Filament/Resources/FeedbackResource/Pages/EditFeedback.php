<?php

namespace App\Filament\Resources\FeedbackResource\Pages;

use App\Filament\Resources\FeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeedback extends EditRecord
{
    protected static string $resource = FeedbackResource::class;

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
            if (isset($data['admin_reply']) && filled($data['admin_reply'])) {
                // If admin replied, record who and when
                $data['replied_by'] = $user->id;
                // If not already replied, set timestamp
                if (!$this->record->replied_at) {
                    $data['replied_at'] = now();
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
