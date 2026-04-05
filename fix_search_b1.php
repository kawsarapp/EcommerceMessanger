<?php

function replaceSearchPlaceholdersBatch1($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newPlaceholder = '{{ $client->widgets[\'search_bar\'][\'text\'] ?? \'Search in \'.$client->shop_name.\'...\' }}';
        
        // Use a more specific regex targeting only inputs with name="search"
        $content = preg_replace('/(<input[^>]*name="search"[^>]*?)placeholder="[^"]+"/', '$1placeholder="'.$newPlaceholder.'"', $content);
        
        file_put_contents($file, $content);
        echo "Fixed search placeholder in $file\n";
    }
}

replaceSearchPlaceholdersBatch1('resources/views/shop/themes/vegist/layout.blade.php');
replaceSearchPlaceholdersBatch1('resources/views/shop/themes/shwapno/layout.blade.php');
replaceSearchPlaceholdersBatch1('resources/views/shop/themes/pikabo/layout.blade.php');

echo "Done fixing Search Placeholders for Batch 1.\n";
