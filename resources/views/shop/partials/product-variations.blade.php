@php
    $baseUrl = $clean ? 'https://'.$clean : route('shop.show', $client->slug);
    $cartUrl = $clean ? $baseUrl.'/cart/add' : route('shop.cart.add', $client->slug);
    $checkoutUrl = $clean ? $baseUrl.'/checkout/'.$product->slug : route('shop.checkout', ['slug' => $client->slug, 'productSlug' => $product->slug]);
    
    // Parse Colors & Sizes safely
    $sizes = is_array($product->sizes) ? $product->sizes : (json_decode($product->sizes ?? '[]', true) ?? []);
    $colors = is_array($product->colors) ? $product->colors : (json_decode($product->colors ?? '[]', true) ?? []);
    
    // Calculate initial price
    $currentPrice = $product->discount_amount > 0 
        ? ($product->discount_type === 'percentage' 
            ? $product->price - ($product->price * $product->discount_amount / 100)
            : $product->price - $product->discount_amount)
        : $product->price;
@endphp

<div x-data="productVariations({
    basePrice: {{ $currentPrice }},
    cartUrl: '{{ $cartUrl }}',
    checkoutUrl: '{{ $checkoutUrl }}',
    productId: {{ $product->id }},
    csrfToken: '{{ csrf_token() }}'
})" class="product-variations-wrapper">

    {{-- Universal Features Banner --}}
    <div class="mb-5">
        @include('shop.partials.product-features-bar', ['product' => $product, 'client' => $client, 'clean' => $clean ?? false, 'baseUrl' => $baseUrl ?? ''])
    </div>

    @if(isset($product->stock_status) && $product->stock_status == 'out_of_stock')
        <div class="mb-5 bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-100 flex items-center font-bold text-sm">
            <i class="fas fa-box-open mr-2 font-normal text-lg"></i> Product is currently out of stock
        </div>
        
        @if($client->widget('stock_notify'))
            @include('shop.partials.stock-notify-btn', ['product' => $product, 'client' => $client])
        @endif
    @else
        <form @submit.prevent="submitForm($event)" class="space-y-6">
            
            {{-- Sizes --}}
            @if(!empty($sizes))
            <div>
                <dt class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-3">
                    Size: <span class="text-primary font-normal Normal ml-1" x-text="form.size || 'Select...'"></span>
                </dt>
                <div class="flex flex-wrap gap-2">
                    @foreach($sizes as $s)
                    <button type="button" 
                            @click="form.size = form.size === '{{ $s }}' ? '' : '{{ $s }}'"
                            :class="form.size === '{{ $s }}' ? 'bg-gray-900 border-gray-900 text-white shadow-md' : 'bg-white border-gray-200 text-gray-700 hover:border-gray-500'"
                            class="border rounded-md px-4 py-2 text-sm font-semibold transition-all">
                        {{ $s }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Colors --}}
            @if(!empty($colors))
            <div>
                <dt class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-3">
                    Color: <span class="text-primary font-normal Normal ml-1" x-text="form.color || 'Select...'"></span>
                </dt>
                <div class="flex flex-wrap gap-2">
                    @foreach($colors as $c)
                    <button type="button" 
                            @click="form.color = form.color === '{{ $c }}' ? '' : '{{ $c }}'"
                            :class="form.color === '{{ $c }}' ? 'bg-gray-900 border-gray-900 text-white shadow-md' : 'bg-white border-gray-200 text-gray-700 hover:border-gray-500'"
                            class="border rounded-md px-4 py-2 text-sm font-semibold transition-all">
                        {{ $c }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Quantity & Live Price --}}
            <div class="flex items-center gap-4">
                <div class="flex items-center border border-gray-200 rounded-lg bg-gray-50 h-14 shrink-0 px-2 sm:w-36 justify-between">
                    <button type="button" @click="if(form.qty > 1) form.qty--" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-black">
                        <i class="fas fa-minus text-sm"></i>
                    </button>
                    <input type="number" x-model="form.qty" min="1" class="w-10 text-center bg-transparent border-none p-0 focus:ring-0 font-bold text-lg" readonly>
                    <button type="button" @click="form.qty++" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-black">
                        <i class="fas fa-plus text-sm"></i>
                    </button>
                </div>
                <div class="flex-1 text-right sm:text-left">
                    <span class="text-sm text-gray-500 block mb-0.5">Total Price</span>
                    <span class="text-2xl font-black text-gray-900 tracking-tight" x-text="'৳' + (basePrice * form.qty).toLocaleString('en-IN')"></span>
                </div>
            </div>

            {{-- CTA Buttons --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4 border-t border-gray-100">
                <button type="button" @click="submitLogic('cart')" :disabled="isLoading" 
                        class="h-14 w-full flex items-center justify-center rounded-xl bg-white border-2 border-primary text-primary font-bold hover:bg-primary/5 transition disabled:opacity-50">
                    <span x-show="!isLoading" class="flex gap-2 items-center"><i class="fas fa-shopping-cart"></i> Add to Cart</span>
                    <span x-show="isLoading"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
                <button type="button" @click="submitLogic('checkout')" :disabled="isLoading" 
                        class="h-14 w-full flex items-center justify-center rounded-xl bg-primary text-white font-bold tracking-wider hover:-translate-y-0.5 transition-all shadow hover:shadow-lg disabled:opacity-50">
                    <i class="fas fa-bolt mr-2"></i> Buy Now
                </button>
            </div>
            
        </form>
    @endif
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('productVariations', (config) => ({
        basePrice: config.basePrice,
        form: {
            qty: 1,
            size: '',
            color: ''
        },
        isLoading: false,
        submitLogic(actionType) {
            // Require selections if exist
            @if(!empty($sizes))
            if(!this.form.size) { alert('Please select a size'); return; }
            @endif
            @if(!empty($colors))
            if(!this.form.color) { alert('Please select a color'); return; }
            @endif

            if (actionType === 'checkout') {
                // Redirect standard GET URL
                let params = new URLSearchParams();
                let vArr = [];
                if(this.form.color) vArr.push("Color: " + this.form.color);
                if(this.form.size)  vArr.push("Size: " + this.form.size);
                if(vArr.length > 0) params.append('variant', vArr.join(', '));
                params.append('qty', this.form.qty);
                window.location.href = config.checkoutUrl + '?' + params.toString();
            } else {
                // Add to Cart via POST fetch
                this.isLoading = true;
                const body = new FormData();
                body.append('_token', config.csrfToken);
                body.append('product_id', config.productId);
                body.append('qty', this.form.qty);
                
                let vArr = [];
                if(this.form.color) vArr.push("Color: " + this.form.color);
                if(this.form.size)  vArr.push("Size: " + this.form.size);
                if(vArr.length > 0) body.append('variant', vArr.join(', '));

                fetch(config.cartUrl, {
                    method: 'POST',
                    body: body,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        window.location.reload(); // Quick refresh to update cart icons/drawers natively
                    } else {
                        alert(data.message || 'Failed to add item to cart.');
                    }
                })
                .catch(() => alert('Network Error'))
                .finally(() => this.isLoading = false);
            }
        }
    }));
});
</script>
