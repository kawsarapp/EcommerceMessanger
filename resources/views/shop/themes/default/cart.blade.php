@extends('shop.themes.default.layout')
@section('title', 'My Cart | ' . $client->shop_name)

@section('content')
@php
    $clean   = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain ?? '', '/'));
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);
    $cartUrl      = $clean ? $baseUrl.'/cart'              : route('shop.cart',             $client->slug);
    $addUrl       = $clean ? $baseUrl.'/cart/add'          : route('shop.cart.add',          $client->slug);
    $removeUrl    = $clean ? $baseUrl.'/cart/remove'       : route('shop.cart.remove',       $client->slug);
    $updateUrl    = $clean ? $baseUrl.'/cart/update'       : route('shop.cart.update',       $client->slug);
    $clearUrl     = $clean ? $baseUrl.'/cart/clear'        : route('shop.cart.clear',        $client->slug);
    $checkoutUrl  = $clean ? $baseUrl.'/cart/checkout'     : route('shop.cart.checkout',     $client->slug);

    $subtotal  = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
    $cartItems = array_values($cart); // pre-encode for safe JS injection
@endphp

<div class="max-w-[1200px] mx-auto px-4 xl:px-8 py-10 pb-28 md:pb-12"
     x-data="cartApp()"
     x-init="init()">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-6 flex items-center gap-2">
        <a href="{{ $baseUrl }}" class="hover:text-primary transition">Home</a>
        <span>/</span>
        <span class="text-dark font-medium">Shopping Cart</span>
    </nav>

    <h1 class="text-2xl font-bold text-dark mb-8">Shopping Cart
        <span class="text-base font-normal text-gray-400 ml-2">(<span x-text="cartCount"></span> items)</span>
    </h1>

    {{-- Empty Cart --}}
    <div x-show="cartCount === 0" class="text-center py-20">
        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shopping-bag text-4xl text-gray-200"></i>
        </div>
        <h3 class="text-xl font-bold text-dark mb-2">Your cart is empty</h3>
        <p class="text-gray-400 text-sm mb-8">Looks like you haven't added anything to your cart yet.</p>
        <a href="{{ $baseUrl }}" class="btn-primary rounded-xl px-8 py-3 inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    </div>

    {{-- Cart Content --}}
    <div x-show="cartCount > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- Left: Cart Items --}}
        <div class="lg:col-span-8 space-y-4">

            {{-- Header (desktop only) --}}
            <div class="hidden md:grid grid-cols-12 text-[11px] font-bold text-gray-400 uppercase tracking-widest pb-3 border-b border-gray-100 px-2">
                <div class="col-span-6">Product</div>
                <div class="col-span-2 text-center">Price</div>
                <div class="col-span-3 text-center">Quantity</div>
                <div class="col-span-1 text-right">Total</div>
            </div>

            {{-- Items --}}
            <template x-for="item in items" :key="item.key">
                <div class="grid grid-cols-1 md:grid-cols-12 items-center gap-4 bg-white border border-gray-100 rounded-xl p-4 hover:shadow-md transition duration-300"
                     :class="item.removing ? 'opacity-40 scale-95' : ''"
                     style="transition: opacity 0.3s, transform 0.3s;">

                    {{-- Product Info --}}
                    <div class="md:col-span-6 flex items-center gap-4">
                        <div class="w-18 h-18 bg-gray-50 rounded-lg border border-gray-100 flex items-center justify-center p-2 shrink-0" style="width:72px;height:72px;">
                            <img :src="item.thumbnail ? '/storage/' + item.thumbnail : ''" class="w-full h-full object-contain" x-on:error="$el.src='/images/placeholder.png'">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-dark line-clamp-2 leading-tight" x-text="item.name"></h4>
                            <p x-show="item.variant" class="text-xs text-primary mt-1 font-medium" x-text="item.variant"></p>
                            <button @click="removeItem(item.key)"
                                    class="text-[11px] text-red-400 hover:text-red-600 transition mt-2 flex items-center gap-1">
                                <i class="fas fa-trash-alt text-[10px]"></i> Remove
                            </button>
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="md:col-span-2 text-center">
                        <span class="text-sm font-semibold text-dark">৳<span x-text="item.price.toLocaleString()"></span></span>
                    </div>

                    {{-- Qty Controls --}}
                    <div class="md:col-span-3 flex items-center justify-center gap-2">
                        <button @click="updateQty(item.key, item.qty - 1)"
                                class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-primary hover:text-white hover:border-primary transition"
                                :disabled="item.qty <= 1">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span class="w-10 text-center text-sm font-bold text-dark" x-text="item.qty"></span>
                        <button @click="updateQty(item.key, item.qty + 1)"
                                class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-primary hover:text-white hover:border-primary transition">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>

                    {{-- Item Total --}}
                    <div class="md:col-span-1 text-right">
                        <span class="text-sm font-bold text-dark">৳<span x-text="(item.price * item.qty).toLocaleString()"></span></span>
                    </div>
                </div>
            </template>

            {{-- Continue Shopping & Clear --}}
            <div class="flex items-center justify-between pt-4">
                <a href="{{ $baseUrl }}" class="text-sm text-primary font-medium hover:underline flex items-center gap-2">
                    <i class="fas fa-arrow-left text-xs"></i> Continue Shopping
                </a>
                <button @click="clearCart()" class="text-sm text-red-400 hover:text-red-600 transition flex items-center gap-2">
                    <i class="fas fa-trash-alt text-xs"></i> Clear Cart
                </button>
            </div>
        </div>

        {{-- Right: Order Summary --}}
        <div class="lg:col-span-4">
            <div class="bg-white border border-gray-100 rounded-xl p-6 sticky top-24 shadow-sm">
                <h3 class="text-base font-bold text-dark mb-5 pb-4 border-b border-gray-100">Order Summary</h3>

                <div class="space-y-3 text-sm text-gray-600 mb-5">
                    <div class="flex justify-between">
                        <span>Subtotal (<span x-text="cartCount"></span> items)</span>
                        <span class="font-semibold text-dark">৳<span x-text="subtotal.toLocaleString()"></span></span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-dark">Total</span>
                        <span class="text-xl font-black text-dark">৳<span x-text="subtotal.toLocaleString()"></span></span>
                    </div>
                </div>

                <a :href="cartCount > 0 ? '{{ $checkoutUrl }}' : '#'"
                   class="w-full btn-primary rounded-xl py-4 text-base flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition"
                   :class="cartCount === 0 ? 'opacity-50 pointer-events-none' : ''">
                    <i class="fas fa-lock text-sm"></i> Proceed to Checkout
                </a>

                {{-- Secure badge --}}
                <div class="flex items-center justify-center gap-2 mt-4 text-[11px] text-gray-400">
                    <i class="fas fa-shield-alt text-green-400"></i>
                    Secure & encrypted checkout
                </div>

                {{-- Payment icons --}}
                <div class="flex items-center justify-center gap-3 mt-3">
                    <i class="fab fa-cc-visa text-2xl text-blue-700"></i>
                    <i class="fab fa-cc-mastercard text-2xl text-red-500"></i>
                    <i class="fab fa-cc-paypal text-2xl text-blue-500"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cart data injected via raw PHP to bypass Blade compilation issues --}}
