<?php

$dir = __DIR__ . '/resources/views/shop/themes';
$themes = glob($dir . '/*', GLOB_ONLYDIR);

foreach ($themes as $themeDir) {
    $layoutFile = $themeDir . '/layout.blade.php';
    if (!file_exists($layoutFile)) continue;
    
    // bdshop has a completely new layout in HEAD which already includes the floating chat safely
    if (basename($themeDir) === 'bdshop') {
        continue;
    }

    $content = file_get_contents($layoutFile);
    
    // Check if floating-chat is already there
    if (strpos($content, 'floating-chat') === false) {
        $replacement = "    @include('shop.partials.floating-chat', ['client' => \$client])\n</body>";
        $content = str_replace('</body>', $replacement, $content);
        file_put_contents($layoutFile, $content);
        echo "Fixed " . basename($themeDir) . "\n";
    }
}
