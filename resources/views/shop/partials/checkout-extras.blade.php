{{-- 
    Checkout Extras Partial - Quantity, Coupon, Payment Methods, Terms & Conditions
    Required variables: $client, $product, $activePaymentMethods, $paymentConfig
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

{{-- ✅ Payment Methods — Dynamic Gateway-Aware UI --}}
@php
    $methods  = $activePaymentMethods ?? ['cod' => '🚚 Cash on Delivery'];
    $gateways = $paymentConfig ?? [];
    $firstKey = array_key_first($methods);
@endphp

<div x-data="{ selectedPayment: '{{ $firstKey }}' }">
    <div class="flex items-center gap-4 mb-5">
        <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">
            <i class="fas fa-credit-card text-xs"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-900">Payment Method</h3>
    </div>

    {{-- Gateway Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">

        @if(isset($methods['cod']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="cod" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'cod' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-primary peer-checked:bg-primary/5 transition-all hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-truck text-emerald-600"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Cash on Delivery</span>
                        <span class="block text-xs text-slate-500">ডেলিভারির সময় টাকা দিন</span>
                    </div>
                </div>
            </div>
        </label>
        @endif

        @if(isset($methods['partial']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="partial" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'partial' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-violet-500 peer-checked:bg-violet-50 transition-all hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-wallet text-violet-600"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Partial Advance</span>
                        <span class="block text-xs text-slate-500">কিছু টাকা আগে দিন</span>
                    </div>
                </div>
            </div>
        </label>
        @endif

        @if(isset($methods['full']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="full" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'full' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition-all hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check-circle text-indigo-600"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Full Pre-Payment</span>
                        <span class="block text-xs text-slate-500">পুরো পেমেন্ট আগে করুন</span>
                    </div>
                </div>
            </div>
        </label>
        @endif

        @if(isset($methods['bkash_merchant']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="bkash_merchant" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'bkash_merchant' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-pink-500 peer-checked:bg-pink-50 transition-all hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center flex-shrink-0 text-center">
                        <span class="font-black text-pink-600 text-xs leading-none">bKash</span>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">bKash Merchant</span>
                        <span class="block text-xs text-slate-500 font-mono">{{ $gateways['bkash_merchant']['number'] ?? '' }}</span>
                    </div>
                </div>
            </div>
        </label>
        @endif

        @if(isset($methods['bkash_personal']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="bkash_personal" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'bkash_personal' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-pink-400 peer-checked:bg-pink-50 transition-all hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-50 border border-pink-200 flex items-center justify-center flex-shrink-0 text-center">
                        <span class="font-black text-pink-500 text-xs leading-none">bKash</span>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">bKash Personal</span>
                        <span class="block text-xs text-slate-500 font-mono">{{ $gateways['bkash_personal']['number'] ?? '' }}</span>
                    </div>
                </div>
            </div>
        </label>
        @endif

        @if(isset($methods['sslcommerz']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="sslcommerz" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'sslcommerz' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-credit-card text-blue-600"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Online Payment</span>
                        <span class="block text-xs text-slate-500">Visa, Mastercard, Nagad, Rocket</span>
                    </div>
                </div>
            </div>
        </label>
        @endif

        @if(isset($methods['surjopay']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="surjopay" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'surjopay' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-orange-500 peer-checked:bg-orange-50 transition-all hover:border-slate-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-sun text-orange-500"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Surjopay</span>
                        <span class="block text-xs text-slate-500">Online Payment Gateway</span>
                    </div>
                </div>
            </div>
        </label>
        @endif

        {{-- 🔴 bKash PGW — Official Checkout API --}}
        @if(isset($methods['bkash_pgw']))
        <label class="cursor-pointer">
            <input type="radio" name="payment_method" value="bkash_pgw" x-model="selectedPayment" class="peer hidden" {{ $firstKey === 'bkash_pgw' ? 'checked' : '' }}>
            <div class="border-2 border-slate-200 rounded-xl p-4 peer-checked:border-[#E2136E] peer-checked:bg-[#FFF0F6] transition-all hover:border-pink-300">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#E2136E] flex items-center justify-center flex-shrink-0">
                        <span class="font-black text-white text-[10px] leading-none text-center">bKash</span>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">bKash Payment</span>
                        <span class="block text-xs text-[#E2136E] font-medium">Official Gateway — Auto Verify</span>
                    </div>
                </div>
            </div>
        </label>
        @endif
    </div>

    {{-- ══ bKash Merchant Info + TRX ID ══ --}}
    <div x-show="selectedPayment === 'bkash_merchant'"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="bg-pink-50 border-2 border-pink-200 rounded-xl p-5 mb-4">
        <div class="flex items-start gap-3 mb-4">
            <i class="fas fa-info-circle text-pink-500 mt-0.5"></i>
            <div>
                <p class="text-sm font-bold text-pink-800 mb-1">bKash করুন তারপর Transaction ID দিন</p>
                @if(!empty($gateways['bkash_merchant']['number']))
                <div class="mt-2 bg-white border border-pink-200 rounded-lg px-4 py-3 inline-flex items-center gap-3">
                    <span class="font-mono font-black text-pink-700 text-2xl tracking-widest">{{ $gateways['bkash_merchant']['number'] }}</span>
                    <span class="text-xs bg-pink-100 text-pink-700 rounded px-2 py-1 font-bold">Merchant</span>
                </div>
                @if(!empty($gateways['bkash_merchant']['account_name']))
                <p class="text-xs text-pink-600 mt-1">Account: <strong>{{ $gateways['bkash_merchant']['account_name'] }}</strong></p>
                @endif
                @endif
            </div>
        </div>
        <label class="block text-sm font-bold text-pink-800 mb-2"><i class="fas fa-receipt mr-1"></i> bKash Transaction ID *</label>
        <input type="text" name="bkash_trx_id" placeholder="e.g. AB12CD34EF" maxlength="20"
            class="w-full border-2 border-pink-200 bg-white rounded-xl px-4 py-3 font-mono font-bold text-xl tracking-wider focus:border-pink-400 focus:ring-4 focus:ring-pink-100 transition"
            :required="selectedPayment === 'bkash_merchant'">
        <p class="text-xs text-pink-600 mt-1.5">📱 bKash SMS থেকে TrxID copy করুন।</p>
    </div>

    {{-- ══ bKash Personal Info + TRX ID ══ --}}
    <div x-show="selectedPayment === 'bkash_personal'"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="bg-pink-50 border-2 border-pink-200 rounded-xl p-5 mb-4">
        <div class="flex items-start gap-3 mb-4">
            <i class="fas fa-info-circle text-pink-500 mt-0.5"></i>
            <div>
                <p class="text-sm font-bold text-pink-800 mb-1">Send Money করুন তারপর Transaction ID দিন</p>
                @if(!empty($gateways['bkash_personal']['number']))
                <div class="mt-2 bg-white border border-pink-200 rounded-lg px-4 py-3 inline-flex items-center gap-3">
                    <span class="font-mono font-black text-pink-700 text-2xl tracking-widest">{{ $gateways['bkash_personal']['number'] }}</span>
                    <span class="text-xs bg-pink-100 text-pink-700 rounded px-2 py-1 font-bold">Personal</span>
                </div>
                @if(!empty($gateways['bkash_personal']['account_name']))
                <p class="text-xs text-pink-600 mt-1">Account: <strong>{{ $gateways['bkash_personal']['account_name'] }}</strong></p>
                @endif
                @endif
            </div>
        </div>
        <label class="block text-sm font-bold text-pink-800 mb-2"><i class="fas fa-receipt mr-1"></i> bKash Transaction ID *</label>
        <input type="text" name="bkash_trx_id" placeholder="e.g. AB12CD34EF" maxlength="20"
            class="w-full border-2 border-pink-200 bg-white rounded-xl px-4 py-3 font-mono font-bold text-xl tracking-wider focus:border-pink-400 focus:ring-4 focus:ring-pink-100 transition"
            :required="selectedPayment === 'bkash_personal'">
        <p class="text-xs text-pink-600 mt-1.5">📱 bKash SMS থেকে TrxID copy করুন।</p>
    </div>

    {{-- ══ Partial Amount Input ══ --}}
    @if($client->partial_payment_active ?? false)
    <div x-show="selectedPayment === 'partial'"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="bg-violet-50 border-2 border-violet-200 rounded-xl p-5 mb-4">
        <label class="block text-sm font-bold text-violet-800 mb-2"><i class="fas fa-wallet mr-1"></i> Advance Amount (৳) *</label>
        <div class="flex items-center gap-2">
            <span class="text-2xl font-black text-violet-700">৳</span>
            <input type="number" name="advance_amount"
                min="{{ $client->partial_payment_amount ?? 0 }}"
                placeholder="{{ ($client->partial_payment_amount ?? 0) > 0 ? 'Minimum ৳'.($client->partial_payment_amount) : 'যেকোনো পরিমাণ' }}"
                class="flex-1 border-2 border-violet-200 bg-white rounded-xl px-4 py-3 font-bold text-xl focus:border-violet-400 focus:ring-4 focus:ring-violet-100 transition"
                :required="selectedPayment === 'partial'">
        </div>
        @if(($client->partial_payment_amount ?? 0) > 0)
        <p class="text-xs text-violet-600 mt-1.5">সর্বনিম্ন advance: <strong>৳{{ $client->partial_payment_amount }}</strong></p>
        @endif
    </div>
    @endif

    {{-- ══ SSL Commerz Info ══ --}}
    @if(isset($methods['sslcommerz']))
    <div x-show="selectedPayment === 'sslcommerz'"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="bg-blue-50 border-2 border-blue-200 rounded-xl p-5 mb-4 flex items-start gap-3">
        <i class="fas fa-external-link-alt text-blue-500 mt-0.5"></i>
        <div>
            <p class="text-sm font-bold text-blue-800">SSL Commerz Secure Payment</p>
            <p class="text-xs text-blue-600 mt-1">
                "Order Confirm" করলে আপনাকে SSL Commerz এর secure payment page এ নেওয়া হবে।
                Visa, Mastercard, Nagad, bKash, Rocket সব পদ্ধতিতে pay করতে পারবেন।
            </p>
        </div>
    </div>
    @endif

    {{-- ══ Surjopay Info ══ --}}
    @if(isset($methods['surjopay']))
    <div x-show="selectedPayment === 'surjopay'"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="bg-orange-50 border-2 border-orange-200 rounded-xl p-5 mb-4 flex items-start gap-3">
        <i class="fas fa-sun text-orange-500 mt-0.5"></i>
        <div>
            <p class="text-sm font-bold text-orange-800">Surjopay Secure Payment</p>
            <p class="text-xs text-orange-600 mt-1">
                "Order Confirm" করলে আপনাকে Surjopay payment page এ নেওয়া হবে।
            </p>
        </div>
    </div>
    @endif

    {{-- ══ bKash PGW Info ══ --}}
    @if(isset($methods['bkash_pgw']))
    <div x-show="selectedPayment === 'bkash_pgw'"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="bg-[#FFF0F6] border-2 border-[#E2136E]/30 rounded-xl p-5 mb-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#E2136E] flex items-center justify-center flex-shrink-0">
                <span class="font-black text-white text-[10px] leading-none">bKash</span>
            </div>
            <div>
                <p class="text-sm font-bold text-[#9B1B4A] mb-1">bKash Official Payment Gateway</p>
                <p class="text-xs text-[#C01F5F] leading-relaxed">
                    "Order Confirm" করলে আপনাকে bKash এর Official Payment page এ নেওয়া হবে।<br>
                    bKash App অথবা USSD দিয়ে payment করুন।<br>
                    Payment হলে <strong>automatically order confirm</strong> হবে।
                </p>
            </div>
        </div>
    </div>
    @endif
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
