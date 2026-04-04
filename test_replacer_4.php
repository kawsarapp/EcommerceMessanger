<?php

$dir = __DIR__ . '/resources/views/shop/themes/';
$all_themes = array_diff(scandir($dir), array('..', '.'));

// Skip complex themes that break with regex form stripping
$skip_themes = ['vegist', 'daraz', 'athletic'];

foreach ($all_themes as $theme) {
    if (in_array($theme, $skip_themes)) continue;
    if (!is_dir($dir . $theme)) continue;
    $file = $dir . $theme . '/product.blade.php';
    if (!file_exists($file)) continue;

    $content = file_get_contents($file);

    // 1. Target the form.
    // Replace <form ...> ... </form> with @include('shop.partials.product-variations')
    $content = preg_replace('/<form\b[^>]*>.*?<\/form>/is', "@include('shop.partials.product-variations')", $content, 1);

    // 2. Add sticky bar before @endsection
    if (strpos($content, "@include('shop.partials.product-sticky-bar')") === false) {
        $content = str_replace('@endsection', "@include('shop.partials.product-sticky-bar')\n@endsection", $content);
    }
    
    file_put_contents($file, $content);
    echo "Updated $theme/product.blade.php\n";
}
echo "Done.";
