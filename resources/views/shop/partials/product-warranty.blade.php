@if($client->show_return_warranty ?? true)
    @if(!empty($product->warranty) || !empty($product->return_policy))
    <div class="mt-8 bg-slate-50 rounded-2xl p-6 border border-slate-100">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Warranty & Return</h3>
        <div class="flex flex-col gap-3">
            @if(!empty($product->warranty))
            <div class="flex items-center gap-3 text-sm text-slate-700 font-medium">
                <i class="fas fa-shield-alt text-primary"></i>
                <span>Warranty: {{ $product->warranty }}</span>
            </div>
            @endif
            @if(!empty($product->return_policy))
            <div class="flex items-center gap-3 text-sm text-slate-700 font-medium">
                <i class="fas fa-undo text-primary"></i>
                <span>Return Policy: {{ $product->return_policy }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
@endif
