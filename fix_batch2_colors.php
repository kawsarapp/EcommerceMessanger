<?php
function replaceInDir($dir, $replacements) {
    if(!is_dir($dir)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            $changed = false;
            foreach ($replacements as $search => $replace) {
                if (strpos($content, $search) !== false) {
                    $content = str_replace($search, $replace, $content);
                    $changed = true;
                }
            }
            if ($changed) {
                file_put_contents($path, $content);
                echo "Updated: $path\n";
            }
        }
    }
}

$darazReplacements = [
    '#F85606' => 'var(--tw-color-primary)', // Usually defined inline as hex
    '#d04000' => 'var(--tw-color-primary)', 
    'hover:bg-[#d04000]' => 'hover:brightness-95',
    '#ff8a00' => 'var(--tw-color-secondary)'
];

$bdproReplacements = [
    'bdblue' => 'primary',
    'bdlight' => 'gray-50',
    'bdhover' => 'gray-100',
    '#1a3673' => 'var(--tw-color-primary)'
];

$shoppersReplacements = [
    'shred-' => 'primary-',
    'shred' => 'primary',
    'shdark' => 'gray-900',
    'shbg' => 'gray-50',
    '#eb484e' => 'var(--tw-color-primary)',
    '#24263f' => 'var(--tw-color-primary)'
];

replaceInDir('resources/views/shop/themes/daraz', $darazReplacements);
replaceInDir('resources/views/shop/themes/bdpro', $bdproReplacements);
replaceInDir('resources/views/shop/themes/shoppers', $shoppersReplacements);

echo "Done replacing deeper files for Batch 2.\n";
