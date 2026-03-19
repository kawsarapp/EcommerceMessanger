@props(['client', 'config'])

<div class="px-4 sm:px-6 max-w-7xl mx-auto mb-12 py-10 bg-slate-50 md:rounded-[2rem] border border-slate-100 shadow-sm mt-8">
    @if(!empty($config['text']))
    <h3 class="text-center text-xl md:text-2xl font-bold mb-10 tracking-tight" style="color: {{ $config['color'] ?? '#0f172a' }};">
        {{ $config['text'] }}
    </h3>
    @endif
    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-8 text-center">
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center bg-white shadow-sm mb-4 border border-slate-100">
                <i class="fas fa-truck text-2xl" style="color: {{ $config['color'] ?? '#10b981' }};"></i>
            </div>
            <h4 class="font-bold text-slate-800 text-sm">Fast Delivery</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-[150px]">Quick shipping to your doorstep</p>
        </div>
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center bg-white shadow-sm mb-4 border border-slate-100">
                <i class="fas fa-shield-alt text-2xl" style="color: {{ $config['color'] ?? '#10b981' }};"></i>
            </div>
            <h4 class="font-bold text-slate-800 text-sm">Secure Payment</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-[150px]">100% encrypted & secure checkout</p>
        </div>
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center bg-white shadow-sm mb-4 border border-slate-100">
                <i class="fas fa-undo-alt text-2xl" style="color: {{ $config['color'] ?? '#10b981' }};"></i>
            </div>
            <h4 class="font-bold text-slate-800 text-sm">Easy Returns</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-[150px]">Hassel-free return policy</p>
        </div>
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center bg-white shadow-sm mb-4 border border-slate-100">
                <i class="fas fa-headset text-2xl" style="color: {{ $config['color'] ?? '#10b981' }};"></i>
            </div>
            <h4 class="font-bold text-slate-800 text-sm">24/7 Support</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-[150px]">Dedicated customer support team</p>
        </div>
    </div>
</div>
