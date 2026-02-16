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
     * 🔥 নিরাপদ ক্লায়েন্ট ডিটেকশন হেল্পার (Safe Fallback)
     * এটি 404 এরর আটকাবে এবং ডিফল্ট শপ লোড করবে।
     */
    private function getSafeClient(Request $request, $slug = null)
    {
        // ১. যদি রিকোয়েস্টে অলরেডি ক্লায়েন্ট থাকে (Custom Domain Middleware)
        if ($request->has('current_client')) {
            return $request->current_client;
        }

        // ২. যদি URL এ স্লাগ থাকে, সেটা খোঁজার চেষ্টা করা
        if ($slug) {
            $client = Client::where('slug', $slug)->where('status', 'active')->first();
            if ($client) {
                return $client;
            }
        }

        // ৩. 🔥 Fallback: যদি কিছুই না পাওয়া যায়, প্রথম অ্যাক্টিভ ক্লায়েন্ট লোড হবে
        // যদি ডাটাবেসে কোনো ক্লায়েন্টই না থাকে, তবে একটি খালি অবজেক্ট রিটার্ন করবে যাতে কোড ক্র্যাশ না করে
        return Client::where('status', 'active')->first() ?? new Client(); 
    }

    /**
     * দোকানের হোমপেজ (প্রোডাক্ট লিস্ট + পেজ লিংক)
     */
    public function show(Request $request, $slug = null)
    {
        // ১. সেফ ক্লায়েন্ট ডিটেকশন
        $client = $this->getSafeClient($request, $slug);

        // যদি ডাটাবেসে একদমই কোনো ক্লায়েন্ট না থাকে
        if (!$client->exists) {
            abort(404, 'No Active Shop Found');
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
        // URL হ্যান্ডলিং (Custom Domain vs Path)
        if ($request->has('current_client')) {
            $client = $request->current_client;
            $productSlug = $slug; 
        } else {
            $client = $this->getSafeClient($request, $slug);
        }

        // সেফ চেক: ক্লায়েন্ট যদি ভ্যালিড না হয়
        if (!$client->exists) return redirect('/');

        $product = Product::where('client_id', $client->id)
            ->where('slug', $productSlug)
            ->with(['category'])
            ->first(); 

        // 🔥 Safe Fix: যদি প্রোডাক্ট না পাওয়া যায়, শপ হোমপেজে রিডাইরেক্ট করবে
        if (!$product) {
            if($request->has('current_client')){
                return redirect()->route('shop.index');
            }
            // স্লাগ না থাকলে রুটে, থাকলে স্লাগ সহ রিডাইরেক্ট
            return $client->slug ? redirect()->route('shop.index', $client->slug) : redirect('/');
        }

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
    /**
     * 🔥 ডাইনামিক পেজ ভিউয়ার (Terms, Policy, etc.)
     * FIXED VERSION
     */
    public function showPage(Request $request, $slug = null, $pageSlug = null)
    {
        $client = null;
        $actualPageSlug = null;

        // ১. কোন রাউট থেকে এসেছে চেক করি
        $routeName = $request->route()->getName();

        if ($routeName === 'shop.page.custom') {
            // A. কাস্টম ডোমেইন রাউট (example.com/terms)
            if ($request->has('current_client')) {
                $client = $request->current_client;
                // কাস্টম রাউটে প্যারামিটার একাই আসে, তাই প্রথম আর্গুমেন্ট ($slug) কেই পেজ স্লাগ হিসেবে ধরে
                $actualPageSlug = $request->route('pageSlug') ?? $slug; 
            }
        } 
        elseif ($routeName === 'shop.page.slug') {
            // B. সাব-পাথ রাউট (domain.com/shop/fashion/page/terms)
            $client = Client::where('slug', $slug)->where('status', 'active')->first();
            $actualPageSlug = $pageSlug;
        }

        // ২. যদি ক্লায়েন্ট বা স্লাগ না থাকে -> 404
        if (!$client || !$actualPageSlug) {
            abort(404, 'Shop or Page Not Found');
        }

        // ৩. পেজ ডাটাবেসে খোঁজা
        $page = Page::where('client_id', $client->id)
            ->where('slug', $actualPageSlug)
            ->where('is_active', true)
            ->first();

        // ৪. পেজ না পাওয়া গেলে রিডাইরেক্ট (Redirect Logic)
        if (!$page) {
            // কাস্টম ডোমেইন হলে হোমে
            if ($request->has('current_client')) {
                return redirect()->route('home');
            }
            // সাব-পাথ হলে সেই শপের হোমপেজে
            return redirect()->route('shop.show', $client->slug);
        }

        // ৫. ফুটার লিংকস
        $pages = Page::where('client_id', $client->id)->where('is_active', true)->get();

        return view('shop.page', compact('client', 'page', 'pages'));
    }

    /**
     * অর্ডার ট্র্যাকিং পেজ
     */
    public function trackOrder(Request $request, $slug = null)
    {
        $client = $this->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');
       
        $pages = Page::where('client_id', $client->id)->where('is_active', true)->get();
       
        return view('shop.tracking', compact('client', 'pages'));
    }

    /**
     * অর্ডার খোঁজার লজিক
     */
    public function trackOrderSubmit(Request $request, $slug = null)
    {
        $request->validate(['phone' => 'required|min:11']);

        $client = $this->getSafeClient($request, $slug);
        if (!$client->exists) return redirect('/');
       
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