<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;
use App\Filament\Resources\ClientResource\Schemas\Tabs\StorefrontHeaderTab;

class EditStorefrontHeader extends EditRecord
{
    protected static string $resource = ClientResource::class;
    protected static ?string $title = 'Header & Brand';
    protected static ?string $navigationIcon = 'heroicon-m-sparkles';
    protected static ?string $navigationLabel = '🎨 Header & Brand';

    public function form(Form $form): Form
    {
        return $form->schema(
            StorefrontHeaderTab::schema()
        );
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->isSuperAdmin()) return true;

        $client = $user->client_id
            ? \App\Models\Client::find($user->client_id)
            : ($user->client ?? null);

        if ($client) {
            if ($client->plan) {
                $planHiddenMenus = $client->plan->hidden_menus ?? [];
                if (is_array($planHiddenMenus) && in_array('storefront', $planHiddenMenus)) {
                    return false;
                }
            }
            $hiddenMenus = $client->admin_permissions['hidden_menus'] ?? [];
            if (is_array($hiddenMenus) && in_array('storefront', $hiddenMenus)) {
                return false;
            }
        }

        if ($user->role === 'staff') {
            return $user->hasStaffPermission('manage_storefront');
        }

        return true;
    }
}
