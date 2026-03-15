@if($client->show_related_products ?? true)
    @php
        $relatedProducts = \App\Models\Product::where('category_id', $product->category_id)
                        ->where('id', '!=', $product->id)
                        ->take(4)->get();
    @endphp
    @if($relatedProducts->count() > 0)
    <div class="mt-16 mb-8">
        <h2 class="text-2xl font-bold text-slate-900 mb-8 tracking-tight">Related Products</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($relatedProducts as $related)
                @include('shop.partials.product-card', ['product' => $related, 'client' => $client])
            @endforeach
        </div>
    </div>
    @endif
@endif
