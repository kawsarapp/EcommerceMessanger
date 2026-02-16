<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * দোকানের হোমপেজ (প্রোডাক্ট লিস্ট) - Custom Domain & Slug Supported
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

        // ২. প্রোডাক্ট কুয়েরি বিল্ডার
        $query = Product::where('client_id', $client->id)->where('stock_status', 'in_stock');

        // 🔥 ফিচার ১: সার্চ (Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('tags', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // 🔥 ফিচার ২: ক্যাটাগরি ফিল্টার
        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // 🔥 ফিচার ৩: প্রাইস রেঞ্জ
        if ($request->filled('min_price')) {
            $query->where('regular_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('regular_price', '<=', $request->max_price);
        }

        // 🔥 ফিচার ৪: সর্টিং
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

        // ৪. ক্যাটাগরি এবং কাউন্ট লোড করা
        $categories = Category::whereHas('products', function ($q) use ($client) {
            $q->where('client_id', $client->id)->where('stock_status', 'in_stock');
        })->withCount(['products' => function ($q) use ($client) {
            $q->where('client_id', $client->id)->where('stock_status', 'in_stock');
        }])->get();

        return view('shop.index', compact('client', 'products', 'categories'));
    }

    /**
     * 🔥 ফিচার ৫: সিঙ্গেল প্রোডাক্ট ডিটেইলস পেজ
     */
    public function productDetails(Request $request, $slug = null, $productSlug = null)
    {
        // Custom Domain এ $slug প্যারামিটার থাকে না, তাই শিফট করা হচ্ছে
        if ($request->has('current_client')) {
            $client = $request->current_client;
            $productSlug = $slug; // ১ম প্যারামিটারই প্রোডাক্ট স্লাগ হয়ে যায়
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

        return view('shop.product', compact('client', 'product', 'relatedProducts'));
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
        return view('shop.tracking', compact('client'));
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
        
        // বাংলা নম্বরকে ইংরেজিতে কনভার্ট
        $phone = $request->phone;
        $bn = ["১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০"];
        $en = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"];
        $phone = str_replace($bn, $en, $phone);

        // শেষ ৫টি অর্ডার বের করা
        $orders = Order::where('client_id', $client->id)
            ->where('customer_phone', 'LIKE', "%{$phone}%")
            ->with('orderItems.product') // রিলেশনশিপ ফিক্সড (orderItems)
            ->latest()
            ->take(5)
            ->get();

        return view('shop.tracking', compact('client', 'orders', 'phone'));
    }

    /**
     * 🔥 Optional: Load More API (যদি আলাদা রাউট থাকে)
     */
    public function loadMore(Request $request)
    {
        // এই লজিকটি show মেথডের Ajax ব্লকেই হ্যান্ডেল করা হয়েছে
        return $this->show($request, $request->slug);
    }

    /**
     * 🔥 Optional: Category API
     */
    public function getCategoryCounts(Request $request)
    {
        // এটি ফ্রন্টএন্ডে ডাইনামিক ফিল্টারের জন্য লাগতে পারে
        return response()->json(['status' => 'ok']);
    }
}