<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = [];
foreach (\Illuminate\Support\Facades\Route::getRoutes() as $r) {
    if (strpos((string)$r->getName(), 'filament.admin.pages.connection') !== false) {
        $routes[] = $r->getName();
    }
}
echo json_encode($routes);
