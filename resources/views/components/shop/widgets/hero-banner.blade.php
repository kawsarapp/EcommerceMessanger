@props(['client', 'config'])

@if($client->banner)
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-6" id="widget-hero">
    <div class="w-full h-[35vh] md:h-[60vh] rounded-[2rem] overflow-hidden relative group shadow-sm" 
         style="{{ !empty($config['color']) ? 'background-color: '.$config['color'].';' : '' }}">
        
        <img src="{{ asset('storage/'.$client->banner) }}" class="absolute inset-0 w-full h-full object-cover origin-center transition-transform duration-[1.5s] ease-out group-hover:scale-105">
        
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/80 via-slate-900/40 to-transparent pointer-events-none"></div>
        
        <div class="absolute inset-y-0 left-0 z-10 flex flex-col justify-center p-8 md:p-16 w-full lg:w-2/3 pointer-events-none">
            @if(!empty($config['text']))
                <h2 class="text-3xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6 leading-[1.2]" 
                    style="{{ !empty($config['color']) ? 'color: '.$config['color'].';' : 'color: white;' }}">
                    {{ $config['text'] }}
                </h2>
            @endif
            
            @if(!empty($config['link']))
                <a href="{{ $config['link'] }}" 
                   class="pointer-events-auto w-fit text-white font-bold text-sm uppercase tracking-wide px-8 py-4 rounded-xl shadow-lg hover:-translate-y-1 flex items-center gap-3 transition-all"
                   style="background-color: var(--tw-color-primary, #ef4444);">
                    Shop Now <i class="fas fa-arrow-right"></i>
                </a>
            @endif
        </div>
    </div>
</section>
@endif
