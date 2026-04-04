<?php

$dir = __DIR__ . '/resources/views/shop/themes/';
$themes = array_diff(scandir($dir), array('..', '.'));

foreach ($themes as $theme) {
    if (!is_dir($dir . $theme)) continue;
    $file = $dir . $theme . '/product.blade.php';
    if (!file_exists($file)) continue;

    $content = file_get_contents($file);

    // Some themes use <main> some use <div> at top block. We removed x-data completely earlier.
    // Let's inject a safe x-data for mainImg to the first tag containing 'max-w' (usually the top container).
    $content = preg_replace('/(<(?:main|div)[^>]*class="[^"]*max-w-[^"]*"[^>]*)>/i', '$1 x-data="{ mainImg: \'{{ asset(\'storage/\'.($product->thumbnail ?? \'images/placeholder.png\')) }}\' }">', $content, 1);

    file_put_contents($file, $content);
}
echo "Re-attached gallery x-data";
