<?php
$basePath = __DIR__ . '/app/Filament/Resources/ClientResource/Pages';

$pages = [
    ['class' => 'EditBasicInfo', 'title' => 'Basic Info', 'icon' => 'heroicon-m-information-circle', 'schema' => 'BasicInfoTab', 'permission' => 'manage_basic_info', 'slug' => 'basic-info'],
    ['class' => 'EditStorefront', 'title' => 'Storefront', 'icon' => 'heroicon-m-paint-brush', 'schema' => 'StorefrontTab', 'permission' => 'manage_storefront', 'slug' => 'storefront'],
    ['class' => 'EditDomainSeo', 'title' => 'Domain & SEO', 'icon' => 'heroicon-m-globe-alt', 'schema' => 'DomainSeoTab', 'permission' => 'manage_domain_seo', 'slug' => 'domain-seo'],
    ['class' => 'EditAiBrain', 'title' => 'AI Brain & Automation', 'icon' => 'heroicon-m-cpu-chip', 'schema' => 'AiBrainTab', 'permission' => 'manage_ai_brain', 'slug' => 'ai-brain'],
    ['class' => 'EditLogistics', 'title' => 'Logistics', 'icon' => 'heroicon-m-truck', 'schema' => 'LogisticsTab', 'permission' => 'manage_logistics', 'slug' => 'logistics'],
    ['class' => 'EditCourierApi', 'title' => 'Courier API', 'icon' => 'heroicon-m-archive-box-arrow-down', 'schema' => 'CourierApiTab', 'permission' => 'manage_courier_api', 'slug' => 'courier-api'],
    ['class' => 'EditIntegrations', 'title' => 'Integrations & Social', 'icon' => 'heroicon-m-share', 'schema' => 'IntegrationsTab', 'permission' => 'manage_integrations', 'slug' => 'integrations'],
    ['class' => 'EditInboxAutomation', 'title' => 'Inbox Automation', 'icon' => 'heroicon-m-chat-bubble-left-right', 'schema' => 'InboxAutomationTab', 'permission' => 'manage_inbox_automation', 'slug' => 'inbox-automation'],
    ['class' => 'EditStoreSync', 'title' => 'Store Sync', 'icon' => 'heroicon-m-arrow-path-rounded-square', 'schema' => 'StoreSyncTab', 'permission' => 'manage_store_sync', 'slug' => 'store-sync'],
    ['class' => 'EditWhatsAppApi', 'title' => 'WhatsApp API', 'icon' => 'heroicon-m-chat-bubble-oval-left-ellipsis', 'schema' => 'WhatsAppApiTab', 'permission' => 'manage_whatsapp', 'slug' => 'whatsapp-api'],
    ['class' => 'EditAdminPermissions', 'title' => '🔑 Admin Permissions', 'icon' => 'heroicon-m-shield-check', 'schema' => 'AdminPermissionsTab', 'permission' => 'manage_admin_permissions', 'slug' => 'admin-permissions'],
];

foreach ($pages as $p) {
    $code = "<?php\n\nnamespace App\\Filament\\Resources\\ClientResource\\Pages;\n\n";
    $code .= "use App\\Filament\\Resources\\ClientResource;\n";
    $code .= "use Filament\\Resources\\Pages\\EditRecord;\n";
    $code .= "use Filament\\Forms\\Form;\n";
    $code .= "use App\\Filament\\Resources\\ClientResource\\Schemas\\Tabs\\" . $p['schema'] . ";\n\n";
    $code .= "class {$p['class']} extends EditRecord\n{\n";
    $code .= "    protected static string \$resource = ClientResource::class;\n";
    $code .= "    protected static ?string \$title = '{$p['title']}';\n";
    $code .= "    protected static ?string \$navigationIcon = '{$p['icon']}';\n\n";
    $code .= "    public function form(Form \$form): Form\n    {\n";
    
    // Add specific settings schema inside form
    $code .= "        return \$form->schema(\n";
    // For Basic Info we might need to add Subscription Plan on top for Super Admin, but maybe just stick to Schema.
    $code .= "            " . $p['schema'] . "::schema()\n";
    $code .= "        );\n";
    $code .= "    }\n\n";

    if ($p['class'] === 'EditAdminPermissions') {
        $code .= "    public static function canAccess(array \$parameters = []): bool\n    {\n";
        $code .= "        return auth()->user()?->isSuperAdmin() ?? false;\n    }\n";
    } else {
        $code .= "    public static function canAccess(array \$parameters = []): bool\n    {\n";
        $code .= "        \$user = auth()->user();\n";
        $code .= "        if (!\$user) return false;\n";
        $code .= "        if (\$user->isSuperAdmin() || (\$user->client_id && \$user->role !== 'staff') || (\$user->user_id && \$user->role !== 'staff') || \$user->id === \$parameters['record']?->user_id) return true;\n";
        $code .= "        return \$user->hasStaffPermission('{$p['permission']}');\n";
        $code .= "    }\n";
    }

    $code .= "}\n";

    file_put_contents("$basePath/{$p['class']}.php", $code);
}
echo "Pages created successfully.\n";
