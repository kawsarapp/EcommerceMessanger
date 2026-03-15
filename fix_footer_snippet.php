<?php
$themesDir = __DIR__ . '/resources/views/shop/themes';
$themes = glob($themesDir . '/*', GLOB_ONLYDIR);

foreach ($themes as $theme) {
    $layoutFile = $theme . '/layout.blade.php';
    if (file_exists($layoutFile)) {
        $content = file_get_contents($layoutFile);
        $content = str_replace('$footerSnippet', "    @include('shop.partials.footer-links', ['client' => \$client])", $content);
        file_put_contents($layoutFile, $content);
    }
}
