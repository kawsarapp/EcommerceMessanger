<?php
namespace App\Traits;

use Illuminate\Http\Request;
use App\Services\SmsService;
use App\Services\StockAlertService;

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

        // 🔥 Active payment methods — checkout page এ দেখাবে
        $activePaymentMethods = $client->getActivePaymentMethods();
        $paymentConfig        = $client->payment_gateways ?? [];

        return $this->themeView($client, 'checkout', compact(
            'client', 'product', 'pages', 'shippingMethods',
            'activePaymentMethods', 'paymentConfig'
        ));
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code'      => 'required',
            'client_id' => 'required',
            'subtotal'  => 'required|numeric'
        ]);

        $coupon = \App\Models\Coupon::where('client_id', $request->client_id)
            ->where('code', $request->code)->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired coupon!']);
        }

        if ($coupon->min_spend && $request->subtotal < $coupon->min_spend) {
            return response()->json(['success' => false, 'message' => "Minimum spend is ৳{$coupon->min_spend}"]);
        }

        $discount = $coupon->type === 'percent'
            ? ($request->subtotal * $coupon->discount_amount / 100)
            : $coupon->discount_amount;

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

        // Active gateway keys থেকে validation list
        $validPaymentMethods = array_merge(
            ['cod', 'partial', 'full'],
            array_keys($client->getActivePaymentMethods())
        );
        $validPaymentList = implode(',', array_unique($validPaymentMethods));

        $request->validate([
            'customer_name'     => 'required|string|max:255',
            'customer_phone'    => 'required|string|min:11',
            'shipping_address'  => 'required|string',
            'shipping_method_id'=> 'nullable|exists:shipping_methods,id',
            'area'              => 'nullable|in:inside,outside',
            'product_id'        => 'required|exists:products,id',
            'qty'               => 'required|integer|min:1',
            'payment_method'    => 'nullable|string|in:' . $validPaymentList,
            'advance_amount'    => 'nullable|numeric|min:0',
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);

        $variant = null;
        if ($product->has_variants) {
            $vQuery = \App\Models\ProductVariant::where('product_id', $product->id)->where('is_active', true);
            if ($request->filled('color')) $vQuery->where('color', trim($request->color));
            if ($request->filled('size'))  $vQuery->where('size',  trim($request->size));
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

        // Shipping
        $shipping = 0;
        if ($request->filled('shipping_method_id')) {
            $sm = \App\Models\ShippingMethod::where('client_id', $client->id)
                ->where('id', $request->shipping_method_id)->first();
            if ($sm) $shipping = $sm->cost;
        } else {
            $shipping = $request->area === 'inside'
                ? ($client->delivery_charge_inside ?? 0)
                : ($client->delivery_charge_outside ?? 0);
        }

        // Coupon
        $discount   = 0;
        $couponCode = null;
        if ($request->filled('coupon_code')) {
            $coupon = \App\Models\Coupon::where('client_id', $client->id)
                ->where('code', $request->coupon_code)->first();
            if ($coupon && $coupon->isValid() && (!$coupon->min_spend || $subtotal >= $coupon->min_spend)) {
                $discount   = $coupon->type === 'percent'
                    ? ($subtotal * $coupon->discount_amount / 100)
                    : $coupon->discount_amount;
                $couponCode = $coupon->code;
                $coupon->increment('used_count');
            }
        }

        $total          = ($subtotal + $shipping) - $discount;
        $selectedMethod = $request->payment_method ?? 'cod';
        $advanceAmount  = 0;
        $paymentStatus  = 'pending';

        if ($selectedMethod === 'partial') {
            $advanceAmount = floatval($request->advance_amount ?? 0);
            $paymentStatus = 'partial';
        }

        $order = \App\Models\Order::create([
            'client_id'        => $client->id,
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'shipping_address' => $request->shipping_address,
            'subtotal'         => $subtotal,
            'shipping_charge'  => $shipping,
            'discount_amount'  => $discount,
            'coupon_code'      => $couponCode,
            'total_amount'     => $total,
            'is_guest_checkout'=> true,
            'order_status'     => 'pending',
            'customer_note'    => $request->notes,
            'payment_method'   => $selectedMethod,
            'payment_status'   => $paymentStatus,
            'advance_amount'   => $advanceAmount,
        ]);

        \App\Models\OrderItem::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => $request->qty,
            'unit_price' => $unitPrice,
            'price'      => $subtotal,
            'attributes' => ['color' => $request->color, 'size' => $request->size],
        ]);

        // Stock deduction
        if ($variant) {
            $variant->decrement('stock_quantity', $request->qty);
        } else {
            $product->decrement('stock_quantity', $request->qty);
            $product->stock_status = $product->stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
            $product->saveQuietly();
        }

        // 📱 SMS Confirmation
        app(SmsService::class)->sendOrderConfirmation($client, $order);

        // 📦 Stock Alert check
        app(StockAlertService::class)->checkAfterOrder($client, $product);

        // 🔥 Server-Side Tracking
        app(\App\Services\ServerSideTrackingService::class)->dispatchPurchase($order);

        // ──────────────────────────────────────────────────────
        // 🔀 Payment Gateway Redirect
        // ──────────────────────────────────────────────────────
        if ($selectedMethod === 'sslcommerz') {
            return redirect()->route('payment.sslcommerz.init', $order->id);
        }

        if ($selectedMethod === 'surjopay') {
            return redirect()->route('payment.surjopay.init', $order->id);
        }

        // bKash → tracking page এ যাবে, সেখানে TRX entry দেবে
        if ($selectedMethod === 'bkash_personal' || $selectedMethod === 'bkash_merchant') {
            $cleanDomain = $client->custom_domain
                ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'))
                : null;
            $trackBase = $cleanDomain
                ? 'https://' . $cleanDomain . '/track'
                : route('shop.track', $client->slug);

            return redirect($trackBase . '?order_id=' . $order->id . '&show_bkash=1')
                ->with('order_confirmed', true)
                ->with('order_id', $order->id)
                ->with('payment_method', $selectedMethod)
                ->with('bkash_number', $client->payment_gateways[$selectedMethod]['number'] ?? '');
        }

        // COD / Partial — tracking page
        $cleanDomain = $client->custom_domain
            ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'))
            : null;
        $trackBase = $cleanDomain
            ? 'https://' . $cleanDomain . '/track'
            : route('shop.track', $client->slug);

        return redirect($trackBase . '?order_id=' . $order->id)
            ->with('order_confirmed', true)
            ->with('order_id', $order->id);
    }
}