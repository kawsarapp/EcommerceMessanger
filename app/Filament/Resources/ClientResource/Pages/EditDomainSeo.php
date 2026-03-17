<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;
use App\Filament\Resources\ClientResource\Schemas\Tabs\DomainSeoTab;

class EditDomainSeo extends EditRecord
{
    protected static string $resource = ClientResource::class;
    protected static ?string $title = 'Domain & SEO';
    protected static ?string $navigationIcon = 'heroicon-m-globe-alt';

    public function form(Form $form): Form
    {
        return $form->schema(
            DomainSeoTab::schema()
        );
    }

            public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if super admin has hidden this menu for the client
        $client = $user->client_id 
            ? \App\Models\Client::find($user->client_id) 
            : ($user->client ?? null);

        if ($client) {
            // First check plan based hidden menus
            if ($client->plan) {
                $planHiddenMenus = $client->plan->hidden_menus ?? [];
                if (is_array($planHiddenMenus) && in_array('domain-seo', $planHiddenMenus)) {
                    return false; // Menu is hidden by the assigned plan
                }
            }
            
            // Then check client specific override hidden menus
            $hiddenMenus = $client->admin_permissions['hidden_menus'] ?? [];
            if (is_array($hiddenMenus) && in_array('domain-seo', $hiddenMenus)) {
                return false; // Menu is hidden specifically for this client
            }
        }

        if ($user->role === 'staff') {
            return $user->hasStaffPermission('manage_domain_seo');
        }

        return true; // Seller has access (since it's not hidden)
    }
}
