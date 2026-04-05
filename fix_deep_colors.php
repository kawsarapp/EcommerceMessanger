<?php
function replaceInDir($dir, $replacements) {
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

$vegistReplacements = [
    'bg-lightgreen' => 'bg-primary/10',
    'text-lightgreen' => 'text-primary',
    'bg-red-50 ' => 'bg-primary/5 ',
    'border-red-50 ' => 'border-primary/5 ',
    'text-red-600' => 'text-primary',
    'border-red-100' => 'border-primary/20',
    'bg-red-500' => 'bg-primary',
    'text-red-500' => 'text-primary',
    'bg-red-600' => 'bg-primary'
];

$shwapnoReplacements = [
    'swred-' => 'primary-',
    'bg-swred' => 'bg-primary',
    'text-swred' => 'text-primary',
    'border-swred' => 'border-primary',
    'bg-swyellow' => 'bg-secondary',
    'text-swyellow' => 'text-secondary',
    'bg-red-50 ' => 'bg-primary/5 ',
    'border-red-50 ' => 'border-primary/5 ',
    'text-red-600' => 'text-primary',
    'border-red-100' => 'border-primary/20',
    'bg-red-500' => 'bg-primary',
    'text-red-500' => 'text-primary',
    'bg-red-600' => 'bg-primary'
];

$pikaboReplacements = [
    'bdblue' => 'primary',
    'bg-red-500' => 'bg-primary',
    'text-red-500' => 'text-primary',
    'border-red-500' => 'border-primary',
    'bg-red-600' => 'bg-primary',
    'text-red-600' => 'text-primary',
    'bg-red-50 ' => 'bg-primary/5 ',
    'border-red-50 ' => 'border-primary/5 ',
    'bg-blue-50' => 'bg-primary/10',
    'border-red-100' => 'border-primary/20',
];

replaceInDir('resources/views/shop/themes/vegist', $vegistReplacements);
replaceInDir('resources/views/shop/themes/shwapno', $shwapnoReplacements);
replaceInDir('resources/views/shop/themes/pikabo', $pikaboReplacements);

echo "Done replacing deeper files.\n";
