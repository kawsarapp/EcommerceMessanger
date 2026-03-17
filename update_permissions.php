<?php

$pagesDir = __DIR__ . '/app/Filament/Resources/ClientResource/Pages';
$files = glob($pagesDir . '/*.php');

foreach ($files as $file) {
    if (basename($file) === 'ListClients.php' || basename($file) === 'CreateClient.php') {
        continue;
    }

    $content = file_get_contents($file);
    
    if (basename($file) === 'EditAdminPermissions.php') {
        continue;
    }
    
    // Replace the old bloated if condition with the simpler one
    $pattern = '/if \(\$user->isSuperAdmin.*?return true;/s';
    $replacement = "if (\$user->isSuperAdmin() || \$user->role !== 'staff') {\n            return true;\n        }";
    
    $content = preg_replace($pattern, $replacement, $content);
    file_put_contents($file, $content);
}

echo "Permissions updated successfully.";
