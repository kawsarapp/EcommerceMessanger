@props(['client', 'config', 'products', 'title' => 'Products'])

@if($products && $products->count() > 0)
<div class="px-4 sm:px-6 max-w-7xl mx-auto mb-12">
    @if(!empty($config['text']) || !empty($title))
    <div class="flex justify-between items-end mb-6 border-b border-gray-100 pb-3">
        <h3 class="text-2xl font-bold tracking-tight relative pl-3" style="color: {{ $config['color'] ?? '#0f172a' }};">
            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-6 rounded-full" style="background-color: {{ $config['color'] ?? 'var(--tw-color-primary, #ef4444)' }};"></span>
            {{ $config['text'] ?? $title }}
        </h3>
        
        @if(!empty($config['link']))
        <a href="{{ $config['link'] }}" class="text-sm font-semibold hover:underline flex items-center pr-2" style="color: {{ $config['color'] ?? 'var(--tw-color-primary, #ef4444)' }};">
            View All <i class="fas fa-chevron-right text-xs ml-1"></i>
        </a>
        @endif
    </div>
    @endif
    
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-5">
        @foreach($products as $p)
            @include('shop.partials.product-card', ['product' => $p, 'client' => $client])
        @endforeach
    </div>
</div>
@endif
