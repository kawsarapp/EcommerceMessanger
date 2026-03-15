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

        return $this->themeView($client, 'checkout', compact('client', 'product', 'pages'));
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

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|min:11',
            'shipping_address' => 'required|string',
            'area' => 'required|in:inside,outside',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'payment_method' => 'nullable|string|in:cod,partial,full'
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);
        $unitPrice = $product->sale_price ?? $product->regular_price;
        $subtotal = $unitPrice * $request->qty;
        
        $shipping = $request->area === 'inside' ? $client->delivery_charge_inside : $client->delivery_charge_outside;
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

        $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
        $redirectUrl = $cleanDomain ? 'https://' . $cleanDomain . '/track' : route('shop.track', $client->slug);

        return redirect($redirectUrl)->with('success_phone', $order->customer_phone);
    }
}