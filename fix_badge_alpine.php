<?php

function fixCartAlpineBug($dir) {
    if(!is_dir($dir)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() === 'cart.blade.php') {
            $path = $file->getPathname();
            $content = file_get_contents($path);

            $find = "        updateBadge() {
            document.querySelectorAll('[data-cart-badge]').forEach(el => {
                el.textContent = this.cartCount;
            });
        },";

            $replace = "        updateBadge() {
            document.querySelectorAll('[data-cart-badge]').forEach(el => {
                el.textContent = this.cartCount;
                if(this.cartCount > 0) el.classList.remove('hidden');
                else el.classList.add('hidden');
            });
        },";

            if (strpos($content, $find) !== false) {
                $content = str_replace($find, $replace, $content);
                file_put_contents($path, $content);
                echo "Fixed updateBadge in $path\n";
            }
        }
    }
}

fixCartAlpineBug('resources/views/shop/themes');
echo "Done checking all themes for Alpine badge bug.\n";
