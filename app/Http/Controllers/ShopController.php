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
            $productSlug = $slug; 
        } else {
            $client = $this->clientService->getSafeClient($request, $slug);
        }

        if (!$client->exists) return redirect('/');

        $product = $this->productService->getProductBySlug($client->id, $productSlug);

        if (!$product) {
            if ($request->has('current_client')) return redirect()->route('shop.index');
            return $client->slug ? redirect()->route('shop.index', $client->slug) : redirect('/');
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
}