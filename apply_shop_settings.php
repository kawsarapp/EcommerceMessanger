<?php

$themesDir = __DIR__ . '/resources/views/shop/themes';
$themes = glob($themesDir . '/*', GLOB_ONLYDIR);

foreach ($themes as $theme) {
    // 1. Update product.blade.php
    $productFile = $theme . '/product.blade.php';
    if (file_exists($productFile)) {
        $content = file_get_contents($productFile);
        
        // Add related products and warranty info at the bottom of the <main> tag
        if (strpos($content, 'Related Products') === false) {
            $relatedSnippet = "
    <!-- Related Products -->
    @if(\$client->show_related_products ?? true)
        @php
            \$relatedProducts = \App\Models\Product::where('category_id', \$product->category_id)
                            ->where('id', '!=', \$product->id)
                            ->take(4)->get();
        @endphp
        @if(\$relatedProducts->count() > 0)
        <div class=\"mt-16 mb-8\">
            <h2 class=\"text-2xl font-bold text-slate-900 mb-8 tracking-tight\">Related Products</h2>
            <div class=\"grid grid-cols-2 lg:grid-cols-4 gap-6\">
                @foreach(\$relatedProducts as \$related)
                    @include('shop.partials.product-card', ['product' => \$related, 'client' => \$client])
                @endforeach
            </div>
        </div>
        @endif
    @endif
    
    <!-- Warranty & Return Policy -->
    @if(\$client->show_return_warranty ?? true)
        @if(!empty(\$product->warranty) || !empty(\$product->return_policy))
        <div class=\"mt-8 bg-slate-50 rounded-2xl p-6 border border-slate-100\">
            <h3 class=\"text-lg font-bold text-slate-900 mb-4\">Warranty & Return</h3>
            <div class=\"flex flex-col gap-3\">
                @if(!empty(\$product->warranty))
                <div class=\"flex items-center gap-3 text-sm text-slate-700 font-medium\">
                    <i class=\"fas fa-shield-alt text-primary\"></i>
                    <span>Warranty: {{ \$product->warranty }}</span>
                </div>
                @endif
                @if(!empty(\$product->return_policy))
                <div class=\"flex items-center gap-3 text-sm text-slate-700 font-medium\">
                    <i class=\"fas fa-undo text-primary\"></i>
                    <span>Return Policy: {{ \$product->return_policy }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
    @endif
";
            
            // Inject before the end of </main>
            $content = preg_replace('/(<\/main>)/i', $relatedSnippet . "$1", $content);
        }
        
        // Update Stock Info
        if (strpos($content, 'show_stock') === false) {
            $content = preg_replace(
                '/(<span class="text-emerald-500"><i class="fas fa-circle text-\[8px\] mr-1"><\/i>\s*In Stock)<\/span>/',
                "@if(\$client->show_stock ?? true)\n$1 ({{ \$product->stock_quantity }})</span>\n@else\n$1</span>\n@endif",
                $content
            );
        }
        
        file_put_contents($productFile, $content);
    }
}
