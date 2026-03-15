<?php
/**
 * This script updates ALL theme index.blade.php and product.blade.php files
 * to use the new reusable partials for:
 * 1. Homepage: Offer Banner + Category-based products
 * 2. Product Page: Warranty inline + tabbed content
 */

$themesDir = __DIR__ . '/resources/views/shop/themes';
$themes = glob($themesDir . '/*', GLOB_ONLYDIR);
$defaultTheme = $themesDir . '/default';

foreach ($themes as $theme) {
    $themeName = basename($theme);
    if ($themeName === 'default') continue; // already updated

    // ========== UPDATE INDEX.BLADE.PHP ==========
    $indexFile = $theme . '/index.blade.php';
    if (file_exists($indexFile)) {
        $content = file_get_contents($indexFile);
        
        // Add offer banner if not already present
        if (strpos($content, 'homepage-offer-banner') === false) {
            // Try to inject right before the product grid/category pills area
            // Look for "Our Products" or the product grid section
            $patterns = [
                '<!-- Clean Top Navigation',
                '<!-- Category Filter',
                '<!-- Products Section',
                'Our Products',
                '<div id="shop"',
            ];
            
            $bannerInclude = "\n    {{-- Homepage Offer Banner (Timer + Link) --}}\n    @include('shop.partials.homepage-offer-banner', ['client' => \$client])\n\n";
            
            $injected = false;
            foreach ($patterns as $pattern) {
                if (strpos($content, $pattern) !== false) {
                    $pos = strpos($content, $pattern);
                    // Find the start of the line
                    $lineStart = strrpos(substr($content, 0, $pos), "\n");
                    if ($lineStart !== false) {
                        $content = substr($content, 0, $lineStart) . $bannerInclude . substr($content, $lineStart);
                        $injected = true;
                        break;
                    }
                }
            }
        }
        
        // Add homepage-categories if not present
        if (strpos($content, 'homepage-categories') === false) {
            // Add before @endsection
            $endsection = strrpos($content, '@endsection');
            if ($endsection !== false) {
                $categoriesInclude = "\n    {{-- Homepage: Category-based product sections (when no filter) --}}\n    @if(!request('category') || request('category') == 'all')\n        @include('shop.partials.homepage-categories', ['client' => \$client])\n    @endif\n\n";
                $content = substr($content, 0, $endsection) . $categoriesInclude . substr($content, $endsection);
            }
        }
        
        file_put_contents($indexFile, $content);
        echo "Updated $themeName/index.blade.php\n";
    }

    // ========== UPDATE PRODUCT.BLADE.PHP ==========
    $productFile = $theme . '/product.blade.php';
    if (file_exists($productFile)) {
        $content = file_get_contents($productFile);
        
        // 1. Add warranty inline in the stock/SKU area if not there
        if (strpos($content, 'show_return_warranty') === false) {
            // Find "In Stock</span>" and add warranty after it
            $inStockPattern = "In Stock</span>";
            if (strpos($content, $inStockPattern) !== false) {
                $warrantyInline = "In Stock</span>\n                        @endif\n\n                        {{-- Warranty & Return inline --}}\n                        @if((\$client->show_return_warranty ?? true) && !empty(\$product->warranty))\n                            <div class=\"w-1 h-1 bg-slate-300 rounded-full\"></div>\n                            <span class=\"text-blue-500\"><i class=\"fas fa-shield-alt text-[8px] mr-1\"></i> {{ \$product->warranty }}</span>\n                        @endif\n                        @if((\$client->show_return_warranty ?? true) && !empty(\$product->return_policy))\n                            <div class=\"w-1 h-1 bg-slate-300 rounded-full\"></div>\n                            <span class=\"text-orange-500\"><i class=\"fas fa-undo text-[8px] mr-1\"></i> {{ \$product->return_policy }}</span>\n                        @endif";
                // Only replace first occurrence
                $pos = strpos($content, $inStockPattern);
                $content = substr_replace($content, $warrantyInline, $pos, strlen($inStockPattern));
            }
        }
        
        // 2. Replace product-warranty partial with related-products if both aren't there
        if (strpos($content, 'related-products') === false) {
            // Add before </main>
            $mainClose = strrpos($content, '</main>');
            if ($mainClose !== false) {
                $partials = "\n    @include('shop.partials.related-products', ['client' => \$client, 'product' => \$product])\n";
                $content = substr($content, 0, $mainClose) . $partials . substr($content, $mainClose);
            }
        }
        
        file_put_contents($productFile, $content);
        echo "Updated $themeName/product.blade.php\n";
    }
}

echo "\nAll themes updated!\n";