<script>
window.__vegistCart = <?php echo json_encode($cartItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
window.__vegistRemoveUrl  = <?php echo json_encode($removeUrl); ?>;
window.__vegistUpdateUrl  = <?php echo json_encode($updateUrl); ?>;
window.__vegistClearUrl   = <?php echo json_encode($clearUrl); ?>;
window.__vegistCsrf       = <?php echo json_encode(csrf_token()); ?>;
</script>

<script>
function cartApp() {
    return {
        items: window.__vegistCart || [],
        csrfToken: window.__vegistCsrf,
        removeUrl: window.__vegistRemoveUrl,
        updateUrl: window.__vegistUpdateUrl,
        clearUrl:  window.__vegistClearUrl,

        get cartCount() { return this.items.length; },
        get subtotal()  { return this.items.reduce((sum, i) => sum + (i.price * i.qty), 0); },

        init() {
            this.updateBadge();
        },

        updateBadge() {
            document.querySelectorAll('[data-cart-badge]').forEach(el => {
                el.textContent = this.cartCount;
                if(this.cartCount > 0) el.classList.remove('hidden');
                else el.classList.add('hidden');
            });
        },

        async removeItem(key) {
            const item = this.items.find(i => i.key === key);
            if (item) item.removing = true;

            const fd = new FormData();
            fd.append('_token', this.csrfToken);
            fd.append('key', key);

            await fetch(this.removeUrl, { method: 'POST', body: fd });
            this.items = this.items.filter(i => i.key !== key);
            this.updateBadge();
        },

        async updateQty(key, newQty) {
            if (newQty < 1) return;
            const item = this.items.find(i => i.key === key);
            if (!item) return;
            item.qty = newQty;

            const fd = new FormData();
            fd.append('_token', this.csrfToken);
            fd.append('key', key);
            fd.append('qty', newQty);

            await fetch(this.updateUrl, { method: 'POST', body: fd });
        },

        async clearCart() {
            if (!confirm('Are you sure you want to clear the cart?')) return;

            const fd = new FormData();
            fd.append('_token', this.csrfToken);
            await fetch(this.clearUrl, { method: 'POST', body: fd });
            this.items = [];
            this.updateBadge();
        }
    }
}
</script>

@endsection

