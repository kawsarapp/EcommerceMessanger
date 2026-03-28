<?php
namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * সেলার নতুন ক্যাটাগরি তৈরি করলে অটোমেটিক তার client_id সেট হয়ে যাবে
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            // SuperAdmin: client_id & is_global come from form fields.
            // If is_global is true, client_id can remain null (global categories).
            return $data;
        }

        // Regular seller — always force their own client_id, never global
        $data['client_id'] = $user->client?->id ?? $user->client_id;
        $data['is_global'] = false;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}