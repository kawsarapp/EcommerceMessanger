@if(isset($categories) && count($categories) > 0)
<div x-data="{ open: false, isMobile: window.innerWidth < 1024 }" 
     @resize.window="isMobile = window.innerWidth < 1024"
     @click.away="open = false" 
     @mouseleave="if(!isMobile) open = false"
     class="relative group mr-2">
     
    <!-- Dropdown Toggle Button -->
    <button @click="open = !open" 
            @mouseover="if(!isMobile) open = true"
            class="flex items-center gap-2 {{ $btnClass ?? 'bg-white/10 hover:bg-white/20 text-white' }} px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg font-bold text-xs sm:text-sm transition-all focus:outline-none h-10 sm:h-auto">
        <i class="fas fa-bars"></i>
        <span class="hidden sm:inline">ক্যাটাগরি</span>
        <i class="fas fa-chevron-down text-[10px] sm:text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         class="absolute top-[110%] left-0 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 z-[120] py-2 overflow-hidden"
         style="display: none;">
         
        <a href="?category=all" class="block px-5 py-3 text-sm font-bold text-gray-800 hover:text-primary hover:bg-primary/5 transition-colors border-b border-gray-50">
            <i class="fas fa-th-large w-5 text-center mr-2 text-primary"></i> সকল পণ্য
        </a>
        
        <div class="max-h-[60vh] overflow-y-auto custom-scrollbar">
            @foreach($categories as $c)
                @if($c->children->count() > 0)
                    <!-- Category with Subcategories -->
                    <div x-data="{ expanded: false }" class="relative border-b border-gray-50 last:border-none">
                        <button @click="expanded = !expanded" 
                                class="w-full flex items-center justify-between px-5 py-3 text-sm font-semibold text-gray-700 hover:text-primary hover:bg-primary/5 transition-colors text-left focus:outline-none">
                            <span class="flex items-center gap-2">
                                @if($c->image)
                                    <img src="{{ asset('storage/'.$c->image) }}" class="w-4 h-4 object-contain opacity-80" alt="{{ $c->name }}">
                                @endif
                                {{ $c->name }}
                            </span>
                            <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200" :class="{'rotate-180': expanded}"></i>
                        </button>
                        
                        <!-- Subcategories Accordion -->
                        <div x-show="expanded" x-collapse>
                            <div class="bg-slate-50/80 py-2 flex flex-col border-t border-gray-100">
                                <a href="?category={{ $c->slug }}" class="px-8 py-2 text-[13px] font-bold text-primary hover:text-primary-dark transition-colors">
                                    <i class="fas fa-arrow-right text-[10px] mr-1"></i> সব {{ $c->name }}
                                </a>
                                @foreach($c->children as $sub)
                                    <a href="?category={{ $sub->slug }}" class="px-8 py-2 text-xs font-semibold text-slate-600 hover:text-primary hover:bg-white transition-colors">
                                        {{ $sub->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Simple Category -->
                    <a href="?category={{ $c->slug }}" class="flex items-center gap-2 px-5 py-3 text-sm font-semibold text-gray-700 hover:text-primary hover:bg-primary/5 transition-colors border-b border-gray-50 last:border-none">
                        @if($c->image)
                            <img src="{{ asset('storage/'.$c->image) }}" class="w-4 h-4 object-contain opacity-80" alt="{{ $c->name }}">
                        @endif
                        {{ $c->name }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endif
