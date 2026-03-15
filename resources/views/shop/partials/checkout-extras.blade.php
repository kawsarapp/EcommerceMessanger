{{-- 
    Checkout Extras Partial - Coupon, Quantity, Terms & Conditions
    Required variables: $client, $product
    Alpine.js data context must include: qty, price, couponCode, couponDiscount, couponApplied, couponError, termsAccepted
--}}

{{-- Quantity Section --}}
<div>
    <div class="flex items-center gap-4 mb-6">
        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">Q</div>
        <h3 class="text-lg font-bold text-slate-900">Quantity</h3>
    </div>
    <div class="flex items-center gap-4">
        <div class="h-14 bg-slate-50 rounded-xl border border-slate-200 flex w-44 items-center px-1">
            <button type="button" @click="if(qty>1)qty--" class="flex-1 h-full flex items-center justify-center text-slate-500 hover:text-slate-900 transition">
                <i class="fas fa-minus text-sm"></i>
            </button>
            <input type="number" name="qty" x-model="qty" class="w-14 text-center bg-transparent border-none font-bold text-slate-900 p-0 focus:ring-0 text-lg" readonly>
            <button type="button" @click="qty++" class="flex-1 h-full flex items-center justify-center text-slate-500 hover:text-slate-900 transition">
                <i class="fas fa-plus text-sm"></i>
            </button>
        </div>
        <span class="text-sm font-semibold text-slate-500">× ৳<span x-text="price"></span> = ৳<span x-text="qty * price" class="text-slate-900 font-bold"></span></span>
    </div>
</div>

<hr class="border-slate-100">

{{-- Coupon Section --}}
<div>
    <div class="flex items-center gap-4 mb-6">
        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm"><i class="fas fa-tag text-xs"></i></div>
        <h3 class="text-lg font-bold text-slate-900">Coupon Code</h3>
    </div>
    
    <div class="flex gap-3" x-show="!couponApplied">
        <input type="text" x-model="couponCode" placeholder="Enter coupon code" 
            class="flex-1 bg-slate-50/50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 font-semibold focus:border-primary focus:ring-4 focus:ring-primary/5 focus:bg-white transition placeholder-slate-400 text-sm">
        <button type="button" @click="applyCoupon()" 
            class="px-6 py-3 bg-primary text-white rounded-xl font-bold text-sm uppercase tracking-wider hover:bg-primary/90 transition flex items-center gap-2">
            <i class="fas fa-check text-xs"></i> Apply
        </button>
    </div>
    
    {{-- Applied Coupon --}}
    <div x-show="couponApplied" 
        class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-4">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
            <div>
                <span class="font-bold text-emerald-700 text-sm block" x-text="'Coupon: ' + couponCode"></span>
                <span class="text-xs text-emerald-600 font-medium" x-text="'You saved ৳' + couponDiscount"></span>
            </div>
        </div>
        <button type="button" @click="removeCoupon()" class="text-red-400 hover:text-red-600 text-sm font-bold transition">
            <i class="fas fa-times"></i> Remove
        </button>
    </div>
    <input type="hidden" name="coupon_code" :value="couponApplied ? couponCode : ''">
    <input type="hidden" name="coupon_discount" :value="couponDiscount">
    
    <p x-show="couponError" x-text="couponError" class="text-red-500 text-xs font-bold mt-2"></p>
</div>

<hr class="border-slate-100 mb-6 mt-6">

{{-- Payment Methods --}}
<div>
    <div class="flex items-center gap-4 mb-6">
        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm"><i class="fas fa-credit-card text-xs"></i></div>
        <h3 class="text-lg font-bold text-slate-900">Payment Method</h3>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @if($client->cod_active ?? true)
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="cod" class="peer hidden" checked>
            <div class="border border-slate-200 rounded-xl p-4 text-center peer-checked:border-primary peer-checked:bg-primary/5 transition hover:bg-slate-50">
                <i class="fas fa-money-bill-wave text-xl text-slate-400 peer-checked:text-primary mb-2 block"></i>
                <span class="block text-sm font-bold text-slate-700 peer-checked:text-primary">Cash on Delivery</span>
            </div>
        </label>
        @endif
        
        @if($client->partial_payment_active ?? false)
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="partial" class="peer hidden" {{ !($client->cod_active ?? true) ? 'checked' : '' }}>
            <div class="border border-slate-200 rounded-xl p-4 text-center peer-checked:border-primary peer-checked:bg-primary/5 transition hover:bg-slate-50">
                <i class="fas fa-wallet text-xl text-slate-400 peer-checked:text-primary mb-2 block"></i>
                <span class="block text-sm font-bold text-slate-700 peer-checked:text-primary">Partial Pre-Payment</span>
            </div>
        </label>
        @endif
        
        @if($client->full_payment_active ?? false)
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="full" class="peer hidden" {{ !($client->cod_active ?? true) && !($client->partial_payment_active ?? false) ? 'checked' : '' }}>
            <div class="border border-slate-200 rounded-xl p-4 text-center peer-checked:border-primary peer-checked:bg-primary/5 transition hover:bg-slate-50">
                <i class="fas fa-credit-card text-xl text-slate-400 peer-checked:text-primary mb-2 block"></i>
                <span class="block text-sm font-bold text-slate-700 peer-checked:text-primary">Full Pre-Payment</span>
            </div>
        </label>
        @endif
    </div>
</div>

<hr class="border-slate-100 mt-6 mb-6">

{{-- Terms & Conditions --}}
@if($client->show_terms_checkbox ?? false)
<div class="flex items-start gap-3">
    <input type="checkbox" name="terms_accepted" id="terms_accepted" x-model="termsAccepted"
        class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary/20 mt-0.5 cursor-pointer" required>
    <label for="terms_accepted" class="text-sm text-slate-600 font-medium cursor-pointer leading-relaxed">
        I agree to the 
        @if($client->terms_conditions_url)
            <a href="{{ $client->terms_conditions_url }}" target="_blank" class="text-primary font-bold hover:underline">Terms & Conditions</a>
        @else
            <span class="text-primary font-bold">Terms & Conditions</span>
        @endif
        @if($client->terms_conditions_text)
            <span class="block text-xs text-slate-400 mt-1">{{ $client->terms_conditions_text }}</span>
        @endif
    </label>
</div>
@endif
