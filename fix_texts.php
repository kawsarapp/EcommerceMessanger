<?php
// Fix master components
$files = [
    'resources/views/shop/partials/product-card.blade.php' => [
        'স্টক শেষ' => 'Out of Stock'
    ],
    'resources/views/components/shop/widgets/hero-banner.blade.php' => [
        'ক্যাটাগরি সমূহ' => "{{ \$client->widgets['category_filter']['text'] ?? 'Categories' }}",
        'সব {{ $c->name }}' => 'All {{ $c->name }}'
    ],
    'resources/views/shop/themes/vegist/index.blade.php' => [
        'bg-lightgreen' => 'bg-primary/10',
        'text-lightgreen' => 'text-primary',
        'bg-red-50 ' => 'bg-primary/5 ',
        'text-red-600' => 'text-primary',
        'border-red-100' => 'border-primary/20',
        'bg-red-500' => 'bg-primary',
        'text-red-500' => 'text-primary'
    ],
    'resources/views/shop/themes/vegist/layout.blade.php' => [
        'bg-lightgreen' => 'bg-primary/10',
        'text-lightgreen' => 'text-primary',
        'bg-red-50 ' => 'bg-primary/5 ',
        'text-red-600' => 'text-primary',
        'bg-red-500' => 'bg-primary'
    ],
    'resources/views/shop/themes/shwapno/index.blade.php' => [
        'bg-swred' => 'bg-primary',
        'text-swred' => 'text-primary',
        'border-swred' => 'border-primary',
        'bg-swyellow' => 'bg-secondary',
        'text-swyellow' => 'text-secondary',
        'bg-red-50 ' => 'bg-primary/5 ',
        'text-red-600' => 'text-primary',
        'bg-red-500' => 'bg-primary',
        'text-red-500' => 'text-primary'
    ],
    'resources/views/shop/themes/shwapno/layout.blade.php' => [
        'swred-' => 'primary-',
        'bg-swred' => 'bg-primary',
        'text-swred' => 'text-primary',
        'border-swred' => 'border-primary',
        'bg-swyellow' => 'bg-secondary',
        'text-swyellow' => 'text-secondary'
    ],
    'resources/views/shop/themes/pikabo/index.blade.php' => [
        'bdblue' => 'primary',
        'bg-red-500' => 'bg-primary',
        'text-red-500' => 'text-primary',
        'border-red-500' => 'border-primary',
        'bg-red-600' => 'bg-primary',
        'text-red-600' => 'text-primary',
        'bg-red-50 ' => 'bg-primary/5 ',
        'bg-blue-50' => 'bg-primary/10'
    ],
    'resources/views/shop/themes/pikabo/layout.blade.php' => [
        'bdblue' => 'primary',
        'bg-red-500' => 'bg-primary',
        'text-red-500' => 'text-primary',
        'bg-red-600' => 'bg-primary'
    ]
];

foreach($files as $file => $replacements) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        foreach($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
