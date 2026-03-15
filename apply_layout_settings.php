<?php

$themesDir = __DIR__ . '/resources/views/shop/themes';
$themes = glob($themesDir . '/*', GLOB_ONLYDIR);

foreach ($themes as $theme) {
    $layoutFile = $theme . '/layout.blade.php';
    if (file_exists($layoutFile)) {
        $content = file_get_contents($layoutFile);
        
        // Add Dynamic Footer text and links
        if (strpos($content, 'footer_text') === false) {
            $footerSnippet = "
                @if(!empty(\$client->footer_text))
                    <p class=\"text-slate-500 font-medium text-sm leading-relaxed mt-4\">{{ \$client->footer_text }}</p>
                @endif
                
                @if(!empty(\$client->footer_links) && is_array(\$client->footer_links))
                    <div class=\"mt-6\">
                        <h4 class=\"font-bold text-slate-900 mb-4 text-sm uppercase tracking-wider\">Useful Links</h4>
                        <div class=\"flex flex-col space-y-3 font-medium text-sm text-slate-500\">
                            @foreach(\$client->footer_links as \$link)
                                <a href=\"{{ \$link['url'] }}\" target=\"_blank\" class=\"hover:text-primary premium-transition\">{{ \$link['title'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            ";
            
            // Inject inside the first column of the footer
            // We search for `<p class="text-slate-500 font-medium text-sm leading-relaxed">Providing high-quality products...`
            $content = preg_replace('/(<p class="text-slate-500 font-medium text-sm leading-relaxed">Providing high-quality products(.*?))<\/p>/', "\$1</p>\n\$footerSnippet", $content);
        }
        
        // Add Popup Banner
        if (strpos($content, 'popup_active') === false) {
            $popupSnippet = "
    <!-- Offer Popup Banner -->
    @if(!empty(\$client->popup_active) && (!\$client->popup_expires_at || \Carbon\Carbon::parse(\$client->popup_expires_at)->isFuture()))
    <div x-data=\"{ open: true }\" x-show=\"open\" class=\"fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4\" x-cloak>
        <div class=\"bg-white rounded-[2rem] shadow-float max-w-lg w-full relative overflow-hidden transform transition-all\" @click.away=\"open = false\">
            <button @click=\"open = false\" class=\"absolute top-4 right-4 w-10 h-10 bg-white/50 backdrop-blur rounded-full flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-white z-10 transition-colors shadow-sm\">
                <i class=\"fas fa-times\"></i>
            </button>
            @if(!empty(\$client->popup_link))
            <a href=\"{{ \$client->popup_link }}\" target=\"_blank\" class=\"block\">
            @endif
                @if(!empty(\$client->popup_image))
                <img src=\"{{ asset('storage/' . \$client->popup_image) }}\" class=\"w-full max-h-64 object-cover\">
                @endif
                @if(!empty(\$client->popup_title) || !empty(\$client->popup_description))
                <div class=\"p-8 text-center\">
                    @if(!empty(\$client->popup_title))
                    <h2 class=\"text-2xl font-extrabold text-slate-900 mb-3\">{{ \$client->popup_title }}</h2>
                    @endif
                    @if(!empty(\$client->popup_description))
                    <p class=\"text-slate-500 font-medium leading-relaxed\">{{ \$client->popup_description }}</p>
                    @endif
                    @if(!empty(\$client->popup_link))
                    <div class=\"mt-6 inline-block bg-primary text-white font-bold px-6 py-3 rounded-xl shadow-sm hover:shadow-md transition-shadow uppercase tracking-wider text-sm\">
                        Learn More
                    </div>
                    @endif
                </div>
                @endif
            @if(!empty(\$client->popup_link))
            </a>
            @endif
        </div>
    </div>
    @endif
";
            
            // Inject before </body>
            if (strpos($content, '</body>') !== false) {
                $content = str_replace('</body>', $popupSnippet . "\n</body>", $content);
            }
        }
        
        file_put_contents($layoutFile, $content);
    }
}
