<?php
namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;

trait ShopCartTrait
{
    // ─── Helper: session key per shop ───────────────────────────
    private function cartKey($clientId): string
    {
        return 'cart_' . $clientId;
    }

    private function getCart($clientId): array
    {
        return session($this->cartKey($clientId), []);
    }

    private function saveCart($clientId, array $cart): void
    {
        session([$this->cartKey($clientId) => $cart]);
    }

    // ─── POST /cart/add ──────────────────────────────────────────
    public function addToCart(Request $request, $slug = null)
    {
        try {
            $client = $request->has('current_client')
                ? $request->current_client
                : $this->clientService->getSafeClient($request, $slug);

            if (!$client || !$client->exists) {
                return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
            }

            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'qty'        => 'nullable|integer|min:1|max:100',
            ]);

            $product = Product::where('id', $request->product_id)
                ->where('client_id', $client->id)
                ->first();

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            if (($product->stock_status ?? 'in_stock') === 'out_of_stock') {
                return response()->json(['success' => false, 'message' => 'This product is out of stock'], 422);
            }

            $qty     = max(1, (int)($request->qty ?? 1));
            
            // Cleanly get variant or attributes, avoiding Symfony ParameterBag clashes
            $rawVariant = $request->input('variant') ?? $request->input('attributes');
            $variant = is_string($rawVariant) ? trim($rawVariant) : (is_array($rawVariant) ? json_encode($rawVariant) : '');
            
            $price   = (float)($product->sale_price ?? $product->regular_price ?? 0);

            if ($request->filled('price')) {
                $reqPrice = (float)$request->price;
                if ($reqPrice > 0 && $reqPrice <= ($product->regular_price * 5)) {
                    $price = $reqPrice;
                }
            }

            $itemKey = 'p' . $product->id . ($variant ? '_' . md5($variant) : '');

            $cart = $this->getCart($client->id);

            if (isset($cart[$itemKey])) {
                $cart[$itemKey]['qty'] = min(99, $cart[$itemKey]['qty'] + $qty);
            } else {
                $cart[$itemKey] = [
                    'key'        => $itemKey,
                    'product_id' => $product->id,
                    'slug'       => $product->slug,
                    'name'       => $product->name,
                    'thumbnail'  => $product->thumbnail,
                    'price'      => $price,
                    'qty'        => $qty,
                    'variant'    => $variant,
                ];
            }

            $this->saveCart($client->id, $cart);

            $totalItems = array_sum(array_column($cart, 'qty'));

            return response()->json([
                'success'     => true,
                'message'     => 'Added to cart!',
                'cart_count'  => count($cart),
                'total_items' => $totalItems,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CART_ADD_ERROR: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
        }
    }

