<?php

$dir = __DIR__ . '/resources/views/shop/themes/';
$themes = array_diff(scandir($dir), array('..', '.'));

foreach ($themes as $theme) {
    if (!is_dir($dir . $theme)) continue;
    $file = $dir . $theme . '/product.blade.php';
    if (!file_exists($file)) continue;

    $content = file_get_contents($file);

    // 1. Remove x-data, x-init, :class from the main wrapper to prevent Alpine conflicts
    $content = preg_replace('/x-data="[^"]*"/', '', $content);
    $content = preg_replace('/x-data=\'[^\']*\'/', '', $content);
    $content = preg_replace('/x-init="[^"]*"/', '', $content);

    file_put_contents($file, $content);
    echo "Stripped Alpine bindings from $theme/product.blade.php\n";
}
echo "Done.";
