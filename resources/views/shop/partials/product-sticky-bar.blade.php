<div x-data="{ showSticky: false }" 
     @scroll.window="showSticky = window.pageYOffset > 500"
     x-show="showSticky"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-full"
     x-transition:enter-end="translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="translate-y-0"
     x-transition:leave-end="translate-y-full"
     class="md:hidden fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-[9999] px-4 py-3"
     style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));"
     x-cloak>
     
    <div class="flex items-center gap-3 max-w-lg mx-auto">
        <a href="#buy-section" @click.prevent="let el = document.querySelector('.product-variations-wrapper') || document.querySelector('.lg\\:col-span-5'); if(el) el.scrollIntoView({behavior: 'smooth', block: 'start'})" 
           class="flex-1 flex justify-center items-center h-12 rounded-xl bg-gray-100 text-gray-700 font-bold text-sm">
            <i class="fas fa-shopping-cart mr-2"></i> Cart
        </a>
        <a href="#buy-section" @click.prevent="let el = document.querySelector('.product-variations-wrapper') || document.querySelector('.lg\\:col-span-5'); if(el) { el.scrollIntoView({behavior: 'smooth', block: 'start'}); setTimeout(() => { let btn = el.querySelector('button:last-of-type'); if(btn) { btn.classList.add('ring-4', 'ring-primary/40'); setTimeout(()=>btn.classList.remove('ring-4', 'ring-primary/40'), 1000) } }, 500) }" 
           class="flex-1 flex justify-center items-center h-12 rounded-xl bg-primary text-white font-bold text-sm shadow-md">
            <i class="fas fa-bolt mr-2"></i> Buy Now
        </a>
    </div>
</div>
