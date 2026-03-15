<?php

$themesDir = __DIR__ . '/resources/views/shop/themes';
$themes = glob($themesDir . '/*', GLOB_ONLYDIR);

foreach ($themes as $theme) {
    // 1. Clean product.blade.php
    $productFile = $theme . '/product.blade.php';
    if (file_exists($productFile)) {
        $content = file_get_contents($productFile);
        
        // Remove the block from <!-- Related Products --> to @endif\n    @endif\n</main>
        // and replace with includes
        $content = preg_replace('/<!-- Related Products -->.*?@endif\s*@endif\s*<\/main>/s', "    @include('shop.partials.related-products', ['client' => \$client, 'product' => \$product])\n    @include('shop.partials.product-warranty', ['client' => \$client, 'product' => \$product])\n</main>", $content);

        // Also, replace the exact Warranty block again just in case the regex missed it or didn't match the whole thing.
        // Or better yet, we can do it properly:
        $content = preg_replace('/<!-- Warranty & Return Policy -->.*?@endif\s*@endif\s*/s', "", $content);
        
        // Save
        file_put_contents($productFile, $content);
    }
    
    // 2. Clean layout.blade.php
    $layoutFile = $theme . '/layout.blade.php';
    if (file_exists($layoutFile)) {
        $content = file_get_contents($layoutFile);
        
        // Remove the injected footer links
        $content = preg_replace('/@if\(!empty\(\$client->footer_text\)\).*?@endif\s*@if\(!empty\(\$client->footer_links\) && is_array\(\$client->footer_links\)\).*?@endif/s', "@include('shop.partials.footer-links', ['client' => \$client])", $content);
        
        // Remove the popup banner
        $content = preg_replace('/<!-- Offer Popup Banner -->.*?@endif\s*<\/body>/s', "    @include('shop.partials.popup-banner', ['client' => \$client])\n</body>", $content);
        
        file_put_contents($layoutFile, $content);
    }
}
