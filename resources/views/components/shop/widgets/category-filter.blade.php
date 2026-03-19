@props(['client', 'config', 'categories'])

@if($categories->count() > 0)
<div class="px-4 sm:px-6 max-w-7xl mx-auto mb-8 mt-4">
    <div class="flex flex-col md:flex-row justify-between items-center md:items-end mb-6 gap-4 border-b border-gray-100 pb-4">
        <h3 class="text-2xl md:text-3xl font-extrabold tracking-tight relative pl-4"
            style="color: {{ $config['color'] ?? '#0f172a' }};">
            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-2 h-8 rounded-full" style="background-color: {{ $config['color'] ?? 'var(--tw-color-primary, #ef4444)' }};"></span>
            {{ $config['text'] ?? 'Categories' }}
        </h3>
    </div>
    
    <div class="flex gap-3 overflow-x-auto hide-scroll w-full pb-4">
        <a href="?category=all" 
           class="px-6 py-3 rounded-xl text-sm font-bold whitespace-nowrap transition-all border shadow-sm hover:scale-105"
           style="{{ !request('category') || request('category')=='all' ? 'background-color: '.($config['color'] ?? 'var(--tw-color-primary, #ef4444)').'; color: white; border-color: transparent;' : 'background-color: white; color: #475569; border-color: #e2e8f0;' }}">
            All Items
        </a>
        @foreach($categories as $c)
            <a href="?category={{$c->slug}}" 
               class="px-6 py-3 rounded-xl text-sm font-bold whitespace-nowrap transition-all border shadow-sm hover:scale-105"
               style="{{ request('category')==$c->slug ? 'background-color: '.($config['color'] ?? 'var(--tw-color-primary, #ef4444)').'; color: white; border-color: transparent;' : 'background-color: white; color: #475569; border-color: #e2e8f0;' }}">
                {{$c->name}}
            </a>
        @endforeach
    </div>
</div>
@endif
