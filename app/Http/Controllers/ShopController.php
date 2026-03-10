<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Shop\ShopClientService;
use App\Services\Shop\ShopProductService;
use App\Services\Shop\ShopOrderTrackingService;

class ShopController extends Controller
{
    protected $clientService;
    protected $productService;
    protected $trackingService;

    // Dependency Injection এর মাধ্যমে ৩টি সার্ভিস লোড করা হলো
    public function __construct(
        ShopClientService $clientService,
        ShopProductService $productService,
        ShopOrderTrackingService $trackingService
    ) {
        $this->clientService = $clientService;
        $this->productService = $productService;
        $this->trackingService = $trackingService;
    }

    /**
     * দোকানের হোমপেজ (প্রোডাক্ট লিস্ট + পেজ লিংক)
     */
    public function show(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) abort(404, 'No Active Shop Found');

        $products = $this->productService->getFilteredProducts($request, $client->id);

        if ($request->ajax()) {
            return view('shop.partials.product_list', compact('products'))->render();
        }

        $categories = $this->productService->getSidebarCategories($client->id);
        $pages = $this->clientService->getActivePages($client->id, true);

        return view('shop.index', compact('client', 'products', 'categories', 'pages'));
    }

    /**
     * সিঙ্গেল প্রোডাক্ট ডিটেইলস পেজ
     */
    public function productDetails(Request $request, $slug = null, $productSlug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
            // 🔥 FIX: যদি productSlug আগে থেকেই থাকে, তবে সেটিকে ওভাররাইড করবে না
            $productSlug = $productSlug ?? $slug; 
        } else {
            $client = $this->clientService->getSafeClient($request, $slug);
        }

        if (!$client->exists) return redirect('/');

        $product = $this->productService->getProductBySlug($client->id, $productSlug);

        if (!$product) {
            // 🔥 FIX: shop.index এর বদলে সরাসরি / বা shop.show তে পাঠানো হলো
            if ($request->has('current_client')) return redirect('/');
            return $client->slug ? redirect()->route('shop.show', $client->slug) : redirect('/');
        }

        $relatedProducts = $this->productService->getRelatedProducts($client->id, $product->category_id, $product->id);
        $pages = $this->clientService->getActivePages($client->id);

        return view('shop.product', compact('client', 'product', 'relatedProducts', 'pages'));
    }

    /**
     * ডাইনামিক পেজ ভিউয়ার (Terms, Policy, etc.)
     */
    public function showPage(Request $request, $slug = null, $pageSlug = null)
    {
        $result = $this->clientService->resolveDynamicPage($request, $slug, $pageSlug);

        if (isset($result['error'])) {
            if ($result['error'] === 'not_found') abort(404, 'Shop or Page Not Found');
            if ($result['error'] === 'redirect') return redirect($result['redirect_url']);
        }

        $client = $result['client'];
        $page = $result['page'];
        $pages = $this->clientService->getActivePages($client->id);

        return view('shop.page', compact('client', 'page', 'pages'));
    }

    /**
     * অর্ডার ট্র্যাকিং পেজ
     */
    public function trackOrder(Request $request, $slug = null)
    {
        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');
       
        $pages = $this->clientService->getActivePages($client->id);
       
        return view('shop.tracking', compact('client', 'pages'));
    }

    /**
     * অর্ডার খোঁজার লজিক
     */
    public function trackOrderSubmit(Request $request, $slug = null)
    {
        $request->validate(['phone' => 'required|min:11']);

        $client = $this->clientService->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');
       
        $orders = $this->trackingService->trackOrder($client->id, $request->phone);
        $pages = $this->clientService->getActivePages($client->id);
        $phone = $request->phone;

        return view('shop.tracking', compact('client', 'orders', 'phone', 'pages'));
    }

    /**
     * Load More Features
     */
    public function loadMore(Request $request)
    {
        return $this->show($request, $request->slug);
    }

    /**
     * চেকআউট পেজ ভিউ করানো
     */
    public function checkout(Request $request, $slug = null, $productSlug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
            // 🔥 FIX: Checkout এর ক্ষেত্রেও সেম ফিক্স দেওয়া হলো
            $productSlug = $productSlug ?? $slug; 
        } else {
            $client = $this->clientService->getSafeClient($request, $slug);
        }

        if (!$client->exists) return redirect('/');

        $product = $this->productService->getProductBySlug($client->id, $productSlug);

        if (!$product) {
            // 🔥 FIX: এখানেও shop.show ব্যবহার করা হলো
            if ($request->has('current_client')) return redirect('/');
            return redirect()->route('shop.show', $client->slug ?? '');
        }

        $pages = $this->clientService->getActivePages($client->id);

        return view('shop.checkout', compact('client', 'product', 'pages'));
    }

    /**
     * AJAX: কুপন কোড চেক করা
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required', 
            'client_id' => 'required', 
            'subtotal' => 'required|numeric'
        ]);
        
        $coupon = \App\Models\Coupon::where('client_id', $request->client_id)
                    ->where('code', $request->code)
                    ->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired coupon!']);
        }

        if ($coupon->min_spend && $request->subtotal < $coupon->min_spend) {
            return response()->json(['success' => false, 'message' => "Minimum spend is ৳{$coupon->min_spend}"]);
        }

        $discount = $coupon->type === 'percent' 
            ? ($request->subtotal * $coupon->discount_amount / 100) 
            : $coupon->discount_amount;

        return response()->json([
            'success' => true, 
            'discount' => round($discount), 
            'message' => 'Coupon applied successfully!'
        ]);
    }

    /**
     * অর্ডার সাবমিট প্রসেস করা (Guest / Direct Checkout)
     */
    public function processCheckout(Request $request, $slug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
        } else {
            $client = $this->clientService->getSafeClient($request, $slug);
        }

        // ভ্যালিডেশন (নাম, নাম্বার ও ঠিকানা বাধ্যতামূলক)
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|min:11',
            'shipping_address' => 'required|string',
            'delivery_area' => 'required|in:inside,outside',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);
        $unitPrice = $product->sale_price ?? $product->regular_price;
        $subtotal = $unitPrice * $request->quantity;
        
        // ডেলিভারি চার্জ নির্ধারণ
        $shipping = $request->delivery_area === 'inside' 
            ? $client->delivery_charge_inside 
            : $client->delivery_charge_outside;

        $discount = 0;
        $couponCode = null;

        // কুপন থাকলে ডিসকাউন্ট ক্যালকুলেট করা
        if ($request->filled('coupon_code')) {
            $coupon = \App\Models\Coupon::where('client_id', $client->id)->where('code', $request->coupon_code)->first();
            if ($coupon && $coupon->isValid() && (!$coupon->min_spend || $subtotal >= $coupon->min_spend)) {
                $discount = $coupon->type === 'percent' ? ($subtotal * $coupon->discount_amount / 100) : $coupon->discount_amount;
                $couponCode = $coupon->code;
                $coupon->increment('used_count'); // কুপন ব্যবহারের সংখ্যা বাড়ানো হলো
            }
        }

        $total = ($subtotal + $shipping) - $discount;

        // ১. মেইন অর্ডার ডাটাবেসে সেভ করা
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
            'payment_method' => 'cod',
        ]);

        // ২. অর্ডারের প্রোডাক্ট আইটেম সেভ করা
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'unit_price' => $unitPrice,
            'price' => $subtotal,
            'attributes' => [
                'color' => $request->color,
                'size' => $request->size
            ]
        ]);

        // অর্ডার সম্পন্ন হলে ট্র্যাকিং পেজে রিডাইরেক্ট করে দেওয়া
        $redirectUrl = $client->custom_domain 
            ? route('shop.track.custom') 
            : route('shop.track', $client->slug);

        return redirect($redirectUrl)->with('success_phone', $order->customer_phone);
    }
}