@props(['client', 'config', 'categories' => null])

@if($client->banner)
<section class="max-w-[1280px] mx-auto px-4 py-4 sm:py-6" id="widget-hero">
    <div class="flex flex-col lg:flex-row gap-5">
        
        {{-- Left Sidebar Categories (Visible only on LG+) --}}
        @if($categories && count($categories) > 0)
        <div x-data="{ activeCategory: null, hoverPanel: false }" class="hidden lg:block w-[260px] xl:w-[280px] shrink-0 bg-white rounded-2xl shadow-sm border border-slate-100 h-[350px] md:h-[450px] relative z-[90]">
            <h3 class="bg-slate-50 px-5 py-3.5 border-b border-slate-100 rounded-t-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-list-ul text-primary"></i> ক্যাটাগরি সমূহ
            </h3>
            <ul class="overflow-y-auto h-[calc(100%-52px)] custom-scrollbar pb-2 rounded-b-2xl relative" @mouseleave="setTimeout(() => { if(!hoverPanel) activeCategory = null }, 50)">
                @foreach($categories as $c)
                <li class="border-b border-slate-50 last:border-0 hover:bg-primary/5 transition-colors" 
                    @mouseenter="activeCategory = '{{$c->slug}}'">
                    <a href="?category={{ $c->slug }}" class="flex items-center justify-between px-5 py-3 text-sm font-semibold text-slate-700 hover:text-primary">
                        <span>{{ $c->name }}</span>
                        @if($c->children->count() > 0)
                            <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
                        @endif
                    </a>
                </li>
                @endforeach
            </ul>

            {{-- Flyout Submenus Rendered Outside UL to prevent scroll clipping --}}
            @foreach($categories as $c)
                @if($c->children->count() > 0)
                <div x-show="activeCategory === '{{$c->slug}}'" 
                     @mouseenter="hoverPanel = true; activeCategory = '{{$c->slug}}'" 
                     @mouseleave="hoverPanel = false; activeCategory = null"
                     x-transition.opacity.duration.150ms
                     style="display: none;"
                     class="absolute top-0 left-full ml-2 w-56 h-full bg-white border border-slate-100 rounded-2xl shadow-xl z-[100] py-4 overflow-y-auto custom-scrollbar">
                    <a href="?category={{ $c->slug }}" class="block px-6 py-2 text-[13px] font-bold text-primary hover:text-primary-dark transition-colors border-b border-slate-50 pb-3 mb-1">
                        <i class="fas fa-arrow-right text-[10px] mr-1"></i> সব {{ $c->name }}
                    </a>
                    @foreach($c->children as $sub)
                        <a href="?category={{ $sub->slug }}" class="block px-6 py-2.5 text-[13px] font-medium text-slate-600 hover:text-primary hover:bg-slate-50 transition-colors">
                            {{ $sub->name }}
                        </a>
                    @endforeach
                </div>
                @endif
            @endforeach
        </div>
        @endif

        {{-- Right: The actual Banner Image --}}
        <div class="flex-1 w-full h-[200px] sm:h-[300px] md:h-[350px] lg:h-[450px] rounded-2xl overflow-hidden relative group shadow-sm bg-slate-100" 
             style="{{ !empty($config['color']) ? 'background-color: '.$config['color'].';' : '' }}">
            
            <img src="{{ asset('storage/'.$client->banner) }}" class="absolute inset-0 w-full h-full object-cover origin-center transition-transform duration-[1.5s] ease-out group-hover:scale-105">
            
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900/80 via-slate-900/40 to-transparent pointer-events-none"></div>
            
            <div class="absolute inset-y-0 left-0 z-10 flex flex-col justify-center p-6 md:p-12 xl:p-16 w-full lg:w-2/3 pointer-events-none">
                @if(!empty($config['text']))
                    <h2 class="text-2xl md:text-4xl xl:text-5xl font-extrabold tracking-tight mb-4 md:mb-6 leading-[1.2]" 
                        style="{{ !empty($config['color']) ? 'color: '.$config['color'].';' : 'color: white;' }}">
                        {{ $config['text'] }}
                    </h2>
                @endif
                
                @if(!empty($config['link']))
                    <a href="{{ $config['link'] }}" 
                       class="pointer-events-auto w-fit text-white font-bold text-xs md:text-sm uppercase tracking-wide px-6 py-3 md:px-8 md:py-4 rounded-xl shadow-lg hover:-translate-y-1 flex items-center gap-2 md:gap-3 transition-all"
                       style="background-color: var(--tw-color-primary, #ef4444);">
                        Shop Now <i class="fas fa-arrow-right"></i>
                    </a>
                @endif
            </div>
        </div>

    </div>
</section>
@endif