    // ─── GET /cart ───────────────────────────────────────────────
    public function viewCart(Request $request, $slug = null)
    {
        $client = $request->has('current_client')
            ? $request->current_client
            : $this->clientService->getSafeClient($request, $slug);

        if (!$client || !$client->exists) return redirect('/');

        $cart   = $this->getCart($client->id);
        $pages  = $this->clientService->getActivePages($client->id);
        $clean  = preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain ?? '', '/'));

        return $this->themeView($client, 'cart', compact('client', 'cart', 'pages', 'clean'));
    }

    // ─── POST /cart/remove ───────────────────────────────────────
    public function removeCartItem(Request $request, $slug = null)
    {
        $client = $request->has('current_client')
            ? $request->current_client
            : $this->clientService->getSafeClient($request, $slug);

        if (!$client || !$client->exists) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $key  = $request->input('key');
        $cart = $this->getCart($client->id);
        unset($cart[$key]);
        $this->saveCart($client->id, $cart);

        return response()->json([
            'success'    => true,
            'cart_count' => count($cart),
        ]);
    }

    // ─── POST /cart/update ───────────────────────────────────────
    public function updateCartItem(Request $request, $slug = null)
    {
        $client = $request->has('current_client')
            ? $request->current_client
            : $this->clientService->getSafeClient($request, $slug);

        if (!$client || !$client->exists) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $key  = $request->input('key');
        $qty  = max(1, (int)$request->input('qty', 1));
        $cart = $this->getCart($client->id);

        if (isset($cart[$key])) {
            $cart[$key]['qty'] = min(99, $qty);
            $this->saveCart($client->id, $cart);
        }

        // Recalculate totals
        $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

        return response()->json([
            'success'    => true,
            'cart_count' => count($cart),
            'subtotal'   => $subtotal,
            'item_total' => isset($cart[$key]) ? $cart[$key]['price'] * $cart[$key]['qty'] : 0,
        ]);
    }

    // ─── POST /cart/clear ────────────────────────────────────────
    public function clearCart(Request $request, $slug = null)
    {
        $client = $request->has('current_client')
            ? $request->current_client
            : $this->clientService->getSafeClient($request, $slug);

        if ($client && $client->exists) {
            $this->saveCart($client->id, []);
        }

        return response()->json(['success' => true]);
    }

    // ─── GET /cart/checkout ─────────────────────────────────────
    public function cartCheckout(Request $request, $slug = null)
    {
        $client = $request->has('current_client')
            ? $request->current_client
            : $this->clientService->getSafeClient($request, $slug);

        if (!$client || !$client->exists) return redirect('/');

        $cart = $this->getCart($client->id);

        if (empty($cart)) {
            $baseUrl = $client->custom_domain
                ? 'https://' . preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/'))
                : route('shop.show', $client->slug);
            return redirect($baseUrl)->with('error', 'Your cart is empty!');
        }

        $pages           = $this->clientService->getActivePages($client->id);
        $shippingMethods = ShippingMethod::where('client_id', $client->id)->where('is_active', true)->get();

        // Fetch customer loyalty balance
        $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();
        $loyaltyBalance = $customer ? \App\Models\LoyaltyPoint::balanceFor($client->id, $customer->phone) : 0;

        return $this->themeView($client, 'cart-checkout', compact('client', 'cart', 'pages', 'shippingMethods', 'loyaltyBalance'));
    }

    // ─── POST /cart/checkout/process ────────────────────────────
    public function processCartCheckout(Request $request, $slug = null)
    {
        $client = $request->has('current_client')
            ? $request->current_client
            : $this->clientService->getSafeClient($request, $slug);

        if (!$client || !$client->exists) return redirect('/');

        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|min:11',
            'shipping_address' => 'required|string',
        ]);

        $cart = $this->getCart($client->id);
        if (empty($cart)) return back()->with('error', 'Your cart is empty!');

        // Shipping
        $shipping = 0;
        if ($request->filled('shipping_method_id')) {
            $sm = ShippingMethod::where('client_id', $client->id)->where('id', $request->shipping_method_id)->first();
            if ($sm) $shipping = $sm->cost;
        } else {
            $shipping = $request->area === 'inside'
                ? ($client->delivery_charge_inside ?? 0)
                : ($client->delivery_charge_outside ?? 0);
        }

        // Coupon
        $discount    = 0;
        $couponCode  = null;
        $subtotal    = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('client_id', $client->id)->where('code', $request->coupon_code)->first();
            if ($coupon && $coupon->isValid() && (!$coupon->min_spend || $subtotal >= $coupon->min_spend)) {
                $discount   = $coupon->type === 'percent' ? ($subtotal * $coupon->discount_amount / 100) : $coupon->discount_amount;
                $couponCode = $coupon->code;
                $coupon->increment('used_count');
            }
        }

        // Redemption of Points
        $redeemedPoints = 0;
        $loyaltyDiscount = 0;
        if ($client->widget('loyalty.active') && $request->filled('redeem_points') && $request->redeem_points > 0) {
            $pointsRequested = (int) $request->redeem_points;
            $customerId = \Illuminate\Support\Facades\Auth::guard('customer')->id();
            if ($customerId) {
                // Verify balance
                $customerPhone = \Illuminate\Support\Facades\Auth::guard('customer')->user()->phone;
                $balance = \App\Models\LoyaltyPoint::balanceFor($client->id, $customerPhone);
                if ($pointsRequested <= $balance) {
                    $rate = (float)($client->widgets['loyalty']['redemption_value'] ?? 1);
                    $redeemedPoints = $pointsRequested;
                    $loyaltyDiscount = $redeemedPoints * $rate;
                    $discount += $loyaltyDiscount; // Add to overall order discount
                }
            }
        }

        $total = ($subtotal + $shipping) - $discount;
        if ($total < 0) $total = 0;

        // Create or Find Customer for CRM
        $customerId = \Illuminate\Support\Facades\Auth::guard('customer')->id();
        if (!$customerId && $request->filled('customer_phone')) {
            $customer = \App\Models\Customer::firstOrCreate(
                ['client_id' => $client->id, 'phone' => $request->customer_phone],
                ['name' => $request->customer_name ?? 'Guest User']
            );
            $customerId = $customer->id;
        }

        // Create Order
        $order = Order::create([
            'client_id'       => $client->id,
            'customer_id'     => $customerId,
            'customer_name'   => $request->customer_name,
            'customer_phone'  => $request->customer_phone,
            'shipping_address'=> $request->shipping_address,
            'subtotal'        => $subtotal,
            'shipping_charge' => $shipping,
            'discount_amount' => $discount,
            'coupon_code'     => $couponCode,
            'total_amount'    => $total,
            'is_guest_checkout' => true,
            'order_status'    => 'pending',
            'customer_note'   => $request->notes,
            'payment_method'  => $request->payment_method ?? 'cod',
        ]);

        $totalEarnedPoints = 0;

        // Create Order Items from cart
        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) continue;

            if ($product->earnable_points > 0) {
                $totalEarnedPoints += ($product->earnable_points * $item['qty']);
            }

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item['product_id'],
                'quantity'   => $item['qty'],
                'unit_price' => $item['price'],
                'price'      => $item['price'] * $item['qty'],
                'attributes' => [
                    'variant' => $item['variant'] ?? null,
                ],
            ]);

            // Deduct stock
            $product->decrement('stock_quantity', $item['qty']);
            if ($product->stock_quantity <= 0) {
                $product->stock_status = 'out_of_stock';
                $product->saveQuietly();
            }
        }

        // Tracking
        app(\App\Services\ServerSideTrackingService::class)->dispatchPurchase($order);

        // Loyalty Points (Award exactly what was calculated per product)
        if ($client->widget('loyalty.active') && $totalEarnedPoints > 0) {
            \App\Models\LoyaltyPoint::earnFromOrder($order, $totalEarnedPoints);
        }

        // Deduct redeemed points
        if ($redeemedPoints > 0) {
            \App\Models\LoyaltyPoint::create([
                'client_id'      => $client->id,
                'sender_id'      => $order->customer_phone,
                'customer_name'  => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'points'         => -$redeemedPoints,
                'type'           => 'redeemed',
                'order_id'       => $order->id,
                'note'           => "Redeemed $redeemedPoints points for discount of ৳$loyaltyDiscount on Order #{$order->id}"
            ]);
        }

        // Clear cart after successful order
        $this->saveCart($client->id, []);

        // 🔀 Payment Gateway Redirects
        $selectedMethod = $request->payment_method ?? 'cod';

        if ($selectedMethod === 'sslcommerz') {
            return redirect()->route('payment.sslcommerz.init', $order->id);
        }
        if ($selectedMethod === 'surjopay') {
            return redirect()->route('payment.surjopay.init', $order->id);
        }
        if ($selectedMethod === 'uddoktapay') {
            return redirect()->route('payment.uddoktapay.init', $order->id);
        }
        if ($selectedMethod === 'bkash_personal' || $selectedMethod === 'bkash_merchant') {
            $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
            $trackBase   = $cleanDomain ? 'https://' . $cleanDomain . '/track' : route('shop.track', $client->slug);
            return redirect($trackBase . '?order_id=' . $order->id . '&show_bkash=1')
                ->with('order_confirmed', true)
                ->with('order_id', $order->id)
                ->with('payment_method', $selectedMethod)
                ->with('bkash_number', $client->payment_gateways[$selectedMethod]['number'] ?? '');
        }

        // Default: COD — Redirect to track page
        $cleanDomain = $client->custom_domain ? preg_replace('/^https?:\/\//', '', rtrim($client->custom_domain, '/')) : null;
        $trackBase   = $cleanDomain ? 'https://' . $cleanDomain . '/track' : route('shop.track', $client->slug);

        return redirect($trackBase . '?order_id=' . $order->id)
            ->with('order_confirmed', true)
            ->with('order_id', $order->id);
    }
}
