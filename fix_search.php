<?php

function replaceSearchPlaceholders($file, $oldPlaceholder) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newPlaceholder = '{{ $client->widgets[\'search_bar\'][\'text\'] ?? \'Search in \'.$client->shop_name.\'...\' }}';
        
        // Use regex to replace placeholder="..." 
        $content = preg_replace('/placeholder="[^"]+"/', 'placeholder="'.$newPlaceholder.'"', $content);
        
        file_put_contents($file, $content);
        echo "Fixed search placeholders in $file\n";
    }
}

replaceSearchPlaceholders('resources/views/shop/themes/daraz/layout.blade.php', '');
replaceSearchPlaceholders('resources/views/shop/themes/bdpro/layout.blade.php', '');
replaceSearchPlaceholders('resources/views/shop/themes/shoppers/layout.blade.php', '');

echo "Done fixing Search Placeholders.\n";
