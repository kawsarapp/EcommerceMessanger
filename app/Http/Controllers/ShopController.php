<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * দোকানের হোমপেজ (প্রোডাক্ট লিস্ট + পেজ লিংক)
     * Custom Domain & Slug Supported
     */
    public function show(Request $request, $slug = null)
    {
        // ১. ক্লায়েন্ট ডিটেকশন (Custom Domain or Slug)
        if ($request->has('current_client')) {
            $client = $request->current_client;
        } elseif ($slug) {
            $client = Client::where('slug', $slug)->where('status', 'active')->firstOrFail();
        } else {
            abort(404, 'Shop Not Found');
        }

        // ২. প্রোডাক্ট কুয়েরি বিল্ডার (শুধুমাত্র ইন-স্টক)
        $query = Product::where('client_id', $client->id)
            ->where('stock_status', 'in_stock');

        // 🔥 সার্চ ফিল্টার
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('tags', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // 🔥 ক্যাটাগরি ফিল্টার
        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // 🔥 প্রাইস রেঞ্জ ফিল্টার
        if ($request->filled('min_price')) {
            $query->where('regular_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('regular_price', '<=', $request->max_price);
        }

        // 🔥 সর্টিং লজিক
        switch ($request->sort) {
            case 'price_asc':
                $query->orderBy('sale_price', 'asc')->orderBy('regular_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('sale_price', 'desc')->orderBy('regular_price', 'desc');
                break;
            case 'oldest':
                $query->oldest();
                break;
            default: // newest
                $query->latest();
                break;
        }

        // ৩. পেজিনেশন (Ajax Support Included)
        $products = $query->with('category')->paginate(12)->withQueryString();

        // যদি Ajax রিকোয়েস্ট হয় (Load More Feature)
        if ($request->ajax()) {
            return view('shop.partials.product_list', compact('products'))->render();
        }

        // ৪. সাইডবারের জন্য ক্যাটাগরি এবং কাউন্ট লোড করা
        $categories = Category::whereHas('products', function ($q) use ($client) {
            $q->where('client_id', $client->id)->where('stock_status', 'in_stock');
        })->withCount(['products' => function ($q) use ($client) {
            $q->where('client_id', $client->id)->where('stock_status', 'in_stock');
        }])
        ->orderBy('name')
        ->get();

        // ৫. ফুটার লিংক (Dynamic Pages) লোড করা
        $pages = Page::where('client_id', $client->id)
            ->where('is_active', true)
            ->select('title', 'slug')
            ->get();

        return view('shop.index', compact('client', 'products', 'categories', 'pages'));
    }

    /**
     * 🔥 সিঙ্গেল প্রোডাক্ট ডিটেইলস পেজ
     */
    public function productDetails(Request $request, $slug = null, $productSlug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
            $productSlug = $slug; 
        } else {
            $client = Client::where('slug', $slug)->where('status', 'active')->firstOrFail();
        }

        $product = Product::where('client_id', $client->id)
            ->where('slug', $productSlug)
            ->with(['category'])
            ->firstOrFail();

        // রিলেটেড প্রোডাক্ট
        $relatedProducts = Product::where('client_id', $client->id)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('stock_status', 'in_stock')
            ->inRandomOrder()
            ->take(4)
            ->get();

        $pages = Page::where('client_id', $client->id)->where('is_active', true)->get();

        return view('shop.product', compact('client', 'product', 'relatedProducts', 'pages'));
    }

    /**
     * 🔥 ডাইনামিক পেজ ভিউয়ার (Terms, Policy, etc.)
     */
    public function showPage(Request $request, $slug = null, $pageSlug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
            $pageSlug = $slug; 
        } else {
            $client = Client::where('slug', $slug)->where('status', 'active')->firstOrFail();
        }

        $page = Page::where('client_id', $client->id)
            ->where('slug', $pageSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $pages = Page::where('client_id', $client->id)->where('is_active', true)->get();

        return view('shop.page', compact('client', 'page', 'pages'));
    }

    /**
     * অর্ডার ট্র্যাকিং পেজ
     */
    public function trackOrder(Request $request, $slug = null)
    {
        if ($request->has('current_client')) {
            $client = $request->current_client;
        } else {
            $client = Client::where('slug', $slug)->where('status', 'active')->firstOrFail();
        }
        
        $pages = Page::where('client_id', $client->id)->where('is_active', true)->get();
        
        return view('shop.tracking', compact('client', 'pages'));
    }

    /**
     * অর্ডার খোঁজার লজিক
     */
    public function trackOrderSubmit(Request $request, $slug = null)
    {
        $request->validate(['phone' => 'required|min:11']);

        if ($request->has('current_client')) {
            $client = $request->current_client;
        } else {
            $client = Client::where('slug', $slug)->firstOrFail();
        }
        
        $phone = str_replace(["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"], ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"], $request->phone);

        $orders = Order::where('client_id', $client->id)
            ->where('customer_phone', 'LIKE', "%{$phone}%")
            ->with('orderItems.product')
            ->latest()
            ->take(5)
            ->get();

        $pages = Page::where('client_id', $client->id)->where('is_active', true)->get();

        return view('shop.tracking', compact('client', 'orders', 'phone', 'pages'));
    }

    public function loadMore(Request $request)
    {
        return $this->show($request, $request->slug);
    }
}