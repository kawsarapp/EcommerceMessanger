<?php
namespace App\Traits;

use Illuminate\Http\Request;

trait ShopCheckoutTrait
{
    public function checkout(Request $request, $slug = null, $productSlug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
            $productSlug = $productSlug ?? $slug; 
        } else {
            $client = $this->clientService->getSafeClient($request, $slug);
        }

        if (!$client->exists) return redirect('/');

        $product = $this->productService->getProductBySlug($client->id, $productSlug);

        if (!$product) {
            if ($request->has('current_client')) return redirect('/');
            return redirect()->route('shop.show', $client->slug ?? '');
        }

        $pages = $this->clientService->getActivePages($client->id);
        $shippingMethods = \App\Models\ShippingMethod::where('client_id', $client->id)->where('is_active', true)->get();

        return $this->themeView($client, 'checkout', compact('client', 'product', 'pages', 'shippingMethods'));
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required', 
            'client_id' => 'required', 
            'subtotal' => 'required|numeric'
        ]);
        
        $coupon = \App\Models\Coupon::where('client_id', $request->client_id)->where('code', $request->code)->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired coupon!']);
        }

        if ($coupon->min_spend && $request->subtotal < $coupon->min_spend) {
            return response()->json(['success' => false, 'message' => "Minimum spend is ৳{$coupon->min_spend}"]);
        }

        $discount = $coupon->type === 'percent' ? ($request->subtotal * $coupon->discount_amount / 100) : $coupon->discount_amount;

        return response()->json(['success' => true, 'discount' => round($discount), 'message' => 'Coupon applied successfully!']);
    }

    public function processCheckout(Request $request, $slug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
        } else {
            $client = $this->clientService->getSafeClient($request, $slug);
        }

        if (!$client || !$client->exists) return redirect('/');

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|min:11',
            'shipping_address' => 'required|string',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'area' => 'nullable|in:inside,outside',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'payment_method' => 'nullable|string|in:cod,partial,full'
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);
        
        $variant = null;
        if ($product->has_variants) {
            $vQuery = \App\Models\ProductVariant::where('product_id', $product->id)->where('is_active', true);
            if ($request->filled('color')) $vQuery->where('color', trim($request->color));
            if ($request->filled('size')) $vQuery->where('size', trim($request->size));
            $variant = $vQuery->first();
        }

        if ($variant) {
            if ($variant->stock_quantity < $request->qty) {
                return back()->with('error', 'Sorry, the requested variant is out of stock.');
            }
            $unitPrice = $variant->price > 0 ? $variant->price : ($product->sale_price ?? $product->regular_price);
        } else {
            if ($product->stock_quantity < $request->qty) {
                return back()->with('error', 'Sorry, selected product is out of stock.');
            }
            $unitPrice = $product->sale_price ?? $product->regular_price;
        }

        $subtotal = $unitPrice * $request->qty;
        
        $shipping = 0;
        if ($request->filled('shipping_method_id')) {
            $sm = \App\Models\ShippingMethod::where('client_id', $client->id)->where('id', $request->shipping_method_id)->first();
            if ($sm) $shipping = $sm->cost;
        } else {
            $shipping = $request->area === 'inside' ? ($client->delivery_charge_inside ?? 0) : ($client->delivery_charge_outside ?? 0);
        }
        $discount = 0;
        $couponCode = null;

        if ($request->filled('coupon_code')) {
            $coupon = \App\Models\Coupon::where('client_id', $client->id)->where('code', $request->coupon_code)->first();
            if ($coupon && $coupon->isValid() && (!$coupon->min_spend || $subtotal >= $coupon->min_spend)) {
                $discount = $coupon->type === 'percent' ? ($subtotal * $coupon->discount_amount / 100) : $coupon->discount_amount;
                $couponCode = $coupon->code;
                $coupon->increment('used_count'); 
            }
        }

        $total = ($subtotal + $shipping) - $discount;

        $order = \App\Models\Order::create([
            'client_id' => $client->id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'shipping_address' => $request->shipping_address,
            'subtotal' => $subtotal,
            'shipping_charge' => $shipping,
            'discount_amount' => $discount,
            'coupon_code' => $couponCode,
            'total_amount' => $total,
            'is_guest_checkout' => true,
            'order_status' => 'pending',
            'customer_note' => $request->notes,
            'payment_method' => $request->payment_method ?? 'cod',
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $request->qty,
            'unit_price' => $unitPrice,
            'price' => $subtotal,
            'attributes' => ['color' => $request->color, 'size' => $request->size]
        ]);

        // Deduct Securely
        if ($variant) {
            $variant->decrement('stock_quantity', $request->qty);
        } else {
            $product->decrement('stock_quantity', $request->qty);
            $product->stock_status = $product->stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
            $product->saveQuietly();
        }

        // 🔥 Server-Side Tracking (Meta CAPI & GA4)
        app(\App\Services\ServerSideTrackingService::class)->dispatchPurchase($order);

        $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
        $redirectUrl = $cleanDomain ? 'https://' . $cleanDomain . '/track' : route('shop.track', $client->slug);

        return redirect($redirectUrl)->with('success_phone', $order->customer_phone);
    }
}